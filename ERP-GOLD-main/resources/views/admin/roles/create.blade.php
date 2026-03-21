@extends('admin.layouts.master')
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 25px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 5px;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 0px;
        bottom: 0px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

</style>
@section('content')
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>Errors :</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- row -->
    <div class="row">
        <div class="col-md-12">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="col-12">
                        <h4 class="alert alert-primary  text-center">
                            اضافة صلاحية جديدة
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                    <br>
                    {!! Form::open(array('route' => 'admin.roles.store','method'=>'POST')) !!}
                    <input type="hidden" value="admin-web" name="guard_name"/>
                    <div class="main-content-label mg-b-5">
                        <div class="row">
                            <div class="col-md-6  col-md-offset-6 mx-auto">
                                <div class="form-group text-center">
                                    <p> اسم مجموعة الصلاحية </p>
                                    {!! Form::text('name', null, array('class' => 'form-control','required')) !!}
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-bordered table-condensed table-hover text-center">
                            <thead>
                            <tr>
                                <th class="text-center"> #</th>
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
                                            <input type="checkbox" name="permission[]" value="employee.{{$permission}}.add">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" value="employee.{{$permission}}.show">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" value="employee.{{$permission}}.edit">
                                            <span class="slider round"></span>
                                        </label>
                                    </td>
                                    <td>
                                        <label class="switch">
                                            <input type="checkbox" name="permission[]" value="employee.{{$permission}}.delete">
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
                            <button type="submit" class="btn btn-info"><i class="fa fa-plus"></i> تأكيد واضافة الصلاحيات</button>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <!-- main-content closed -->
    <script src="{{asset('assets/js/jquery.min.js')}}"></script>
    <script>
        $('#check_all').click(function () {
            $('input[type=checkbox]').prop('checked', true);
        });
    </script>
@endsection
