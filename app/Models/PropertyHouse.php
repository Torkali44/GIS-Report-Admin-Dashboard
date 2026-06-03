<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class PropertyHouse extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'client_name',
        'address',
        'reference_code',
        'notes',
        // New fields from images
        'activity',
        'property_type',
        'building_status',
        'document_number',
        'intro_number',
        'villa_number',
        'road',
        'compound',
        'area',
        'buyer_name',
        'id_number',
        'developer_name',
        'engineering_supervisor',
        'main_contractor',
        'property_age',
        'land_area',
        'building_area',
        'floors_count',
        'rooms_count',
        'bathrooms_count',
        'halls_count',
        'parking_count',
        'kitchens_count',
        'total_percentage',
    ];

    protected function casts(): array
    {
        return [
            'total_percentage' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function inspectionAreas(): HasMany
    {
        return $this->hasMany(InspectionArea::class)->orderBy('sort_order')->orderBy('id');
    }
}
