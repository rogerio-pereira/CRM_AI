<?php

namespace App\Models;

use App\Enums\FollowUpPriority;
use App\Enums\FollowUpReminderStatus;
use Database\Factories\FollowUpFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FollowUp extends Model
{
    /** @use HasFactory<FollowUpFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'opportunity_id',
        'due_at',
        'priority',
        'notes',
        'reminder_status',
        'snoozed_until',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'priority' => FollowUpPriority::class,
            'reminder_status' => FollowUpReminderStatus::class,
            'snoozed_until' => 'datetime',
            'completed_at' => 'datetime',
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
     * @return BelongsTo<Opportunity, $this>
     */
    public function opportunity(): BelongsTo
    {
        return $this->belongsTo(Opportunity::class);
    }

    public function isOverdue(): bool
    {
        if ($this->reminder_status !== FollowUpReminderStatus::Pending) {
            return false;
        }

        return $this->due_at->isPast();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function overdue(Builder $query): void
    {
        $query
            ->where('reminder_status', FollowUpReminderStatus::Pending)
            ->where('due_at', '<', now());
    }
}
