@php
    // وضع التدوير الفيزيائي: لو ?rotate=1 نخبر الطابعة أن الورقة portrait (148×210)
    // ثم نُدوّر المحتوى 90° عبر CSS. يُستخدم حين تتجاهل الطابعة `A5 landscape`
    // (سلوك شائع في تعريفات Windows). العميل يُدخل الورقة بالعرض يدوياً.
    $physicalRotation = request()->boolean('rotate', false);
@endphp
<style>
    /*
       A5 أفقي — نموذج المناطق الكتلية الثابتة (block fixed-zones):
       الصفحة = كتلة ترويسة بارتفاع ثابت 36مم + المحتوى + كتلة تذييل 20مم.
       المناطق كتل حقيقية في تدفّق الصفحة (ليست @page margin) فتُضمن إزاحة المحتوى
       36مم من الأعلى مهما كانت إعدادات هوامش المتصفح. لا يُفرض ارتفاع الصفحة الكامل
       (يتجنّب الصفحة الفارغة الزائدة)، وسكربت autofit يُصغّر الخط حتى يتسع المحتوى في
       الشريط الأوسط (~92مم) فتبقى صفحة واحدة. المناطق محجوزة دائماً بنفس الارتفاع سواء
       "مع ترويسة" (مملوءة) أو "بدون" (فارغة) → موضع الجدول ثابت لا يتغيّر.
       @page margin: 0 ليطابق المحتوى حافة الورق المطبوع مسبقاً بدقة.

       أبعاد @page صريحة (210mm 148mm) بدل `A5 landscape` لأن بعض تعريفات الطابعات
       تتجاهل كلمة landscape وتطبع portrait افتراضياً → قص الجوانب. الأبعاد الصريحة
       موثوقة عبر المتصفحات/الطابعات.
    */
    @page {
        size: {{ $physicalRotation ? '148mm 210mm' : '210mm 148mm' }};
        margin: 0;
    }

    @font-face {
        font-family: 'Almarai';
        src: url("{{ asset('assets/fonts/Almarai.ttf') }}");
    }

    * {
        box-sizing: border-box;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    html,
    body {
        margin: 0;
        padding: 0;
        color: #111;
        background: #fff;
        font-family: 'Almarai', 'DejaVu Sans', sans-serif;
        font-size: var(--invoice-print-font-size);
        font-weight: 700;
        line-height: 1.3;
    }

    body {
        --line-color: #d5d9df;
        --line-strong: #9aa0a6;
        --head-bg: #f1f4f8;
        --page-bg: #fff;
        --screen-bg: #3f4550;

        /* ── المناطق الثابتة (A5) ── */
        --zone-header: 36mm;
        --zone-footer: 20mm;
        --page-w: 210mm;
        --page-h: 148mm;
        --side-pad: 7mm;

        /* ── الخطوط (أرضيات مقروءة × المقياس) ── */
        --invoice-print-font-size: calc(11px * var(--invoice-print-scale, 1));
        --item-font-size: calc(10px * var(--invoice-print-scale, 1));
        --summary-font-size: calc(10px * var(--invoice-print-scale, 1));
        --invoice-title-font-size: calc(17px * var(--invoice-print-scale, 1));
        --invoice-title-sub-font-size: calc(8.5px * var(--invoice-print-scale, 1));
        --invoice-meta-font-size: calc(10.5px * var(--invoice-print-scale, 1));

        --qr-size: 21mm;
        --meta-width: 56mm;
        --head-gap: 4mm;
        --head-margin-bottom: 2.4mm;
        --table-cell-padding-block: 1mm;
        --table-cell-padding-inline: 0.9mm;
        --summary-gap: 2mm;
        --signature-gap: 8mm;
        --signature-margin-top: 2.4mm;
    }

    body.invoice-template-compact {
        --invoice-title-font-size: calc(15.5px * var(--invoice-print-scale, 1));
        --invoice-title-sub-font-size: calc(8px * var(--invoice-print-scale, 1));
        --invoice-meta-font-size: calc(9.8px * var(--invoice-print-scale, 1));
        --qr-size: 19mm;
        --meta-width: 52mm;
    }

    body.invoice-template-modern {
        --line-color: #cbd5e1;
        --line-strong: #64748b;
        --head-bg: #e8eef7;
    }

    table,
    th,
    td {
        font-size: inherit;
    }

    /* ─────────────────────────────────────────────────────────
       الإطار: ثلاث كتل رأسية (ترويسة ثابتة / محتوى / تذييل ثابت)
    ───────────────────────────────────────────────────────── */
    .page {
        position: relative;
        width: var(--page-w);
        margin: 0 auto;
        padding: 0 var(--side-pad);
        background: var(--page-bg);
    }

    .zone-header {
        height: var(--zone-header);
        overflow: hidden;
        padding-top: 2.5mm;
    }

    .zone-footer {
        height: var(--zone-footer);
        overflow: hidden;
        display: flex;
        align-items: flex-end;
        padding-bottom: 2mm;
    }

    .page-content {
        padding: 1.5mm 0;
        min-width: 0;
    }

    .invoice-shell {
        width: 100%;
        margin: 0 auto;
    }

    .ltr {
        direction: ltr;
        unicode-bidi: embed;
        display: inline-block;
    }

    /* ── منطقة الترويسة (بيانات الشركة عند "مع ترويسة") ── */
    .micro-header {
        display: flex;
        justify-content: space-between;
        gap: 6mm;
        font-size: calc(9.5px * var(--invoice-print-scale, 1));
        line-height: 1.45;
    }

    .micro-header-block {
        display: flex;
        flex-direction: column;
        gap: 0.5mm;
        min-width: 0;
    }

    .micro-header-title {
        font-weight: 700;
        font-size: calc(11.5px * var(--invoice-print-scale, 1));
    }

    /* ── شريط الرأس (QR + العنوان + بيانات العميل) ── */
    .compact-head {
        display: grid;
        grid-template-columns: var(--qr-size) minmax(0, 1fr) var(--meta-width);
        gap: var(--head-gap);
        align-items: start;
        direction: ltr;
        margin-bottom: var(--head-margin-bottom);
    }

    .compact-head > * {
        min-width: 0;
    }

    .compact-qr {
        width: var(--qr-size);
        min-height: var(--qr-size);
        border: 1px solid var(--line-color);
        display: flex;
        align-items: center;
        justify-content: center;
        overflow: hidden;
        background: #fff;
    }

    .compact-qr.is-placeholder {
        border-style: dashed;
    }

    .compact-qr img {
        width: 100%;
        height: auto;
        aspect-ratio: 1 / 1;
        object-fit: contain;
        display: block;
    }

    .qr-placeholder {
        font-size: calc(6.2px * var(--invoice-print-scale, 1));
        color: #666;
    }

    .compact-title-block {
        direction: rtl;
        text-align: center;
        padding-top: 1mm;
    }

    .compact-title {
        margin: 0;
        font-size: var(--invoice-title-font-size);
        line-height: 1.12;
        font-weight: 700;
    }

    .compact-subtitle {
        margin: 0.5mm 0 0;
        font-size: var(--invoice-title-sub-font-size);
        line-height: 1.2;
        color: #4b5563;
    }

    .compact-meta {
        direction: rtl;
        font-size: var(--invoice-meta-font-size);
        line-height: 1.32;
        font-weight: 700;
    }

    .compact-meta-row {
        display: flex;
        align-items: flex-start;
        gap: 1.2mm;
    }

    .compact-meta-label {
        flex: 0 0 auto;
        white-space: nowrap;
    }

    .compact-meta-value {
        flex: 1 1 auto;
        min-width: 0;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    /* ── الجداول ── */
    .reference-table,
    .summary-table {
        width: 100%;
        border-collapse: collapse;
    }

    .reference-table {
        table-layout: fixed;
        margin-bottom: 2.2mm;
        font-size: var(--item-font-size);
    }

    .summary-table {
        table-layout: auto;
        font-size: var(--summary-font-size);
    }

    .reference-table th,
    .reference-table td,
    .summary-table th,
    .summary-table td {
        border: 1px solid var(--line-color);
        padding: var(--table-cell-padding-block) var(--table-cell-padding-inline);
        vertical-align: middle;
        overflow-wrap: anywhere;
        word-break: break-word;
    }

    .reference-table th,
    .summary-table th {
        background: var(--head-bg);
        font-weight: 700;
        text-align: center;
    }

    .reference-table td {
        text-align: center;
    }

    .head-main,
    .head-sub {
        display: block;
        line-height: 1.15;
    }

    .head-main {
        font-size: calc(9.5px * var(--invoice-print-scale, 1));
        font-weight: 700;
    }

    .head-sub {
        margin-top: 0.4mm;
        font-size: calc(7.5px * var(--invoice-print-scale, 1));
        color: #6b7280;
        direction: ltr;
    }

    .description-cell {
        text-align: right !important;
    }

    .description-main {
        display: block;
        font-size: calc(10.5px * var(--invoice-print-scale, 1));
        font-weight: 700;
        line-height: 1.14;
    }

    .description-sub {
        display: block;
        margin-top: 0.4mm;
        font-size: calc(8px * var(--invoice-print-scale, 1));
        line-height: 1.08;
    }

    .summary-grid {
        display: grid;
        grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
        gap: var(--summary-gap);
        margin-bottom: 1.7mm;
    }

    .summary-label {
        font-weight: 700;
        line-height: 1.18;
    }

    .summary-sub {
        display: inline;
        margin-inline-start: 0.45mm;
        font-size: calc(7.2px * var(--invoice-print-scale, 1));
        line-height: 1;
        color: #6b7280;
        direction: ltr;
    }

    .summary-value {
        width: 25%;
        text-align: center;
        white-space: nowrap;
        line-height: 1.1;
    }

    .payment-table .summary-label {
        width: 56%;
        font-size: calc(9px * var(--invoice-print-scale, 1));
    }

    .payment-table .summary-value {
        width: 44%;
    }

    .invoice-summary-table .summary-label {
        width: 75%;
    }

    .invoice-summary-table .summary-value {
        width: 25%;
    }

    .terms-box {
        margin-bottom: 1.7mm;
        border: 1px solid var(--line-color);
        padding: 1.05mm 1.45mm;
    }

    .terms-title {
        margin-bottom: 0.35mm;
        font-size: calc(9px * var(--invoice-print-scale, 1));
        font-weight: 700;
    }

    .terms-content {
        font-size: calc(8.5px * var(--invoice-print-scale, 1));
        line-height: 1.28;
        white-space: normal;
        overflow: visible;
        overflow-wrap: anywhere;
    }

    .signatures {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: var(--signature-gap);
        margin-top: var(--signature-margin-top);
        font-size: calc(9.5px * var(--invoice-print-scale, 1));
    }

    .signature-box {
        text-align: center;
    }

    .signature-label {
        display: block;
        margin-bottom: 1.1mm;
        font-weight: 700;
    }

    .signature-line {
        display: flex;
        align-items: flex-end;
        justify-content: center;
        min-height: 5.5mm;
        padding-top: 2mm;
        border-top: 1px solid var(--line-strong);
        overflow-wrap: anywhere;
    }

    /* ── منطقة التذييل (العنوان/الهاتف عند "مع تذييل") ── */
    .micro-footer {
        width: 100%;
        padding-top: 1.5mm;
        border-top: 1px solid var(--line-color);
        display: flex;
        justify-content: space-between;
        gap: 3mm;
        font-size: calc(8.5px * var(--invoice-print-scale, 1));
        line-height: 1.26;
    }

    .no-print {
        display: none !important;
    }

    /* ─────────────────────────────────────────────────────────
       شاشة: محاكاة الورقة بارتفاعها الكامل + خطوط مرجعية للمناطق
    ───────────────────────────────────────────────────────── */
    @media screen {
        body {
            padding: 18px 0 40px;
            background: var(--screen-bg);
        }

        .page {
            min-height: var(--page-h);
            margin: 0 auto 18px;
            box-shadow: 0 6px 30px rgba(0, 0, 0, 0.45);
        }

        .zone-header {
            border-bottom: 1px dashed rgba(239, 68, 68, 0.35);
        }

        .zone-footer {
            border-top: 1px dashed rgba(239, 68, 68, 0.35);
        }
    }

    /* ─────────────────────────────────────────────────────────
       طباعة: لا ارتفاع مفروض (يتجنّب الصفحة الفارغة). المناطق كتل ثابتة.
    ───────────────────────────────────────────────────────── */
    @media print {
        html,
        body {
            background: #fff;
        }

        .page {
            width: auto;
            box-shadow: none;
        }
    }

@if($physicalRotation)
    /* ─────────────────────────────────────────────────────────
       وضع التدوير الفيزيائي:
       - الطابعة ترى الورقة portrait (148×210mm) — اتجاه افتراضي موثوق.
       - المحتوى مرسوم landscape (210×148mm) ويُدوَّر 90° عبر transform.
       - العميل يُدخل ورق A5 بالعرض (الحافة الطويلة في مقدمة المُلقّم).

       النتيجة: لا اعتماد على تفسير تعريف الطابعة لكلمة `landscape`،
       والورقة المطبوعة سلفاً تتطابق طالما العميل يدخلها بالاتجاه الصحيح.

       ملاحظة: مصمم للفاتورة المعتادة بصفحة واحدة. الفواتير متعددة الصفحات
       في هذا الوضع: page-break-after يضمن صفحة فيزيائية لكل .page،
       لكن التدوير قد يحتاج ضبطاً إن ظهرت مشاكل تموضع.
    ───────────────────────────────────────────────────────── */

    /* شاشة: نعرض كما لو landscape — أكثر فائدة للمستخدم في المعاينة */
    @media screen {
        body.invoice-print-format-a5 .page {
            transform: none;
        }
    }

    /* طباعة: تدوير فعلي */
    @media print {
        html,
        body.invoice-print-format-a5 {
            width: 148mm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        body.invoice-print-format-a5 .page {
            width: 210mm;
            margin: 0 0 0 148mm;
            transform: rotate(90deg);
            transform-origin: top left;
            page-break-after: always;
            page-break-inside: avoid;
        }

        body.invoice-print-format-a5 .page:last-of-type {
            page-break-after: auto;
        }
    }
@endif
</style>
