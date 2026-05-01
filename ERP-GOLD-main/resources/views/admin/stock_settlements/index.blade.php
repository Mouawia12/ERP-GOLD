@extends('admin.layouts.master')
@section('content')
@can('employee.stock_settlements.show')  
    @include('admin.reports.partials.result_print_styles')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

<style>

    .stock-settlement-index-page {
        overflow-x: hidden;
    }

    .stock-settlement-index-page .card,
    .stock-settlement-index-page .card-body,
    .stock-settlement-index-page .table-responsive,
    .stock-settlement-index-page .dataTables_wrapper {
        width: 100%;
        max-width: 100%;
        min-width: 0;
    }

    .stock-settlement-index-page .card-body.px-0 {
        padding-left: 1rem !important;
        padding-right: 1rem !important;
    }

    .stock-settlement-index-page .table-responsive {
        overflow-x: auto;
        overflow-y: hidden;
    }

    .stock-settlement-index-page .dataTables_wrapper .dataTables_info,
    .stock-settlement-index-page .dataTables_wrapper .dataTables_paginate,
    .stock-settlement-index-page .dataTables_wrapper .dataTables_filter,
    .stock-settlement-index-page .dataTables_wrapper .dataTables_length {
        float: none;
        margin-top: 0 !important;
        padding-top: 0 !important;
        text-align: inherit;
    }

    .stock-settlement-index-page .stock-settlement-index__toolbar-row,
    .stock-settlement-index-page .stock-settlement-index__footer-row {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        justify-content: space-between;
        gap: 12px;
        width: 100%;
        margin-bottom: 14px;
    }

    .stock-settlement-index-page .stock-settlement-index__controls {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        min-width: 0;
    }

    .stock-settlement-index-page .stock-settlement-index__buttons .dt-buttons {
        display: inline-flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        width: auto;
        max-width: 100%;
    }

    .stock-settlement-index-page .stock-settlement-index__buttons .dt-buttons > .btn,
    .stock-settlement-index-page .stock-settlement-index__buttons .dt-buttons > .dt-button {
        margin: 0 !important;
        flex: 0 0 auto;
        width: auto;
        min-width: 42px;
    }

    .stock-settlement-index-page .stock-settlement-index__filter label,
    .stock-settlement-index-page .stock-settlement-index__length label {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        margin-bottom: 0;
    }

    .stock-settlement-index-page .stock-settlement-index__filter input {
        width: 180px !important;
        max-width: 100%;
    }

    .stock-settlement-index-page .stock-settlement-index__length select {
        width: 72px;
    }

    .stock-settlement-index-page .stock-settlement-index__pagination .pagination {
        margin-bottom: 0;
    }

    .stock-settlement-index-page #SalesTable {
        width: 100% !important;
        min-width: 0;
    }

    @media (max-width: 991.98px) {
        .stock-settlement-index-page .stock-settlement-index__toolbar-row,
        .stock-settlement-index-page .stock-settlement-index__footer-row,
        .stock-settlement-index-page .stock-settlement-index__controls {
            align-items: stretch;
        }

        .stock-settlement-index-page .stock-settlement-index__filter,
        .stock-settlement-index-page .stock-settlement-index__length {
            width: 100%;
        }

        .stock-settlement-index-page .stock-settlement-index__filter label,
        .stock-settlement-index-page .stock-settlement-index__length label {
            width: 100%;
        }

        .stock-settlement-index-page .stock-settlement-index__filter input,
        .stock-settlement-index-page .stock-settlement-index__length select {
            width: 100% !important;
        }
    }

    table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
        direction: rtl;
        text-align:center;
    }
    body{
        direction: rtl; 
    } 

</style>   

    <!-- row opened -->
    <div class="row row-sm stock-settlement-index-page">
        <div class="col-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right" >
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                         [ {{__('main.stock_settlements')}} ]
                        </h4>
                    </div> 
                    <div class="clearfix"></div>
                </div> 
                <div class="card-body px-0 pt-0 pb-2"> 
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
                                            <th>الفرع</th>
                                            <th>المستخدم</th>
                                            <th> {{__('اجمالي الكميات')}} </th>
                                            <th>فرق الوزن</th>
                                            <th> {{__('اجمالي المبلغ')}} </th>
                                            <th class="no-print">{{__('main.actions')}}</th>
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

<!-- Scroll to Top Button-->

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

        document.title = "{{__('main.stock_settlements')}}";

        $(document).ready(function () {
    
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
 
            var table = $('#SalesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                autoWidth: false,

                ajax: "{{ route('stock_settlements.index') }}",
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
                        data: 'branch_name',
                        name: 'branch_name'
                    },
                    {
                        data: 'user_name',
                        name: 'user_name'
                    },
                    {
                        data: 'total_quantity',
                        name: 'total_quantity'
                    },
                    {
                        data: 'difference_weight',
                        name: 'difference_weight'
                    },
                    {
                        data: 'net_total',
                        name: 'net_total'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        className: 'no-print',
                        orderable: false,
                        searchable: false
                    },
                ],
                dom: "<'stock-settlement-index__toolbar-row'<'stock-settlement-index__buttons'B><'stock-settlement-index__controls'<'stock-settlement-index__length'l><'stock-settlement-index__filter'f>>>t<'stock-settlement-index__footer-row'<'stock-settlement-index__info'i><'stock-settlement-index__pagination'p>>",
                
                buttons: [
                    {   
                        text: ' @can("employee.stock_settlements.add") <a id="createButton" href="javascript:;" class="text-white"><i class="fa fa-plus"></i></a>  @endcan ',
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
             
                
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                order: [[0, 'desc']]
            });
       
            $(document).on('click', '#createButton', function (event) {   
                window.location = "{{route('stock_settlements.create')}}"; 
            });
        });
</script> 
 
 
@endsection
 
