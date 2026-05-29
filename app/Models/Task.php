<?php

namespace App\Models;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'client_id',
        'opportunity_id',
        'title',
        'description',
        'due_at',
        'priority',
        'status',
        'is_important',
        'completed_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'due_at' => 'datetime',
            'priority' => TaskPriority::class,
            'status' => TaskStatus::class,
            'is_important' => 'boolean',
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
        if ($this->status !== TaskStatus::Pending) {
            return false;
        }

        return $this->due_at->isPast();
    }

    public function hasDoneRowHighlight(): bool
    {
        return $this->status === TaskStatus::Done;
    }

    public function hasOverdueRowHighlight(): bool
    {
        return $this->isOverdue();
    }

    public function statusBadgeClasses(): string
    {
        if ($this->isOverdue()) {
            return 'bg-status-danger/20 text-status-danger border-status-danger/50';
        }

        return $this->status->badgeClasses();
    }

    /**
     * @param  Builder<self>  $query
     */
    #[Scope]
    protected function pending(Builder $query): void
    {
        $query->where('status', TaskStatus::Pending);
    }
}
