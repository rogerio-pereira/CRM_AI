<?php

namespace App\Models;

use App\Enums\CommercialServiceCategory;
use Database\Factories\CommercialServiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CommercialService extends Model
{
    /** @use HasFactory<CommercialServiceFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category_slug',
        'name',
        'description',
        'default_unit_price',
        'is_active',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category_slug' => CommercialServiceCategory::class,
            'default_unit_price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }
}
