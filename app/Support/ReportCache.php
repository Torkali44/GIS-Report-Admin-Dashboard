<?php

namespace App\Support;

use App\Models\PropertyHouse;
use Illuminate\Support\Facades\Storage;

class ReportCache
{
    public static function clear(PropertyHouse $house): void
    {
        $path = self::path($house->id);
        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    public static function path(int $houseId): string
    {
        return 'reports/inspection-' . $houseId . '.pdf';
    }
}
