<?php

namespace App\Services;

use App\Models\PropertyHouse;
use App\Support\InspectionScoreLabels;
use PhpOffice\PhpWord\Element\Footer;
use PhpOffice\PhpWord\Element\Header;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Style\Language;

class InspectionReportWordGeneratorEn
{
    private string $stampPath;

    private string $logoPath;

    public function __construct()
    {
        $this->stampPath = public_path('images/GIS SIGN.png');
        $this->logoPath = public_path('images/company-logo.png');
    }

    public function renderBinary(PropertyHouse $house): string
    {
        $phpWord = new PhpWord;

        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $lang = new Language('en-US', null, 'en-US');
        $phpWord->getSettings()->setThemeFontLang($lang);

        $phpWord->addParagraphStyle('Normal', [
            'alignment' => Jc::LEFT,
            'bidi' => false,
        ]);
        $phpWord->setDefaultParagraphStyle([
            'alignment' => Jc::LEFT,
            'bidi' => false,
        ]);

        $section = $phpWord->addSection([
            'marginTop' => 1134,
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        $pLeft = ['alignment' => Jc::LEFT, 'bidi' => false, 'textAlignment' => 'auto'];
        $pCenter = ['alignment' => Jc::CENTER, 'bidi' => false, 'textAlignment' => 'auto'];
        $pRight = ['alignment' => Jc::RIGHT, 'bidi' => false, 'textAlignment' => 'auto'];

        $fNormal = ['name' => 'Arial', 'size' => 11];
        $fNormalLg = ['name' => 'Arial', 'size' => 11.5];
        $fNormalXl = ['name' => 'Arial', 'size' => 12.5];
        $fBold = ['name' => 'Arial', 'size' => 11, 'bold' => true];
        $fBoldLg = ['name' => 'Arial', 'size' => 12, 'bold' => true];
        $fBoldXl = ['name' => 'Arial', 'size' => 13.5, 'bold' => true];
        $fRedBold = ['name' => 'Arial', 'size' => 12, 'bold' => true, 'color' => 'C00000'];
        $fRedHeader = ['name' => 'Arial', 'size' => 13.5, 'bold' => true, 'color' => 'C00000'];
        $fRedTitle = ['name' => 'Arial', 'size' => 16, 'bold' => true, 'color' => 'C00000'];
        $fRedUnder = ['name' => 'Arial', 'size' => 13.5, 'bold' => true, 'color' => 'C00000', 'underline' => 'single'];
        $fGreenBold = ['name' => 'Arial', 'size' => 12.5, 'bold' => true, 'color' => 'C00000'];

        $headerTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => false,
            'borderColor' => '000000', 'borderSize' => 6, 'cellMargin' => 100, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('HeaderTable', $headerTableStyle);

        $borderTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => false,
            'borderColor' => 'D0D0D0', 'borderSize' => 4, 'cellMargin' => 80, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('BorderTable', $borderTableStyle);

        $recTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => false,
            'borderColor' => '000000', 'borderSize' => 6, 'cellMargin' => 100, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('RecTable', $recTableStyle);

        $noBorderTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => false,
            'borderColor' => 'FFFFFF', 'borderSize' => 0, 'cellMargin' => 40, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('NoBorderTable', $noBorderTableStyle);

        $reportNo = $house->reference_code ?: ('H-' . $house->id);
        $reportDate = $house->reportInspectionDateFormatted();
        $clientName = $house->clientNameEn();
        $propertyAddress = $house->propertyAddressEn();

        $section->addHeader(Header::FIRST);
        $section->addFooter(Footer::FIRST);

        $header = $section->addHeader();
        $headerTable = $header->addTable('NoBorderTable');
        $headerTable->addRow();
        $headerCellLogo = $headerTable->addCell(3856);
        if (file_exists($this->logoPath)) {
            $headerCellLogo->addImage($this->logoPath, [
                'width' => 90,
                'height' => 36,
                'alignment' => Jc::LEFT,
                'wrappingStyle' => 'inline',
            ]);
        }
        $headerCellMeta = $headerTable->addCell(5782);
        $headerCellMeta->addText("Request No. {$reportNo}", ['bold' => true, 'size' => 10, 'color' => 'C00000'], $pRight);
        $headerCellMeta->addText("Inspection Date {$reportDate}", ['bold' => true, 'size' => 10, 'color' => 'C00000'], $pRight);

        $footer = $section->addFooter();
        $footerTable = $footer->addTable('NoBorderTable');
        $footerTable->addRow();
        $footerCellLeft = $footerTable->addCell(4819);
        $footerCellLeft->addText('GIS VALUATION AND EVALUATION', ['bold' => true, 'size' => 8.5, 'color' => 'C00000'], $pLeft);
        $footerCellLeft->addText('GIS Property Inspection & Valuation — C.R. 160528-1', ['size' => 8, 'color' => '555555'], $pLeft);
        $footerCellRight = $footerTable->addCell(4819);
        $footerCellRight->addText('36698895 | infogisgulf@gmail.com | gis.Bahrain', ['size' => 8, 'color' => '555555'], $pRight);
        $footerCellRight->addText('Seef District - Kingdom of Bahrain', ['size' => 8, 'color' => '555555'], $pRight);

        // Cover
        if (file_exists($this->logoPath)) {
            $section->addImage($this->logoPath, [
                'width' => 120,
                'height' => 50,
                'alignment' => Jc::CENTER,
                'wrappingStyle' => 'inline',
            ]);
        }
        $section->addTextBreak(1);
        $section->addText('GIS Property Inspection & Valuation', ['bold' => true, 'size' => 18, 'color' => 'C00000'], $pCenter);
        $section->addText('Property Inspection Report', ['bold' => true, 'size' => 14, 'color' => '333333'], $pCenter);
        $section->addTextBreak(1);

        $infoTable = $section->addTable('HeaderTable');
        foreach ([
            ['Report No.', $reportNo],
            ['Date', $reportDate],
            ['Client Name', $clientName],
            ['Property Address', $propertyAddress],
        ] as $row) {
            $infoTable->addRow();
            $infoTable->addCell(2891, ['bgColor' => 'F2F2F2'])->addText($row[0], ['bold' => true, 'size' => 11], $pLeft);
            $infoTable->addCell(6747)->addText($row[1], ['bold' => true, 'size' => 11, 'color' => 'C00000'], $pLeft);
        }

        $this->renderIntroPages($section, $fNormal, $fNormalLg, $fNormalXl, $fBold, $fBoldLg, $fBoldXl, $fRedBold, $fRedHeader, $fRedTitle, $fRedUnder, $fGreenBold, $pLeft, $pCenter);

        // Property data & specs
        $section->addPageBreak();
        $section->addText('Property Data', ['bold' => true, 'size' => 15, 'color' => 'C00000'], array_merge($pLeft, ['spaceAfter' => 60]));
        $dataTable = $section->addTable('BorderTable');
        foreach ([
            ['Activity per building permit', $house->fieldEn('activity')],
            ['Property type', $house->fieldEn('property_type')],
            ['Building status', $house->fieldEn('building_status')],
            ['Document No.', $house->document_number ?: '---'],
            ['Intro No.', $house->intro_number ?: '---'],
            ['Villa', $house->villa_number ?: '---'],
            ['Road', $house->road ?: '---'],
            ['Compound', $house->fieldEn('compound')],
            ['Area', $house->fieldEn('area')],
            ['Buyer', $house->fieldEn('buyer_name')],
            ['ID No.', $house->id_number ?: '---'],
            ['Developer', $house->fieldEn('developer_name')],
            ['Engineering supervisor', $house->fieldEn('engineering_supervisor')],
            ['Main contractor', $house->fieldEn('main_contractor')],
        ] as $item) {
            $dataTable->addRow();
            $dataTable->addCell(4819, ['bgColor' => 'F9F9F9'])->addText($item[0], ['bold' => true, 'size' => 10.5], $pLeft);
            $dataTable->addCell(4819)->addText($item[1], ['size' => 10.5], $pLeft);
        }
        $section->addTextBreak(1);

        $section->addText('Property Specifications', ['bold' => true, 'size' => 15, 'color' => 'C00000'], array_merge($pLeft, ['spaceAfter' => 60]));
        $specsTable = $section->addTable('BorderTable');
        foreach ([
            ['Approx. property age', $house->property_age ?: '---'],
            ['Land area', $house->land_area ? $house->land_area . ' m²' : '---'],
            ['Built-up area', $house->building_area ? $house->building_area . ' m²' : '---'],
            ['Floors', $house->floors_count ?: '---'],
            ['Rooms', $house->rooms_count ?: '---'],
            ['Bathrooms', $house->bathrooms_count ?: '---'],
            ['Halls', $house->halls_count ?: '---'],
            ['Parking spaces', $house->parking_count ?: '---'],
            ['Kitchens', $house->kitchens_count ?: '---'],
        ] as $spec) {
            $specsTable->addRow();
            $specsTable->addCell(4819, ['bgColor' => 'F9F9F9'])->addText($spec[0], ['bold' => true, 'size' => 10.5], $pLeft);
            $specsTable->addCell(4819)->addText($spec[1], ['size' => 10.5], $pLeft);
        }
        $section->addTextBreak(1);
        $this->addStamp($section);

        // Scores
        $section->addPageBreak();
        $totalPct = min(100, max(0, (int) ($house->total_percentage ?? 0)));
        $rating = $this->resolveInspectorRatingEn($house, $totalPct);

        $summaryBox = $section->addTable('HeaderTable');
        $summaryBox->addRow();
        $summaryBox->addCell(9638, ['bgColor' => 'FFFF00'])->addText('Building Inspection Score', ['bold' => true, 'size' => 15], $pCenter);
        $section->addTextBreak(1);

        $scoreCard = $section->addTable('BorderTable');
        $scoreCard->addRow();
        $scoreCard->addCell(9638, ['bgColor' => 'F2F2F2'])->addText("Overall property score: {$totalPct}% — Rating: ({$rating})", ['bold' => true, 'size' => 14, 'color' => 'C00000'], $pCenter);
        $section->addTextBreak(1);

        $section->addText('Sub-Percentages by Section', ['bold' => true, 'size' => 13.5, 'color' => 'C00000'], $pLeft);

        $areas = $house->inspectionAreas()->orderBy('sort_order')->orderBy('id')->get();
        if ($areas->isNotEmpty()) {
            $scoresTable = $section->addTable('BorderTable');
            $scoresTable->addRow();
            $scoresTable->addCell(5782, ['bgColor' => 'E0E0E0'])->addText('Section / Item', ['bold' => true, 'size' => 11.5], $pLeft);
            $scoresTable->addCell(1928, ['bgColor' => 'E0E0E0'])->addText('Percentage', ['bold' => true, 'size' => 11.5], $pCenter);
            $scoresTable->addCell(1928, ['bgColor' => 'E0E0E0'])->addText('Status', ['bold' => true, 'size' => 11.5], $pCenter);

            foreach ($areas as $area) {
                $score = min(100, max(0, (int) ($area->score ?: 0)));
                [$bgColor, $textColor, $statusLabel] = $this->scoreStyle($score);

                $scoresTable->addRow();
                $scoresTable->addCell(5782)->addText($area->displayNameEn(), ['bold' => true, 'size' => 11.5], $pLeft);
                $scoresTable->addCell(1928, ['bgColor' => $bgColor])->addText($score . '%', ['bold' => true, 'size' => 11.5, 'color' => $textColor], $pCenter);
                $scoresTable->addCell(1928, ['bgColor' => $bgColor])->addText($statusLabel, ['bold' => true, 'size' => 11, 'color' => $textColor], $pCenter);
            }
        }
        $section->addTextBreak(1);

        $legendTable = $section->addTable('BorderTable');
        $legendTable->addRow();
        $legendTable->addCell(2410, ['bgColor' => '00C853'])->addText('Excellent (80% - 100%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'], $pCenter);
        $legendTable->addCell(2409, ['bgColor' => 'FFFF00'])->addText('Good (70% - 79%)', ['bold' => true, 'size' => 10, 'color' => '000000'], $pCenter);
        $legendTable->addCell(2409, ['bgColor' => 'D77814'])->addText('Average (60% - 69%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'], $pCenter);
        $legendTable->addCell(2410, ['bgColor' => 'F70000'])->addText('Poor (below 60%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF'], $pCenter);

