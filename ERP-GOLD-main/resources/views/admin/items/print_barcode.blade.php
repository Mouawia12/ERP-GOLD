
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <link href="{{asset('css/barcode.css')}}" rel="stylesheet" type="text/css" />
    <style>
        @page {
            margin: {{ $paperProfile['page_margin_mm'] }}mm;
            size: {{ $paperProfile['page_size'] }};
        }
    </style>
</head>
@php
    $printUnits = isset($unit) ? collect([$unit]) : ($item->units ?? collect());
@endphp
<body class="barcode-sheet barcode-sheet--{{ $paperProfile['key'] }} {{ isset($unit) ? 'single-barcode' : 'multiple-barcodes' }}" data-paper-profile="{{ $paperProfile['key'] }}">
    <div class="barcode-sheet-header">
        <span>Barcode Profile: {{ $paperProfile['label'] }}</span>
    </div>
    <div class="barcode-grid" style="grid-template-columns: repeat({{ isset($unit) ? 1 : $paperProfile['columns'] }}, minmax({{ $paperProfile['label_width_mm'] }}mm, 1fr)); gap: {{ $paperProfile['gap_mm'] }}mm;">
        @foreach($printUnits as $printUnit)
            @php
                $printItem = $printUnit->item ?? $item;
                $caratLabel = $printItem->goldCarat?->label;
            @endphp
            <div class="barcode" style="width: {{ $paperProfile['label_width_mm'] }}mm; min-height: {{ $paperProfile['label_height_mm'] }}mm;">
                <div class="company_name">{{ $printItem->branch?->name ?? '-' }}</div>
                <div class="item_name">{{ $printItem->title }}</div>
                <div class="barcode-img">
                    <img
                        src="data:image/png;base64,{{ DNS1D::getBarcodePNG($printUnit->barcode, 'C128', $paperProfile['barcode_scale'], $paperProfile['barcode_height']) }}"
                        alt="barcode"
                    />
                </div>
                <div class="barcode-number">{{ $printUnit->barcode }}</div>
                <div class="item_prices">
                    <span>التصنيف: {{ $printItem->inventory_classification_label }}</span>
                    @if($caratLabel)
                        <span>{{ __('main.carats') }}: {{ $caratLabel }}</span>
                    @endif
                    <span>{{ __('main.weight') }}: {{ $printUnit->weight }}</span>
                </div>
            </div>
        @endforeach
    </div>
</body>
<script>
  @if(request('auto_print') == '1')
  window.onload = function() {
    window.print();
  };
  @endif
</script>
</html>
