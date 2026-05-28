<?php

namespace App\Models;

use App\Enums\ClientStatus;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'company_name',
    'contacts',
    'website',
    'social_links',
    'lead_source',
    'qualification_notes',
    'ai_insights',
    'status',
])]
class Client extends Model
{
    /** @use HasFactory<ClientFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contacts' => 'array',
            'social_links' => 'array',
            'ai_insights' => 'array',
            'status' => ClientStatus::class,
        ];
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Active);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Archived);
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeIgnored(Builder $query): Builder
    {
        return $query->where('status', ClientStatus::Ignored);
    }
}
