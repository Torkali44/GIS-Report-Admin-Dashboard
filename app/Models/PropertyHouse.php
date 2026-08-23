<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

class PropertyHouse extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'client_name',
        'address',
        'reference_code',
        'inspection_date',
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
        'inspector_rating_override',
        'final_result_text',
        'final_general_notes',
        'report_delivered_at',
    ];

    protected function casts(): array
    {
        return [
            'total_percentage' => 'integer',
            'inspection_date' => 'date',
            'report_delivered_at' => 'date',
        ];
    }

    public function reportInspectionDate(): Carbon
    {
        return $this->inspection_date
            ?? $this->created_at
            ?? now();
    }

    public function reportInspectionDateFormatted(): string
    {
        return $this->reportInspectionDate()->format('Y-m-d');
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
