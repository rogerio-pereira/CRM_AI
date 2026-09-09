<?php

namespace App\Models;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use App\Enums\QualificationStatus;
use Carbon\Carbon;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'qualification_status' => 'pending',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'title',
        'stage',
        'estimated_value',
        'status',
        'proposal_notes',
        'proposal_payload',
        'ai_recommendations',
        'qualification_notes',
        'qualification_status',
        'qualification_last_error',
        'qualified_at',
        'ai_insights',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => PipelineStage::class,
            'estimated_value' => 'decimal:2',
            'status' => OpportunityStatus::class,
            'proposal_payload' => 'array',
            'ai_recommendations' => 'array',
            'qualification_status' => QualificationStatus::class,
            'qualified_at' => 'datetime',
            'ai_insights' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    /**
     * @return HasMany<FollowUp, $this>
     */
    public function followUps(): HasMany
    {
        return $this->hasMany(FollowUp::class);
    }

    /**
     * @return HasMany<OpportunityNote, $this>
     */
    public function notes(): HasMany
    {
        return $this->hasMany(OpportunityNote::class);
    }

    /**
     * @return list<array{body: string, author: string, created_at: string}>
     */
    public function notesForAiContext(): array
    {
        $this->loadMissing('notes.user');

        $notes = $this->notes
                    ->sortBy('created_at')
                    ->values();
        $payload = [];

        foreach ($notes as $note) {
            $user = $note->user;
            $authorName = '';

            if ($user !== null) {
                $authorName = $user->name;
            }

            $createdAt = $note->created_at;

            if ($createdAt === null) {
                continue;
            }

            $payload[] = [
                'body' => $note->body,
                'author' => $authorName,
                'created_at' => $createdAt->toIso8601String(),
            ];
        }

        return $payload;
    }

    public function forgetGeneratedAiOutputs(): void
    {
        $this->ai_insights = null;
        $this->ai_recommendations = null;
        $this->qualification_notes = null;
    }

    public function forgetContactExamples(): void
    {
        $insights = $this->ai_insights;

        if (is_array($insights)) {
            $this->ai_insights = $this->withoutNestedContactExample($insights, 'outreach_strategy');
        }

        $recommendations = $this->ai_recommendations;

        if (is_array($recommendations)) {
            $recommendations = $this->withoutNestedContactExample($recommendations, 'outreach_strategy');
            $recommendations = $this->withoutNestedContactExample($recommendations, 'conversation_strategy');
            $this->ai_recommendations = $recommendations;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function withoutNestedContactExample(array $payload, string $strategyKey): array
    {
        $strategy = $payload[$strategyKey] ?? null;

        if (! is_array($strategy)) {
            return $payload;
        }

        unset($strategy['contact_example']);
        $payload[$strategyKey] = $strategy;

        return $payload;
    }

    /**
     * @return HasMany<Task, $this>
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function hasAiRecommendations(): bool
    {
        if ($this->ai_recommendations === null) {
            return false;
        }

        if ($this->ai_recommendations === []) {
            return false;
        }

        return true;
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function inStage(Builder $query, PipelineStage $stage): void
    {
        $query->where('stage', $stage);
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function withNextFollowUpDate(Builder $query): void
    {
        $now = Carbon::now();

        $query->withMin(
            [
                'followUps as next_follow_up_date' => function (Builder $followUpQuery) use ($now): void {
                    $followUpQuery
                        ->where('reminder_status', 'pending')
                        ->where('due_at', '>=', $now);
                },
            ],
            'due_at',
        );
    }
}
