<?php

namespace App\Services;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use App\Support\InspectionScoreLabels;
use TCPDF;
use Throwable;

class InspectionReportPdfGenerator
{
    /** Arial Bold for clearer, thicker Arabic text throughout the report. */
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
            $reportDate = ($house->created_at ?: now())->format('Y-m-d');
            $logoPath = $this->resolveLogoPath();

            $pdf = new ReportTCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->reportNo = $reportNo;
            $pdf->reportDate = $reportDate;
            $pdf->logoPath = $logoPath;
            $pdf->reportFont = $this->font;
            $pdf->reportFontBold = $this->fontBold;

            $pdf->SetCreator(config('app.name'));
            $pdf->SetAuthor(config('app.name'));
            $pdf->SetTitle('تقرير معاينة — ' . $house->title);

            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);

            $pdf->SetMargins(12, 30, 12);
            $pdf->SetHeaderMargin(8);
            $pdf->SetFooterMargin(12);
            $pdf->SetAutoPageBreak(true, 24);

            $pdf->setRTL(true);
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
            $pdf->Cell(0, 8, 'بيانات العقار :-', 0, 1, 'R');

            $pdf->SetFont($this->font, '', 11);
            $this->renderPropertyDataTable($pdf, $house);

            $pdf->Ln(6);
            $pdf->SetFont($this->fontBold, '', 16);
            $pdf->Cell(0, 10, 'مواصفات العقار :-', 0, 1, 'R');

            $pdf->SetFont($this->font, '', 11);
            $this->renderPropertySpecsTable($pdf, $house);

            // Draw signature stamp directly below the tables, bottom-right of THIS page.
            // No page navigation here — this guarantees the stamp never lands on its own
            // near-empty page and never overlaps the table rows.
            $signaturePath = $this->resolveSignaturePath();
            if ($signaturePath) {
                $pageW = $pdf->getPageWidth();
                $pageH = $pdf->getPageHeight();
                $sigW = 38;
                $sigH = 24;
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
                $pdf->setRTL(true);
            }

            // --- Percentages ---
            $pdf->AddPage();

            // 1. Total Percentage (Donut)
            $pdf->SetFont($this->fontBold, '', 16);
            $pdf->Cell(0, 10, 'النسبة الإجمالية', 0, 1, 'C');
            $pdf->Ln(1);
            $this->renderTotalDonut($pdf, $house->total_percentage);

            // 2. Sub-Percentages (Bars)
            $pdf->Ln(5);
            $pdf->SetFont($this->fontBold, '', 16);
            $pdf->Cell(0, 10, 'النسب الفرعية', 0, 1, 'C');
            $pdf->Ln(3);
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

        $reportNo = $house->reference_code ?: ('H-' . $house->id);
        $reportDate = ($house->created_at ?: now())->format('Y-m-d');

        $addressParts = [];
        if ($house->villa_number) {
            $addressParts[] = 'فيلا ' . $house->villa_number;
        }
        if ($house->road) {
            $addressParts[] = 'طريق ' . $house->road;
        }
        if ($house->compound) {
            $addressParts[] = 'مجمع ' . $house->compound;
        }
        if ($house->area) {
            $addressParts[] = $house->area;
        }
        $propertyAddress = implode('، ', $addressParts) ?: ($house->title ?: '---');

        $infoRows = [
            ['رقم التقرير', 'Report No.', $reportNo, true],
            ['التاريخ', 'Date', $reportDate, false],
            ['اسم العميل', 'Client Name', $house->client_name ?: $house->buyer_name ?: '---', false],
            ['عنوان المنزل', 'Property Address', $propertyAddress, false],
        ];

        // Centered info block in the white area under the logo (matches official cover).
        $tableW = 160;
        $tableX = ($pageW - $tableW) / 2;
        $wAr = 38;
        $wEn = 38;
        $wVal = $tableW - $wAr - $wEn;
        $rowH = 18;

        $pdf->SetY(112 + $coverTopPad);

        foreach ($infoRows as [$arLabel, $enLabel, $value, $isHighlight]) {
            $y = $pdf->GetY();

            // Arabic label (right)
            $pdf->SetFont($this->fontBold, '', 12);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($tableX + $tableW - $wAr, $y);
            $pdf->Cell($wAr, $rowH, $arLabel, 0, 0, 'C');

            // Value (center) — always bold for clarity
            if ($isHighlight) {
                $pdf->SetTextColor(139, 26, 26);
            } else {
                $pdf->SetTextColor(0, 0, 0);
            }
            $valSize = ($arLabel === 'عنوان المنزل') ? 11 : 13;
            $pdf->SetFont($this->fontBold, '', $valSize);
            $pdf->SetXY($tableX + $wEn, $y);
            $pdf->MultiCell($wVal, $rowH / 2, $value, 0, 'C', false, 0, '', '', true, 0, false, true, $rowH, 'M');

            // English label (left)
            $pdf->SetFont($this->fontBold, '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY($tableX, $y);
            $pdf->Cell($wEn, $rowH, $enLabel, 0, 0, 'C');

            $pdf->SetY($y + $rowH + 4);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setRTL(true);
    }

    private function renderFixedPages(TCPDF $pdf): void
    {
        $pdf->setCellHeightRatio(1.7);
        $pdf->SetFont($this->font, '', 11);

        foreach ($this->getFixedPagesContent() as $html) {
            $pdf->AddPage();
            $pdf->SetFont($this->font, '', 11);
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div style="font-size:11.5px; line-height: 1.7; font-family: arialbd;">' . $html . '</div>',
                0,
                1,
                false,
                true,
                'R',
                true
            );
        }

        $pdf->setCellHeightRatio(1.7);
    }

    private function getFixedPagesContent(): array
    {
        return [
            <<<'HTML'
<div style="text-align:right; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:14pt; text-align:center;">شركة GIS لفحص تقييم المباني</h1>
    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; text-align:center;">نبذة تعريفية – مقدمة الشركة وخدماتها</h2>

    <p style="font-size:10pt; font-family:arialbd; font-weight:bold; color:#000; margin-bottom:3px;">من نحن ؟</p>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">نحن في شركة <strong>GIS</strong> نؤمن بأن سلامة العقار تبدأ من القرار الصحيح، ولهذا نقدم خدمة فحص شاملة للمباني الجاهزة قبل الشراء أو السكن، وذلك للتأكد من خلوها من العيوب الفنية الظاهرة أو المخاطر الخفية.</p>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">نعمل من خلال خبراء مختصين، ونعتمد على أجهزة وتقنيات حديثة لضمان تقديم تقارير دقيقة وموثوقة تساعد العميل على اتخاذ قرار استثماري أو سكني آمن.</p>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">نعد من أوائل الشركات المتخصصة في فحص العقارات في البحرين، ونفتخر بسجل حافل من الإنجازات وخدمة مئات العملاء من الأفراد والشركات والمطورين.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; margin-bottom:3px; margin-top:5px;">خدماتنا الرئيسية</h2>
    <p style="font-size:10pt; font-family:arialbd; font-weight:bold; color:#c00000; margin-top:0px; margin-bottom:3px;">تقدم شركة GIS خدمات فحص المباني الجاهزة عبر تقييم شامل للأنظمة التالية:</p>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:17px"><strong>1- الكهرباء:</strong> فحص جودة وسلامة الأفياش، المفاتيح، الطبلون، الأحمال، التأريض.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>2- السباكة:</strong> فحص جودة المواسير، الصفايات، ضغط المياه، الخزانات، السخانات، ملوحة الماء.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>3- الرطوبة والتسريب:</strong> قياس الرطوبة، كشف التسريبات، فحص العزل المائي.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>4- الخزانات:</strong> التأكد من سلامتها من التسريب الحراري أو المائي لتفادي التلوث أو تلف الأسقف.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>5- الأبواب والنوافذ:</strong> التحقق من جودة الفتح والإغلاق، المفصلات، الإطارات، العزل، الديكورات.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>6- الأسقف والجدران:</strong> فحص الشقوق، آثار الرطوبة، جودة التشطيب، الزوايا، العزل الحراري.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>7- السيراميك والأرضيات:</strong> فحص الترويب، الاستواء، الثبات، جودة التركيب.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>8- أنظمة الحماية والسلامة:</strong> التأكد من جاهزية كاميرات المراقبة، كواشف الدخان، وحساسات الإنذار.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>9- التهوية والتكييف:</strong> فحص وحدات التكييف، فتحات الهواء، مراوح الشفط، جودة التأسيس.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px"><strong>10- مواقف السيارات والواجهات:</strong> فحص الأرضية، المظلات، باب الكراج، التشققات، فواصل التمدد.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:right; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-bottom:3px;">ما يميزنا</h1>
    
    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; margin-top:3px; margin-bottom:3px;">ضمانات GIS:</h2>
    <p style="font-size:9.5pt; margin-top:0px; margin-bottom:3px;">• <strong>ضمان الإحاطة:</strong> يشمل شمولية ودقة البيانات وفق معايير الشركة.</p>
    <p style="font-size:9.5pt; color:#008000; font-weight:bold; margin-top:0px; margin-bottom:0px;"><span style="color:#008000; font-weight:bold; font-size:11pt;">✔</span> شرح ضمان الإحاطة</p>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">تقديم تقرير شامل قدر الإمكان عن حالة العقار الظاهرة، بناءً على المعايير الفنية المتبعة داخل الشركة. لا يُعد هذا ضمانًا قانونيًا أو فنيًا لحالة العقار المستقبلية أو للعيوب المخفية.</p>

    <p style="font-size:9.5pt; margin-top:0px; margin-bottom:3px;">• <strong>ضمان السلامة:</strong> التأكد من خلو البنود المفحوصة من العيوب الخطرة، والرجوع لشهادات الضمان إن وجدت.</p>
    <p style="font-size:9.5pt; color:#008000; font-weight:bold; margin-top:0px; margin-bottom:0px;"><span style="color:#008000; font-weight:bold; font-size:11pt;">✔</span> شرح ضمان السلامة</p>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">يُقصد به التأكد من عدم وجود عيوب خطرة في العناصر المفحوصة وقت الفحص، وفق المشاهدات الظاهرة. في حال توفر شهادات ضمان للعناصر مثل الكهرباء أو السباكة أو التكييف، يتم الرجوع إليها دون أن تتحمل الشركة مسؤولية صلاحيتها.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; margin-top:5px; margin-bottom:3px;">امتيازاتنا:</h2>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:17px">• سرعة في الاستجابة وإنجاز الفحص.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• سهولة في تقديم الطلب واستلام التقرير.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• توثيق مصور لجميع الملاحظات والعيوب.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• تقارير احترافية سهلة القراءة وغنية بالتفاصيل.</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• أرشفة بيانات كل وحدة عقارية بشكل مستقل ومنظم.</td></tr>
    </table>

    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; margin-top:5px; margin-bottom:3px;">آلية الفحص والتسليم</h2>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:16px">• تستغرق عملية الفحص من ساعتين إلى يومين حسب مساحة العقار وتعقيد أنظمته.</td></tr>
        <tr><td style="font-size:9pt; line-height:16px">• يتم تسليم التقرير النهائي خلال 5 أيام عمل بعد مراجعته من قسم الجودة.</td></tr>
        <tr><td style="font-size:9pt; line-height:16px">• في حال وجود ملاحظات من العميل، يجب إرسالها خلال مدة أقصاها 3 أيام من استلام التقرير لإجراء التعديلات المطلوبة، إن وجدت.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:right; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:12.5pt; text-decoration:underline; margin-bottom:6px;">المستندات الاختيارية التي تُحسن جودة تقرير الفحص العقاري :-</h1>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">تساهم المستندات التالية في رفع دقة وجودة التقييم الفني للعقار، ويُفضل إرفاق ما يتوفر منها مع طلب الفحص:</p>

    <p style="font-size:10pt; font-weight:bold; margin-bottom:3px; color:#c00000;">أولاً: مستندات هندسية وفنية</p>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:17px">• رخصة البناء</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• المخططات الهندسية المعتمدة (معمارية، إنشائية، ميكانيكية، كهربائية)</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• شهادة ضمان الهيكل الإنشائي (عادة لمدة 10 سنوات)</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• تقارير فحص التربة</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• شهادات ضمان المواد والأنظمة مثل: (السباكة، الكهرباء، التكييف، العازل المائي، الألمنيوم والزجاج)</td></tr>
    </table>

    <p style="font-size:10pt; font-weight:bold; margin-top:5px; margin-bottom:3px; color:#c00000;">ثانياً: معلومات عامة وموقع العقار</p>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:17px">• صورة من خرائط العقار المعتمدة من المكتب الهندسي</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• موقع العقار (رابط مباشر أو صورة من خرائط Google أو تطبيق مشابه)</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• شهادة العنوان (تشمل رقم المنزل، اسم الشارع، رقم الطريق، ورقم المجمع)</td></tr>
    </table>

    <p style="font-size:10pt; font-weight:bold; margin-top:5px; margin-bottom:3px; color:#c00000;">ثالثاً: مستندات إضافية داعمة لتقييم العقار</p>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:17px">• شهادة ضغط الأرض (إن وُجدت)</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• عمر العقار التقريبي (سنة الإنشاء أو التسليم)</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• صورة البطاقة الذكية للمشتري أو طالب خدمة الفحص</td></tr>
        <tr><td style="font-size:9pt; line-height:17px">• عقد المقاول العام و ضمانات المواد المستخدمة في البناء ( اختياري )</td></tr>
    </table>
</div>
HTML,  
            <<<'HTML'
<div style="text-align:right; font-family: arialbd;">
    <h1 style="font-family:arialbd; color:#c00000; font-size:13pt; text-decoration:underline; margin-bottom:6px;">تنويه هام – حدود المسؤولية</h1>
    <p style="font-size:9.5pt; color:#222; margin-top:0px; margin-bottom:6px;">تلتزم شركة <strong>GIS</strong> بتقديم خدماتها بأقصى درجات الدقة والحيادية، باستخدام أدوات تقنية ومن خلال خبراء مختصين. مع ذلك، لا تتحمل الشركة أي مسؤولية قانونية أو مادية عن الأضرار الناتجة عن عملية الفحص, سواء أثناء المعاينة أو بعدها.</p>
    <p style="font-size:9.5pt; color:#222; margin-top:0px; margin-bottom:6px;">كما لا تتحمل الشركة أي التزامات ناتجة عن نزاعات بين العميل وأطراف أخرى مثل المقاول أو المطور، إذ تقتصر مسؤوليتنا على التشخيص والتوثيق فقط.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; margin-top:5px; margin-bottom:3px;"><span style="color:#c00000; font-weight:bold; font-size:11pt;">✖</span> تنويه هام:</h2>
    <p style="font-size:9pt; color:#222; margin-top:0px; margin-bottom:6px;">تقتصر مهمة شركة GIS على فحص وتوثيق الحالة الظاهرة للعقار وقت المعاينة فقط. ولا يُعد التقرير الصادر عنها التزامًا بضمان مستقبلي لحالة العقار أو لأي تطورات قد تطرأ لاحقًا. تنتهي مسؤولية الشركة بشكل كامل فور انتهاء الفحص ومغادرة الفريق للموقع، ما لم يتم الاتفاق المسبق على خدمة متابعة أو فحص إضافي.</p>

    <h2 style="font-family:arialbd; color:#c00000; font-size:11pt; text-decoration:underline; margin-top:5px; margin-bottom:3px;">الأسئلة الشائعة</h2>
    <table cellpadding="5">
        <tr><td style="font-size:9pt; line-height:16px;"><strong style="font-family:arialbd; color:red;">هل يمكن استرداد المبلغ؟</strong><br/><br/> نعم، مع تطبيق رسوم إدارية في حال تم الإلغاء قبل 24 ساعة من موعد الفحص.</td></tr><br/>
        <tr><td style="font-size:9pt; line-height:16px;"><strong style="font-family:arialbd; color:red;">هل يمكن تعديل التقرير؟</strong><br/><br/> نعم، يتم قبول الملاحظات خلال 3 أيام فقط من استلام التقرير.</td></tr><br/>
        <tr><td style="font-size:9pt; line-height:16px;"><strong style="font-family:arialbd; color:red;">هل تقدمون خدمات صيانة؟</strong><br/><br/> لا، نحن جهة فحص فقط لضمان الحيادية والاستقلالية.</td></tr><br/>
        <tr><td style="font-size:9pt; line-height:16px;"><strong style="font-family:arialbd; color:red;">كيف يتم تعيين الفاحص؟</strong><br/><br/> نقوم بترشيح فاحص متخصص بناءً على نوع العقار وتوزيع العمل، ويرفع التقرير ضمن نموذج GIS المعتمد.</td></tr>
        <tr><td style="font-size:9pt; line-height:16px;"><strong style="font-family:arialbd; color:red;">لماذا الفحص مهم؟</strong><br/> لأنه يوفر عليك آلاف الدنانير ويكشف عيوب قد تكون خفية قبل الشراء أو الاستلام.</td></tr>
    </table>
</div>
HTML,
        ];
    }

    private function renderTotalDonut(TCPDF $pdf, int $percentage): void
    {
        $cX = 105;
        $cY = $pdf->GetY() + 35;
        $radius = 32;
        $thickness = 10;

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
        $pdf->SetFont($this->fontBold, '', 24);
        $pdf->Cell(30, 10, $percentage . '%', 0, 0, 'C');
        $pdf->SetTextColor(0, 0, 0);

        $pdf->SetY($cY + $radius + 10);
    }

    private function renderSubPercentageBars(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(true);
        $pageW = $pdf->getPageWidth();
        $rightMargin = 14;
        $h = 8.0;
        $spacing = 3.0;
        $firstRowY = $pdf->GetY();

        foreach ($areas as $area) {
            if ($pdf->GetY() > 240) {
                $pdf->AddPage();
            }

            $score = min(100, max(0, $area->score ?: 0));
            $currentY = $pdf->GetY();

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

            $pdf->setRTL(false);

            // Calculate text width to place bar exactly next to it
            $pdf->SetFont($this->fontBold, '', 12);
            $textW = $pdf->GetStringWidth($area->name) + 2; // +2 for tiny padding

            // Name is right-aligned to the margin
            $nameX = $pageW - $rightMargin - $textW;
            $pdf->SetXY($nameX, $currentY);
            $pdf->setRTL(true);
            $pdf->Cell($textW, $h, $area->name, 0, 0, 'L');
            $pdf->setRTL(false);

            // Bar goes from fixed left margin to the text
            $barX = 30; // Fixed left margin for grey bars
            $barW_full = $nameX - $barX - 2; // -2 for small gap between bar and text

            // Draw grey background bar
            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($barX, $currentY + 1, $barW_full, $h - 2, 'F');

            // Draw colored percentage bar (aligned to the right side of the grey bar)
            $filledW = ($score / 100) * $barW_full;
            $filledX = $barX + $barW_full - $filledW;

            $pdf->SetFillColor($r, $g, $b);
            $pdf->Rect($filledX, $currentY + 1, $filledW, $h - 2, 'F');

            // Place percentage text flush at the LEFT edge of the colored bar
            $pdf->SetFont('helvetica', 'B', 9);
            $pdf->SetXY($filledX + 1, $currentY + 1);

            if ($score >= 70 && $score <= 100) {
                $pdf->SetTextColor(50, 50, 50);
            } else {
                $pdf->SetTextColor(255, 255, 255);
            }

            $pdf->Cell($filledW - 1, $h - 2, $score . '%', 0, 0, 'L');

            $pdf->setRTL(true);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetY($currentY + $h + $spacing);
        }

        // Decorative separator line at left side (matching report style)
        $lineX = 20;
        $pdf->setRTL(false);
        $pdf->SetDrawColor(225, 225, 225);
        $pdf->SetLineWidth(0.4);
        $pdf->Line($lineX, $firstRowY - 2, $lineX, $pdf->GetY() + 2);
        $pdf->SetLineWidth(0.2);
        $pdf->setRTL(true);

        $this->renderLegend($pdf);
    }

    private function renderTechnicalNotes(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(true);

        // Header Title Box
        $pdf->SetFont($this->fontBold, '', 18);
        $pdf->SetFillColor(218, 227, 243);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $title = 'تقرير الملاحظات الفنية';
        $pdf->Cell(0, 14, $title, 1, 1, 'C', true);
        $pdf->Ln(6);

        $pageW = $pdf->getPageWidth();
        $rightMargin = 14;

        foreach ($areas as $area) {
            if ($pdf->GetY() > $pdf->getPageHeight() - 40) {
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
            $textW = $pdf->GetStringWidth($area->name) + 2;

            $nameX = $pageW - $rightMargin - $textW;

            $pdf->SetXY($nameX, $currentY);
            $pdf->setRTL(true);
            $pdf->Cell($textW, $h, $area->name, 0, 0, 'L');
            $pdf->setRTL(false);

            $barX = $nameX - 1 - $barW_full;

            $pdf->SetFillColor(245, 245, 245);
            $pdf->Rect($barX, $currentY + 1, $barW_full, $h, 'F');

            $filledW = ($score / 100) * $barW_full;
            $filledX = $barX + $barW_full - $filledW;

            $pdf->SetFillColor($r, $g, $b);
            $pdf->Rect($filledX, $currentY + 1, $filledW, $h, 'F');

            $pdf->SetFont('helvetica', '', 9);
            $textX = $filledX + 2;
            $pdf->SetXY($textX, $currentY + 1);

            if ($score >= 70 && $score <= 100) {
                $pdf->SetTextColor(50, 50, 50);
            } else {
                $pdf->SetTextColor(255, 255, 255);
            }

            $pdf->Cell(15, $h, $score . '%', 0, 0, 'L');

            $pdf->setRTL(true);
            $pdf->SetTextColor(0, 0, 0);

            $pdf->SetY($currentY + $h + 6);

            $pdf->SetFont($this->fontBold, '', 12);
            $noteItems = $area->notesList();
            if ($noteItems === []) {
                $noteItems = ['تم التحقق من أعمال ' . $area->name . '، وتبين أن جميع الأعمال منفذة بشكل سليم ولا توجد ملاحظات.'];
            }

            $pdf->setCellHeightRatio(1.8);
            foreach ($noteItems as $i => $noteText) {
                $line = ($i + 1) . '- ' . $noteText;
                $pdf->MultiCell(0, 6, $line, 0, 'R', false, 1, '', '', true);
                $pdf->Ln(2);
            }
            $pdf->setCellHeightRatio(1.5);

            $pdf->Ln(5);
        }
    }

    private function renderRecommendations(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(true);
        $pageW = $pdf->getPageWidth();
        $leftMargin = 14;
        $tableW = $pageW - ($leftMargin * 2);
        $colArea = 45;
        $colRecommendation = $tableW - $colArea;
        $headerH = 10;

        // Table Header - Right column = 🔧 الأعمال, Left column = ✅ التوصيات
        $pdf->SetFont($this->fontBold, '', 12);
        $pdf->SetFillColor(129, 199, 132); // Beautiful Green Header (#81C784)
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);

        $recX = $leftMargin;
        $areaX = $leftMargin + $colRecommendation;

        $pdf->setRTL(false);
        $pdf->SetXY($areaX, $pdf->GetY());
        $pdf->Cell($colArea, $headerH, 'الأعمال', 1, 0, 'C', true);
        $pdf->SetXY($recX, $pdf->GetY());
        $pdf->Cell($colRecommendation, $headerH, 'التوصيات', 1, 1, 'C', true);
        $pdf->setRTL(true);

        $drawTableHeader = function () use ($pdf, $areaX, $recX, $colArea, $colRecommendation, $headerH): void {
            $pdf->SetFont($this->fontBold, '', 12);
            $pdf->SetFillColor(129, 199, 132);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.2);
            $pdf->setRTL(false);
            $pdf->SetXY($areaX, $pdf->GetY());
            $pdf->Cell($colArea, $headerH, 'الأعمال', 1, 0, 'C', true);
            $pdf->SetXY($recX, $pdf->GetY());
            $pdf->Cell($colRecommendation, $headerH, 'التوصيات', 1, 1, 'C', true);
            $pdf->setRTL(true);
        };

        $emojiMap = [
            'الموقع العام' => '🚗',
            'مواقف السيارات' => '🚗',
            'واجهات المبنى' => '🏢',
            'واجهة المبنى' => '🏢',
            'التكييف والتهوية' => '❄️',
            'الأسقف' => '🏠',
            'الالمنيوم النوافذ والابواب' => '🚪',
            'الالمنيوم النوافذ والأبواب' => '🚪',
            'الألومنيوم والنوافذ والأبواب' => '🚪',
            'الجدران' => '🧱',
            'الارضيات' => '📐',
            'الأرضيات' => '📐',
            'انظمة الحماية والسلامة' => '🚨',
            'أنظمة الحماية والسلامة' => '🚨',
            'السباكة' => '🚰',
            'الكهرباء' => '⚡',
            'الصرف الصحي' => '🚽',
            'الاعمال الخشبية' => '🪵',
            'الأعمال الخشبية' => '🪵',
            'عازل المياه' => '☔',
        ];

        foreach ($areas as $area) {
            $recItems = $area->recommendationsList();
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

            $nameTrimmed = trim($area->name);
            $areaDisplayName = $nameTrimmed;

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
            $pdf->MultiCell($colRecommendation - 6, 6, $recommendations, 0, 'R', false, 1, '', '', true);
            $pdf->setRTL(false);

            // Draw borders for both columns
            $pdf->Rect($areaX, $startY, $colArea, $rowH, 'D');
            $pdf->Rect($recX, $startY, $colRecommendation, $rowH, 'D');

            $pdf->SetY($startY + $rowH);
        }

        $pdf->SetTextColor(0, 0, 0);
        $pdf->setRTL(true);
    }

    private function renderFinalResult(TCPDF $pdf, PropertyHouse $house, string $reportDate): void
    {
        $pdf->AddPage();
        $pdf->setRTL(true);

        $pdf->SetFont($this->fontBold, '', 14);
        $pdf->SetFillColor(255, 255, 0); // Yellow background
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $titleW = 100;
        $titleX = ($pdf->getPageWidth() - $titleW) / 2;
        $pdf->SetXY($titleX, $pdf->GetY());
        $pdf->Cell($titleW, 10, 'النتيجة النهائية لتقرير الفحص', 1, 1, 'C', true);
        $pdf->Ln(3);

        $totalPct = min(100, max(0, (int) ($house->total_percentage ?? 0)));
        $rating = InspectionScoreLabels::label($totalPct);
        $deliveryDate = ($house->report_delivered_at ?? $house->updated_at ?? now())->format('Y-m-d');

        $pdf->SetFont($this->font, '', 10.5);
        $pdf->setCellHeightRatio(1.3);

        $manualText = trim((string) ($house->final_result_text ?? ''));
        if ($manualText !== '') {
            $formattedText = nl2br(e($manualText));
            // Standardize double spacing between paragraphs and lines to fill space and look readable
            $formattedText = str_replace("<br />\n<br />", "<br/><br/>", $formattedText);
            $formattedText = str_replace("<br />", "<br/><br/>", $formattedText);
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div dir="rtl" style="font-size:10.5pt; text-align:justify; line-height:1.35;">' . $formattedText . '</div>',
                0,
                1,
                false,
                true,
                'R'
            );
        } else {
            $pdf->SetTextColor(120, 120, 120);
            $pdf->MultiCell(0, 6, 'لم يتم إدخال نص النتيجة النهائية بعد.', 0, 'R');
            $pdf->SetTextColor(0, 0, 0);
        }

        $pdf->Ln(2);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="rtl" style="font-size:10.5pt;text-align:justify;line-height:1.35;">'
            . 'وبناءً على التقييم الفني، بلغت نسبة الإنجاز التقديرية للعقار '
            . '<b>' . $totalPct . '%</b>'
            . '، مما يعني أن العقار يعتبر صالحاً للاستخدام بعد استكمال الأعمال المتبقية وتنفيذ التوصيات الفنية الواردة في التقرير، وذلك لضمان أعلى مستويات الجودة والسلامة والجاهزية التشغيلية.'
            . '</div>',
            0,
            1,
            false,
            true,
            'R',
        );

        $generalNotes = trim((string) ($house->final_general_notes ?? ''));
        if ($generalNotes !== '') {
            $pdf->Ln(2);
            $pdf->SetFont($this->fontBold, '', 11);
            $pdf->SetTextColor(192, 0, 0);
            $pdf->Cell(0, 6, 'ملاحظات عامة :-', 0, 1, 'R');
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFont($this->font, '', 10.5);

            $notesLines = [];
            foreach (preg_split("/\r\n|\n|\r/", $generalNotes) as $line) {
                $line = trim($line);
                if ($line === '') {
                    continue;
                }
                if (mb_substr($line, 0, 1) !== '•' && mb_substr($line, 0, 1) !== '-') {
                    $line = '• ' . $line;
                }
                $notesLines[] = '<u>' . e($line) . '</u>';
            }
            $notesHtml = implode('<br/>', $notesLines);
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div dir="rtl" style="font-size:10.5pt; text-align:right; line-height:1.35;">' . $notesHtml . '</div>',
                0,
                1,
                false,
                true,
                'R'
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
            '<div dir="rtl" style="font-size:11pt;text-align:right;">'
            . 'تقييم الفاحص لحالة العقار (<b>' . e($rating) . '</b>)'
            . '</div>',
            0,
            1,
            false,
            true,
            'R',
        );

        $pdf->Ln(2);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="rtl" style="font-size:11pt;text-align:right;color:#c00000;line-height:1.3;">'
            . '• تم تسليم التقرير <b>' . e($deliveryDate) . '</b>'
            . '</div>',
            0,
            1,
            false,
            true,
            'R',
        );

        $pdf->Ln(2);
        $pdf->SetFont($this->font, '', 10.5);
        $pdf->writeHTMLCell(
            0,
            0,
            '',
            '',
            '<div dir="rtl" style="font-size:10.5pt;text-align:right;line-height:1.3;">'
            . '<span style="text-decoration:underline; font-weight:bold;">يرجى الاطلاع على المرفقات</span><br/>'
            . '1-تقرير الكهرباء .<br/>'
            . '2-الصور .'
            . '</div>',
            0,
            1,
            false,
            true,
            'R',
        );

        $signaturePath = $this->resolveSignaturePath();
        if ($signaturePath) {
            $pageW = $pdf->getPageWidth();
            $pageH = $pdf->getPageHeight();
            $sigW = 36;
            $sigH = 22;
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
        $x = $pdf->getPageWidth() - ($w * 2) - 14; // Position on the right

        $pdf->SetX($x);
        $pdf->SetFont($this->fontBold, '', 9);
        $pdf->SetFillColor(200, 210, 235);
        $pdf->Cell($w, $h, 'اللون', 1, 0, 'C', true);
        $pdf->Cell($w, $h, 'الوصف', 1, 1, 'C', true);

        $legend = [
            ['أخضر', 'ممتاز', [0, 200, 83]],
            ['أصفر', 'جيد', [255, 255, 0]],
            ['برتقالي', 'متوسط', [215, 120, 20]],
            ['أحمر', 'ضعيف', [247, 0, 0]],
        ];

        foreach ($legend as $item) {
            $pdf->SetX($x);
            $pdf->SetFillColor($item[2][0], $item[2][1], $item[2][2]);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->Cell($w, $h, $item[0], 1, 0, 'C', true);
            $pdf->Cell($w, $h, $item[1], 1, 1, 'C', true);
        }
        $pdf->SetTextColor(0, 0, 0);
    }

    private function renderPropertyDataTable(TCPDF $pdf, PropertyHouse $house): void
    {
        $data = [
            ['النشاط بحسب رخصة البناء', $house->activity ?: '---'],
            ['نوع العقار', $house->property_type ?: '---'],
            ['حالة المبنى', $house->building_status ?: '---'],
            ['رقم الوثيقة', $house->document_number ?: '---'],
            ['رقم المقدمة', $house->intro_number ?: '---'],
            ['فيلا', $house->villa_number ?: '---'],
            ['الطريق', $house->road ?: '---'],
            ['المجمع', $house->compound ?: '---'],
            ['المنطقة', $house->area ?: '---'],
            ['المشتري', $house->buyer_name ?: '---'],
            ['رقم الهوية', $house->id_number ?: '---'],
            ['اسم المطور العقاري', $house->developer_name ?: '---'],
            ['المشرف الهندسي', $house->engineering_supervisor ?: '---'],
            ['المقاول الرئيسي', $house->main_contractor ?: '---'],
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
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'R', true);
            $pdf->SetFont($this->font, '', 10.5);
            $pdf->Cell($wValue, $h, $row[1], 1, 1, 'R', true);
        }
    }

    private function renderPropertySpecsTable(TCPDF $pdf, PropertyHouse $house): void
    {
        $data = [
            ['عمر العقار التقريبي', $house->property_age ?: '---'],
            ['مساحة الأرض التقريبية', $house->land_area ?: '---'],
            ['مساحة البناء التقريبية', $house->building_area ?: '---'],
            ['عدد الطوابق', $house->floors_count ?: '---'],
            ['عدد الغرف', $house->rooms_count ?: '---'],
            ['عدد دورات المياه', $house->bathrooms_count ?: '---'],
            ['عدد الصالات', $house->halls_count ?: '---'],
            ['عدد مواقف السيارات', $house->parking_count ?: '---'],
            ['عدد المطابخ', $house->kitchens_count ?: '---'],
        ];

        $wLabel = 56;
        $wValue = 78;
        $tableW = $wLabel + $wValue;
        $tableX = ($pdf->getPageWidth() - $tableW) / 2;
        $h = 7;

        foreach ($data as $row) {
            $pdf->SetX($tableX);
            $pdf->SetFont($this->fontBold, '', 10.5);
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'R', true);
            $pdf->SetFont($this->font, '', 10.5);
            $pdf->Cell($wValue, $h, $row[1], 1, 1, 'R', true);
        }
    }

    private function resolveLogoPath(): ?string
    {
        $candidates = [env('REPORT_LOGO_PATH'), 'images/logo.png', 'images/company-logo.png'];
        foreach ($candidates as $candidate) {
            if (!$candidate)
                continue;
            $path = public_path($candidate);
            if (is_file($path))
                return $path;
        }
        return null;
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

class ReportTCPDF extends TCPDF
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
                    $this->Image($this->logoPath, 14, 8, 30, 20, '', '', '', false, 300);
                } catch (Throwable $e) {
                }
            }

            // Request details on the top-right
            $this->SetFont($this->reportFontBold, '', 10);
            $this->SetTextColor(192, 0, 0); // Maroon red
            $this->SetXY(135, 11);
            $this->Cell(60, 5, 'رقم الطلب ' . $this->reportNo, 0, 0, 'R');

            $this->SetFont($this->reportFont, '', 9);
            $this->SetXY(135, 17);
            $this->Cell(60, 5, 'تاريخ الفحص ' . $this->reportDate, 0, 0, 'R');
        }

        $this->setRTL(true);
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
            $this->Cell($rightW, 3.6, 'جي إي إس للتقييم والتثمين العقاري', 0, 2, 'R');
            $this->SetFont('helvetica', '', 7);
            $this->Cell($rightW, 3.0, 'C.R. 160528-1', 0, 0, 'R');

            $this->setRTL(true);
            $this->SetTextColor(0, 0, 0);
        }
    }
}