        // Technical notes
        $section->addPageBreak();
        $techTitle = $section->addTable('HeaderTable');
        $techTitle->addRow();
        $techTitle->addCell(9638, ['bgColor' => 'DAE3F3'])->addText('Technical Observations Report', ['bold' => true, 'size' => 16], $pCenter);
        $section->addTextBreak(1);

        foreach ($areas as $area) {
            $notes = $area->notesListEn();
            $score = min(100, max(0, (int) ($area->score ?: 0)));
            [$bgColor, $textColor] = $this->scoreStyle($score);

            if ($notes === []) {
                $name = $area->displayNameEn();
                $notes = ['Works for ' . $name . ' were inspected and found properly executed with no remarks.'];
            }

            $areaBlock = $section->addTable('NoBorderTable');
            $areaBlock->addRow(null, ['cantSplit' => true]);
            $areaCell = $areaBlock->addCell(9638);

            $areaBadge = $areaCell->addTable('BorderTable');
            $areaBadge->addRow();
            $areaBadge->addCell(6746)->addText($area->displayNameEn(), ['bold' => true, 'size' => 12], $pLeft);
            $areaBadge->addCell(2892, ['bgColor' => $bgColor])->addText("Score: {$score}%", ['bold' => true, 'size' => 11.5, 'color' => $textColor], $pCenter);
            $areaCell->addTextBreak(1);

            foreach ($notes as $noteText) {
                $areaCell->addText('• ' . $this->stripUnsupportedEmoji($noteText), $fNormalLg, array_merge($pLeft, ['spaceAfter' => 40]));
            }

            $section->addTextBreak(1);
        }

