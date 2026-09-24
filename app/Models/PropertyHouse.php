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
        'activity_en',
        'property_type',
        'property_type_en',
        'building_status',
        'building_status_en',
        'document_number',
        'intro_number',
        'villa_number',
        'road',
        'compound',
        'compound_en',
        'area',
        'area_en',
        'buyer_name',
        'buyer_name_en',
        'id_number',
        'developer_name',
        'developer_name_en',
        'engineering_supervisor',
        'engineering_supervisor_en',
        'main_contractor',
        'main_contractor_en',
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
        'final_result_text_en',
        'final_general_notes',
        'final_general_notes_en',
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

    /** Prefer English field value, fall back to Arabic/base value. */
    public function fieldEn(string $field, string $fallback = '---'): string
    {
        $en = trim((string) ($this->{$field.'_en'} ?? ''));
        if ($en !== '') {
            return $en;
        }

        $base = trim((string) ($this->{$field} ?? ''));

        return $base !== '' ? $base : $fallback;
    }

    public function clientNameEn(): string
    {
        $en = trim((string) ($this->buyer_name_en ?? ''));
        if ($en !== '') {
            return $en;
        }

        return trim((string) ($this->buyer_name ?? $this->client_name ?? '')) ?: '---';
    }

    public function propertyAddressEn(): string
    {
        $parts = [];
        if ($this->villa_number) {
            $parts[] = 'Villa '.$this->villa_number;
        }
        if ($this->road) {
            $parts[] = 'Road '.$this->road;
        }
        $compound = $this->fieldEn('compound', '');
        if ($compound !== '' && $compound !== '---') {
            $parts[] = 'Compound '.$compound;
        }
        $area = $this->fieldEn('area', '');
        if ($area !== '' && $area !== '---') {
            $parts[] = $area;
        }

        $joined = implode('  ', $parts);

        return $joined !== '' ? $joined : (trim((string) ($this->address ?: $this->title)) ?: '---');
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
