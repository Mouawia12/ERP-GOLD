@extends('admin.layouts.master')
@section('content')
@canany(['employee.simplified_tax_invoices.show', 'employee.tax_invoices.show'])  
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

<style>

    table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
        direction: rtl;
        text-align:center;
    }
    body{
        direction: rtl; 
    }

    .paper-size-option {
        border: 1px solid #dbe4ff;
        border-radius: 12px;
        padding: 14px 12px;
        cursor: pointer;
        transition: all 0.15s ease;
        background: #f8fbff;
        text-align: center;
    }

    .paper-size-option:hover {
        border-color: #4f7cff;
        background: #eef4ff;
    }

    .paper-size-option input {
        display: none;
    }

    .paper-size-option .paper-size-title {
        display: block;
        font-weight: 700;
        color: #1e3a8a;
        font-size: 15px;
    }

    .paper-size-option .paper-size-hint {
        display: block;
        margin-top: 4px;
        font-size: 12px;
        color: #64748b;
    }

    .paper-size-option.is-active {
        border-color: #2563eb;
        background: #dbeafe;
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

</style>   

    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right" >
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                         [ @if($type == 'standard')
                            {{__('main.sales_standard')}}
                            @else
                            {{__('main.sales_simplified')}}
                            @endif ]
                        </h4>
                    </div> 
                    <div class="clearfix"></div>
                </div> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-3 mx-3 mt-3">
                        <div class="card-body">
                            <form method="GET" action="{{ route('sales.index', $type) }}">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label class="form-label">الفرع</label>
                                        <select class="form-control" name="branch_id" id="branch_filter">
                                            <option value="">كل الفروع المتاحة</option>
                                            @foreach($branches as $branch)
                                                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>
                                                    {{ $branch->branch_name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">من تاريخ</label>
                                        <input type="date" class="form-control" name="date_from" id="date_from_filter" value="{{ request('date_from') }}">
                                    </div>
                                    <div class="col-md-3 mb-3">
                                        <label class="form-label">إلى تاريخ</label>
                                        <input type="date" class="form-control" name="date_to" id="date_to_filter" value="{{ request('date_to') }}">
                                    </div>
                                    <div class="col-md-2 mb-3 d-flex align-items-end">
                                        <button type="submit" class="btn btn-primary btn-block">تطبيق</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display w-100  text-nowrap table-bordered" id="SalesTable" 
                                   style="text-align: center;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.bill_no')}}</th> 
                                            <th>{{__('التاريخ')}}</th> 
                                            <th> {{__('main.client')}} </th>
                                            <th> {{__('الاجمالي')}} </th>
                                            <th> {{__('المبلغ')}} </th> 
                                            <th> {{__('الضريبة')}} </th>
                                            <th>{{__('main.actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody></tbody>  
                                </table>
                            </div>
                        </div>
                    </div> 
                </div> 
            </div>
            <!-- /.container-fluid --> 
        </div>
        <!-- End of Main Content --> 
    </div>
    <!-- End of Content Wrapper --> 
</div>
<!-- End of Page Wrapper -->

<!-- Scroll to Top Button-->

<div id="salesTablePrintToolbarTemplate" class="d-none">
    <div class="btn-group mr-2" dir="rtl">
        <button type="button" class="btn btn-primary dropdown-toggle" id="salesTablePrintDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            <i class="fa fa-print"></i>
        </button>
        <div class="dropdown-menu">
            <a class="dropdown-item sales-table-print-option" href="#" data-paper-size="a4">طباعة القائمة A4</a>
            <a class="dropdown-item sales-table-print-option" href="#" data-paper-size="a5">طباعة القائمة A5</a>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>

            </div>
            <div class="modal-body" id="smallBody">
                <img src="../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;">
                                <i class="fa fa-check"></i>
                            </span>{{__('main.confirm_btn')}}
                        </button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;">
                                <i class="fa fa-close"></i>
                            </span>{{__('main.cancel_btn')}}
                        </button>
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

        document.title = "{{__('main.pos_sales_list')}}";

        $(document).ready(function () {
            function printSalesTable(paperSize) {
                var tableElement = document.getElementById('SalesTable');
                var titleElement = document.querySelector('#head-right h4');
                var tableWindow = window.open('', '_blank', 'width=1280,height=900');

                if (!tableWindow || !tableElement) {
                    return;
                }

                var printTitle = titleElement ? titleElement.textContent.trim() : 'قائمة الفواتير';
                var tableHtml = tableElement.outerHTML;
                var printFontSize = paperSize === 'a5' ? '11px' : '12px';
                var tablePadding = paperSize === 'a5' ? '4px 6px' : '6px 8px';

                tableWindow.document.open();
                tableWindow.document.write(`
                    <!DOCTYPE html>
                    <html dir="rtl">
                    <head>
                        <meta charset="utf-8">
                        <title>${printTitle}</title>
                        <style>
                            @page { size: ${paperSize.toUpperCase()} portrait; margin: 8mm; }
                            body {
                                direction: rtl;
                                margin: 0;
                                color: #000;
                                font-family: Almarai, "DejaVu Sans", sans-serif;
                                font-size: ${printFontSize};
                            }
                            .print-page {
                                padding: 8mm;
                            }
                            .print-title {
                                margin: 0 0 14px;
                                text-align: center;
                                font-size: 16px;
                                font-weight: 700;
                            }
                            table {
                                width: 100%;
                                border-collapse: collapse;
                            }
                            th, td {
                                border: 1px solid #999;
                                padding: ${tablePadding};
                                text-align: center;
                                vertical-align: middle;
                            }
                            thead th {
                                background: #e0e0e0;
                            }
                            th:last-child,
                            td:last-child {
                                display: none;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="print-page">
                            <h1 class="print-title">${printTitle}</h1>
                            ${tableHtml}
                        </div>
                    </body>
                    </html>
                `);
                tableWindow.document.close();

                setTimeout(function () {
                    tableWindow.focus();
                    tableWindow.print();
                }, 250);
            }

            function appendPrintToolbar(buttonContainer) {
                if ($('#salesTablePrintDropdown').length) {
                    return;
                }

                var printToolbar = $('#salesTablePrintToolbarTemplate').children().clone(true, true);
                $(buttonContainer).append(printToolbar);
            }

            function bindPrintToolbarEvents() {
                $(document).on('click', '.sales-table-print-option', function (event) {
                    event.preventDefault();
                    printSalesTable($(this).data('paper-size'));
                });
            }
    
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
 
            var table = $('#SalesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,

                ajax: {
                    url: "{{ route('sales.index', $type) }}",
                    data: function (d) {
                        d.branch_id = $('#branch_filter').val();
                        d.date_from = $('#date_from_filter').val();
                        d.date_to = $('#date_to_filter').val();
                    }
                },
                columns: [
                    {
                        data: 'id', 
                        name: 'id'
                    },
                    {
                        data: 'bill_number',
                        name: 'bill_number'
                    },
                    {
                        data: 'date',
                        name: 'date'
                    }, 
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'net_money',
                        name: 'net_money'
                    },
                    {
                        data: 'total_money',
                        name: 'total_money'
                    },
                    {
                        data: 'tax',
                        name: 'tax'
                    }, 
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
                dom: 'lBfrtip',
                
                buttons: [
                    {   
                        text: ' @canany("employee.simplified_tax_invoices.add", "employee.tax_invoices.add") <a id="createButton" href="javascript:;" class="text-white"><i class="fa fa-plus"></i></a>  @endcan ',
                    }, 
                    {
                        extend: 'excel',
                        text: '<i title="export to excel" class="fa fa-file-excel"></i>',
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                    },  
                ],
             
                
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                order: [[0, 'desc']]
            });

            table.buttons().container().appendTo('#SalesTable_wrapper .col-md-6:eq(0)');
            appendPrintToolbar(table.buttons().container());
            bindPrintToolbarEvents();
       
            $(document).on('click', '#createButton', function (event) {   
                window.location = "{{route('sales.create', $type)}}"; 
            });
        });
</script> 
 
 
@endsection
 
