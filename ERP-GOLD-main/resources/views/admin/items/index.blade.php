@extends('admin.layouts.master')
@can('employee.items.show')
@section('css')    
    <style>
        table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
            direction: rtl;
            text-align:center;
        }
        body{
            direction: rtl; 
        }

        .hoverable-table tbody .btn {
            margin-left: 2% !important; 
        }
    </style>  
@endsection
@section('content')

@if (session('success'))
    <div class="alert alert-success  fade show">
        <button class="close" data-dismiss="alert" aria-label="Close">×</button>
        {{ session('success') }}
    </div>
@endif  


    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0" id="head-right" >
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                        {{__('main.item_list')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>  
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <div class="table-responsive hoverable-table"> 
                                <table class="display w-100 text-nowrap table-bordered" id="ItemTable" 
                                   style="text-align: center;">
                                    <thead>
                                        <tr class="bg-light">
                                            <th> # </th>  
                                            <th> {{__('main.code')}} </th>
                                            <th> {{__(' الصنف')}} </th>  
                                            <th> {{__('المجموعة')}} </th>
                                            <th> {{__('النوع')}} </th>
                                            <th> {{__('العيار')}} </th>
                                            <th> {{__('الوزن جرام')}} </th>
                                            <th> {{__('المصنعية / جرام')}} </th> 
                                            <th> {{__('الحالة')}} </th>
                                            <th> {{__('main.actions')}} </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    </tbody> 
                                </table> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> 
    <!--/div-->

<!-- Logout Modal-->
<div class="modal fade" id="createModal"  role="dialog" aria-labelledby="paymentModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle">  <span id="item-name"></span> {{__('صنف')}}</label>
                <button type="button" class="close modal-close-btn close-create" data-bs-dismiss="modal"
                        aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="paymentBody">
          
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="deleteModal" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>
                <button type="button" class="close cancel-modal" data-bs-dismiss="modal" aria-label="Close"
                        style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody"> 
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                   <div class="col-12 text-center">
                        <input type="number" step="any" id="id" name="id"
                                       class="form-control" readonly/>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete(1)">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-check"></i></span>{{__('main.confirm_btn')}}
                        </button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-danger cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-close"></i></span>{{__('main.cancel_btn')}}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div> 
<div class="modal fade" id="deleteModal2" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"
                        style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
                <img src="../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete(2)">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="show_modal">

</div>

<div class="modal fade" id="barcodeModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
     <div class="modal-dialog modal-md" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.barcodes_table')}}</label>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"
                        style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="barcodeModalBody">
     
        </div>
    </div>
</div>


@endsection 
@endcan 
@section('js')
<script type="text/javascript">
          $(document).ready(function () {
    
            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });
 
            var table = $('#ItemTable').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,

                ajax: "{{ route('items.index') }}",
                columns: [
                    {
                        data: 'id', 
                        name: 'id'
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'title',
                        name: 'title'
                    },
                    {
                        data: 'category',
                        name: 'category'
                    },
                    {
                        data: 'gold_carat_type',
                        name: 'gold_carat_type'
                    },
                    {
                        data: 'gold_carat',
                        name: 'gold_carat'
                    },
                    {
                        data: 'weight',
                        name: 'weight'
                    },
                    {
                        data: 'labor_cost_per_gram',
                        name: 'labor_cost_per_gram'
                    }, 
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    }, 
                    {
                        @canany(['employee.items.edit','employee.items.delete'])
                            data: 'action',
                            name: 'action',
                            orderable: false,
                            searchable: false
                        @endcan
                    },
                ],
                dom: 'lBfrtip',
                
                buttons: [
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
        });
</script> 
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            reader.onload = function (e) {
                $('#profile-img-tag').attr('src', e.target.result);
            }
            reader.readAsDataURL(input.files[0]);
            document.getElementById('path').innerHTML = input.files[0].name;
        }
    }

    $("#img").change(function () {
        readURL(this);
    });
</script>

<script type="text/javascript">
    let id = 0; 
    $(document).ready(function () {

        id = 0;
        document.title = "{{__('main.item_list')}}";
        $(document).on('click', '#createItem', function (event) {
            window.location = "{{route('items.create')}}";
        });

        $(document).on('click', '.deleteBtn', function (event) {
            id = event.currentTarget.value;
            event.preventDefault();
            let href = $(this).attr('data-attr');
            $.ajax({
                url: href,
                beforeSend: function () {
                    $('#loader').show();
                },
                // return the result
                success: function (result) {
                    $('#deleteModal #id').val(id);
                    $('#deleteModal').modal("show");
                },
                complete: function () {
                    $('#loader').hide();
                },
                error: function (jqXHR, testStatus, error) {
                    console.log(error);
                    alert("Page " + href + " cannot open. Error:" + error);
                    $('#loader').hide();
                },
                 timeout: 30000
            })
        });


        $(document).on('click', '.cancel-modal', function (event) {
            $('#deleteModal').modal("hide");
            id = 0;
        });
        $(document).on('click', '.close-create', function (event) {
            $('#createModal').modal("hide");
            id = 0;
        });
    });

    function confirmDelete(index) {
        let url = "" ;
        if(index == 1){
            url = "{{ route('items.delete', ':id') }}";
        } else {
            url = "{{ route('items.delete', ':id') }}";
        }
        url = url.replace(':id', id);
        document.location.href = url;
    }

    $(document).on('click', '.showBarcodeTable', function (event) {
        event.preventDefault();
   
        let href = $(this).attr('href');
        $.ajax({
            url: href,
            type: 'get',
            beforeSend: function () {
                $('#barcodeModalBody').html('');
                $('#loader').show();
            },
            // return the result
            success: function (result) {
                $('#barcodeModalBody').html(result);
                $('#barcodeModal').modal("show");
            },
            complete: function () {
                $('#loader').hide();
            },
            error: function (jqXHR, testStatus, error) {
                console.log(error);
                alert("Page " + href + " cannot open. Error:" + error);
                $('#loader').hide();
            },
             timeout: 30000
        })
    });
</script> 
@endsection
