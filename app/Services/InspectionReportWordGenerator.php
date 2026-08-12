<?php

namespace App\Services;

use App\Models\PropertyHouse;
use App\Support\InspectionScoreLabels;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\Style\Language;

class InspectionReportWordGenerator
{
    private string $stampPath;
    private string $logoPath;

    public function __construct()
    {
        $this->stampPath = public_path('images/GIS SIGN.png');
        $this->logoPath  = public_path('images/company-logo.png');
    }

    public function renderBinary(PropertyHouse $house): string
    {
        $phpWord = new PhpWord();

        // ─── RTL & Arabic document settings ───────────────────────────────────
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $lang = new Language('ar-SA');
        $phpWord->getSettings()->setThemeFontLang($lang);

        // ─── Section ──────────────────────────────────────────────────────────
        $section = $phpWord->addSection([
            'marginTop'    => 1134,
            'marginBottom' => 1134,
            'marginLeft'   => 1134,
            'marginRight'  => 1134,
            'bidi'         => true,
        ]);

        // ─── Helpers ─────────────────────────────────────────────────────────
        $rtlPara       = ['alignment' => Jc::END,    'bidi' => true];
        $rtlParaCenter = ['alignment' => Jc::CENTER, 'bidi' => true];
        $boldRed       = ['bold' => true, 'color' => 'C00000'];

        // ─── Logo (centered) ─────────────────────────────────────────────────
        if (file_exists($this->logoPath)) {
            $section->addImage($this->logoPath, [
                'width'         => 100,
                'height'        => 40,
                'alignment'     => Jc::CENTER,
                'wrappingStyle' => 'inline',
            ]);
        }
        $section->addTextBreak(1);

        // ─── Title block ──────────────────────────────────────────────────────
        $section->addText('شركة GIS للتقييم والتثمين العقاري',
            ['bold' => true, 'size' => 18, 'color' => 'C00000'],
            $rtlParaCenter
        );
        $section->addText('تقرير فحص وتقييم عقار',
            ['bold' => true, 'size' => 14, 'color' => '333333'],
            $rtlParaCenter
        );
        $section->addTextBreak(1);

        // ─── Cover data ───────────────────────────────────────────────────────
        $reportNo    = $house->reference_code ?: ('H-' . $house->id);
        $reportDate  = ($house->created_at ?: now())->format('Y-m-d');
        $clientName  = trim((string) ($house->buyer_name ?? $house->client_name ?? '')) ?: '---';

        $addressParts = [];
        if ($house->villa_number) $addressParts[] = 'فيلا ' . $house->villa_number;
        if ($house->road)         $addressParts[] = 'طريق ' . $house->road;
        if ($house->compound)     $addressParts[] = 'مجمع ' . $house->compound;
        if ($house->area)         $addressParts[] = $house->area;
        $propertyAddress = implode('، ', $addressParts) ?: ($house->address ?? ($house->title ?? '---'));

        $tableStyle = [
            'borderColor' => 'CCCCCC',
            'borderSize'  => 6,
            'cellMargin'  => 100,
            'alignment'   => Jc::CENTER,
            'bidi'        => true,
        ];
        $phpWord->addTableStyle('InfoTable', $tableStyle);
        $table = $section->addTable('InfoTable');

        foreach ([
            ['رقم التقرير',  $reportNo,       'Report No.'],
            ['التاريخ',      $reportDate,      'Date'],
            ['اسم العميل',   $clientName,      'Client Name'],
            ['عنوان المنزل', $propertyAddress, 'Property Address'],
        ] as $row) {
            $table->addRow();
            $table->addCell(3000)->addText($row[0], ['bold' => true, 'size' => 11], ['alignment' => Jc::END,   'bidi' => true]);
            $table->addCell(4000)->addText($row[1], ['bold' => true, 'size' => 11, 'color' => 'C00000'], $rtlParaCenter);
            $table->addCell(3000)->addText($row[2], ['bold' => true, 'size' => 10], ['alignment' => Jc::START, 'bidi' => false]);
        }
        $section->addTextBreak(1);

        // ─── Property Specs ───────────────────────────────────────────────────
        $section->addText('بيانات العقار ومواصفاته :-',
            array_merge($boldRed, ['size' => 13]),
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        $specTable = $section->addTable('InfoTable');
        foreach ([
            ['النشاط بحسب رخصة البناء', $house->activity        ?: 'سكوني'],
            ['نوع العقار',               $house->property_type   ?: 'فيلا'],
            ['حالة المبنى',              $house->building_status ?: 'تشطيب (غير مؤثث)'],
            ['رقم الوثيقة',              $house->document_number ?: '---'],
            ['رقم المقدمة',              $house->intro_number    ?: '---'],
            ['فيلا',                     $house->villa_number    ?: '---'],
            ['الطريق',                   $house->road            ?: '---'],
            ['المجمع',                   $house->compound        ?: '---'],
            ['المنطقة',                  $house->area            ?: '---'],
            ['المشتري / العميل',         $clientName],
            ['رقم الهوية',               $house->id_number       ?: '---'],
            ['اسم المطور العقاري',        $house->developer_name  ?: '---'],
            ['المشرف الهندسي',            $house->engineering_supervisor ?: '---'],
            ['المقاول الرئيسي',           $house->main_contractor ?: '---'],
            ['عمر العقار التقريبي',       $house->property_age    ?: 'جديد'],
            ['مساحة الأرض التقريبية',     $house->land_area    ? $house->land_area    . ' م²' : '---'],
            ['مساحة البناء التقريبية',    $house->building_area ? $house->building_area . ' م²' : '---'],
            ['عدد الطوابق',               $house->floors_count   ?: '---'],
            ['عدد الغرف',                 $house->rooms_count    ?: '---'],
            ['عدد دورات المياه',           $house->bathrooms_count ?: '---'],
            ['عدد الصالات',               $house->halls_count    ?: '---'],
            ['عدد مواقف السيارات',         $house->parking_count  ?: '---'],
            ['عدد المطابخ',               $house->kitchens_count ?: '---'],
        ] as $spec) {
            $specTable->addRow();
            $specTable->addCell(5000)->addText($spec[0], ['bold' => true], ['alignment' => Jc::END, 'bidi' => true]);
            $specTable->addCell(5000)->addText($spec[1], [],               ['alignment' => Jc::END, 'bidi' => true]);
        }
        $section->addTextBreak(1);

        // ─── Overall Score ────────────────────────────────────────────────────
        $totalPct = min(100, max(0, (int) ($house->total_percentage ?? 0)));
        $rating   = $house->inspector_rating_override ?: InspectionScoreLabels::label($totalPct);

        $section->addText(
            "النسبة الإجمالية لتقييم العقار: {$totalPct}% ({$rating})",
            array_merge($boldRed, ['size' => 13]),
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        $areas = $house->inspectionAreas()->orderBy('sort_order')->orderBy('id')->get();

        if ($areas->isNotEmpty()) {
            $areasTable = $section->addTable('InfoTable');
            $areasTable->addRow();
            $areasTable->addCell(7000)->addText('القسم / البند',  ['bold' => true], ['alignment' => Jc::END,    'bidi' => true]);
            $areasTable->addCell(3000)->addText('النسبة المئوية', ['bold' => true], $rtlParaCenter);

            foreach ($areas as $area) {
                $score = min(100, max(0, (int) ($area->score ?: 0)));
                $areasTable->addRow();
                $areasTable->addCell(7000)->addText($area->name,  [], ['alignment' => Jc::END, 'bidi' => true]);
                $areasTable->addCell(3000)->addText($score . '%', ['bold' => true], $rtlParaCenter);
            }
        }
        $section->addTextBreak(1);

        // ─── Technical Notes ─────────────────────────────────────────────────
        $section->addText('الملاحظات الفنية للأقسام :-',
            array_merge($boldRed, ['size' => 13]),
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        foreach ($areas as $area) {
            $notes = $area->notesList();
            $score = min(100, max(0, (int) ($area->score ?: 0)));

            $section->addText(
                "• {$area->name} ({$score}%):",
                ['bold' => true, 'size' => 12],
                array_merge($rtlPara, ['spaceAfter' => 40])
            );

            if ($notes === []) {
                $section->addText(
                    'تم التحقق من أعمال ' . $area->name . '، وتبين أن جميع الأعمال منفذة بشكل سليم ولا توجد ملاحظات.',
                    [],
                    array_merge($rtlPara, ['spaceAfter' => 40])
                );
            } else {
                foreach ($notes as $noteText) {
                    $section->addText('• ' . $noteText, [], array_merge($rtlPara, ['spaceAfter' => 30]));
                }
            }
        }
        $section->addTextBreak(1);

        // ─── Recommendations Table ────────────────────────────────────────────
        $section->addText('جدول التوصيات :-',
            array_merge($boldRed, ['size' => 13]),
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        $recTable = $section->addTable('InfoTable');
        $recTable->addRow();
        $recTable->addCell(3500)->addText('الأعمال',  ['bold' => true], ['alignment' => Jc::END, 'bidi' => true]);
        $recTable->addCell(6500)->addText('التوصيات', ['bold' => true], ['alignment' => Jc::END, 'bidi' => true]);

        $hasRecs = false;
        foreach ($areas as $area) {
            $recs = $area->recommendationsList();
            if ($recs === []) continue;

            $hasRecs = true;
            $recTable->addRow();
            $recTable->addCell(3500)->addText($area->name, ['bold' => true], ['alignment' => Jc::END, 'bidi' => true]);
            $recCell = $recTable->addCell(6500);
            foreach ($recs as $recText) {
                $recCell->addText('• ' . trim($recText), [], ['alignment' => Jc::END, 'bidi' => true]);
            }
        }

        if (!$hasRecs) {
            $recTable->addRow();
            $recTable->addCell(3500)->addText('جميع الأقسام',               [], ['alignment' => Jc::END, 'bidi' => true]);
            $recTable->addCell(6500)->addText('لا توجد توصيات فنية إضافية.', [], ['alignment' => Jc::END, 'bidi' => true]);
        }
        $section->addTextBreak(1);

        // ─── Final Result ─────────────────────────────────────────────────────
        $section->addText('النتيجة النهائية لتقرير الفحص',
            array_merge($boldRed, ['size' => 13]),
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        $finalText = trim((string) ($house->final_result_text ?? ''));
        if ($finalText !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $finalText) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $section->addText($line, [], array_merge($rtlPara, ['spaceAfter' => 60]));
                }
            }
        }

        $section->addText(
            "وبناءً على التقييم الفني، بلغت نسبة الإنجاز التقديرية للعقار {$totalPct}%، وتقييم الفاحص لحالة العقار: ({$rating}).",
            ['bold' => true],
            array_merge($rtlPara, ['spaceAfter' => 80])
        );

        $generalNotes = trim((string) ($house->final_general_notes ?? ''));
        if ($generalNotes !== '') {
            $section->addText('ملاحظات عامة:',
                array_merge($boldRed, ['size' => 11]),
                array_merge($rtlPara, ['spaceAfter' => 40])
            );
            foreach (preg_split('/\r\n|\n|\r/', $generalNotes) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $section->addText('• ' . $line, [], array_merge($rtlPara, ['spaceAfter' => 30]));
                }
            }
        }

        $deliveryDate = ($house->report_delivered_at ?? $house->updated_at ?? now())->format('Y-m-d');
        $section->addText(
            "تاريخ تسليم التقرير: {$deliveryDate}",
            array_merge($boldRed, ['size' => 11]),
            array_merge($rtlPara, ['spaceBefore' => 120])
        );

        $section->addTextBreak(2);

        // ─── Stamp ────────────────────────────────────────────────────────────
        if (file_exists($this->stampPath)) {
            $section->addText('توقيع وختم المفتش:', ['bold' => true, 'size' => 11], $rtlPara);
            $section->addImage($this->stampPath, [
                'width'         => 120,
                'height'        => 80,
                'alignment'     => Jc::START,
                'wrappingStyle' => 'inline',
            ]);
        }

        // ─── Save ─────────────────────────────────────────────────────────────
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempFile  = tempnam(sys_get_temp_dir(), 'word_report_') . '.docx';
        $objWriter->save($tempFile);

        $contents = file_get_contents($tempFile);
        @unlink($tempFile);

        return $contents ?: '';
    }
}
