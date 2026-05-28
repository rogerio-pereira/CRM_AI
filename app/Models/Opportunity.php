<?php

namespace App\Models;

use App\Enums\OpportunityStage;
use Database\Factories\OpportunityFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'client_id',
    'title',
    'stage',
    'estimated_value',
    'status',
    'proposal_information',
    'ai_recommendations',
])]
class Opportunity extends Model
{
    /** @use HasFactory<OpportunityFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'stage' => OpportunityStage::class,
            'estimated_value' => 'decimal:2',
            'ai_recommendations' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Opportunity $opportunity): void {
            if ($opportunity->stage === null) {
                $opportunity->stage = OpportunityStage::Lead;
            }
        });
    }

    /**
     * @return BelongsTo<Client, $this>
     */
    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }
}