        // Recommendations
        $section->addPageBreak();
        $section->addText('Technical Recommendations', ['bold' => true, 'size' => 15, 'color' => 'C00000'], $pLeft);
        $section->addTextBreak(1);

        $recTable = $section->addTable('RecTable');
        $recTable->addRow();
        $recTable->addCell(3373, ['bgColor' => '81C784'])->addText('Section', ['bold' => true, 'size' => 12, 'color' => 'FFFFFF'], $pCenter);
        $recTable->addCell(6265, ['bgColor' => '81C784'])->addText('Recommendations', ['bold' => true, 'size' => 12, 'color' => 'FFFFFF'], $pCenter);

        $hasRecs = false;
        foreach ($areas as $area) {
            $recs = $area->recommendationsListEn();
            if ($recs === []) {
                continue;
            }

            $hasRecs = true;
            $score = min(100, max(0, (int) ($area->score ?: 0)));
            [$bgColor, $textColor] = $this->scoreStyle($score);

            $recTable->addRow();
            $recTable->addCell(3373, ['bgColor' => $bgColor])->addText($area->displayNameEn(), ['bold' => true, 'size' => 11.5, 'color' => $textColor], $pCenter);

            $recCell = $recTable->addCell(6265);
            foreach ($recs as $recText) {
                $recCell->addText('• ' . $this->stripUnsupportedEmoji(trim($recText)), $fNormalLg, $pLeft);
            }
        }

