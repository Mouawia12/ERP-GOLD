@extends('admin.layouts.master') 
@section('content')
@can('employee.users.add')
    <!-- main-content closed -->
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

    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary  text-center">
                        اضافة مستخدم جديد
                    </h4>
                </div>
                <div class="card-body">
                    <form action="{{route('admin.users.store')}}" method="post"
                          enctype="multipart/form-data">
                        {{csrf_field()}}
                        <input type="hidden"  name="remember_token"  value="" />
                                                      
                        <div class="row m-t-3 mb-3">
                            <div class="parsley-input col-md-4" id="fnWrapper">
                                <label> اسم المستخدم <span class="text-danger"> </span></label>
                                <input class="form-control mg-b-20"
                                       data-parsley-class-handler="#lnWrapper" name="name" required=""
                                       type="text">
                            </div>
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> البريد الالكترونى : <span class="text-danger"> </span></label>
                                <input class="form-control  mg-b-20 @error('email') is-invalid @enderror" style="text-align: left;direction:ltr;"
                                       data-parsley-class-handler="#lnWrapper" name="email" required=""
                                       type="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label class="form-label"> الصلاحية </label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الصلاحية"
                                        class="form-control selectpicker" required id="role_id" name="role_id">
                                    @foreach($roles as $role)
                                        <option value="{{$role->id}}">{{$role->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row m-t-3 mb-3">
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> كلمة المرور : <span class="text-danger"> </span></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword" id="basic-addon1">
                                            <i class="fa fa-eye basic-addon1"></i>
                                        </span>
                                    </div>
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror text-left"
                                           dir="ltr" name="password" required
                                           aria-describedby="basic-addon1">
                                </div>
                            </div>
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> تأكيد كلمة المرور : <span class="text-danger"> </span></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword2"
                                              id="basic-addon2">
                                            <i class="fa fa-eye basic-addon2"></i>
                                        </span>
                                    </div>
                                    <input id="confirm-password" type="password"
                                           class="form-control @error('password') is-invalid @enderror text-left"
                                           dir="ltr" name="confirm-password" required
                                           aria-describedby="basic-addon2">
                                </div>
                            </div>
                            <div class="parsley-input branch col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label class="form-label"> الفرع </label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الفرع" 
                                        class="form-control selectpicker" name="branch_id" @if(!empty(Auth::user()->branch_id)) required @endif id="branch_id">
                                    @if(empty(Auth::user()->branch_id))
                                        <option value="">كل الفروع</option>
                                    @endif
                                    @foreach($branches as $branch)
                                        <option value="{{$branch->id}}">{{$branch->name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                            <button class="btn btn-info pd-x-20" type="submit">حفظ</button>
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
        $(".showPassword").click(function () {
            if ($("#password").attr("type") == "password") {
                $("#password").attr("type", "text");
                $(".showPassword").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            } else {
                $("#password").attr("type", "password");
                $(".showPassword").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            }
        });
        $(".showPassword2").click(function () {
            if ($("#confirm-password").attr("type") == "password") {
                $("#confirm-password").attr("type", "text");
                $(".showPassword2").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            } else {
                $("#confirm-password").attr("type", "password");
                $(".showPassword2").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            }
        });
        $('#role_name').on('change',function () {
            let role_name = $(this).val();
            if(role_name == "مدير النظام"){
                $('.branch').hide();
                $('#branch_id').val('').selectpicker('refresh');
                $('#branch_id').attr('required',false);
            }
            else{
                $('.branch').show();
                $('#branch_id').val('').selectpicker('refresh');
                $('#branch_id').attr('required',true);
            }
        });
    </script>
@endsection
