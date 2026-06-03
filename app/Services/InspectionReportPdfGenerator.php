<?php

namespace App\Services;

use App\Models\InspectionArea;
use App\Models\PropertyHouse;
use TCPDF;
use Throwable;

class InspectionReportPdfGenerator
{
    public function renderBinary(PropertyHouse $house): string
    {
        $prev = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $house->load([
                'inspectionAreas' => fn ($q) => $q->orderBy('sort_order')->orderBy('id'),
            ]);

            $reportNo = $house->reference_code ?: ('H-' . $house->id);
            $reportDate = ($house->created_at ?: now())->format('Y-m-d');
            $logoPath = $this->resolveLogoPath();

            $pdf = new ReportTCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->reportNo = $reportNo;
            $pdf->reportDate = $reportDate;
            $pdf->logoPath = $logoPath;

            $pdf->SetCreator(config('app.name'));
            $pdf->SetAuthor(config('app.name'));
            $pdf->SetTitle('تقرير معاينة — ' . $house->title);

            $pdf->setPrintHeader(true);
            $pdf->setPrintFooter(true);

            $pdf->SetMargins(14, 34, 14);
            $pdf->SetHeaderMargin(8);
            $pdf->SetFooterMargin(8);
            $pdf->SetAutoPageBreak(true, 24);

            $pdf->setRTL(true);
            $pdf->SetFont('aealarabiya', '', 12);

            // --- Cover ---
            $pdf->AddPage();
            $this->renderCoverPage($pdf, $house, $reportNo, $reportDate, $logoPath);

            // --- Fixed introduction pages ---
            $this->renderFixedPages($pdf);

            // --- Property Data & Specifications ---
            $pdf->AddPage();

            $pdf->SetFont('aealarabiya', 'B', 15);
            $pdf->Cell(0, 8, 'بيانات العقار :-', 0, 1, 'R');

            $pdf->SetFont('aealarabiya', '', 11);
            $this->renderPropertyDataTable($pdf, $house);

            $pdf->Ln(10);
            $pdf->SetFont('aealarabiya', 'B', 16);
            $pdf->Cell(0, 10, 'مواصفات العقار :-', 0, 1, 'R');

            $pdf->SetFont('aealarabiya', '', 11);
            $this->renderPropertySpecsTable($pdf, $house);

            // --- Percentages ---
            $pdf->AddPage();

            // 1. Total Percentage (Donut)
            $pdf->SetFont('aealarabiya', 'B', 16);
            $pdf->Cell(0, 10, 'النسبة الإجمالية', 0, 1, 'C');
            $pdf->Ln(1);
            $this->renderTotalDonut($pdf, $house->total_percentage);

            // 2. Sub-Percentages (Bars)
            $pdf->Ln(5);
            $pdf->SetFont('aealarabiya', 'B', 16);
            $pdf->Cell(0, 10, 'النسب الفرعية', 0, 1, 'C');
            $pdf->Ln(3);
            $this->renderSubPercentageBars($pdf, $house->inspectionAreas);

            // --- Technical Notes ---
            $pdf->AddPage();
            $this->renderTechnicalNotes($pdf, $house->inspectionAreas);

            // --- Recommendations ---
            $pdf->AddPage();
            $this->renderRecommendations($pdf, $house->inspectionAreas);

            return $pdf->Output('inspection-' . $house->id . '.pdf', 'S');
        } finally {
            ini_set('memory_limit', (string) $prev);
        }
    }

    private function renderCoverPage(
        TCPDF $pdf,
        PropertyHouse $house,
        string $reportNo,
        string $reportDate,
        ?string $logoPath
    ): void {
        $pageW = $pdf->getPageWidth();
        $pageH = $pdf->getPageHeight();
        $pdf->setRTL(false);

        // ── Decorative brown lines (top-right) ─────────────────────────
        $lineX = $pageW - 8;
        $lineStartY = 38;
        $pdf->SetDrawColor(139, 115, 85); // Brown
        $pdf->SetLineWidth(1.2);
        $widths = [18, 22, 26, 30, 30, 30, 30, 26, 22, 18, 12];
        foreach ($widths as $i => $w) {
            $y = $lineStartY + ($i * 4);
            $pdf->Line($lineX, $y, $lineX - $w, $y);
        }

        // ── Decorative dots (bottom-left) ──────────────────────────────
        $dotStartX = 14;
        $dotStartY = $pageH - 95;
        $dotR = 1.5;
        // Brown dots (top group)
        $pdf->SetFillColor(139, 115, 85);
        for ($row = 0; $row < 4; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $pdf->Circle($dotStartX + $col * 6, $dotStartY + $row * 6, $dotR, 0, 360, 'F');
            }
        }
        // Maroon dots (bottom group)
        $pdf->SetFillColor(128, 0, 0);
        $dotStartY2 = $dotStartY + 28;
        for ($row = 0; $row < 6; $row++) {
            for ($col = 0; $col < 3; $col++) {
                $cx = $dotStartX + $col * 6;
                $cy = $dotStartY2 + $row * 6;
                // Dense cluster: 4 small dots per cell
                $sr = 1.0;
                $pdf->Circle($cx - 1, $cy - 1, $sr, 0, 360, 'F');
                $pdf->Circle($cx + 1, $cy - 1, $sr, 0, 360, 'F');
                $pdf->Circle($cx - 1, $cy + 1, $sr, 0, 360, 'F');
                $pdf->Circle($cx + 1, $cy + 1, $sr, 0, 360, 'F');
            }
        }

        // ── Logo centered ──────────────────────────────────────────────
        $logoW = 50;
        $logoH = 35;
        $logoX = ($pageW - $logoW) / 2;
        $logoY = 50;

        if ($logoPath && is_readable($logoPath)) {
            try {
                $pdf->Image($logoPath, $logoX, $logoY, $logoW, $logoH, '', '', '', false, 300, '', false, false, 0, false, false, false);
            } catch (Throwable) {
            }
        }

        // ── Company name ───────────────────────────────────────────────
        $pdf->SetY($logoY + $logoH + 2);
        $pdf->SetTextColor(192, 0, 0);
        $pdf->SetFont('aealarabiya', 'B', 22);
        $pdf->Cell(0, 10, 'GIS VALUATION AND EVALUATION', 0, 1, 'C');

        $pdf->SetFont('aealarabiya', 'B', 16);
        $pdf->SetTextColor(80, 80, 80);
        $pdf->Cell(0, 10, 'جي إي إس للتقييم والتثمين العقاري', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);

        // ── Info table ─────────────────────────────────────────────────
        $clientName = trim((string) ($house->buyer_name ?? ''));
        if ($clientName === '')
            $clientName = '---';

        $propertyAddress = collect([
            $house->villa_number ? 'فيلا ' . $house->villa_number : null,
            $house->road ? 'طريق ' . $house->road : null,
            $house->compound ? 'مجمع ' . $house->compound : null,
            $house->area ?: null,
        ])->filter()->implode('  ') ?: ($house->address ?: $house->title);

        $infoRows = [
            ['رقم التقرير', 'Report No.', $reportNo, true],
            ['التاريخ', 'Date', $reportDate, false],
            ['اسم العميل', 'Client Name', $clientName, false],
            ['عنوان المنزل', 'Property Address', $propertyAddress, false],
        ];

        $tableW = 160;
        $tableX = ($pageW - $tableW) / 2;
        $wAr = 40;
        $wEn = 40;
        $wVal = $tableW - $wAr - $wEn;
        $rowH = 14;

        $pdf->SetY($pdf->GetY() + 15);

        foreach ($infoRows as [$arLabel, $enLabel, $value, $isHighlight]) {
            $y = $pdf->GetY();

            // Arabic Label (Right)
            $pdf->SetFont('aealarabiya', 'B', 13);
            $pdf->SetTextColor(128, 0, 0);
            $pdf->SetXY($tableX + $tableW - $wAr, $y);
            $pdf->Cell($wAr, $rowH, $arLabel, 0, 0, 'C', false);

            // Value (Middle)
            $pdf->SetFont('aealarabiya', 'B', 12);
            if ($isHighlight) {
                $pdf->SetTextColor(192, 0, 0);
            } else {
                $pdf->SetTextColor(30, 30, 30);
            }
            $pdf->SetXY($tableX + $wEn, $y);
            $pdf->Cell($wVal, $rowH, $value, 0, 0, 'C', false);

            // English Label (Left)
            $pdf->SetFont('aealarabiya', 'B', 11);
            $pdf->SetTextColor(60, 60, 60);
            $pdf->SetXY($tableX, $y);
            $pdf->Cell($wEn, $rowH, $enLabel, 0, 0, 'C', false);

            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetY($y + $rowH + 4);
        }

        // ── Footer contact ─────────────────────────────────────────────
        $pdf->SetY($pageH - 45);
        $pdf->SetFont('aealarabiya', '', 11);
        $pdf->SetTextColor(150, 150, 150);
        $pdf->Cell(0, 6, 'infogisulf@gmail.com  |  36689895  |  gis.bahrain', 0, 1, 'C');
        $pdf->SetTextColor(0, 0, 0);
        $pdf->setRTL(true);
    }

    private function renderFixedPages(TCPDF $pdf): void
    {
        $pdf->setCellHeightRatio(1.58);

        foreach ($this->getFixedPagesContent() as $html) {
            $pdf->AddPage();
            $pdf->SetFont('aealarabiya', '', 11);
            $pdf->writeHTMLCell(
                0,
                0,
                '',
                '',
                '<div style="font-size:12px;">' . $html . '</div>',
                0,
                1,
                false,
                true,
                'R',
                true
            );
        }

        $pdf->setCellHeightRatio(1.5);
    }

    private function getFixedPagesContent(): array
    {
        return [
            <<<'HTML'
<div style="text-align:right; font-family: aealarabiya;">
    <h1 style="color:#c00000; font-size:16pt; text-align:center;">شركة GIS لفحص تقييم المباني</h1>
    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline; text-align:center;">نبذة تعريفية – مقدمة الشركة وخدماتها</h2>

    <p style="font-size:11pt; font-weight:bold; color:#000;">من نحن ؟</p>
    <p style="font-size:10pt; color:#222;">نحن في شركة <strong>GIS</strong> نؤمن بأن سلامة العقار تبدأ من القرار الصحيح، ولهذا نقدم خدمة فحص شاملة للمباني الجاهزة قبل الشراء أو السكن، وذلك للتأكد من خلوها من العيوب الفنية الظاهرة أو المخاطر الخفية.</p>
    <p style="font-size:10pt; color:#222;">نعمل من خلال خبراء مختصين، ونعتمد على أجهزة وتقنيات حديثة لضمان تقديم تقارير دقيقة وموثوقة تساعد العميل على اتخاذ قرار استثماري أو سكني آمن.</p>
    <p style="font-size:10pt; color:#222;">نعد من أوائل الشركات المتخصصة في فحص العقارات في البحرين، ونفتخر بسجل حافل من الإنجازات وخدمة مئات العملاء من الأفراد والشركات والمطورين.</p>

    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline;">خدماتنا الرئيسية</h2>
    <p style="font-size:11pt; font-weight:bold; color:#c00000;">تقدم شركة GIS خدمات فحص المباني الجاهزة عبر تقييم شامل للأنظمة التالية:</p>
    <table cellpadding="2">
        <tr><td style="font-size:11pt; line-height:25px"><strong>1- الكهرباء:</strong> فحص جودة وسلامة الأفياش، المفاتيح، الطبلون، الأحمال، التأريض.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>2- السباكة:</strong> فحص جودة المواسير، الصفايات، ضغط المياه، الخزانات، السخانات، ملوحة الماء.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>3- الرطوبة والتسريب:</strong> قياس الرطوبة، كشف التسريبات، فحص العزل المائي.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>4- الخزانات:</strong> التأكد من سلامتها من التسريب الحراري أو المائي لتفادي التلوث أو تلف الأسقف.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>5- الأبواب والنوافذ:</strong> التحقق من جودة الفتح والإغلاق، المفصلات، الإطارات، العزل، الديكورات.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>6- الأسقف والجدران:</strong> فحص الشقوق، آثار الرطوبة، جودة التشطيب، الزوايا، العزل الحراري.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>7- السيراميك والأرضيات:</strong> فحص الترويب، الاستواء، الثبات، جودة التركيب.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>8- أنظمة الحماية والسلامة:</strong> التأكد من جاهزية كاميرات المراقبة، كواشف الدخان، وحساسات الإنذار.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>9- التهوية والتكييف:</strong> فحص وحدات التكييف، فتحات الهواء، مراوح الشفط، جودة التأسيس.</td></tr>
        <tr><td style="font-size:11pt; line-height:25px"><strong>10- مواقف السيارات والواجهات:</strong> فحص الأرضية، المظلات، باب الكراج، التشققات، فواصل التمدد.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:right; font-family: aealarabiya;">
    <h1 style="color:#c00000; font-size:14pt; text-decoration:underline;">ما يميزنا</h1>
    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline;">ضمانات GIS:</h2>

    <p style="font-size:11pt;">• <strong>ضمان الإحاطة:</strong> يشمل شمولية ودقة البيانات وفق معايير الشركة.</p>
    <p style="font-size:10pt; color:#006400; text-decoration:underline;">- شرح ضمان الإحاطة</p>
    <p style="font-size:10pt; color:#222;">تقديم تقرير شامل قدر الإمكان عن حالة العقار الظاهرة، بناءً على المعايير الفنية المتبعة داخل الشركة. لا يُعد هذا ضمانًا قانونيًا أو فنيًا لحالة العقار المستقبلية أو للعيوب المخفية.</p>

    <p style="font-size:11pt;">• <strong>ضمان السلامة:</strong> التأكد من خلو البنود المفحوصة من العيوب الخطرة، والرجوع لشهادات الضمان إن وجدت.</p>
    <p style="font-size:10pt; color:#006400; text-decoration:underline;">- شرح ضمان السلامة</p>
    <p style="font-size:10pt; color:#222;">يُقصد به التأكد من عدم وجود عيوب خطرة في العناصر المفحوصة وقت الفحص، وفق المشاهدات الظاهرة. في حال توفر شهادات ضمان للعناصر مثل الكهرباء أو السباكة أو التكييف، يتم الرجوع إليها دون أن تتحمل الشركة مسؤولية صلاحيتها.</p>

    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline;">امتيازاتنا:</h2>
    <table cellpadding="2">
        <tr><td style="font-size:10pt; line-height:25px">• سرعة في الاستجابة وإنجاز الفحص.</td></tr>
        <tr><td style="font-size:10pt; line-height:25px">• سهولة في تقديم الطلب واستلام التقرير.</td></tr>
        <tr><td style="font-size:10pt; line-height:25px">• توثيق مصور لجميع الملاحظات والعيوب.</td></tr>
        <tr><td style="font-size:10pt; line-height:25px">• تقارير احترافية سهلة القراءة وغنية بالتفاصيل.</td></tr>
        <tr><td style="font-size:10pt; line-height:25px">• أرشفة بيانات كل وحدة عقارية بشكل مستقل ومنظم.</td></tr>
    </table>

    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline;">آلية الفحص والتسليم</h2>
    <table cellpadding="2">
        <tr><td style="font-size:10pt;">• تستغرق عملية الفحص من ساعتين إلى يومين حسب مساحة العقار وتعقيد أنظمته.</td></tr>
        <tr><td style="font-size:10pt;">• يتم تسليم التقرير النهائي خلال 5 أيام عمل بعد مراجعته من قسم الجودة.</td></tr>
        <tr><td style="font-size:10pt;">• في حال وجود ملاحظات من العميل، يجب إرسالها خلال مدة أقصاها 3 أيام من استلام التقرير لإجراء التعديلات المطلوبة، إن وجدت.</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:right; font-family: aealarabiya;">
    <h1 style="color:#c00000; font-size:14pt; text-decoration:underline;">المستندات الاختيارية التي تُحسن جودة تقرير الفحص العقاري :-</h1>

    <p style="font-size:10pt; color:#222;">تساهم المستندات التالية في رفع دقة وجودة التقييم الفني للعقار، ويُفضل إرفاق ما يتوفر منها مع طلب الفحص:</p>

    <p style="font-size:11pt; font-weight:bold;">أولاً: مستندات هندسية وفنية</p>
    <table cellpadding="1">
        <tr><td style="font-size:10pt;">• رخصة البناء</td></tr>
        <tr><td style="font-size:10pt;">• المخططات الهندسية المعتمدة (معمارية، إنشائية، ميكانيكية، كهربائية)</td></tr>
        <tr><td style="font-size:10pt;">• شهادة ضمان الهيكل الإنشائي (عادة لمدة 10 سنوات)</td></tr>
        <tr><td style="font-size:10pt;">• تقارير فحص التربة</td></tr>
        <tr><td style="font-size:10pt;">• شهادات ضمان المواد والأنظمة مثل:</td></tr>
        <tr><td style="font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;• ضمان السباكة</td></tr>
        <tr><td style="font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;• ضمان الكهرباء</td></tr>
        <tr><td style="font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;• ضمان أنظمة التكييف</td></tr>
        <tr><td style="font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;• ضمان العازل المائي</td></tr>
        <tr><td style="font-size:10pt;">&nbsp;&nbsp;&nbsp;&nbsp;• ضمان الألمنيوم والزجاج</td></tr>
    </table>

    <p style="font-size:11pt; font-weight:bold;">ثانياً: معلومات عامة وموقع العقار</p>
    <table cellpadding="1">
        <tr><td style="font-size:10pt;">• صورة من خرائط العقار المعتمدة من المكتب الهندسي</td></tr>
        <tr><td style="font-size:10pt;">• موقع العقار (رابط مباشر أو صورة من خرائط Google أو تطبيق مشابه)</td></tr>
        <tr><td style="font-size:10pt;">• شهادة العنوان (تشمل رقم المنزل، اسم الشارع، رقم الطريق، ورقم المجمع)</td></tr>
    </table>

    <p style="font-size:11pt; font-weight:bold;">ثالثاً: مستندات إضافية داعمة لتقييم العقار</p>
    <table cellpadding="1">
        <tr><td style="font-size:10pt;">• شهادة ضغط الأرض (إن وُجدت)</td></tr>
        <tr><td style="font-size:10pt;">• عمر العقار التقريبي (سنة الإنشاء أو التسليم)</td></tr>
        <tr><td style="font-size:10pt;">• صورة البطاقة الذكية للمشتري أو طالب خدمة الفحص</td></tr>
        <tr><td style="font-size:10pt;">• عقد المقاول العام (اختياري)</td></tr>
        <tr><td style="font-size:10pt;">• ضمانات المواد المستخدمة في البناء (اختياري)</td></tr>
    </table>
</div>
HTML,
            <<<'HTML'
<div style="text-align:right; font-family: aealarabiya;">
    <h1 style="color:#c00000; font-size:14pt; text-decoration:underline;">تنويه هام – حدود المسؤولية</h1>

    <p style="font-size:10pt; color:#222;">تلتزم شركة <strong>GIS</strong> بتقديم خدماتها بأقصى درجات الدقة والحيادية، باستخدام أدوات تقنية ومن خلال خبراء مختصين. مع ذلك، لا تتحمل الشركة أي مسؤولية قانونية أو مادية عن الأضرار الناتجة عن عملية الفحص، سواء أثناء المعاينة أو بعدها.</p>
    <p style="font-size:10pt; color:#222;">كما لا تتحمل الشركة أي التزامات ناتجة عن نزاعات بين العميل وأطراف أخرى مثل المقاول أو المطور، إذ تقتصر مسؤوليتنا على التشخيص والتوثيق فقط.</p>

    <h2 style="color:#c00000; font-size:12pt;">🔴 <span style="text-decoration:underline;">تنويه هام:</span></h2>
    <p style="font-size:10pt; color:#222;">تقتصر مهمة شركة GIS على فحص وتوثيق الحالة الظاهرة للعقار وقت المعاينة فقط. ولا يُعد التقرير الصادر عنها التزامًا بضمان مستقبلي لحالة العقار أو لأي تطورات قد تطرأ لاحقًا. تنتهي مسؤولية الشركة بشكل كامل فور انتهاء الفحص ومغادرة الفريق للموقع، ما لم يتم الاتفاق المسبق على خدمة متابعة أو فحص إضافي.</p>

    <h2 style="color:#c00000; font-size:12pt; text-decoration:underline;">الأسئلة الشائعة</h2>

    <p style="font-size:11pt; color:#c00000; font-weight:bold;">هل يمكن استرداد المبلغ؟</p>
    <p style="font-size:10pt; color:#222;">نعم، مع تطبيق رسوم إدارية في حال تم الإلغاء قبل 24 ساعة من موعد الفحص.</p>

    <p style="font-size:11pt; color:#c00000; font-weight:bold;">هل يمكن تعديل التقرير؟</p>
    <p style="font-size:10pt; color:#222;">نعم، يتم قبول الملاحظات خلال 3 أيام فقط من استلام التقرير.</p>

    <p style="font-size:11pt; color:#c00000; font-weight:bold;">هل تقدمون خدمات صيانة؟</p>
    <p style="font-size:10pt; color:#222;">لا، نحن جهة فحص فقط لضمان الحيادية والاستقلالية.</p>

    <p style="font-size:11pt; color:#c00000; font-weight:bold;">كيف يتم تعيين الفاحص؟</p>
    <p style="font-size:10pt; color:#222;">نقوم بترشيح فاحص متخصص بناءً على نوع العقار وتوزيع العمل، ويقوم برفع التقرير ضمن نموذج GIS المعتمد.</p>

    <p style="font-size:11pt; color:#c00000; font-weight:bold;">لماذا الفحص مهم؟</p>
    <p style="font-size:10pt; color:#222;">لأنه يوفر عليك آلاف الدنانير ويكشف عيوب قد تكون خفية قبل الشراء أو الاستلام.</p>
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
        $pdf->SetFont('aealarabiya', 'B', 24);
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
                $r = 139;
                $g = 69;
                $b = 19; // Brown
            } else {
                $r = 247;
                $g = 0;
                $b = 0; // Red
            }

            $pdf->setRTL(false);

            // Calculate text width to place bar exactly next to it
            $pdf->SetFont('aealarabiya', 'B', 12);
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
        $pdf->SetFont('aealarabiya', 'B', 18);
        $pdf->SetFillColor(218, 227, 243);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $title = 'تقرير الملاحظات الفنية';
        $pdf->Cell(0, 14, $title, 1, 1, 'C', true);
        $pdf->Ln(10);

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
                $r = 139;
                $g = 69;
                $b = 19; // Brown
            } else {
                $r = 247;
                $g = 0;
                $b = 0; // Red
            }

            $h = 6;
            $barW_full = 100;

            $pdf->setRTL(false);
            $pdf->SetFont('aealarabiya', 'B', 12);
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

            $pdf->SetFont('aealarabiya', 'B', 12);
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

            $pdf->Ln(8);
        }
    }

    private function renderRecommendations(TCPDF $pdf, $areas): void
    {
        $pdf->setRTL(true);

        // Header Title Box
        $pdf->SetFont('aealarabiya', 'B', 18);
        $pdf->SetFillColor(218, 227, 243);
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.3);

        $title = 'التوصيات';
        $pdf->Cell(0, 14, $title, 1, 1, 'C', true);
        $pdf->Ln(6);

        $pdf->setRTL(false); // Switch to manual positioning

        $pageW = $pdf->getPageWidth();
        $leftMargin = 14;
        $tableW = $pageW - ($leftMargin * 2);
        $colArea = 45;
        $colRecommendation = $tableW - $colArea;
        $headerH = 10;

        // Table Header - Right column = الأعمال, Left column = التوصيات
        $pdf->SetFont('aealarabiya', 'B', 12);
        $pdf->SetFillColor(130, 210, 100); // Green Header matching image
        $pdf->SetDrawColor(0, 0, 0);
        $pdf->SetLineWidth(0.2);

        $recX = $leftMargin;
        $areaX = $leftMargin + $colRecommendation;

        $pdf->SetXY($areaX, $pdf->GetY());
        $pdf->Cell($colArea, $headerH, 'الأعمال', 1, 0, 'C', true);
        $pdf->SetXY($recX, $pdf->GetY());
        $pdf->Cell($colRecommendation, $headerH, 'التوصيات', 1, 1, 'C', true);

        $drawTableHeader = function () use ($pdf, $areaX, $recX, $colArea, $colRecommendation, $headerH): void {
            $pdf->SetFont('aealarabiya', 'B', 12);
            $pdf->SetFillColor(130, 210, 100);
            $pdf->SetDrawColor(0, 0, 0);
            $pdf->SetLineWidth(0.2);
            $pdf->SetXY($areaX, $pdf->GetY());
            $pdf->Cell($colArea, $headerH, 'الأعمال', 1, 0, 'C', true);
            $pdf->SetXY($recX, $pdf->GetY());
            $pdf->Cell($colRecommendation, $headerH, 'التوصيات', 1, 1, 'C', true);
        };

        foreach ($areas as $area) {
            $recItems = $area->recommendationsList();
            if ($recItems === []) {
                continue;
            }

            $recommendations = implode("\n", array_map(
                fn (int $i, string $text) => ($i + 1) . '- ' . $text,
                array_keys($recItems),
                $recItems,
            ));

            $score = min(100, max(0, $area->score ?: 0));

            // Determine color
            if ($score >= 80) {
                $r = 0;
                $g = 200;
                $b = 83;
            } elseif ($score >= 70) {
                $r = 255;
                $g = 255;
                $b = 0;
            } elseif ($score >= 60) {
                $r = 139;
                $g = 69;
                $b = 19;
            } else {
                $r = 247;
                $g = 0;
                $b = 0;
            }

            // Calculate the height needed for the recommendations text
            $pdf->SetFont('aealarabiya', '', 11);
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
            $pdf->SetFont('aealarabiya', 'B', 12);
            $pdf->Rect($areaX, $startY, $colArea, $rowH, 'DF');
            $pdf->setRTL(false);
            $pdf->SetXY($areaX, $startY + ($rowH / 2) - 4);
            $pdf->Cell($colArea, 8, (string) $area->name, 0, 0, 'C');

            // Left column: Recommendations with white background
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetFillColor(255, 255, 255);
            $pdf->Rect($recX, $startY, $colRecommendation, $rowH, 'DF');

            // Keep manual coordinates stable so text stays inside recommendations column
            $pdf->setRTL(false);
            $pdf->SetFont('aealarabiya', '', 11);
            $pdf->SetXY($recX + 2, $startY + 2);
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

    private function renderLegend(TCPDF $pdf): void
    {
        // Position at the bottom of the current page
        $pdf->SetY($pdf->getPageHeight() - 55, false);

        $w = 20;
        $h = 5;
        $x = $pdf->getPageWidth() - ($w * 2) - 14; // Position on the right

        $pdf->SetX($x);
        $pdf->SetFont('aealarabiya', 'B', 9);
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

        $wLabel = 80;
        $wValue = 100;
        $h = 8.5;

        foreach ($data as $row) {
            $pdf->SetFont('aealarabiya', 'B', 12);
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'R', true);
            $pdf->SetFont('aealarabiya', '', 12);
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

        $wLabel = 80;
        $wValue = 100;
        $h = 8.5;

        foreach ($data as $row) {
            $pdf->SetFont('aealarabiya', 'B', 12);
            $pdf->Cell($wLabel, $h, $row[0], 1, 0, 'R', true);
            $pdf->SetFont('aealarabiya', '', 12);
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
}

class ReportTCPDF extends TCPDF
{
    public string $reportNo = '';
    public string $reportDate = '';
    public ?string $logoPath = null;

    public function Header()
    {
        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();
        $mX = 8.0;
        $mY = 8.0;
        $cW = $pageW - 16.0;
        $cH = $pageH - 16.0;

        $this->SetFillColor(255, 255, 255);
        $this->SetDrawColor(200, 200, 200);
        $this->SetLineWidth(0.3);
        $this->Rect($mX, $mY, $cW, $cH, 'D');

        $this->setRTL(false);

        $headerH = 20.0;
        $this->SetFillColor(248, 248, 248);
        $this->Rect($mX, $mY, $cW, $headerH, 'F');

        $this->SetDrawColor(192, 0, 0);
        $this->SetLineWidth(0.8);
        $this->Line($mX, $mY, $mX + $cW, $mY);
        $this->SetLineWidth(0.2);
        $this->SetDrawColor(200, 200, 200);
        $this->Line($mX, $mY + $headerH, $mX + $cW, $mY + $headerH);

        $logoW = 25.0;
        $logoH = 16.0;
        if ($this->logoPath && is_readable($this->logoPath)) {
            try {
                $this->Image($this->logoPath, $mX + 4, $mY + 2, $logoW, $logoH, '', '', '', false, 300);
            } catch (Throwable) {
            }
        }

        $this->SetFont('aealarabiya', 'B', 10);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + $cW - 75, $mY + 4);
        $this->Cell(70, 5, 'رقم الطلب ' . $this->reportNo, 0, 0, 'R');
        $this->SetFont('aealarabiya', '', 9);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + $cW - 75, $mY + 10);
        $this->Cell(70, 5, 'تاريخ الفحص ' . $this->reportDate, 0, 0, 'R');

        $this->setRTL(true);
        $this->SetTextColor(0, 0, 0);
    }

    public function Footer()
    {
        $pageW = $this->getPageWidth();
        $pageH = $this->getPageHeight();
        $mX = 8.0;
        $mY = 8.0;
        $cW = $pageW - 16.0;
        $cH = $pageH - 16.0;

        $this->setRTL(false);

        $footerY = $mY + $cH - 12;
        $this->SetFillColor(248, 248, 248);
        $this->Rect($mX, $footerY, $cW, 12, 'F');
        $this->SetDrawColor(200, 200, 200);
        $this->Line($mX, $footerY, $mX + $cW, $footerY);

        $this->SetDrawColor(192, 0, 0);
        $this->SetLineWidth(0.8);
        $this->Line($mX, $mY + $cH, $mX + $cW, $mY + $cH);
        $this->SetLineWidth(0.2);


        $this->SetFont('aealarabiya', 'B', 9);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + 4, $footerY + 3.5);
        $this->Cell(100, 5, '36698895   |   infogisgulf@gmail.com   |   gis.Bahrain', 0, 0, 'L');

        $this->SetFont('aealarabiya', 'B', 8);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + $cW - 80, $footerY + 1);
        $this->Cell(76, 4, 'GIS VALUATION AND EVALUATION', 0, 0, 'R');
        $this->SetFont('aealarabiya', 'B', 8);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + $cW - 80, $footerY + 4.5);
        $this->Cell(76, 4, 'جي إي إس للتقييم والتثمين العقاري', 0, 0, 'R');
        $this->SetFont('helvetica', '', 7);
        $this->SetXY($mX + $cW - 80, $footerY + 8);
        $this->Cell(76, 3, 'C.R. 160528-1', 0, 0, 'R');

        $this->SetFont('aealarabiya', 'B', 11);
        $this->SetTextColor(192, 0, 0);
        $this->SetXY($mX + ($cW / 2) - 10, $footerY + 3);
        $this->Cell(20, 5, $this->PageNo(), 0, 0, 'C');

        $this->setRTL(true);
        $this->SetTextColor(0, 0, 0);
    }
}