        if (! $hasRecs) {
            $recTable->addRow();
            $recTable->addCell(3373)->addText('All sections', ['bold' => true], $pCenter);
            $recTable->addCell(6265)->addText('No additional technical recommendations.', $fNormal, $pLeft);
        }
        $section->addTextBreak(1);

        // Final result
        $section->addPageBreak();
        $finalTitleBox = $section->addTable('HeaderTable');
        $finalTitleBox->addRow();
        $finalTitleBox->addCell(9638, ['bgColor' => 'FFFF00'])->addText('Final Inspection Result', ['bold' => true, 'size' => 15], $pCenter);
        $section->addTextBreak(1);

        $finalText = trim((string) ($house->final_result_text_en ?: $house->final_result_text ?? ''));
        if ($finalText !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $finalText) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $section->addText($this->stripUnsupportedEmoji($line), ['size' => 12], array_merge($pLeft, ['spaceAfter' => 60]));
                }
            }
        }

        $generalNotes = trim((string) ($house->final_general_notes_en ?: $house->final_general_notes ?? ''));
        if ($generalNotes !== '') {
            $section->addText('General Notes', ['bold' => true, 'size' => 12.5, 'color' => 'C00000'], $pLeft);
            foreach (preg_split('/\r\n|\n|\r/', $generalNotes) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $line = $this->stripUnsupportedEmoji($line);
                if (mb_substr($line, 0, 1) !== '•' && mb_substr($line, 0, 1) !== '-') {
                    $line = '• ' . $line;
                }
                $section->addText($line, ['size' => 11.5], array_merge($pLeft, ['spaceAfter' => 40]));
            }
        }
        $section->addTextBreak(1);

        $section->addText("Inspector rating for property condition: ({$rating})", ['bold' => true, 'size' => 12.5], $pLeft);

        $deliveryDate = ($house->report_delivered_at ?? $house->updated_at ?? now())->format('Y-m-d');
        $section->addText("• Report delivered on {$deliveryDate}", ['bold' => true, 'size' => 12, 'color' => 'C00000'], array_merge($pLeft, ['spaceAfter' => 60]));

        $section->addText('Please see the attachments', ['bold' => true, 'underline' => 'single', 'size' => 11.5], $pLeft);
        $section->addText('1. Electrical report.', ['size' => 11.5], $pLeft);
        $section->addText('2. Photographs.', ['size' => 11.5], $pLeft);
        $section->addTextBreak(1);

        $this->addStamp($section);

        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempBase = tempnam(sys_get_temp_dir(), 'word_report_en_');
        if ($tempBase === false) {
            throw new \RuntimeException('Unable to create a temporary Word report file.');
        }

        $tempFile = $tempBase . '.docx';
        try {
            $objWriter->save($tempFile);
            $contents = file_get_contents($tempFile);
            if ($contents === false) {
                throw new \RuntimeException('Unable to read the generated Word report.');
            }

            return $contents;
        } finally {
            @unlink($tempBase);
            @unlink($tempFile);
        }
    }

    /**
     * @param  array<string, mixed>  $pLeft
     * @param  array<string, mixed>  $pCenter
     */
    private function renderIntroPages(
        \PhpOffice\PhpWord\Element\Section $section,
        array $fNormal,
        array $fNormalLg,
        array $fNormalXl,
        array $fBold,
        array $fBoldLg,
        array $fBoldXl,
        array $fRedBold,
        array $fRedHeader,
        array $fRedTitle,
        array $fRedUnder,
        array $fGreenBold,
        array $pLeft,
        array $pCenter,
    ): void {
        $section->addPageBreak();
        $section->addText('GIS Property Inspection & Valuation', ['bold' => true, 'size' => 16, 'color' => 'C00000'], $pCenter);
        $section->addText('About GIS — Company Overview & Services', ['bold' => true, 'size' => 13.5, 'color' => 'C00000', 'underline' => 'single'], $pCenter);
        $section->addTextBreak(1);

        $section->addText('Who We Are', ['bold' => true, 'size' => 13], $pLeft);
        $section->addText('At GIS, we believe property safety starts with the right decision. We provide comprehensive inspection of ready-built properties before purchase or occupancy, checking for visible technical defects and hidden risks.', $fNormalLg, array_merge($pLeft, ['spaceAfter' => 80]));
        $section->addText('Our specialists use modern equipment and proven methods to deliver accurate, reliable reports that support sound residential and investment decisions.', $fNormalLg, array_merge($pLeft, ['spaceAfter' => 80]));
        $section->addText('We are among the leading property inspection firms in Bahrain, with a strong track record serving individuals, companies, and developers.', $fNormalLg, array_merge($pLeft, ['spaceAfter' => 100]));

        $section->addText('Main Services', $fRedUnder, $pLeft);
        $section->addText('GIS inspects ready-built properties across the following systems:', $fRedBold, array_merge($pLeft, ['spaceAfter' => 60]));
        foreach ([
            '1. Electrical: Outlets, switches, panels, loads, and earthing.',
            '2. Plumbing: Piping, drains, water pressure, tanks, heaters, and water quality.',
            '3. Moisture & Leakage: Moisture readings, leak detection, and waterproofing.',
            '4. Tanks: Thermal and water leakage checks to prevent contamination or ceiling damage.',
            '5. Doors & Windows: Operation, hinges, frames, sealing, and finishes.',
            '6. Ceilings & Walls: Cracks, moisture marks, finish quality, corners, and thermal insulation.',
            '7. Tiles & Flooring: Grouting, levelness, stability, and installation quality.',
            '8. Safety Systems: CCTV, smoke detectors, and alarm sensors.',
            '9. HVAC: AC units, air outlets, exhaust fans, and installation quality.',
            '10. Parking & Facades: Surfaces, shading, garage doors, cracks, and expansion joints.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($pLeft, ['spaceAfter' => 40]));
        }

        $section->addPageBreak();
        $section->addText('What Sets Us Apart', ['bold' => true, 'size' => 15, 'color' => 'C00000', 'underline' => 'single'], $pLeft);
        $section->addTextBreak(1);

        $section->addText('GIS Guarantees', $fRedUnder, $pLeft);
        $section->addText('• Coverage assurance: Comprehensive, accurate data per GIS standards.', $fBoldLg, array_merge($pLeft, ['spaceAfter' => 20]));
        $section->addText('* Coverage assurance explained', $fGreenBold, array_merge($pLeft, ['spaceAfter' => 20]));
        $section->addText('We report the visible condition of the property as thoroughly as possible using GIS technical criteria. This is not a legal or technical warranty of future condition or hidden defects.', $fNormal, array_merge($pLeft, ['spaceAfter' => 80]));

        $section->addText('• Safety assurance: Inspected items are checked for hazardous defects; manufacturer warranties are referenced when available.', $fBoldLg, array_merge($pLeft, ['spaceAfter' => 20]));
        $section->addText('* Safety assurance explained', $fGreenBold, array_merge($pLeft, ['spaceAfter' => 20]));
        $section->addText('This confirms no dangerous defects were observed in inspected elements at the time of visit. Where warranties exist (electrical, plumbing, HVAC, etc.), they are noted; GIS does not guarantee their validity.', $fNormal, array_merge($pLeft, ['spaceAfter' => 100]));

        $section->addText('Our Advantages', $fRedUnder, $pLeft);
        foreach ([
            '• Fast response and inspection scheduling.',
            '• Simple request and report delivery process.',
            '• Photo documentation of observations and defects.',
            '• Professional, readable, detailed reports.',
            '• Organized archive for each property unit.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($pLeft, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('Inspection & Delivery Process', $fRedUnder, $pLeft);
        foreach ([
            '• Inspection typically takes 2 hours to 2 days depending on size and complexity.',
            '• Final report is delivered within 5 business days after quality review.',
            '• Client comments must be sent within 3 days of receipt for corrections, if needed.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($pLeft, ['spaceAfter' => 40]));
        }

        $section->addPageBreak();
        $section->addText('Optional Documents That Improve Report Quality', $fRedTitle, $pLeft);
        $section->addText('The following documents improve technical accuracy. Please attach whatever is available with your inspection request:', $fNormalXl, array_merge($pLeft, ['spaceAfter' => 100]));

        $section->addText('1. Engineering & Technical Documents', $fRedHeader, $pLeft);
        foreach ([
            '• Building permit',
            '• Approved drawings (architectural, structural, MEP)',
            '• Structural warranty certificate (often 10 years)',
            '• Soil test reports',
            '• Material/system warranties (plumbing, electrical, HVAC, waterproofing, aluminum & glass)',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($pLeft, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('2. General Information & Location', $fRedHeader, $pLeft);
        foreach ([
            '• Approved plot maps from the engineering office',
            '• Property location (direct link or map screenshot)',
            '• Address certificate (house no., street, road, compound)',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($pLeft, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('3. Additional Supporting Documents', $fRedHeader, $pLeft);
        foreach ([
            '• Ground bearing certificate (if available)',
            '• Approximate property age (year built or handed over)',
            '• Copy of buyer / client ID card',
            '• Main contractor agreement and material warranties (optional)',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($pLeft, ['spaceAfter' => 40]));
        }

        $section->addPageBreak();
        $section->addText('Important Disclaimer — Limits of Liability', $fRedTitle, $pLeft);
        $section->addText('GIS performs services with maximum accuracy and impartiality using technical tools and qualified experts. However, GIS accepts no legal or financial liability for damages arising from the inspection process, during or after the site visit.', $fNormalXl, array_merge($pLeft, ['spaceAfter' => 60]));
        $section->addText('GIS is not responsible for disputes between the client and third parties such as contractors or developers; our role is diagnosis and documentation only.', $fNormalXl, array_merge($pLeft, ['spaceAfter' => 100]));

        $section->addText('* Important notice:', $fRedHeader, $pLeft);
        $section->addText('GIS inspects and documents the visible condition of the property at the time of visit only. The report is not a future warranty of property condition or later developments. GIS responsibility ends when the team leaves the site unless a follow-up service was agreed in advance.', $fBoldLg, array_merge($pLeft, ['spaceAfter' => 100]));

        $section->addText('Frequently Asked Questions', $fRedUnder, $pLeft);
        $faqTable = $section->addTable('BorderTable');
        foreach ([
            ['Can I get a refund?', 'Yes, subject to an administrative fee if cancellation is made at least 24 hours before the inspection appointment.'],
            ['Can the report be revised?', 'Yes — comments are accepted within 3 days of receiving the report only.'],
            ['Do you provide maintenance?', 'No — we are an independent inspection body only.'],
            ['How is the inspector assigned?', 'We assign a specialist based on property type and workload, using the approved GIS report format.'],
            ['Why is inspection important?', 'It can save thousands in unexpected repairs and reveals hidden defects before purchase or handover.'],
        ] as $faq) {
            $faqTable->addRow();
            $cell = $faqTable->addCell(9638);
            $cell->addText($faq[0], ['bold' => true, 'size' => 13, 'color' => 'C00000'], $pLeft);
            $cell->addText($faq[1], $fBoldLg, $pLeft);
        }
    }

    private function addStamp(\PhpOffice\PhpWord\Element\Section $section): void
    {
        if (file_exists($this->stampPath)) {
            $section->addImage($this->stampPath, [
                'width' => 200,
                'height' => 125,
                'alignment' => Jc::START,
                'wrappingStyle' => 'inline',
            ]);
        }
    }

    /** @return array{0: string, 1: string, 2: string} */
    private function scoreStyle(int $score): array
    {
        if ($score >= 80) {
            return ['00C853', 'FFFFFF', InspectionScoreLabels::labelEn($score)];
        }
        if ($score >= 70) {
            return ['FFFF00', '000000', InspectionScoreLabels::labelEn($score)];
        }
        if ($score >= 60) {
            return ['D77814', 'FFFFFF', InspectionScoreLabels::labelEn($score)];
        }

        return ['F70000', 'FFFFFF', InspectionScoreLabels::labelEn($score)];
    }

    private function resolveInspectorRatingEn(PropertyHouse $house, int $totalPct): string
    {
        $override = InspectionScoreLabels::translateRating($house->inspector_rating_override);

        return $override ?: InspectionScoreLabels::labelEn($totalPct);
    }

    private function stripUnsupportedEmoji(string $text): string
    {
        return (string) preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);
    }
}
