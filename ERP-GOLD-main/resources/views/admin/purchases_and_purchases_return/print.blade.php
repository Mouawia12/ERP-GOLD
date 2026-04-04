@php
    $printSettings = app(\App\Services\Invoices\InvoicePrintSettingsService::class)->currentSettings();
@endphp

@if(($printSettings['format'] ?? 'a4') === 'a5')
    @include('admin.purchases_and_purchases_return.print_a5')
@else
    @include('admin.purchases_and_purchases_return.print_legacy')
@endif
