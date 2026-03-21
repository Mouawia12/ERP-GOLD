
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8"/>
    <link href="{{asset('css/barcode.css')}}" rel="stylesheet" type="text/css" />
</head>
<body>
    @if(!isset($unit))
    @foreach($item->units as $item_unit)
    <div class="barcode">
        <div class="company_name">{{$item->branch->name}}</div>
        <div class="item_name">{{$item->title}}</div>
        <div class="barcode-img">
            <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($item_unit->barcode, 'C128',1.1,20)}}" alt="barcode"   />
        </div>
        <div class="barcode-number">{{$item_unit->barcode}}</div>
        <div class="item_prices">
            <span>{{__('main.carats')}} : </span>
            <span>{{$item->goldCarat->label}}</span>
        </div>
    </div>
    @endforeach
    @else
    <div class="barcode">
        <div class="company_name">{{$unit->item->branch->name}}</div>
        <div class="item_name">{{$unit->item->title}}</div>
        <div class="barcode-img">
            <img src="data:image/png;base64,{{DNS1D::getBarcodePNG($unit->barcode, 'C128',1.1,20)}}" alt="barcode"   />
        </div>
        <div class="barcode-number">{{$unit->barcode}}</div>
        <div class="item_prices">
            <span>{{__('main.carats')}} : </span>
            <span>{{$unit->item->goldCarat->label}}</span>
        </div>
    </div>
    @endif
</body>
<script>
  window.onload = function() {
    window.print();
  };
</script>
</html>

