<?php

namespace App\Ai\Agents;

use App\Ai\Contracts\AiAgent;
use App\Ai\Exceptions\FollowUpEmailFailedException;
use App\Enums\FollowUpReminderStatus;
use App\Enums\FollowUpSequenceStep;
use App\Enums\PipelineStage;
use App\Events\ContactWithFollowUp;
use App\Mail\FirstContactOutreachMail;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Services\FollowUpService;
use App\Services\OpportunityService;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class FollowUpEmailAgent implements AiAgent
{
    public function __construct(
        private readonly OpportunityService $opportunities,
        private readonly FollowUpService $followUps,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function handle(array $context): array
    {
        $rawFollowUpId = $context['follow_up_id'] ?? 0;
        $followUpId = (int) $rawFollowUpId;
        $followUp = FollowUp::with(['opportunity.client', 'opportunity.notes.user'])
                        ->findOrFail($followUpId);
        $opportunity = $followUp->opportunity;

        if ($opportunity === null) {
            return [
                    'agent' => 'follow_up_email',
                    'status' => 'skipped_missing_opportunity',
                    'follow_up_id' => $followUp->id,
                ];
        }

        $client = $opportunity->client;

        if ($client === null) {
            throw new RuntimeException('Follow-up email client not found for opportunity: '.$opportunity->id);
        }

        $sequenceStep = $followUp->sequence_step;

        if ($sequenceStep === null) {
            return $this->skippedWrongStage($followUp, $opportunity, $client);
        }

        if ($this->shouldSkipSend($followUp, $opportunity)) {
            return $this->skippedWrongStage($followUp, $opportunity, $client);
        }

        $rawUserId = $context['user_id'] ?? null;
        $userId = null;

        if ($rawUserId !== null) {
            $userId = (int) $rawUserId;
        }

        $copy = $this->writeFollowUpEmail($client, $opportunity, $sequenceStep);
        $subject = $copy['subject'];
        $body = $copy['body'];
        $recipient = $client->contact_email;
        $mail = new FirstContactOutreachMail(
            $subject,
            $body,
        );

        Mail::to($recipient)
            ->send($mail);

        $this->recordSentEmailNote(
            $opportunity,
            $userId,
            $sequenceStep,
            $subject,
            $body,
        );
        $this->followUps
            ->markComplete($followUp);

        if ($sequenceStep === FollowUpSequenceStep::First) {
            ContactWithFollowUp::dispatch(
                $opportunity,
                $userId,
                ContactWithFollowUp::FOLLOW_UP_ONE_STEP,
            );
        } else {
            $this->recordFollowUpTwoSentNote($opportunity, $userId);
            $this->opportunities
                ->moveToStage(
                    $opportunity,
                    PipelineStage::NoResponse,
                    $userId,
                );
        }

        return [
                'agent' => 'follow_up_email',
                'status' => 'completed',
                'opportunity_id' => $opportunity->id,
                'follow_up_id' => $followUp->id,
                'client_id' => $client->id,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    private function skippedWrongStage(
        FollowUp $followUp,
        Opportunity $opportunity,
        Client $client,
    ): array {
        return [
                'agent' => 'follow_up_email',
                'status' => 'skipped_wrong_stage',
                'opportunity_id' => $opportunity->id,
                'follow_up_id' => $followUp->id,
                'client_id' => $client->id,
            ];
    }

    private function shouldSkipSend(FollowUp $followUp, Opportunity $opportunity): bool
    {
        if ($followUp->reminder_status !== FollowUpReminderStatus::Pending) {
            return true;
        }

        return $opportunity->stage !== PipelineStage::ContactSent;
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function writeFollowUpEmail(
        Client $client,
        Opportunity $opportunity,
        FollowUpSequenceStep $sequenceStep,
    ): array {
        $dossier = $this->copywriterDossier($client, $opportunity, $sequenceStep);
        $encodedDossier = json_encode($dossier);

        if (! is_string($encodedDossier)) {
            throw new FollowUpEmailFailedException('Follow-up email output was incomplete.');
        }

        $copywriter = app(WriteFollowUpEmailAgent::class);
        $prompt = "Write the follow-up email for this opportunity. Return structured JSON only.\n\n".$encodedDossier;
        $response = $copywriter->prompt($prompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new FollowUpEmailFailedException('Follow-up email output was incomplete.');
        }

        $writtenEmail = $response->toArray();
        $rawSubject = $writtenEmail['subject'] ?? '';
        $subject = trim((string) $rawSubject);
        $rawBody = $writtenEmail['body'] ?? '';
        $body = trim((string) $rawBody);

        if (
            $subject === '' ||
            $body === ''
        ) {
            throw new FollowUpEmailFailedException('Follow-up email output was incomplete.');
        }

        return [
                'subject' => $subject,
                'body' => $body,
            ];
    }

    /**
     * @return array<string, mixed>
     */
    private function copywriterDossier(
        Client $client,
        Opportunity $opportunity,
        FollowUpSequenceStep $sequenceStep,
    ): array {
        $insights = $opportunity->ai_insights;
        $previousEmails = $this->previousEmails($opportunity);

        return [
                'client' => [
                    'id' => (string) $client->id,
                    'company_name' => $client->company_name,
                    'contact_name' => $client->contact_name,
                    'contact_email' => $client->contact_email,
                    'contact_phone' => $client->contact_phone,
                    'website' => $client->website,
                    'social_links' => $client->social_links,
                    'lead_source' => $client->lead_source,
                    'company_notes' => $client->qualification_notes,
                ],
                'opportunity' => [
                    'id' => (string) $opportunity->id,
                    'title' => $opportunity->title,
                    'stage' => $opportunity->stage->value,
                    'qualification_notes' => $opportunity->qualification_notes,
                ],
                'ai_insights' => $insights,
                'ai_recommendations' => $opportunity->ai_recommendations,
                'opportunity_notes' => $opportunity->notesForAiContext(),
                'sequence_step' => $sequenceStep->value,
                'previous_emails' => $previousEmails,
            ];
    }

    /**
     * @return list<array{source: string, subject: string, body: string}>
     */
    private function previousEmails(Opportunity $opportunity): array
    {
        $emails = [];
        $insights = $opportunity->ai_insights ?? [];
        $outreachStrategy = $insights['outreach_strategy'] ?? [];
        $contactExample = $outreachStrategy['contact_example'] ?? [];
        $rawIntroSubject = $contactExample['subject'] ?? '';
        $introSubject = trim((string) $rawIntroSubject);
        $rawIntroBody = $contactExample['body'] ?? '';
        $introBody = trim((string) $rawIntroBody);

        if (
            $introSubject !== '' ||
            $introBody !== ''
        ) {
            $emails[] = [
                'source' => 'introduction',
                'subject' => $introSubject,
                'body' => $introBody,
            ];
        }

        $notes = $opportunity->notes;

        foreach ($notes as $note) {
            $parsedEmail = $this->parseSentEmailFromNote($note->body);

            if ($parsedEmail === null) {
                continue;
            }

            $emails[] = $parsedEmail;
        }

        return $emails;
    }

    /**
     * @return array{source: string, subject: string, body: string}|null
     */
    private function parseSentEmailFromNote(string $noteBody): ?array
    {
        $marker = __('Subject').': ';
        $markerPosition = strpos($noteBody, $marker);

        if ($markerPosition === false) {
            return null;
        }

        $afterMarker = substr($noteBody, $markerPosition + strlen($marker));
        $parts = explode("\n\n", $afterMarker, 2);
        $subject = trim($parts[0]);
        $body = $parts[1] ?? '';

        if ($subject === '') {
            return null;
        }

        return [
                'source' => 'follow_up',
                'subject' => $subject,
                'body' => $body,
            ];
    }

    private function recordSentEmailNote(
        Opportunity $opportunity,
        ?int $userId,
        FollowUpSequenceStep $sequenceStep,
        string $subject,
        string $body,
    ): void {
        $heading = __('Follow-up :step email sent.', ['step' => $sequenceStep->value]);
        $noteBody = $heading."\n\n".__('Subject').': '.$subject."\n\n".$body;
        $noteAttributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $noteBody,
        ];
        OpportunityNote::create($noteAttributes);
    }

    private function recordFollowUpTwoSentNote(Opportunity $opportunity, ?int $userId): void
    {
        $noteBody = __('Follow-up 2 sent');
        $noteAttributes = [
            'opportunity_id' => $opportunity->id,
            'user_id' => $userId,
            'body' => $noteBody,
        ];
        OpportunityNote::create($noteAttributes);
    }
}
