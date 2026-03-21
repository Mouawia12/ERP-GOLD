@extends('admin.layouts.master')
@section('content')
@can('employee.user_permissions.show') 
<!-- Internal Data table css -->
<style>
    i.la {
        font-size: 15px !important;
    }
</style>

    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <!-- row -->
    <div class="row row-md">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex justify-content-between">
                        <div class="col-lg-12 margin-tb">
                            <h4  class="alert alert-info text-center">
                                عرض صلاحيات المستخدمين
                            </h4>
                        </div>
                        <br>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover" id="example-table">
                            <thead>
                            <tr>
                                <th class="text-center">#</th>
                                <th class="text-center">اسم المجموعة</th>
                                <th class="text-center">تحكم</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($roles as $key => $role) 
                                <tr class="text-center">
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $role->name }}</td>
                                    <td>
                                        @can('employee.user_permissions.edit')
                                            <a class="btn btn-info btn-md"
                                               href="{{ route('admin.roles.edit', $role->id) }}"><i
                                                    class="fa fa-edit"></i></a>
                                        @endcan
                                        @can('employee.user_permissions.delete')
                                            @if (auth()->user()->is_admin)
                                                <a class="modal-effect btn btn-md btn-danger delete_role"
                                                   role_id="{{ $role->id }}"
                                                   role_name="{{ $role->name }}" data-toggle="modal" href="#modaldemo9"
                                                   title="Delete"><i
                                                        class="fa fa-trash"></i></a>
                                            @endif
                                        @endcan
                                    </td>
                                </tr> 
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--/div-->
        <!-- Modal effects -->
        <div class="modal" id="modaldemo9">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header text-center">
                        <h6 class="modal-title w-100"
                            style="font-family: 'Almarai'; ">حذف صلاحية ؟</h6>
                        <button aria-label="Close" class="close"
                                data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
                    </div>
                    <form action="{{ route('admin.roles.destroy', 'test') }}" method="post">
                        {{ method_field('delete') }}
                        {{ csrf_field() }}
                        <div class="modal-body">
                            <p>هل انت متأكد انك تريد الحذف ؟</p><br>
                            <input type="hidden" name="role_id" id="role_id" value="">
                            <input class="form-control" name="rolename" id="rolename" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-dismiss="modal">اغلاق</button>
                            <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
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
        $('.delete_role').on('click', function () {
            var role_id = $(this).attr('role_id');
            var role_name = $(this).attr('role_name');
            $('.modal-body #role_id').val(role_id);
            $('.modal-body #rolename').val(role_name);
        });
        $('#example-table').DataTable({
            "columnDefs": [
                {"orderable": false, "targets": [2]}
            ],
            "order": [[0, "asc"]],
        });
    });
</script>
@endsection 
