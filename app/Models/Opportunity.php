<?php

namespace App\Models;

use App\Enums\OpportunityStatus;
use App\Enums\PipelineStage;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

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
        ];
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
