<?php

namespace App\Services;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use App\Support\InspectionScoreLabels;
use TCPDF;
use Throwable;

class InspectionReportPdfGeneratorEn
{
    /** Arial Bold for report typography. */
    private string $font = 'arialbd';

    private string $fontBold = 'arialbd';

    public function renderBinary(PropertyHouse $house): string
    {
        $prev = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        // Temporarily silence notices and warnings inside TCPDF to prevent Laravel from throwing ErrorException
        set_error_handler(function ($severity, $message, $file, $line) {
            if (strpos($file, 'tcpdf') !== false) {
                return true; // Silence warnings/notices inside TCPDF
            }
            return false; // Delegate other errors to Laravel's handler
        });

        try {
            $this->registerReportFonts();

            $house->load([
                'inspectionAreas' => fn($q) => $q->orderBy('sort_order')->orderBy('id'),
            ]);

            $reportNo = $house->reference_code ?: ('H-' . $house->id);
            $reportDate = $house->reportInspectionDateFormatted();
            $logoPath = $this->resolveLogoPath();

            $pdf = new ReportTCPDFEn('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->reportNo = $reportNo;
            $pdf->reportDate = $reportDate;
            $pdf->logoPath = $logoPath;
            $pdf->reportFont = $this->font;
            $pdf->reportFontBold = $this->fontBold;

            $pdf->SetCreator(config('app.name'));
            $pdf->SetAuthor(config('app.name'));
            $pdf->SetTitle('Inspection Report — ' . $house->title);

            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);

            $pdf->SetMargins(12, 30, 12);
            $pdf->SetHeaderMargin(8);
            $pdf->SetFooterMargin(12);
            $pdf->SetAutoPageBreak(true, 24);

            $pdf->setRTL(false);
            $pdf->SetFont($this->font, '', 12);

            // --- Cover (no inner header/footer so background can bleed to edges) ---
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
            $pdf->SetAutoPageBreak(false, 0);
            $pdf->AddPage();
            $this->renderCoverPage($pdf, $house);
            $pdf->SetAutoPageBreak(true, 24);
            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);

            // --- Fixed introduction pages ---
            $this->renderFixedPages($pdf);

            // --- Property Data & Specifications ---
            $pdf->AddPage();

            $pdf->SetFont($this->fontBold, '', 15);
            $pdf->Cell(0, 8, 'Property Data', 0, 1, 'L');

            $pdf->SetFont($this->font, '', 11);
            $this->renderPropertyDataTable($pdf, $house);

            $pdf->Ln(6);
            $pdf->SetFont($this->fontBold, '', 16);
            $pdf->Cell(0, 10, 'Property Specifications', 0, 1, 'L');

            $pdf->SetFont($this->font, '', 11);
            $this->renderPropertySpecsTable($pdf, $house);

            // Draw signature stamp directly below the tables, bottom-right of THIS page.
            // No page navigation here — this guarantees the stamp never lands on its own
            // near-empty page and never overlaps the table rows.
            $signaturePath = $this->resolveSignaturePath();
            if ($signaturePath) {
                $pageW = $pdf->getPageWidth();
                $pageH = $pdf->getPageHeight();
                $sigW = 65;
                $sigH = 42;
                $sigX = $pageW - $sigW - 16; // Bottom-right
                $sigY = $pdf->GetY() + 6;

                // If the tables ran long and there isn't room left on this page, don't force
                // it — just clamp to a safe spot above the footer rather than overflow.
                $maxY = $pageH - 34;
                if ($sigY > $maxY) {
                    $sigY = $maxY;
                }

                $pdf->setRTL(false);
                try {
                    $pdf->Image($signaturePath, $sigX, $sigY, $sigW, $sigH, '', '', '', false, 300, '', false, false, 0);
                } catch (Throwable $e) {
                }
                $pdf->setRTL(false);
            }

            // --- Percentages ---
            $pdf->AddPage();

            $areaCount = count($house->inspectionAreas);
            $isCompact = $areaCount > 10;

            // 1. Total Percentage (Donut)
            $pdf->SetFont($this->fontBold, '', $isCompact ? 14 : 16);
            $pdf->Cell(0, $isCompact ? 7 : 10, 'Overall Percentage', 0, 1, 'C');
            $this->renderTotalDonut($pdf, $house->total_percentage, $isCompact);

            // 2. Sub-Percentages (Bars)
            $pdf->Ln($isCompact ? 2 : 4);
            $pdf->SetFont($this->fontBold, '', $isCompact ? 14 : 16);
            $pdf->Cell(0, $isCompact ? 7 : 10, 'Sub-Percentages', 0, 1, 'C');
            $pdf->Ln($isCompact ? 1 : 2);
            $this->renderSubPercentageBars($pdf, $house->inspectionAreas);

            // --- Technical Notes ---
            $pdf->AddPage();
            $this->renderTechnicalNotes($pdf, $house->inspectionAreas);

            // --- Recommendations ---
            $pdf->AddPage();
            $this->renderRecommendations($pdf, $house->inspectionAreas);

            // --- Final Result ---
            $this->renderFinalResult($pdf, $house, $reportDate);

            return $pdf->Output('inspection-' . $house->id . '.pdf', 'S');
        } finally {
            restore_error_handler();
            ini_set('memory_limit', (string) $prev);
        }
    }

    private function renderCoverPage(TCPDF $pdf, PropertyHouse $house): void
    {
        $pdf->setRTL(false);

        $pageW = $pdf->getPageWidth();
        $pageH = $pdf->getPageHeight();

        // Exact high-quality cover from extracted_0.png (A4 @ 300dpi).
        // Slight top padding so the logo/title aren't stuck to the page ceiling;
        // artwork still reaches the bottom edge (footer bar stays flush).
        $bgPath = $this->resolveCoverBackgroundPath();
        $coverTopPad = 10; // mm
        if ($bgPath) {
            try {
                $pdf->Image(
                    $bgPath,
                    0,
                    $coverTopPad,
                    $pageW,
                    $pageH - $coverTopPad,
                    '',
                    '',
                    '',
                    false,
                    300,
                    '',
                    false,
                    false,
                    0
                );
            } catch (Throwable $e) {
            }
        }

        $this->overlayCoverLogo($pdf, $pageW, $coverTopPad);

        $reportNo = $house->reference_code ?: ('H-' . $house->id);
        $reportDate = $house->reportInspectionDateFormatted();

        $propertyAddress = $house->propertyAddressEn();
        $clientName = $house->clientNameEn();

        $pdf->SetY(88 + $coverTopPad);
        $pdf->SetFont($this->fontBold, '', 16);
        $pdf->SetTextColor(192, 0, 0);
        $pdf->Cell(0, 10, 'GIS Property Inspection & Valuation', 0, 1, 'C');
        $pdf->SetFont($this->fontBold, '', 13);
        $pdf->SetTextColor(51, 51, 51);
        $pdf->Cell(0, 8, 'Property Inspection Report', 0, 1, 'C');
        $pdf->Ln(4);

        $infoRows = [
            ['Report No.', $reportNo, true],
            ['Date', $reportDate, false],
            ['Client Name', $clientName, false],
            ['Property Address', $propertyAddress, false],
        ];

        $tableW = 150;
        $tableX = ($pageW - $tableW) / 2;
        $wLabel = 52;
        $wVal = $tableW - $wLabel;
        $rowH = 18;

        foreach ($infoRows as [$label, $value, $isHighlight]) {
            $y = $pdf->GetY();
            $pdf->SetFont($this->fontBold, '', 11);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($tableX, $y);
            $pdf->Cell($wLabel, $rowH, $label, 0, 0, 'L');

            if ($isHighlight) {
                $pdf->SetTextColor(139, 26, 26);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $valSize = ($label === 'Property Address') ? 10 : 12;
            $pdf->SetFont($this->fontBold, '', $valSize);
            $pdf->SetXY($tableX + $wLabel, $y);
            $pdf->MultiCell($wVal, $rowH / 2, $value, 0, 'L', false, 0, '', '', true, 0, false, true, $rowH, 'M');

            $pdf->SetY($y + $rowH + 4);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setRTL(false);
    }

    private function renderFixedPages(TCPDF $pdf): void
    {
        $pdf->setRTL(false);
        $pdf->SetAutoPageBreak(false, 0);
        $pdf->setCellHeightRatio(1.55);

        foreach ($this->getFixedPagesContent() as $html) {
            $pdf->AddPage();
            $pdf->SetFont($this->font, '', 11.5);
            // $ln must be 0 — if the HTML ends near the page bottom, $ln=1 opens a blank page
            // before the next AddPage() in this loop (ghost pages 4/6/8).
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div style="font-size:11.5px; line-height: 1.55; font-family: arialbd;">' . $html . '</div>',
                0,
                0,
                false,
                true,
                'L',
                true
            );
        }

        $pdf->SetAutoPageBreak(true, 24);
        $pdf->setCellHeightRatio(1.6);
    }

    private function stripUnsupportedEmoji(string $text): string
    {
        $text = (string) preg_replace('/[\x{10000}-\x{10FFFF}]/u', '', $text);

        return trim((string) preg_replace('/\s{2,}/u', ' ', $text));
    }

    private function resolveInspectorRatingEn(PropertyHouse $house, int $totalPct): string
    {
        $override = InspectionScoreLabels::translateRating($house->inspector_rating_override);

        return $override ?: InspectionScoreLabels::labelEn($totalPct);
    }

    private function getFixedPagesContent(): array
    {
        return InspectionReportEnIntroPages::htmlBlocks();
    }


    private function renderTotalDonut(TCPDF $pdf, int $percentage, bool $compact = false): void
    {
        $radius = $compact ? 20 : 28;
        $thickness = $compact ? 7 : 9;
        $cY = $pdf->GetY() + $radius + 3;
        $cX = 105;

        if ($percentage >= 80) {
            $r = 6;
            $g = 78;
            $b = 59;
        } elseif ($percentage >= 60) {
            $r = 30;
            $g = 58;
            $b = 138;
        } else {
            $r = 253;
            $g = 230;
            $b = 138;
        }

        $pdf->SetDrawColor(241, 245, 249);
        $pdf->SetLineWidth($thickness);
        $pdf->Circle($cX, $cY, $radius, 0, 360, 'D');

        $pdf->SetDrawColor($r, $g, $b);
        $startAngle = -90;
        $endAngle = $startAngle + (360 * ($percentage / 100));
        $pdf->Circle($cX, $cY, $radius, $startAngle, $endAngle, 'D', [], []);

        $pdf->SetXY($cX - 15, $cY - 5);
        $pdf->SetTextColor($r, $g, $b);
        $pdf->SetFont($this->fontBold, '', $compact ? 18 : 22);
        $pdf->Cell(30, 10, $percentage . '%', 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetY($cY + $radius + 4);
    }

    private function renderSubPercentageBars(TCPDF $pdf, $areas): void
    {
        $pageW = $pdf->getPageWidth(); // 210mm
        $count = count($areas);

        // Dynamic height and spacing based on area count so EVERYTHING fits on 1 page!
        if ($count > 12) {
            $h = 5.6;
            $spacing = 1.6;
            $fontSize = 10;
        } elseif ($count > 9) {
            $h = 6.5;
            $spacing = 2.0;
            $fontSize = 10.5;
        } else {
            $h = 7.5;
            $spacing = 2.5;
            $fontSize = 11;
        }

        $nameX = 18;
        $nameW = 54;
        $barLeftX = 74;
        $barRightX = 196;
        $barW_full = $barRightX - $barLeftX;

        $firstRowY = $pdf->GetY();

        foreach ($areas as $area) {
            $score = min(100, max(0, $area->score ?: 0));
            $currentY = $pdf->GetY();
            $areaName = $area->displayNameEn();

            if ($score >= 80) {
                $r = 0;
                $g = 200;
                $b = 83;
            } elseif ($score >= 70) {
                $r = 255;
                $g = 214;
                $b = 0;
            } elseif ($score >= 60) {
                $r = 215;
                $g = 120;
                $b = 20;
            } else {
                $r = 247;
                $g = 0;
                $b = 0;
            }

            $pdf->setRTL(false);
            $pdf->SetFont($this->fontBold, '', $fontSize);
            $pdf->SetXY($nameX, $currentY);
            $pdf->Cell($nameW, $h, $areaName, 0, 0, 'L');

            $pdf->SetFillColor(225, 225, 225);
            $pdf->Rect($barLeftX, $currentY + 0.5, $barW_full, $h - 1, 'F');

            $filledW = ($score / 100.0) * $barW_full;
            $pdf->SetFillColor($r, $g, $b);
            $pdf->Rect($barLeftX, $currentY + 0.5, $filledW, $h - 1, 'F');

            $pdf->SetFont('helvetica', 'B', max(8, $fontSize - 1.5));
            $pdf->SetXY($barLeftX + 1, $currentY + 0.5);
            $pdf->SetTextColor(($score >= 70 && $score <= 100) ? 40 : 255, ($score >= 70 && $score <= 100) ? 40 : 255, ($score >= 70 && $score <= 100) ? 40 : 255);
            $pdf->Cell(max(12, $filledW - 1), $h - 1, $score . '%', 0, 0, 'L');

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetY($currentY + $h + $spacing);
        }

        $pdf->setRTL(false);
        $pdf->SetDrawColor(220, 220, 220);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($nameX + $nameW + 1, $firstRowY, $nameX + $nameW + 1, $pdf->GetY());
        $pdf->SetLineWidth(0.2);
        $pdf->setRTL(false);

        $this->renderLegend($pdf);
    }

    private function renderTechnicalNotes(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(false);

        // Header Title Box
        $pdf->SetFont($this->fontBold, '', 18);
        $pdf->SetFillColor(218, 227, 243);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $title = 'Technical Observations Report';
        $pdf->Cell(0, 14, $title, 1, 1, 'C', true);
        $pdf->Ln(6);

        $leftMargin = 14;
        $pageBottom = $pdf->getPageHeight() - 40;

        foreach ($areas as $area) {
            $noteItems = $area->notesListEn();
            $areaName = $area->displayNameEn();
            if ($noteItems === []) {
                $name = $area->displayNameEn();
                $noteItems = ['Works for ' . $name . ' were inspected and found properly executed with no remarks.'];
            }

            $blockHeight = $this->estimateTechnicalNotesBlockHeight($pdf, $noteItems);
            if ($pdf->GetY() + $blockHeight > $pageBottom) {
                $pdf->AddPage();
            }

            $currentY = $pdf->GetY();

            $score = min(100, max(0, $area->score ?: 0));

            if ($score >= 80) {
                $r = 0;
                $g = 200;
                $b = 83; // Green
            } elseif ($score >= 70) {
                $r = 255;
                $g = 255;
                $b = 0; // Yellow
            } elseif ($score >= 60) {
                $r = 215;
                $g = 120;
                $b = 20; // Orange/Brown
            } else {
                $r = 247;
                $g = 0;
                $b = 0; // Red
            }

            $h = 6;
            $barW_full = 100;

            $pdf->setRTL(false);
            $pdf->SetFont($this->fontBold, '', 12);
            $textW = $pdf->GetStringWidth($areaName) + 2;
            $nameX = $leftMargin;

            $pdf->SetXY($nameX, $currentY);
            $pdf->setRTL(false);
            $pdf->Cell($textW, $h, $areaName, 0, 0, 'L');

            $barX = $nameX + $textW + 2;

            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($barX, $currentY + 1, $barW_full, $h, 'F');

            $filledW = ($score / 100) * $barW_full;
            $pdf->SetFillColor($r, $g, $b);
            $pdf->Rect($barX, $currentY + 1, $filledW, $h, 'F');

            $pdf->SetFont('helvetica', '', 9);
            $pdf->SetXY($barX + 2, $currentY + 1);

            if ($score >= 70 && $score <= 100) {
                $pdf->SetTextColor(50, 50, 50);
            } else {
                $pdf->SetTextColor(255, 255, 255);
            }

            $pdf->Cell(15, $h, $score . '%', 0, 0, 'L');

            $pdf->setRTL(false);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetY($currentY + $h + 6);

            $pdf->SetFont($this->fontBold, '', 12);
            $pdf->setCellHeightRatio(1.8);
            foreach ($noteItems as $noteText) {
                $line = '• ' . $noteText;
                $pdf->MultiCell(0, 6, $this->stripUnsupportedEmoji($line), 0, 'L', false, 1, '', '', true);
                $pdf->Ln(2);
            }
            $pdf->setCellHeightRatio(1.5);

            $pdf->Ln(5);
        }
    }

    /**
     * Estimate the vertical space needed for one technical-notes area block
     * (title bar + all bullet notes) so we can avoid orphan headers at page bottom.
     */
    private function estimateTechnicalNotesBlockHeight(TCPDF $pdf, array $noteItems): float
    {
        $headerHeight = 18.0;
        $notesHeight = 0.0;

        $pdf->SetFont($this->fontBold, '', 12);
        $contentWidth = $pdf->getPageWidth() - $pdf->getMargins()['left'] - $pdf->getMargins()['right'];

        foreach ($noteItems as $noteText) {
            $notesHeight += $pdf->getStringHeight($contentWidth, '• ' . $noteText) + 2;
        }

        return $headerHeight + $notesHeight + 5;
    }

    private function renderRecommendations(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(false);
        $pageW = $pdf->getPageWidth();
        $leftMargin = 14;
        $tableW = $pageW - ($leftMargin * 2);
        $colArea = 45;
        $colRecommendation = $tableW - $colArea;
        $headerH = 10;

        $pdf->SetFont($this->fontBold, '', 12);
        $pdf->SetFillColor(129, 199, 132);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);

        $areaX = $leftMargin;
        $recX = $leftMargin + $colArea;

        $pdf->setRTL(false);
        $pdf->SetXY($areaX, $pdf->GetY());
        $pdf->Cell($colArea, $headerH, 'Section', 1, 0, 'C', true);
        $pdf->SetXY($recX, $pdf->GetY());
        $pdf->Cell($colRecommendation, $headerH, 'Recommendations', 1, 1, 'C', true);

        $drawTableHeader = function () use ($pdf, $areaX, $recX, $colArea, $colRecommendation, $headerH): void {
            $pdf->SetFont($this->fontBold, '', 12);
            $pdf->SetFillColor(129, 199, 132);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.2);
            $pdf->setRTL(false);
            $pdf->SetXY($areaX, $pdf->GetY());
            $pdf->Cell($colArea, $headerH, 'Section', 1, 0, 'C', true);
            $pdf->SetXY($recX, $pdf->GetY());
            $pdf->Cell($colRecommendation, $headerH, 'Recommendations', 1, 1, 'C', true);
        };

        foreach ($areas as $area) {
            $recItems = $area->recommendationsListEn();
            if ($recItems === []) {
                continue;
            }

            // In the official PDF it is printed as bullet points
            $recommendations = implode("\n", array_map(
                fn(string $text) => '• ' . trim($text),
                $recItems
            ));

            $score = min(100, max(0, $area->score ?: 0));

            // Determine color matching the legend
            if ($score >= 80) {
                $r = 0;
                $g = 200;
                $b = 83; // Green
            } elseif ($score >= 70) {
                $r = 255;
                $g = 255;
                $b = 0; // Yellow
            } elseif ($score >= 60) {
                $r = 215;
                $g = 120;
                $b = 20; // Orange/Brown
            } else {
                $r = 247;
                $g = 0;
                $b = 0; // Red
            }

            // Calculate the height needed for the recommendations text
            $pdf->SetFont($this->font, '', 11);
            $recHeight = $pdf->getStringHeight($colRecommendation - 6, $recommendations);
            $rowH = max(18, $recHeight + 8);

            // Check if we need a new page
            if ($pdf->GetY() + $rowH > $pdf->getPageHeight() - 30) {
                $pdf->AddPage();
                $drawTableHeader();
            }

            $startY = $pdf->GetY();

            // Right column: Area name with colored background
            $pdf->SetFillColor($r, $g, $b);
            // Keep text readable on bright colors (yellow/green)
            if ($score >= 70) {
                $pdf->SetTextColor(0, 0, 0);
            } else {
                $pdf->SetTextColor(255, 255, 255);
            }

            $areaDisplayName = trim($area->displayNameEn());

            $pdf->SetFont($this->fontBold, '', 12);
            $pdf->Rect($areaX, $startY, $colArea, $rowH, 'DF');
            $pdf->setRTL(false);
            $pdf->SetXY($areaX, $startY + ($rowH / 2) - 4);
            $pdf->Cell($colArea, 8, $areaDisplayName, 0, 0, 'C');

            // Left column: Recommendations with white background
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($recX, $startY, $colRecommendation, $rowH, 'DF');

            // Keep manual coordinates stable so text stays inside recommendations column
            $pdf->setRTL(false);
            $pdf->SetXY($recX + 2, $startY + 2);
            $pdf->SetFont($this->font, '', 11);
            $pdf->MultiCell($colRecommendation - 6, 6, $this->stripUnsupportedEmoji($recommendations), 0, 'L', false, 1, '', '', true);
            $pdf->setRTL(false);

            // Draw borders for both columns
            $pdf->Rect($areaX, $startY, $colArea, $rowH, 'D');
            $pdf->Rect($recX, $startY, $colRecommendation, $rowH, 'D');

            $pdf->SetY($startY + $rowH);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setRTL(false);
    }

    private function renderFinalResult(TCPDF $pdf, PropertyHouse $house, string $reportDate): void
    {
        $pdf->AddPage();
        $pdf->setRTL(false);

        $pdf->SetFont($this->fontBold, '', 15);
        $pdf->SetFillColor(255, 255, 0); // Yellow background
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $titleW = 120;
        $titleX = ($pdf->getPageWidth() - $titleW) / 2;
        $pdf->SetXY($titleX, $pdf->GetY());
        $pdf->Cell($titleW, 11, 'Final Inspection Result', 1, 1, 'C', true);
        $pdf->Ln(4);

        $totalPct = min(100, max(0, (int) ($house->total_percentage ?? 0)));
        $rating = $this->resolveInspectorRatingEn($house, $totalPct);
        $deliveryDate = ($house->report_delivered_at ?? $house->updated_at ?? now())->format('Y-m-d');

        $manualText = trim((string) ($house->final_result_text_en ?: $house->final_result_text ?? ''));
        if ($manualText !== '') {
            $lines = preg_split("/\r\n|\n|\r/", $manualText);
            $cleanParagraphs = [];
            foreach ($lines as $line) {
                $trimmed = trim($line);
                if ($trimmed !== '') {
                    $cleanParagraphs[] = e($this->stripUnsupportedEmoji($trimmed));
                }
            }
            $formattedHtml = implode('<br/>', $cleanParagraphs);

            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div dir="ltr" style="font-size:12pt; text-align:justify; line-height:1.55; font-family: arialbd;">' . $formattedHtml . '</div>',
                0,
                1,
                false,
                true,
                'L'
            );
        } else {
            $pdf->SetTextColor(120, 120, 120);
            $pdf->MultiCell(0, 6, 'Final result text has not been entered yet.', 0, 'L');
            $pdf->SetTextColor(0, 0, 0);
        }

        $generalNotes = trim((string) ($house->final_general_notes_en ?: $house->final_general_notes ?? ''));
        if ($generalNotes !== '') {
            $pdf->Ln(2);
            $pdf->SetFont($this->fontBold, '', 11);
            $pdf->SetTextColor(192, 0, 0);
            $pdf->Cell(0, 6, 'General Notes', 0, 1, 'L');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont($this->font, '', 10.5);

            $notesLines = [];
            foreach (preg_split("/\r\n|\n|\r/", $generalNotes) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                $line = $this->stripUnsupportedEmoji($line);
                if (mb_substr($line, 0, 1) !== '•' && mb_substr($line, 0, 1) !== '-') {
                    $line = '• ' . $line;
                }
                $notesLines[] = e($line);
            }
            $notesHtml = implode('<br/>', $notesLines);
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div dir="ltr" style="font-size:10.5pt; text-align:left; line-height:1.35; text-decoration:none;">' . $notesHtml . '</div>',
                0,
                1,
                false,
                true,
                'L'
            );
        }

        $pdf->Ln(2);
        $pdf->SetDrawColor(180, 180, 180);
        $pdf->Line(14, $pdf->GetY(), $pdf->getPageWidth() - 14, $pdf->GetY());
        $pdf->Ln(3);

        $pdf->SetFont($this->fontBold, '', 11);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="ltr" style="font-size:11pt;text-align:left;">'
            . 'Inspector rating for property condition: (<b>' . e($rating) . '</b>)'
            . '</div>',
            0,
            1,
            false,
            true,
            'L',
        );

        $pdf->Ln(2);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="ltr" style="font-size:11pt;text-align:left;color:#c00000;line-height:1.3;">'
            . '• Report delivered on <b>' . e($deliveryDate) . '</b>'
            . '</div>',
            0,
            1,
            false,
            true,
            'L',
        );

        $pdf->Ln(2);
        $pdf->SetFont($this->font, '', 10.5);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="ltr" style="font-size:10.5pt;text-align:left;line-height:1.3;">'
            . '<span style="text-decoration:underline; font-weight:bold;">Please see the attachments</span><br/>'
            . '1. Electrical report.<br/>'
            . '2. Photographs.'
            . '</div>',
            0,
            1,
            false,
            true,
            'L',
        );

        $signaturePath = $this->resolveSignaturePath();
        if ($signaturePath) {
            $pageW = $pdf->getPageWidth();
            $pageH = $pdf->getPageHeight();
            $sigW = 65;
            $sigH = 42;
            $sigX = $pageW - $sigW - 16; // Bottom-right
            $sigY = $pdf->GetY() + 4;

            // Never let it run past the footer band — clamp instead of overlapping
            $maxY = $pageH - 32;
            if ($sigY > $maxY) {
                $sigY = $maxY;
            }

            try {
                $pdf->Image($signaturePath, $sigX, $sigY, $sigW, $sigH, '', '', '', false, 300, '', false, false, 0);
            } catch (Throwable) {
            }
        }

        $pdf->setCellHeightRatio(1.25);
    }

    private function resolveSignaturePath(): ?string
    {
        $candidates = [
            env('REPORT_SIGNATURE_PATH'),
            'images/GIS SIGN.png',
            'images/gis-sign.png',
        ];

        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $path = public_path($candidate);
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function renderLegend(TCPDF $pdf): void
    {
        // Position at the bottom of the current page
        $pdf->SetY($pdf->getPageHeight() - 55, false);

        $w = 20;
        $h = 5;
        $x = 14;

        $pdf->SetX($x);
        $pdf->SetFont($this->fontBold, '', 9);
        $pdf->SetFillColor(200, 210, 235);
        $pdf->Cell($w, $h, 'Color', 1, 0, 'C', true);
        $pdf->Cell($w * 2.2, $h, 'Rating', 1, 1, 'C', true);

        $legend = [
            ['Green', 'Excellent (80–100%)', [0, 200, 83]],
            ['Yellow', 'Good (70–79%)', [255, 255, 0]],
            ['Orange', 'Average (60–69%)', [215, 120, 20]],
            ['Red', 'Poor (below 60%)', [247, 0, 0]],
        ];

        foreach ($legend as $item) {
            $pdf->SetX($x);
            $pdf->SetFillColor($item[2][0], $item[2][1], $item[2][2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($w, $h, $item[0], 1, 0, 'C', true);
            $pdf->Cell($w * 2.2, $h, $item[1], 1, 1, 'C', true);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    private function renderPropertyDataTable(TCPDF $pdf, PropertyHouse $house): void
    {
        $data = [
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
        ];

        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);

        $wLabel = 56;
        $wValue = 78;
        $tableW = $wLabel + $wValue;
        $tableX = ($pdf->getPageWidth() - $tableW) / 2;
        $h = 7;

        foreach ($data as $row) {
            $pdf->SetX($tableX);
            $pdf->SetFont($this->fontBold, '', 10.5);
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'L', true);
            $pdf->SetFont($this->font, '', 10.5);
            $pdf->Cell($wValue, $h, $row[1], 1, 1, 'L', true);
        }
    }

    private function renderPropertySpecsTable(TCPDF $pdf, PropertyHouse $house): void
    {
        $data = [
            ['Approx. property age', $house->property_age ?: '---'],
            ['Land area', $house->land_area ?: '---'],
            ['Built-up area', $house->building_area ?: '---'],
            ['Floors', $house->floors_count ?: '---'],
            ['Rooms', $house->rooms_count ?: '---'],
            ['Bathrooms', $house->bathrooms_count ?: '---'],
            ['Halls', $house->halls_count ?: '---'],
            ['Parking spaces', $house->parking_count ?: '---'],
            ['Kitchens', $house->kitchens_count ?: '---'],
        ];

        $wLabel = 56;
        $wValue = 78;
        $tableW = $wLabel + $wValue;
        $tableX = ($pdf->getPageWidth() - $tableW) / 2;
        $h = 7;

        foreach ($data as $row) {
            $pdf->SetX($tableX);
            $pdf->SetFont($this->fontBold, '', 10.5);
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'L', true);
            $pdf->SetFont($this->font, '', 10.5);
            $pdf->Cell($wValue, $h, $row[1], 1, 1, 'L', true);
        }
    }

    private function resolveLogoPath(): ?string
    {
        $candidates = [
            'images/newLog.jpeg',
            'images/newLog.jpg',
            env('REPORT_LOGO_PATH'),
            'images/logo.png',
            'images/company-logo.png',
        ];
        foreach ($candidates as $candidate) {
            if (!$candidate) {
                continue;
            }
            $path = public_path($candidate);
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function overlayCoverLogo(TCPDF $pdf, float $pageW, float $coverTopPad): void
    {
        $logoPath = $this->resolveLogoPath();
        if (!$logoPath || !is_readable($logoPath)) {
            return;
        }

        try {
            $bandH = 78;
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect(0, 0, $pageW, $coverTopPad + $bandH, 'F');

            $logoW = 32;
            $logoH = $logoW * (1474 / 1067);
            $logoX = ($pageW - $logoW) / 2;
            $logoY = 4 + $coverTopPad;

            $pdf->Image($logoPath, $logoX, $logoY, $logoW, $logoH, '', '', '', false, 300);

            $pdf->SetTextColor(128, 0, 0);
            $pdf->SetFont($this->fontBold, '', 9);
            $yText = $logoY + $logoH + 1;
            $pdf->SetXY(0, $yText);
            $pdf->Cell($pageW, 4.5, 'GIS VALUATION AND EVALUATION', 0, 1, 'C');
            $pdf->SetFont($this->fontBold, '', 10);
            $pdf->SetX(0);
            $pdf->Cell($pageW, 5, 'جي إي إس للتقييم والتثمين العقاري', 0, 1, 'C');
            $pdf->SetTextColor(0, 0, 0);
        } catch (Throwable $e) {
        }
    }

    /**
     * Register Arial Bold for thicker, clearer Arabic report typography.
     */
    private function registerReportFonts(): void
    {
        $boldTtf = base_path('resources/fonts/arialbd.ttf');

        if (is_file($boldTtf)) {
            $name = \TCPDF_FONTS::addTTFfont($boldTtf, 'TrueTypeUnicode', '', 32);
            if (is_string($name) && $name !== '') {
                $this->font = $name;
                $this->fontBold = $name;
            }
        }
    }

    /**
     * Prefer the newest full-bleed cover assets, fall back to older extracts.
     */
    private function resolveCoverBackgroundPath(): ?string
    {
        $candidates = [
            public_path('images/cover-a4-300dpi.jpg'),
            public_path('images/cover-a4-300dpi.png'),
            public_path('images/extracted_0.png'),
        ];

        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Returns a path to a version of the cover background PNG with any
     * baked-in white/transparent margin trimmed off.
     */
    private function resolveTrimmedCoverBackground(): ?string
    {
        $sourcePath = public_path('images/extracted_0.png');
        if (!is_file($sourcePath)) {
            return null;
        }

        if (!function_exists('imagecreatefrompng')) {
            return $sourcePath;
        }

        try {
            $cacheDir = storage_path('app/report-cache');
            if (!is_dir($cacheDir)) {
                @mkdir($cacheDir, 0775, true);
            }
            $cachedPath = $cacheDir . '/extracted_0_trimmed.png';

            if (is_file($cachedPath) && filemtime($cachedPath) >= filemtime($sourcePath)) {
                return $cachedPath;
            }

            $trimmed = $this->trimImageWhitespace($sourcePath);
            if ($trimmed) {
                imagepng($trimmed, $cachedPath);
                imagedestroy($trimmed);
                return $cachedPath;
            }
        } catch (Throwable $e) {
        }

        return $sourcePath;
    }

    /**
     * Scans the PNG for the bounding box of its actual artwork and returns
     * a cropped GD image limited to that box.
     */
    private function trimImageWhitespace(string $path)
    {
        $src = @imagecreatefrompng($path);
        if (!$src) {
            return null;
        }

        imagealphablending($src, false);
        imagesavealpha($src, true);

        $width = imagesx($src);
        $height = imagesy($src);

        $minX = $width;
        $maxX = 0;
        $minY = $height;
        $maxY = 0;
        $found = false;

        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $rgba = imagecolorat($src, $x, $y);
                $alpha = ($rgba >> 24) & 0x7F;
                $r = ($rgba >> 16) & 0xFF;
                $g = ($rgba >> 8) & 0xFF;
                $b = $rgba & 0xFF;

                $isWhiteish = ($r > 248 && $g > 248 && $b > 248);
                $isTransparent = $alpha >= 120;

                if (!$isWhiteish && !$isTransparent) {
                    $found = true;
                    if ($x < $minX) {
                        $minX = $x;
                    }
                    if ($x > $maxX) {
                        $maxX = $x;
                    }
                    if ($y < $minY) {
                        $minY = $y;
                    }
                    if ($y > $maxY) {
                        $maxY = $y;
                    }
                }
            }
        }

        if (!$found) {
            imagedestroy($src);
            return null;
        }

        $pad = 2;
        $minX = max(0, $minX - $pad);
        $minY = max(0, $minY - $pad);
        $maxX = min($width - 1, $maxX + $pad);
        $maxY = min($height - 1, $maxY + $pad);

        $cropW = $maxX - $minX + 1;
        $cropH = $maxY - $minY + 1;

        $dst = imagecreatetruecolor($cropW, $cropH);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
        imagefilledrectangle($dst, 0, 0, $cropW, $cropH, $transparent);

        imagecopy($dst, $src, 0, 0, $minX, $minY, $cropW, $cropH);
        imagedestroy($src);

        return $dst;
    }
}

class ReportTCPDFEn extends TCPDF
{
    public string $reportNo = '';
    public string $reportDate = '';
    public ?string $logoPath = null;
    public string $reportFont = 'arialbd';
    public string $reportFontBold = 'arialbd';

    public function Header()
    {
        // Dynamic Header (Page 2 onwards only).
        // Cover background is drawn inside renderCoverPage() so it can
        // full-bleed to the real page edges without Header clipping.
        if ($this->PageNo() > 1) {
            $this->setRTL(false);
            // Small logo on the top-left
            if ($this->logoPath && is_readable($this->logoPath)) {
                try {
                    $this->Image($this->logoPath, 14, 5, 16, 0, '', '', '', false, 300);
                } catch (Throwable $e) {
                }
            }

            // Request details on the top-right
            $this->SetFont($this->reportFontBold, '', 10);
            $this->SetTextColor(192, 0, 0); // Maroon red
            $this->SetXY(135, 11);
            $this->Cell(60, 5, 'Request No. ' . $this->reportNo, 0, 0, 'R');

            $this->SetFont($this->reportFont, '', 9);
            $this->SetXY(135, 17);
            $this->Cell(60, 5, 'Inspection Date ' . $this->reportDate, 0, 0, 'R');
        }

        $this->setRTL(false);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer()
    {
        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();

        // Footer elements (Page 2 onwards) — matches footerNew.png:
        // no grey divider line, contact icons left, page # center, company right.
        if ($this->PageNo() > 1) {
            $this->setRTL(false);

            $maroon = [128, 0, 0];
            $rowY = $pageH - 17;
            $iconSize = 3.4;
            $iconY = $rowY + 0.55;
            $textY = $rowY;

            $whatsappIcon = public_path('images/whatsapp_icon.png');
            $emailIcon = public_path('images/email_icon.png');
            $instagramIcon = public_path('images/instagram.png');

            // Left contact row: WhatsApp | Email | Instagram (compact)
            $x = 14.0;
            if (is_file($whatsappIcon)) {
                try {
                    $this->Image($whatsappIcon, $x, $iconY, $iconSize, $iconSize, '', '', '', false, 300);
                } catch (Throwable $e) {
                }
            }
            $x += $iconSize + 1.0;
            $this->SetFont($this->reportFontBold, '', 8);
            $this->SetTextColor($maroon[0], $maroon[1], $maroon[2]);
            $this->SetXY($x, $textY);
            $this->Cell(17, 4.2, '36698895', 0, 0, 'L');

            $x = 36.5;
            if (is_file($emailIcon)) {
                try {
                    $this->Image($emailIcon, $x, $iconY, $iconSize, $iconSize, '', '', '', false, 300);
                } catch (Throwable $e) {
                }
            }
            $x += $iconSize + 1.0;
            $this->SetFont($this->reportFontBold, '', 7.5);
            $this->SetTextColor($maroon[0], $maroon[1], $maroon[2]);
            $this->SetXY($x, $textY);
            $this->Cell(38, 4.2, 'infogisgulf@gmail.com', 0, 0, 'L');

            $x = 79.0;
            if (is_file($instagramIcon)) {
                try {
                    $this->Image($instagramIcon, $x, $iconY, $iconSize, $iconSize, '', '', '', false, 300);
                } catch (Throwable $e) {
                }
            }
            $x += $iconSize + 1.0;
            $this->SetFont($this->reportFontBold, '', 8);
            $this->SetTextColor($maroon[0], $maroon[1], $maroon[2]);
            $this->SetXY($x, $textY);
            $this->Cell(22, 4.2, 'gis.Bahrain', 0, 0, 'L');

            // Address under contact row
            $this->SetFont('helvetica', '', 7);
            $this->SetTextColor($maroon[0], $maroon[1], $maroon[2]);
            $this->SetXY(14, $pageH - 11.8);
            $this->Cell(90, 3.2, 'Seef District - Kingdom of Bahrain', 0, 0, 'L');

            // Right: company block
            $rightW = 86.0;
            $rightX = $pageW - 14 - $rightW;
            $this->SetFont($this->reportFontBold, '', 8);
            $this->SetTextColor($maroon[0], $maroon[1], $maroon[2]);
            $this->SetXY($rightX, $pageH - 18);
            $this->Cell($rightW, 3.6, 'GIS VALUATION AND EVALUATION', 0, 2, 'R');
            $this->Cell($rightW, 3.6, 'GIS Property Inspection & Valuation', 0, 2, 'R');
            $this->SetFont('helvetica', '', 7);
            $this->Cell($rightW, 3.0, 'C.R. 160528-1', 0, 0, 'R');

            $this->setRTL(false);
            $this->SetTextColor(0, 0, 0);
        }
    }
}