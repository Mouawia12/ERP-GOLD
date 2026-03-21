@extends('admin.layouts.master')

@section('content')
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>Errors : </strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {!! Form::model($role, ['method' => 'PATCH','route' => ['admin.roles.update', $role->id]]) !!}
    <!-- row -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="col-12">
                        <h4  class="alert alert-warning  text-center" style="color:#fff;">
                            تعديل مجموعة صلاحيات
							<br>  
						</h4>
                    </div> 

                    <div class="clearfix"></div> 
                    <br>
                    <div class="main-content-label mg-b-5">
                        <div class="row">
                            <div class="form-group col-lg-12 text-center">
                                <p>اسم المجموعة </p> 
                              <input type="text" value="{{$role->name}}" readonly name="name"
                                       placeholder="اسم المجموعة"
                                       class="form-control text-center" style="font-size:16px;">      
                            </div> 
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover text-center">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th class="text-center">اسم الصلاحية</th>
                                <th class="text-center">اضافة</th>
                                <th class="text-center">عرض</th>
                                <th class="text-center">تعديل</th>
                                <th class="text-center">حذف</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach(config('settings.permissions_modules') as $permission)
                                <tr>
                                    <td>1</td>
                                    <td>{{__("dashboard.permissions_modules.$permission")}}</td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" @if(in_array("employee.{$permission}.add", $rolePermissions)) checked @endif value="employee.{{ $permission }}.add">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" @if(in_array("employee.{$permission}.show", $rolePermissions)) checked @endif value="employee.{{$permission}}.show">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" @if(in_array("employee.{$permission}.edit", $rolePermissions)) checked @endif value="employee.{{$permission}}.edit">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" @if(in_array("employee.{$permission}.delete", $rolePermissions)) checked @endif value="employee.{{$permission}}.delete">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                </tr>
                            
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="row">
                        <div class="col-xs-12 col-md-12 col-md-12 text-center">
                            <button type="button" id="check_all" class="btn btn-danger"><i class="fa fa-check"></i> تحديد الكل</button>
                            <button type="submit" class="btn btn-primary"><i class="fa fa-edit"></i> تأكيد وتعديل الصلاحيات</button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <!-- main-content closed -->
    {!! Form::close() !!}

    <!-- main-content closed -->
    <script src="{{asset('assets/js/jquery.min.js')}}"></script>
    <script>
        $('#check_all').click(function () {
            $('input[type=checkbox]').prop('checked', true);
        });
    </script>
@endsection
