

<div style="margin-bottom: 20px;">
    <button type="button" class="btn btn-success add_barcode_form">{{__('main.add_barcode')}}</button>
    <button type="button" class="btn btn-info print_barcodes">{{__('main.print_barcodes')}}</button>
</div>
<div id="new_barcodes_table" style="display: none;width: 100%;">
<table class="table table-bordered">
        <thead>
            <tr>
                <th style="vertical-align: middle;text-align: center;">{{__('main.count')}}</th>
                <th><input id="count" type="number" step="1" name="count" class="form-control" value="1"></th>
            </tr>
        </thead>
    </table>
<form action="{{ route('items.store_barcodes', $item->id) }}" method="POST" id="new_barcodes_form" style="width: 100%;">
    @csrf

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>#</th>
                <th>{{__('main.weight')}}</th>
            </tr>
        </thead>
        <tbody id="barcodes_table_body">
            <tr>
                <td>1</td>
                <td><input type="number" step="0.001" name="weight[]" class="form-control"></td>
            </tr>
        </tbody>
    </table>

    <button type="submit" class="btn btn-primary" style="float: left;margin-bottom: 20px;">
        {{ __('main.save_btn') }}
    </button>
</form>
</div>

<table class="table table-bordered" id="barcodes_table">
    <thead>
        <tr>
            <th>{{__('main.barcode')}}</th>
            <th>{{__('main.weight')}}</th>
            <th>{{__('main.actions')}}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($item->units??[] as $unit)
        <tr>
            <td>{{$unit->barcode}}</td>
            <td>{{$unit->weight}}</td>
            <td>
                <button type="button" class="btn btn-primary print_barcode" value="{{$unit->id}}">{{__('main.print_barcode')}}</button>
            </td>
        </tr>
        @endforeach
        @if($item->units->count() == 0)
        <tr>
            <td colspan="2">{{__('main.no_data')}}</td>
        </tr>
        @endif
    </tbody>
</table>


<script>
$(document).ready(function () {
    $(document).on('click', '.add_barcode_form', function () {
        $('#new_barcodes_table').show();
    });

    $(document).off('submit', '#new_barcodes_form').on('submit', '#new_barcodes_form', function (e) {
        e.preventDefault();
        let thisme = $(this);
        let href = thisme.attr('action');
        let method = thisme.attr('method');

        $.ajax({
            url: href,
            type: method,
            data: thisme.serialize(),
            success: function (data) {
                console.log(data);
                if (data.status) {
                    $('#barcodeModalBody').html(data.content);
                }
            }
        });
    });

    $(document).on('click', '.print_barcodes', function () {
        window.open("{{ route('items.print_barcodes', $item->id) }}", '_blank');
    });
    $(document).on('click', '.print_barcode', function () {
        let id = $(this).val();
        window.open("{{ route('items.units.print_barcode',':id') }}".replace(':id', id), '_blank');
    });
    $(document).on('keyup', '#count', function () {
        let count = $(this).val();
        let tableBody = $('#barcodes_table_body');
        tableBody.html('');
        for (let i = 1; i <= count; i++) {
            tableBody.append('<tr><td>' + i + '</td><td><input type="number" step="0.001" name="weight[]" class="form-control"></td></tr>');
        }
    });
});
</script>
