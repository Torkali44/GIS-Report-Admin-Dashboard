<?php

namespace Tests\Feature;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use App\Models\User;
use App\Services\InspectionReportPdfGenerator;
use App\Services\InspectionReportWordGenerator;
use App\Support\ReportCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pdf_and_word_reports_are_generated_as_valid_binary_documents(): void
    {
        $house = PropertyHouse::create([
            'user_id' => User::factory()->create(['is_admin' => true])->id,
            'title' => 'Test property',
            'client_name' => 'Test client',
            'total_percentage' => 82,
        ]);
        InspectionArea::create([
            'property_house_id' => $house->id,
            'name' => 'Electrical',
            'score' => 82,
            'sort_order' => 1,
            'notes_json' => ['No visible issues'],
            'recommendations_json' => ['Keep maintenance records'],
        ]);

        $pdf = app(InspectionReportPdfGenerator::class)->renderBinary($house);
        $word = app(InspectionReportWordGenerator::class)->renderBinary($house);

        $this->assertStringStartsWith('%PDF-', $pdf);
        $this->assertStringStartsWith('PK', $word);
        $this->assertGreaterThan(1000, strlen($pdf));
        $this->assertGreaterThan(1000, strlen($word));
    }

    public function test_word_report_contains_rtl_document_and_table_settings(): void
    {
        $house = PropertyHouse::create([
            'user_id' => User::factory()->create(['is_admin' => true])->id,
            'title' => 'Arabic layout test',
        ]);

        $binary = app(InspectionReportWordGenerator::class)->renderBinary($house);
        $tempFile = tempnam(sys_get_temp_dir(), 'word-layout-test-');
        $this->assertNotFalse($tempFile);
        file_put_contents($tempFile, $binary);

        $zip = new \ZipArchive;
        try {
            $this->assertTrue($zip->open($tempFile) === true);
            $documentXml = $zip->getFromName('word/document.xml');
            $stylesXml = $zip->getFromName('word/styles.xml');

            $this->assertIsString($documentXml);
            $this->assertIsString($stylesXml);
            $this->assertStringContainsString('<w:bidi/>', $documentXml);
            $this->assertStringContainsString('<w:rtl/>', $documentXml);
            $this->assertStringContainsString('<w:titlePg/>', $documentXml);
            $this->assertStringContainsString('<w:bidiVisual w:val="1"/>', $stylesXml);
        } finally {
            $zip->close();
            @unlink($tempFile);
        }
    }

    public function test_pdf_download_does_not_leave_a_public_copy_of_the_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $house = PropertyHouse::create(['user_id' => $admin->id, 'title' => 'Private report']);
        Storage::fake('public');
        Storage::disk('public')->put(ReportCache::path($house->id), 'legacy report');

        $generator = Mockery::mock(InspectionReportPdfGenerator::class);
        $generator->shouldReceive('renderBinary')->once()->withArgs(fn (PropertyHouse $model) => $model->is($house))->andReturn('%PDF-1.7');
        $this->app->instance(InspectionReportPdfGenerator::class, $generator);

        $response = $this->actingAs($admin)->get(route('admin.houses.report', $house));

        $response->assertOk()
            ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        Storage::disk('public')->assertMissing(ReportCache::path($house->id));
    }
}
