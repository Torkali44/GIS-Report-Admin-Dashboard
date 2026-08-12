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

class InspectionReportWordGenerator
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

        // ─── Default Document Styles ───────────────────────────────────────────
        $phpWord->setDefaultFontName('Arial');
        $phpWord->setDefaultFontSize(11);

        $lang = new Language('ar-SA', null, 'ar-SA');
        $phpWord->getSettings()->setThemeFontLang($lang);

        // Set default 'Normal' paragraph style to RTL and Right Aligned
        $phpWord->addParagraphStyle('Normal', [
            'alignment'     => Jc::RIGHT,
            'bidi'          => true,
        ]);
        $phpWord->setDefaultParagraphStyle([
            'alignment'     => Jc::RIGHT,
            'bidi'          => true,
        ]);

        // Section with Different First Page Header/Footer (Cover page has no header/footer)
        $section = $phpWord->addSection([
            'marginTop' => 1134, // ~2cm
            'marginBottom' => 1134,
            'marginLeft' => 1134,
            'marginRight' => 1134,
        ]);

        // Helper paragraph styles — bidi=true forces Word to treat paragraph as RTL
        $rtlRight  = ['alignment' => Jc::RIGHT,  'bidi' => true, 'textAlignment' => 'auto'];
        $rtlCenter = ['alignment' => Jc::CENTER, 'bidi' => true, 'textAlignment' => 'auto'];
        // Helper: add a right-aligned RTL paragraph with a TextRun (for mixed Arabic+Latin)
        // Usage: $this->addRtlParagraph($section, [[$text, $fontStyle], ...], $parStyle)
        // defined below as a closure
        $addRtlRun = function (
            \PhpOffice\PhpWord\Element\AbstractContainer $container,
            string $text,
            array $fontStyle,
            array $parStyle = []
        ) use ($rtlRight): void {
            $parStyle = array_merge($rtlRight, $parStyle);
            $textRun = $container->addTextRun($parStyle);
            $textRun->addText($text, array_merge($fontStyle, ['rtl' => true]));
        };

        // Font Styles (ALL MUST HAVE 'rtl' => true)
        $fNormal = ['name' => 'Arial', 'size' => 11,   'rtl' => true];
        $fNormalLg = ['name' => 'Arial', 'size' => 11.5, 'rtl' => true];
        $fNormalXl = ['name' => 'Arial', 'size' => 12.5, 'rtl' => true];
        $fBold = ['name' => 'Arial', 'size' => 11,   'bold' => true, 'rtl' => true];
        $fBoldLg = ['name' => 'Arial', 'size' => 12,   'bold' => true, 'rtl' => true];
        $fBoldXl = ['name' => 'Arial', 'size' => 13.5, 'bold' => true, 'rtl' => true];
        $fRedBold = ['name' => 'Arial', 'size' => 12,   'bold' => true, 'rtl' => true, 'color' => 'C00000'];
        $fRedHeader = ['name' => 'Arial', 'size' => 13.5, 'bold' => true, 'rtl' => true, 'color' => 'C00000'];
        $fRedTitle = ['name' => 'Arial', 'size' => 16,   'bold' => true, 'rtl' => true, 'color' => 'C00000'];
        $fRedUnder = ['name' => 'Arial', 'size' => 13.5, 'bold' => true, 'rtl' => true, 'color' => 'C00000', 'underline' => 'single'];
        $fGreenBold = ['name' => 'Arial', 'size' => 12.5, 'bold' => true, 'rtl' => true, 'color' => '008000'];

        // Table Styles (9638 twips = exact printable width of A4 portrait with 1134 twips margins)
        $headerTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => true,
            'borderColor' => '000000', 'borderSize' => 6, 'cellMargin' => 100, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('HeaderTable', $headerTableStyle);

        $borderTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => true,
            'borderColor' => 'D0D0D0', 'borderSize' => 4, 'cellMargin' => 80, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('BorderTable', $borderTableStyle);

        $recTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => true,
            'borderColor' => '000000', 'borderSize' => 6, 'cellMargin' => 100, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('RecTable', $recTableStyle);

        $noBorderTableStyle = [
            'width' => 9638, 'unit' => TblWidth::TWIP, 'layout' => 'fixed', 'bidiVisual' => true,
            'borderColor' => 'FFFFFF', 'borderSize' => 0, 'cellMargin' => 40, 'alignment' => Jc::CENTER,
        ];
        $phpWord->addTableStyle('NoBorderTable', $noBorderTableStyle);

        // ─── HEADER & FOOTER SETUP (PAGES 2+) ─────────────────────────────────
        $reportNo = $house->reference_code ?: ('H-'.$house->id);
        $reportDate = ($house->created_at ?: now())->format('Y-m-d');
        $clientName = trim((string) ($house->buyer_name ?? $house->client_name ?? '')) ?: '---';

        // Empty first-page parts activate a true cover page without the repeating header/footer.
        $section->addHeader(Header::FIRST);
        $section->addFooter(Footer::FIRST);

        $header = $section->addHeader();
        $headerTable = $header->addTable('NoBorderTable');
        $headerTable->addRow();

        // Right cell in RTL (Order No & Date)
        $headerCellRight = $headerTable->addCell(5782);
        $headerCellRight->addText("رقم الطلب {$reportNo}", ['bold' => true, 'size' => 10, 'color' => 'C00000', 'rtl' => true], $rtlRight);
        $headerCellRight->addText("تاريخ الفحص {$reportDate}", ['bold' => true, 'size' => 10, 'color' => 'C00000', 'rtl' => true], $rtlRight);

        // Left cell in RTL (Company Logo)
        $headerCellLeft = $headerTable->addCell(3856);
        if (file_exists($this->logoPath)) {
            $headerCellLeft->addImage($this->logoPath, [
                'width' => 90,
                'height' => 36,
                'alignment' => Jc::LEFT,
                'wrappingStyle' => 'inline',
            ]);
        }

        $footer = $section->addFooter();
        $footerTable = $footer->addTable('NoBorderTable');
        $footerTable->addRow();

        // Right cell in RTL (Company Title)
        $footerCellRight = $footerTable->addCell(4819);
        $footerCellRight->addText('GIS VALUATION AND EVALUATION', ['bold' => true, 'size' => 8.5, 'color' => 'C00000', 'rtl' => false], ['alignment' => Jc::LEFT, 'bidi' => false]);
        $footerCellRight->addText('جي إي إس للتقييم والتثمين العقاري - C.R. 160528-1', ['size' => 8, 'color' => '555555', 'rtl' => true], $rtlRight);

        // Left cell in RTL (Contacts & Socials)
        $footerCellLeft = $footerTable->addCell(4819);
        $footerCellLeft->addText('36698895 | infogisgulf@gmail.com | gis.Bahrain', ['size' => 8, 'color' => '555555'], ['alignment' => Jc::LEFT, 'bidi' => false]);
        $footerCellLeft->addText('Seef District - Kingdom of Bahrain', ['size' => 8, 'color' => '555555'], ['alignment' => Jc::LEFT, 'bidi' => false]);

        // ─── PAGE 1: COVER PAGE (Ghilaf) ──────────────────────────────────────
        if (file_exists($this->logoPath)) {
            $section->addImage($this->logoPath, [
                'width' => 120,
                'height' => 50,
                'alignment' => Jc::CENTER,
                'wrappingStyle' => 'inline',
            ]);
        }
        $section->addTextBreak(1);

        // Cover page headings — use TextRun for Arabic+Latin mixed headings
        $trCover = $section->addTextRun($rtlCenter);
        $trCover->addText('شركة GIS للتقييم والتثمين العقاري', ['bold' => true, 'size' => 18, 'color' => 'C00000', 'rtl' => true]);
        $section->addText('تقرير فحص وتقييم عقار', ['bold' => true, 'size' => 14, 'color' => '333333', 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        $addressParts = [];
        if ($house->villa_number) {
            $addressParts[] = 'فيلا '.$house->villa_number;
        }
        if ($house->road) {
            $addressParts[] = 'طريق '.$house->road;
        }
        if ($house->compound) {
            $addressParts[] = 'مجمع '.$house->compound;
        }
        if ($house->area) {
            $addressParts[] = $house->area;
        }
        $propertyAddress = implode('، ', $addressParts) ?: ($house->address ?? ($house->title ?? '---'));

        $infoTable = $section->addTable('HeaderTable');
        foreach ([
            ['رقم التقرير',  $reportNo,       'Report No.'],
            ['التاريخ',      $reportDate,      'Date'],
            ['اسم العميل',   $clientName,      'Client Name'],
            ['عنوان المنزل', $propertyAddress, 'Property Address'],
        ] as $row) {
            $infoTable->addRow();
            $infoTable->addCell(2891, ['bgColor' => 'F2F2F2'])->addText($row[0], ['bold' => true, 'size' => 11, 'rtl' => true], $rtlRight);
            $infoTable->addCell(3856)->addText($row[1], ['bold' => true, 'size' => 11, 'color' => 'C00000', 'rtl' => true], $rtlCenter);
            $infoTable->addCell(2891, ['bgColor' => 'F2F2F2'])->addText($row[2], ['bold' => true, 'size' => 10], ['alignment' => Jc::LEFT, 'bidi' => false]);
        }

        // ─── PAGES 2-5: THE 4 INTRO PAGES ──────────────────────────────────────
        // Intro Page 1 (Page 2)
        $section->addPageBreak();
        // Use TextRun for mixed Arabic+Latin headings to ensure RTL
        $tr1 = $section->addTextRun($rtlCenter);
        $tr1->addText('شركة GIS لفحص تقييم المباني', ['bold' => true, 'size' => 16, 'color' => 'C00000', 'rtl' => true]);
        $section->addText('نبذة تعريفية – مقدمة الشركة وخدماتها', ['bold' => true, 'size' => 13.5, 'color' => 'C00000', 'underline' => 'single', 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        $section->addText('من نحن ؟', ['bold' => true, 'size' => 13, 'rtl' => true], $rtlRight);
        $addRtlRun($section, 'نحن في شركة GIS نؤمن بأن سلامة العقار تبدأ من القرار الصحيح، ولهذا نقدم خدمة فحص شاملة للمباني الجاهزة قبل الشراء أو السكن، وذلك للتأكد من خلوها من العيوب الفنية الظاهرة أو المخاطر الخفية.', $fNormalLg, ['spaceAfter' => 80]);
        $section->addText('نعمل من خلال خبراء مختصين، ونعتمد على أجهزة وتقنيات حديثة لضمان تقديم تقارير دقيقة وموثوقة تساعد العميل على اتخاذ قرار استثماري أو سكني آمن.', $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 80]));
        $section->addText('نعد من أوائل الشركات المتخصصة في فحص العقارات في البحرين، ونفتخر بسجل حافل من الإنجازات وخدمة مئات العملاء من الأفراد والشركات والمطورين.', $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 100]));

        $section->addText('خدماتنا الرئيسية', $fRedUnder, $rtlRight);
        $addRtlRun($section, 'تقدم شركة GIS خدمات فحص المباني الجاهزة عبر تقييم شامل للأنظمة التالية:', $fRedBold, ['spaceAfter' => 60]);

        foreach ([
            '1- الكهرباء: فحص جودة وسلامة الأفياش، المفاتيح، الطبلون، الأحمال، التأريض.',
            '2- السباكة: فحص جودة المواسير، الصفايات، ضغط المياه، الخزانات، السخانات، ملوحة الماء.',
            '3- الرطوبة والتسريب: قياس الرطوبة، كشف التسريبات، فحص العزل المائي.',
            '4- الخزانات: التأكد من سلامتها من التسريب الحراري أو المائي لتفادي التلوث أو تلف الأسقف.',
            '5- الأبواب والنوافذ: التحقق من جودة الفتح والإغلاق، المفصلات، الإطارات، العزل، الديكورات.',
            '6- الأسقف والجدران: فحص الشقوق، آثار الرطوبة، جودة التشطيب، الزوايا، العزل الحراري.',
            '7- السيراميك والأرضيات: فحص الترويب، الاستواء، الثبات، جودة التركيب.',
            '8- أنظمة الحماية والسلامة: التأكد من جاهزية كاميرات المراقبة، كواشف الدخان، وحساسات الإنذار.',
            '9- التهوية والتكييف: فحص وحدات التكييف، فتحات الهواء، مراوح الشفط، جودة التأسيس.',
            '10- مواقف السيارات والواجهات: فحص الأرضية، المظلات، باب الكراج، التشققات، فواصل التمدد.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 40]));
        }

        // Intro Page 2 (Page 3: ما يميزنا)
        $section->addPageBreak();
        $section->addText('ما يميزنا', ['bold' => true, 'size' => 15, 'color' => 'C00000', 'underline' => 'single', 'rtl' => true], $rtlRight);
        $section->addTextBreak(1);

        $addRtlRun($section, 'ضمانات GIS:', $fRedUnder);
        $section->addText('• ضمان الإحاطة: يشمل شمولية ودقة البيانات وفق معايير الشركة.', $fBoldLg, array_merge($rtlRight, ['spaceAfter' => 20]));
        $section->addText('* شرح ضمان الإحاطة', $fGreenBold, array_merge($rtlRight, ['spaceAfter' => 20]));
        $section->addText('تقديم تقرير شامل قدر الإمكان عن حالة العقار الظاهرة، بناءً على المعايير الفنية المتبعة داخل الشركة. لا يُعد هذا ضمانًا قانونيًا أو فنيًا لحالة العقار المستقبلية أو للعيوب المخفية.', $fNormal, array_merge($rtlRight, ['spaceAfter' => 80]));

        $section->addText('• ضمان السلامة: التأكد من خلو البنود المفحوصة من العيوب الخطرة، والرجوع لشهادات الضمان إن وجدت.', $fBoldLg, array_merge($rtlRight, ['spaceAfter' => 20]));
        $section->addText('* شرح ضمان السلامة', $fGreenBold, array_merge($rtlRight, ['spaceAfter' => 20]));
        $section->addText('يُقصد به التأكد من عدم وجود عيوب خطرة في العناصر المفحوصة وقت الفحص، وفق المشاهدات الظاهرة. في حال توفر شهادات ضمان للعناصر مثل الكهرباء أو السباكة أو التكييف، يتم الرجوع إليها دون أن تتحمل الشركة مسؤولية صلاحيتها.', $fNormal, array_merge($rtlRight, ['spaceAfter' => 100]));

        $section->addText('امتيازاتنا:', $fRedUnder, $rtlRight);
        foreach ([
            '• سرعة في الاستجابة وإنجاز الفحص.',
            '• سهولة في تقديم الطلب واستلام التقرير.',
            '• توثيق مصور لجميع الملاحظات والعيوب.',
            '• تقارير احترافية سهلة القراءة وغنية بالتفاصيل.',
            '• أرشفة بيانات كل وحدة عقارية بشكل مستقل ومنظم.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('آلية الفحص والتسليم', $fRedUnder, $rtlRight);
        foreach ([
            '• تستغرق عملية الفحص من ساعتين إلى يومين حسب مساحة العقار وتعقيد أنظمته.',
            '• يتم تسليم التقرير النهائي خلال 5 أيام عمل بعد مراجعته من قسم الجودة.',
            '• في حال وجود ملاحظات من العميل، يجب إرسالها خلال مدة أقصاها 3 أيام من استلام التقرير لإجراء التعديلات المطلوبة، إن وجدت.',
        ] as $item) {
            $section->addText($item, $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 40]));
        }

        // Intro Page 3 (Page 4: المستندات الاختيارية)
        $section->addPageBreak();
        $section->addText('المستندات الاختيارية التي تُحسن جودة تقرير الفحص العقاري :-', $fRedTitle, $rtlRight);
        $section->addText('تساهم المستندات التالية في رفع دقة وجودة التقييم الفني للعقار، ويُفضل إرفاق ما يتوفر منها مع طلب الفحص:', $fNormalXl, array_merge($rtlRight, ['spaceAfter' => 100]));

        $section->addText('أولاً: مستندات هندسية وفنية', $fRedHeader, $rtlRight);
        foreach ([
            '• رخصة البناء',
            '• المخططات الهندسية المعتمدة (معمارية، إنشائية، ميكانيكية، كهربائية)',
            '• شهادة ضمان الهيكل الإنشائي (عادة لمدة 10 سنوات)',
            '• تقارير فحص التربة',
            '• شهادات ضمان المواد والأنظمة مثل: (السباكة، الكهرباء، التكييف، العازل المائي، الألمنيوم والزجاج)',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($rtlRight, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('ثانياً: معلومات عامة وموقع العقار', $fRedHeader, $rtlRight);
        foreach ([
            '• صورة من خرائط العقار المعتمدة من المكتب الهندسي',
            '• موقع العقار (رابط مباشر أو صورة من خرائط Google أو تطبيق مشابه)',
            '• شهادة العنوان (تشمل رقم المنزل، اسم الشارع، رقم الطريق، ورقم المجمع)',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($rtlRight, ['spaceAfter' => 40]));
        }
        $section->addTextBreak(1);

        $section->addText('ثالثاً: مستندات إضافية داعمة لتقييم العقار', $fRedHeader, $rtlRight);
        foreach ([
            '• شهادة ضغط الأرض (إن وُجدت)',
            '• عمر العقار التقريبي (سنة الإنشاء أو التسليم)',
            '• صورة البطاقة الذكية للمشتري أو طالب خدمة الفحص',
            '• عقد المقاول العام و ضمانات المواد المستخدمة في البناء ( اختياري )',
        ] as $item) {
            $section->addText($item, $fNormalXl, array_merge($rtlRight, ['spaceAfter' => 40]));
        }

        // Intro Page 4 (Page 5: تنويه هام)
        $section->addPageBreak();
        $section->addText('تنويه هام – حدود المسؤولية', $fRedTitle, $rtlRight);
        $addRtlRun($section, 'تلتزم شركة GIS بتقديم خدماتها بأقصى درجات الدقة والحيادية، باستخدام أدوات تقنية ومن خلال خبراء مختصين. مع ذلك، لا تتحمل الشركة أي مسؤولية قانونية أو مادية عن الأضرار الناتجة عن عملية الفحص، سواء أثناء المعاينة أو بعدها.', $fNormalXl, ['spaceAfter' => 60]);
        $section->addText('كما لا تتحمل الشركة أي التزامات ناتجة عن نزاعات بين العميل وأطراف أخرى مثل المقاول أو المطور، إذ تقتصر مسؤوليتنا على التشخيص والتوثيق فقط.', $fNormalXl, array_merge($rtlRight, ['spaceAfter' => 100]));

        $section->addText('* تنويه هام:', $fRedHeader, $rtlRight);
        $addRtlRun($section, 'تقتصر مهمة شركة GIS على فحص وتوثيق الحالة الظاهرة للعقار وقت المعاينة فقط. ولا يُعد التقرير الصادر عنها التزامًا بضمان مستقبلي لحالة العقار أو لأي تطورات قد تطرأ لاحقًا. تنتهي مسؤولية الشركة بشكل كامل فور انتهاء الفحص ومغادرة الفريق للموقع، ما لم يتم الاتفاق المسبق على خدمة متابعة أو فحص إضافي.', $fBoldLg, ['spaceAfter' => 100]);

        $section->addText('الأسئلة الشائعة', $fRedUnder, $rtlRight);
        $faqTable = $section->addTable('BorderTable');
        foreach ([
            ['هل يمكن استرداد المبلغ؟', 'نعم، مع تطبيق رسوم إدارية في حال تم الإلغاء قبل 24 ساعة من موعد الفحص.'],
            ['هل يمكن تعديل التقرير؟', 'نعم، يتم قبول الملاحظات خلال 3 أيام فقط من استلام التقرير.'],
            ['هل تقدمون خدمات صيانة؟', 'لا، نحن جهة فحص فقط لضمان الحيادية والاستقلالية.'],
            ['كيف يتم تعيين الفاحص؟', 'نقوم بترشيح فاحص متخصص بناءً على نوع العقار وتوزيع العمل، ويرفع التقرير ضمن نموذج GIS المعتمد.'],
            ['لماذا الفحص مهم؟', 'لأنه يوفر عليك آلاف الدنانير ويكشف عيوب قد تكون خفية قبل الشراء أو الاستلام.'],
        ] as $faq) {
            $faqTable->addRow();
            $cell = $faqTable->addCell(9638);
            $cell->addText($faq[0], ['bold' => true, 'size' => 13, 'color' => 'C00000', 'rtl' => true], $rtlRight);
            $cell->addText($faq[1], $fBoldLg, $rtlRight);
        }

        // ─── PAGE 6: PROPERTY DATA & SPECS PAGE (بيانات العقار ومواصفاته) ─────────
        $section->addPageBreak();

        // 1) Property Data (بيانات العقار)
        $section->addText('بيانات العقار :-', ['bold' => true, 'size' => 15, 'color' => 'C00000', 'rtl' => true], array_merge($rtlRight, ['spaceAfter' => 60]));
        $dataTable = $section->addTable('BorderTable');
        foreach ([
            ['النشاط بحسب رخصة البناء', $house->activity ?: 'سكني'],
            ['نوع العقار',               $house->property_type ?: 'فيلا'],
            ['حالة المبنى',              $house->building_status ?: 'تشطيب (غير مؤثث)'],
            ['رقم الوثيقة',              $house->document_number ?: '---'],
            ['رقم المقدمة',              $house->intro_number ?: '---'],
            ['فيلا',                     $house->villa_number ?: '---'],
            ['الطريق',                   $house->road ?: '---'],
            ['المجمع',                   $house->compound ?: '---'],
            ['المنطقة',                  $house->area ?: '---'],
            ['المشتري / العميل',         $clientName],
            ['رقم الهوية',               $house->id_number ?: '---'],
            ['اسم المطور العقاري',        $house->developer_name ?: '---'],
            ['المشرف الهندسي',            $house->engineering_supervisor ?: '---'],
            ['المقاول الرئيسي',           $house->main_contractor ?: '---'],
        ] as $item) {
            $dataTable->addRow();
            $dataTable->addCell(4819, ['bgColor' => 'F9F9F9'])->addText($item[0], ['bold' => true, 'size' => 10.5, 'rtl' => true], $rtlRight);
            $dataTable->addCell(4819)->addText($item[1], ['size' => 10.5, 'rtl' => true], $rtlRight);
        }
        $section->addTextBreak(1);

        // 2) Property Specs (مواصفات العقار)
        $section->addText('مواصفات العقار :-', ['bold' => true, 'size' => 15, 'color' => 'C00000', 'rtl' => true], array_merge($rtlRight, ['spaceAfter' => 60]));
        $specsTable = $section->addTable('BorderTable');
        foreach ([
            ['عمر العقار التقريبي',       $house->property_age ?: 'جديد'],
            ['مساحة الأرض التقريبية',     $house->land_area ? $house->land_area.' م²' : '---'],
            ['مساحة البناء التقريبية',    $house->building_area ? $house->building_area.' م²' : '---'],
            ['عدد الطوابق',               $house->floors_count ?: '---'],
            ['عدد الغرف',                 $house->rooms_count ?: '---'],
            ['عدد دورات المياه',           $house->bathrooms_count ?: '---'],
            ['عدد الصالات',               $house->halls_count ?: '---'],
            ['عدد مواقف السيارات',         $house->parking_count ?: '---'],
            ['عدد المطابخ',               $house->kitchens_count ?: '---'],
        ] as $spec) {
            $specsTable->addRow();
            $specsTable->addCell(4819, ['bgColor' => 'F9F9F9'])->addText($spec[0], ['bold' => true, 'size' => 10.5, 'rtl' => true], $rtlRight);
            $specsTable->addCell(4819)->addText($spec[1], ['size' => 10.5, 'rtl' => true], $rtlRight);
        }
        $section->addTextBreak(1);

        // Signature Stamp at bottom of Page 6
        if (file_exists($this->stampPath)) {
            $section->addImage($this->stampPath, [
                'width' => 120,
                'height' => 75,
                'alignment' => Jc::START,
                'wrappingStyle' => 'inline',
            ]);
        }

        // ─── PAGE 7: OVERALL SCORE & SUB-PERCENTAGES (النسبة المئوية) ──────────
        $section->addPageBreak();

        $totalPct = min(100, max(0, (int) ($house->total_percentage ?? 0)));
        $rating = $house->inspector_rating_override ?: InspectionScoreLabels::label($totalPct);

        $summaryBox = $section->addTable('HeaderTable');
        $summaryBox->addRow();
        $summaryBox->addCell(9638, ['bgColor' => 'FFFF00'])->addText('النسبة المئوية لفحص المبنى', ['bold' => true, 'size' => 15, 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        $scoreCard = $section->addTable('BorderTable');
        $scoreCard->addRow();
        $scoreCard->addCell(9638, ['bgColor' => 'F2F2F2'])->addText("النسبة الإجمالية لتقييم العقار: {$totalPct}% — التقييم: ({$rating})", ['bold' => true, 'size' => 14, 'color' => 'C00000', 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        $section->addText('النسب الفرعية للأقسام :-', ['bold' => true, 'size' => 13.5, 'color' => 'C00000', 'rtl' => true], $rtlRight);

        $areas = $house->inspectionAreas()->orderBy('sort_order')->orderBy('id')->get();
        if ($areas->isNotEmpty()) {
            $scoresTable = $section->addTable('BorderTable');
            $scoresTable->addRow();
            $scoresTable->addCell(5782, ['bgColor' => 'E0E0E0'])->addText('القسم / البند', ['bold' => true, 'size' => 11.5, 'rtl' => true], $rtlRight);
            $scoresTable->addCell(1928, ['bgColor' => 'E0E0E0'])->addText('النسبة المئوية', ['bold' => true, 'size' => 11.5, 'rtl' => true], $rtlCenter);
            $scoresTable->addCell(1928, ['bgColor' => 'E0E0E0'])->addText('الحالة', ['bold' => true, 'size' => 11.5, 'rtl' => true], $rtlCenter);

            foreach ($areas as $area) {
                $score = min(100, max(0, (int) ($area->score ?: 0)));

                if ($score >= 80) {
                    $bgColor = '00C853';
                    $textColor = 'FFFFFF';
                    $statusLabel = 'ممتاز';
                } elseif ($score >= 70) {
                    $bgColor = 'FFFF00';
                    $textColor = '000000';
                    $statusLabel = InspectionScoreLabels::label($score);
                } elseif ($score >= 60) {
                    $bgColor = 'D77814';
                    $textColor = 'FFFFFF';
                    $statusLabel = 'متوسط';
                } else {
                    $bgColor = 'F70000';
                    $textColor = 'FFFFFF';
                    $statusLabel = 'ضعيف';
                }

                $scoresTable->addRow();
                $scoresTable->addCell(5782)->addText($area->name, ['bold' => true, 'size' => 11.5, 'rtl' => true], $rtlRight);
                $scoresTable->addCell(1928, ['bgColor' => $bgColor])->addText($score.'%', ['bold' => true, 'size' => 11.5, 'color' => $textColor, 'rtl' => true], $rtlCenter);
                $scoresTable->addCell(1928, ['bgColor' => $bgColor])->addText($statusLabel, ['bold' => true, 'size' => 11, 'color' => $textColor, 'rtl' => true], $rtlCenter);
            }
        }
        $section->addTextBreak(1);

        // Score Legend
        $legendTable = $section->addTable('BorderTable');
        $legendTable->addRow();
        $legendTable->addCell(2410, ['bgColor' => '00C853'])->addText('ممتاز (80% - 100%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF', 'rtl' => true], $rtlCenter);
        $legendTable->addCell(2409, ['bgColor' => 'FFFF00'])->addText('جيد (70% - 79%)', ['bold' => true, 'size' => 10, 'color' => '000000', 'rtl' => true], $rtlCenter);
        $legendTable->addCell(2409, ['bgColor' => 'D77814'])->addText('متوسط (60% - 69%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF', 'rtl' => true], $rtlCenter);
        $legendTable->addCell(2410, ['bgColor' => 'F70000'])->addText('ضعيف (أقل من 60%)', ['bold' => true, 'size' => 10, 'color' => 'FFFFFF', 'rtl' => true], $rtlCenter);

        // ─── PAGE 8: TECHNICAL NOTES ──────────────────────────────────────────
        $section->addPageBreak();

        $techTitle = $section->addTable('HeaderTable');
        $techTitle->addRow();
        $techTitle->addCell(9638, ['bgColor' => 'DAE3F3'])->addText('تقرير الملاحظات الفنية', ['bold' => true, 'size' => 16, 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        foreach ($areas as $area) {
            $notes = $area->notesList();
            $score = min(100, max(0, (int) ($area->score ?: 0)));

            if ($score >= 80) {
                $bgColor = '00C853';
                $textColor = 'FFFFFF';
            } elseif ($score >= 70) {
                $bgColor = 'FFFF00';
                $textColor = '000000';
            } elseif ($score >= 60) {
                $bgColor = 'D77814';
                $textColor = 'FFFFFF';
            } else {
                $bgColor = 'F70000';
                $textColor = 'FFFFFF';
            }

            $areaBadge = $section->addTable('BorderTable');
            $areaBadge->addRow();
            $areaBadge->addCell(6746)->addText($area->name, ['bold' => true, 'size' => 12, 'rtl' => true], $rtlRight);
            $areaBadge->addCell(2892, ['bgColor' => $bgColor])->addText("النسبة: {$score}%", ['bold' => true, 'size' => 11.5, 'color' => $textColor, 'rtl' => true], $rtlCenter);
            $section->addTextBreak(1);

            if ($notes === []) {
                $section->addText(
                    '• تم التحقق من أعمال '.$area->name.'، وتبين أن جميع الأعمال منفذة بشكل سليم ولا توجد ملاحظات.',
                    $fNormalLg,
                    array_merge($rtlRight, ['spaceAfter' => 60])
                );
            } else {
                foreach ($notes as $noteText) {
                    $section->addText('• '.$noteText, $fNormalLg, array_merge($rtlRight, ['spaceAfter' => 40]));
                }
            }
            $section->addTextBreak(1);
        }

        // ─── PAGE 9: RECOMMENDATIONS TABLE ───────────────────────────────────
        $section->addPageBreak();

        $section->addText('جدول التوصيات الفنية', ['bold' => true, 'size' => 15, 'color' => 'C00000', 'rtl' => true], $rtlRight);
        $section->addTextBreak(1);

        $recTable = $section->addTable('RecTable');
        $recTable->addRow();
        $recTable->addCell(3373, ['bgColor' => '81C784'])->addText('الأعمال', ['bold' => true, 'size' => 12, 'color' => 'FFFFFF', 'rtl' => true], $rtlCenter);
        $recTable->addCell(6265, ['bgColor' => '81C784'])->addText('التوصيات', ['bold' => true, 'size' => 12, 'color' => 'FFFFFF', 'rtl' => true], $rtlCenter);

        $hasRecs = false;
        foreach ($areas as $area) {
            $recs = $area->recommendationsList();
            if ($recs === []) {
                continue;
            }

            $hasRecs = true;
            $score = min(100, max(0, (int) ($area->score ?: 0)));

            if ($score >= 80) {
                $bgColor = '00C853';
                $textColor = 'FFFFFF';
            } elseif ($score >= 70) {
                $bgColor = 'FFFF00';
                $textColor = '000000';
            } elseif ($score >= 60) {
                $bgColor = 'D77814';
                $textColor = 'FFFFFF';
            } else {
                $bgColor = 'F70000';
                $textColor = 'FFFFFF';
            }

            $recTable->addRow();
            $recTable->addCell(3373, ['bgColor' => $bgColor])->addText($area->name, ['bold' => true, 'size' => 11.5, 'color' => $textColor, 'rtl' => true], $rtlCenter);

            $recCell = $recTable->addCell(6265);
            foreach ($recs as $recText) {
                $recCell->addText('• '.trim($recText), $fNormalLg, $rtlRight);
            }
        }

        if (! $hasRecs) {
            $recTable->addRow();
            $recTable->addCell(3373)->addText('جميع الأقسام', ['bold' => true, 'rtl' => true], $rtlCenter);
            $recTable->addCell(6265)->addText('لا توجد توصيات فنية إضافية.', ['rtl' => true], $rtlRight);
        }
        $section->addTextBreak(1);

        // ─── PAGE 10: FINAL RESULT PAGE ───────────────────────────────────────
        $section->addPageBreak();

        $finalTitleBox = $section->addTable('HeaderTable');
        $finalTitleBox->addRow();
        $finalTitleBox->addCell(9638, ['bgColor' => 'FFFF00'])->addText('النتيجة النهائية لتقرير الفحص', ['bold' => true, 'size' => 15, 'rtl' => true], $rtlCenter);
        $section->addTextBreak(1);

        $finalText = trim((string) ($house->final_result_text ?? ''));
        if ($finalText !== '') {
            foreach (preg_split('/\r\n|\n|\r/', $finalText) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    $section->addText($line, ['size' => 12, 'rtl' => true], array_merge($rtlRight, ['spaceAfter' => 60]));
                }
            }
        }

        $section->addText(
            "وبناءً على التقييم الفني، بلغت نسبة الإنجاز التقديرية للعقار {$totalPct}%، مما يعني أن العقار يعتبر صالحاً للاستخدام بعد استكمال الأعمال المتبقية وتنفيذ التوصيات الفنية الواردة في التقرير، وذلك لضمان أعلى مستويات الجودة والسلامة والجاهزية التشغيلية.",
            ['bold' => true, 'size' => 12, 'rtl' => true],
            array_merge($rtlRight, ['spaceAfter' => 80])
        );

        $generalNotes = trim((string) ($house->final_general_notes ?? ''));
        if ($generalNotes !== '') {
            $section->addText('ملاحظات عامة :-', ['bold' => true, 'size' => 12.5, 'color' => 'C00000', 'rtl' => true], $rtlRight);
            foreach (preg_split('/\r\n|\n|\r/', $generalNotes) as $line) {
                $line = trim($line);
                if ($line !== '') {
                    if (mb_substr($line, 0, 1) !== '•' && mb_substr($line, 0, 1) !== '-') {
                        $line = '• '.$line;
                    }
                    $section->addText($line, ['size' => 11.5, 'underline' => 'single', 'rtl' => true], array_merge($rtlRight, ['spaceAfter' => 40]));
                }
            }
        }
        $section->addTextBreak(1);

        $section->addText("تقييم الفاحص لحالة العقار: ({$rating})", ['bold' => true, 'size' => 12.5, 'rtl' => true], $rtlRight);

        $deliveryDate = ($house->report_delivered_at ?? $house->updated_at ?? now())->format('Y-m-d');
        $section->addText("• تم تسليم التقرير {$deliveryDate}", ['bold' => true, 'size' => 12, 'color' => 'C00000', 'rtl' => true], array_merge($rtlRight, ['spaceAfter' => 60]));

        $section->addText('يرجى الاطلاع على المرفقات', ['bold' => true, 'underline' => 'single', 'size' => 11.5, 'rtl' => true], $rtlRight);
        $section->addText('1-تقرير الكهرباء .', ['size' => 11.5, 'rtl' => true], $rtlRight);
        $section->addText('2-الصور .', ['size' => 11.5, 'rtl' => true], $rtlRight);
        $section->addTextBreak(1);

        // Stamp Image
        if (file_exists($this->stampPath)) {
            $section->addImage($this->stampPath, [
                'width' => 130,
                'height' => 80,
                'alignment' => Jc::START,
                'wrappingStyle' => 'inline',
            ]);
        }

        // ─── Save & Output Binary ─────────────────────────────────────────────
        $objWriter = IOFactory::createWriter($phpWord, 'Word2007');
        $tempBase = tempnam(sys_get_temp_dir(), 'word_report_');
        if ($tempBase === false) {
            throw new \RuntimeException('Unable to create a temporary Word report file.');
        }

        $tempFile = $tempBase.'.docx';
        try {
            $objWriter->save($tempFile);
            $this->forceSectionRtl($tempFile);

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
     * PhpWord has no public API to mark a *section* as right-to-left — only
     * individual paragraphs/tables (which we already set via 'bidi' => true
     * and 'bidiVisual' => true everywhere above). Without the section-level
     * <w:bidi/> flag in word/document.xml's <w:sectPr>, some viewers infer
     * RTL from the paragraphs anyway, but real Microsoft Word does not: it
     * keeps the page's own ruler/reading order as left-to-right, which is
     * exactly the "writing starts from the left" symptom. This patches the
     * saved .docx directly to add that missing flag, which is the standard
     * fix for this well-known PhpWord/Word limitation.
     */
    private function forceSectionRtl(string $docxPath): void
    {
        $zip = new \ZipArchive;
        if ($zip->open($docxPath) !== true) {
            return;
        }

        $xml = $zip->getFromName('word/document.xml');
        if ($xml === false) {
            $zip->close();

            return;
        }

        // Only patch the section properties block itself — paragraphs already
        // contain plenty of <w:bidi/> tags, so a whole-document string check
        // would be a false positive.
        if (preg_match('/<w:sectPr\b[^>]*>.*?<\/w:sectPr>/s', $xml, $m)) {
            $sectPr = $m[0];
            if (strpos($sectPr, '<w:bidi/>') === false) {
                $patchedSectPr = str_replace('</w:sectPr>', '<w:bidi/></w:sectPr>', $sectPr);
                $patched = str_replace($sectPr, $patchedSectPr, $xml);
                $zip->addFromString('word/document.xml', $patched);
            }
        }

        $zip->close();
    }
}