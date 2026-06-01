@php
    // نموذج المناطق الثابتة يتولّى الهوامش العلوية/السفلية، فلم يعد هذا الملف يُخرج
    // هوامش @page ولا padding للترويسة/التذييل. يبقى فقط مُضاعِف الخطوط الموحّد
    // --invoice-print-scale (يُغذّى من إعداد المستخدم font_scale ويُقلَّص تلقائياً
    // عبر سكربت autofit عند الحاجة).
    $printSettings = $printSettings ?? app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
    $fontScale = (float) ($printSettings['dimensions']['font_scale'] ?? 1.0);
@endphp
<style>
    :root {
        --invoice-print-scale: {{ number_format($fontScale, 2) }};
    }
</style>
