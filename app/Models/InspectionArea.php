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
        'name_en',
        'sort_order',
        'score',
        'additional_info',
        'additional_info_en',
        'recommendations',
        'recommendations_en',
        'notes_json',
        'notes_json_en',
        'recommendations_json',
        'recommendations_json_en',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'score' => 'integer',
            'notes_json' => 'array',
            'notes_json_en' => 'array',
            'recommendations_json' => 'array',
            'recommendations_json_en' => 'array',
        ];
    }

    /** @return list<string> */
    public function notesList(): array
    {
        return InspectionTextLists::fromArea($this->notes_json, $this->additional_info);
    }

    /** @return list<string> */
    public function notesListEn(): array
    {
        $en = InspectionTextLists::fromArea($this->notes_json_en, $this->additional_info_en);

        return $en !== [] ? $en : $this->notesList();
    }

    /** @return list<string> */
    public function recommendationsList(): array
    {
        return InspectionTextLists::fromArea($this->recommendations_json, $this->recommendations);
    }

    /** @return list<string> */
    public function recommendationsListEn(): array
    {
        $en = InspectionTextLists::fromArea($this->recommendations_json_en, $this->recommendations_en);

        return $en !== [] ? $en : $this->recommendationsList();
    }

    public function displayNameEn(): string
    {
        $en = trim((string) ($this->name_en ?? ''));

        return $en !== '' ? $en : (string) $this->name;
    }

    public function propertyHouse(): BelongsTo
    {
        return $this->belongsTo(PropertyHouse::class);
    }
}
