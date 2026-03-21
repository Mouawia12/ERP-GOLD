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
    
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
 
            var table = $('#SalesTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,

                ajax: "{{ route('sales.index', $type) }}",
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
                        extend: 'print',
                        text: '<i title="print" class="fa fa-print"></i>',
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                    },  
                ],
             
                
                "lengthMenu": [ [10, 25, 50, -1], [10, 25, 50, "All"] ],
                order: [[0, 'desc']]
            }).buttons().container().appendTo('#ItemTable_wrapper .col-md-6:eq(0)');
       
            $(document).on('click', '#createButton', function (event) {   
                window.location = "{{route('sales.create', $type)}}"; 
            });
        });
</script> 
 
 
@endsection
 