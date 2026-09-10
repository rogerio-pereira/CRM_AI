<?php

namespace App\Jobs;

use App\Ai\Agents\WriteFollowUpEmailAgent;
use App\Enums\FollowUpPriority;
use App\Enums\PipelineStage;
use App\Mail\FirstContactOutreachMail;
use App\Models\Client;
use App\Models\FollowUp;
use App\Models\Opportunity;
use App\Models\OpportunityNote;
use App\Services\FollowUpService;
use App\Services\OpportunityService;
use Carbon\Carbon;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Laravel\Ai\Responses\StructuredAgentResponse;
use RuntimeException;

class SendFollowUpEmailJob implements ShouldQueue
{
    use Dispatchable;
    use Queueable;

    public int $tries = 3;

    public int $timeout = 180;

    public function __construct(
        public int $followUpId,
        public ?int $userId,
    ) {}

    public function handle(
        FollowUpService $followUps,
        OpportunityService $opportunities,
        WriteFollowUpEmailAgent $copywriter,
    ): void {
        $followUp = FollowUp::with('opportunity.client')
                        ->findOrFail($this->followUpId);
        $opportunity = $followUp->opportunity;

        if ($opportunity === null) {
            return;
        }

        $firstFollowUpAlreadySent = OpportunityNote::where('opportunity_id', $opportunity->id)
                                        ->where('body', __('Follow-up 1 sent'))
                                        ->exists();
        $sequenceStep = 1;

        if ($firstFollowUpAlreadySent) {
            $sequenceStep = 2;
        }

        $client = $opportunity->client;
        $copy = $this->writeEmail(
            $client,
            $opportunity,
            $sequenceStep,
            $copywriter,
        );
        $subject = $copy['subject'];
        $body = $copy['body'];
        $recipient = $client->contact_email;
        $mail = new FirstContactOutreachMail(
            $subject,
            $body,
        );

        Mail::to($recipient)
            ->send($mail);

        $heading = __('Follow-up :step email sent.', ['step' => $sequenceStep]);
        $subjectLine = __('Subject').': '.$subject;
        $noteBody = $heading."\n\n".$subjectLine."\n\n".$body;

        OpportunityNote::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $this->userId,
            'body' => $noteBody,
        ]);
        $followUps->markComplete($followUp);

        if ($sequenceStep === 1) {
            OpportunityNote::create([
                'opportunity_id' => $opportunity->id,
                'user_id' => $this->userId,
                'body' => __('Follow-up 1 sent'),
            ]);
            $this->createReminder($followUps, $opportunity);

            return;
        }

        OpportunityNote::create([
            'opportunity_id' => $opportunity->id,
            'user_id' => $this->userId,
            'body' => __('Follow-up 2 sent'),
        ]);

        if ($opportunity->stage === PipelineStage::ContactSent) {
            $opportunities->moveToStage(
                $opportunity,
                PipelineStage::NoResponse,
                $this->userId,
            );
        }
    }

    private function createReminder(FollowUpService $followUps, Opportunity $opportunity): void
    {
        $dueAt = Carbon::now()
                    ->addDays(3)
                    ->setTime(9, 0);

        $followUps
            ->create([
                'client_id' => $opportunity->client_id,
                'opportunity_id' => $opportunity->id,
                'due_at' => $dueAt,
                'priority' => FollowUpPriority::Medium,
                'notes' => __('Send the last follow-up email.'),
            ]);
    }

    /**
     * @return array{subject: string, body: string}
     */
    private function writeEmail(
        Client $client,
        Opportunity $opportunity,
        int $sequenceStep,
        WriteFollowUpEmailAgent $copywriter,
    ): array {
        $insights = $opportunity->ai_insights ?? [];
        $outreachStrategy = $insights['outreach_strategy'] ?? [];
        $contactExample = $outreachStrategy['contact_example'] ?? [];
        $previousEmails = [];

        if ($contactExample !== []) {
            $previousEmails[] = [
                'subject' => $contactExample['subject'] ?? '',
                'body' => $contactExample['body'] ?? '',
            ];
        }

        $dossier = [
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
            'sequence_step' => $sequenceStep,
            'previous_emails' => $previousEmails,
        ];
        $encodedDossier = json_encode($dossier);

        if (! is_string($encodedDossier)) {
            throw new RuntimeException('Follow-up email output was incomplete.');
        }

        $prompt = "Write the follow-up email for this opportunity. Return structured JSON only.\n\n".$encodedDossier;
        $response = $copywriter->prompt($prompt);

        if (! $response instanceof StructuredAgentResponse) {
            throw new RuntimeException('Follow-up email output was incomplete.');
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
            throw new RuntimeException('Follow-up email output was incomplete.');
        }

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }
}
