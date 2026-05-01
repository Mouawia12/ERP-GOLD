@extends('admin.layouts.master')
@section('content')
@can('employee.purchase_invoices.show')
    @include('admin.reports.partials.result_print_styles')

    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<style>
    table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
        direction: rtl;
        text-align: center;
    }

    body {
        direction: rtl;
    }
</style>
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right">
                    <div class="col-lg-12 margin-tb">
                        <h4 class="alert alert-primary text-center">
                            [ {{ __('main.purchases_return') }} ]
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display w-100 text-nowrap table-bordered" id="PurchaseReturnTable" style="text-align: center;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{ __('main.bill_no') }}</th>
                                            <th>{{ __('main.date') }}</th>
                                            <th>{{ __('main.purchase_invoice') }}</th>
                                            <th>{{ __('main.supplier') }}</th>
                                            <th>{{ __('قيمة الفاتورة') }}</th>
                                            <th>{{ __('main.total_money') }}</th>
                                            <th>{{ __('main.total_tax') }}</th>
                                            <th class="no-print">{{ __('main.actions') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endcan
@endsection
@section('js')
<script type="text/javascript">
    $(document).ready(function () {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        $('#PurchaseReturnTable').DataTable({
            processing: true,
            responsive: true,
            ajax: "{{ route('purchase_return.index') }}",
            columns: [
                { data: 'id', name: 'id' },
                { data: 'bill_number', name: 'bill_number' },
                { data: 'date', name: 'date' },
                { data: 'parent_invoice', name: 'parent_invoice' },
                { data: 'customer', name: 'customer' },
                { data: 'net_money', name: 'net_money' },
                { data: 'total_money', name: 'total_money' },
                { data: 'tax', name: 'tax' },
                { data: 'action', name: 'action', className: 'no-print', orderable: false, searchable: false },
            ],
            dom: 'lBfrtip',
            buttons: [
                {
                    extend: 'copy',
                    text: '<i title="copy" class="fa fa-copy"></i>',
                },
                {
                    extend: 'excel',
                    text: '<i title="export to excel" class="fa fa-file-excel"></i>',
                },
                {
                    text: '<i title="print" class="fa fa-print"></i>',
                    action: function () {
                        window.ErpPrint.printCurrentPage();
                    },
                },
                {
                    extend: 'colvis',
                    text: '<i title="column visibility" class="fa fa-eye"></i>',
                },
            ],
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'All']],
            order: [[0, 'desc']]
        }).buttons().container().appendTo('#PurchaseReturnTable_wrapper .col-md-6:eq(0)');

        document.title = "{{ __('main.purchases_return') }}";
    });
</script>
@endsection
