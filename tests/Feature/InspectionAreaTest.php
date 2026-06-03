<?php

namespace Tests\Feature;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InspectionAreaTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_update_area_via_json_without_full_redirect(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $house = PropertyHouse::create([
            'user_id' => $admin->id,
            'title' => 'فيلا تجريبية',
        ]);
        $area = InspectionArea::create([
            'property_house_id' => $house->id,
            'name' => 'السطح',
            'score' => 80,
            'sort_order' => 1,
            'notes_json' => ['ملاحظة أولى'],
            'additional_info' => '1- ملاحظة أولى',
        ]);

        $response = $this->actingAs($admin)->patchJson(
            route('admin.houses.areas.update', [$house, $area]),
            [
                'name' => 'السطح المحدث',
                'score' => 75,
                'notes_list' => ['ملاحظة أولى', 'ملاحظة ثانية'],
                'recommendations_list' => ['توصية واحدة'],
                'ajax' => 1,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('area.name', 'السطح المحدث')
            ->assertJsonPath('area.notes.1', 'ملاحظة ثانية');

        $this->assertDatabaseHas('inspection_areas', [
            'id' => $area->id,
            'name' => 'السطح المحدث',
            'score' => 75,
        ]);
    }
}
