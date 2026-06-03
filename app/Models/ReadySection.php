<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReadySection extends Model
{
    protected $fillable = [
        'name',
        'note_category_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'note_category_id' => 'integer',
        ];
    }

    public function noteCategory(): BelongsTo
    {
        return $this->belongsTo(NoteCategory::class);
    }
}
