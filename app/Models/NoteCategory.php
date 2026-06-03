<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NoteCategory extends Model
{
    protected $fillable = [
        'name',
        'name_en',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    public function readyNotes(): HasMany
    {
        return $this->hasMany(ReadyNote::class)->orderBy('sort_order');
    }

    public function recommendationTemplates(): HasMany
    {
        return $this->hasMany(RecommendationTemplate::class)->orderBy('sort_order');
    }
}
