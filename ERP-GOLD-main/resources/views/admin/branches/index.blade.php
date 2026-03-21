@extends('admin.layouts.master') 
@section('content')
@can('employee.branches.show') 
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    <style>
        tfoot input {
            width: 100%;
            padding: 3px;
            box-sizing: border-box;
        }
    
        .btn-md {
            height: 40px !important;
            min-width: 100px !important;
            padding: 10px !important;
            text-align: center !important;
        }
    
        input[type="checkbox"] {
            width: 20px;
            height: 20px;
        }
    
        span.badge {
            padding: 10px !important;
        }
    </style>
    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-info text-center">
                            عرض كل الفروع
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>
                <div class="row mt-1 mb-1 text-center justify-content-center align-content-center">
				    <a href="{{route('admin.branches.create')}}" role="button" class="btn btn-md btn-info m-1">
                        <i class="fa fa-plus"></i>
                        اضافة
                    </a> 
                </div>
                <div class="card-body p-1 m-1">
                    <table
                        class="table table-condensed table-striped table-hover display w-100 table-bordered"
                        id="example-table"
                        style="text-align: center;">
                        <thead>
                        <tr> 
                            <th class="border-bottom-0 text-center">#</th>
                            <th class="border-bottom-0 text-center">اسم الفرع</th>
                            <th class="border-bottom-0 text-center"> رقم جوال الفرع</th>
                            <th class="border-bottom-0 text-center"> عنوان الفرع</th> 
                            <th class="border-bottom-0 text-center"> الحالة</th>
                            <th style="width: 10%!important;" class="border-bottom-0 text-center"></th>
                        </tr>
                        </thead>
                        <tbody>
                        @php
                            $i = 0;
                        @endphp

                        @foreach ($data as $key => $branch)
                            <tr> 
                                <td>{{ ++$i }}</td>
                                <td>{{ $branch->name}}</td>
                                <td>{{ $branch->phone }}</td>
                                <td>{{ $branch->region }}</td> 
                                <td> 
                                    <input type="checkbox" name="status[]" 
                                        @if($branch->status)
                                            checked 
                                        @endif 
                                        value="{{ $branch->status}}">
                                </td>
                                <td>
                                    <div class="dropdown">
                                        <button type="button" class="btn btn-primary dropdown-toggle"
                                                data-toggle="dropdown">
                                            <i class="fa fa-wrench"></i>
                                            اوامر
                                        </button>
                                        <div class="dropdown-menu">
                                            @can('employee.branches.show')
                                                <a href="{{ route('admin.branches.show', $branch->id) }}"
                                                   class="dropdown-item">
                                                    <i class="fa fa-eye"></i>
                                                    عرض
                                                </a>
                                            @endcan
                                            @can('employee.branches.edit')
                                                <a href="{{ route('admin.branches.edit', $branch->id) }}"
                                                   class="dropdown-item">
                                                    <i class="fa fa-edit"></i>
                                                    تعديل
                                                </a>
                                            @endcan
                                            @can('employee.branches.edit')
                                                <a href="{{ route('admin.branches.zatca', $branch->id) }}"
                                                   class="dropdown-item">
                                                    <i class="fa fa-edit"></i>
                                                    اعدادات الربط مع هيئه الزكاه
                                                </a>
                                            @endcan
                                            @can('employee.branches.delete')
                                                <a class="dropdown-item delete_branch"
                                                   branch_id="{{ $branch->id }}"
                                                   branch_name="{{ $branch->branch_name }}" data-toggle="modal"
                                                   href="#modaldemo8">
                                                    <i class="fa fa-trash"></i>
                                                    حذف
                                                </a>
                                            @endcan
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <!--/div-->

        <!-- Modal effects -->

        <div class="modal" id="modaldemo8">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header text-center">
                        <h6 class="modal-title w-100" style="font-family: 'Almarai'; ">حذف فرع</h6>
                        <button aria-label="Close" class="close"
                                data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form action="{{ route('admin.branches.destroy', 'test') }}" method="post">
                        {{ method_field('delete') }}
                        {{ csrf_field() }}
                        <div class="modal-body">
                            <p>هل انت متأكد انك تريد الحذف ؟</p><br>
                            <input type="hidden" name="branch_id" id="branch_id" value="">
                            <input class="form-control" name="branch_name" id="branch_name" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">الغاء</button>
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

@endcan 
@endsection 
@section('js')  
<script>
    $(document).ready(function () { 
        $('.delete_branch').on('click', function () {
            var branch_id = $(this).attr('branch_id');
            var branch_name = $(this).attr('branch_name');
            $('.modal-body #branch_id').val(branch_id);
            $('.modal-body #branch_name').val(branch_name);
        }); 
        $('#example-table tfoot tr th:nth-child(2)').html('<input class="form-control" type="text" placeholder="اسم الفرع" />');
        $('#example-table tfoot tr th:nth-child(3)').html('<input class="form-control" type="text" placeholder="جوال الفرع" />');
        $('#example-table tfoot tr th:nth-child(4)').html('<input class="form-control" type="text" placeholder="عنوان الفرع" />'); 
        $('#example-table').DataTable({
            "columnDefs": [
                {"orderable": false, "targets": [0,4]}
            ],
            "order": [[1, "desc"]],
            initComplete: function () {
                this.api().columns().every(function () {
                    var that = this;
                    $('input[type="text"]', this.footer()).on('keyup change clear', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                    $('select', this.footer()).on('change', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                });
            }
        });
    });
</script>
@endsection