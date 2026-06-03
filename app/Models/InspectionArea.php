<?php

namespace App\Models;

use App\Support\InspectionTextLists;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InspectionArea extends Model
{
    protected $fillable = [
        'property_house_id',
        'name',
        'sort_order',
        'score',
        'additional_info',
        'recommendations',
        'notes_json',
        'recommendations_json',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'score' => 'integer',
            'notes_json' => 'array',
            'recommendations_json' => 'array',
        ];
    }

    /** @return list<string> */
    public function notesList(): array
    {
        return InspectionTextLists::fromArea($this->notes_json, $this->additional_info);
    }

    /** @return list<string> */
    public function recommendationsList(): array
    {
        return InspectionTextLists::fromArea($this->recommendations_json, $this->recommendations);
    }

    public function propertyHouse(): BelongsTo
    {
        return $this->belongsTo(PropertyHouse::class);
    }
}
