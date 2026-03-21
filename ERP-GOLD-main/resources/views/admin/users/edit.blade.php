@extends('admin.layouts.master') 
@section('content')
@can('employee.users.edit')   

    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>الاخطاء :</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif


    <!-- row -->
    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-warning  text-center" style="color:#fff;">
                            تعديل بيانات المستخدم
                        </h4>
                        <div class="clearfix"></div>
                    </div>
                    <br>
                    <form action="{{route('admin.users.update',$user->id)}}" method="post"
                          enctype="multipart/form-data">
                        @method('PATCH')
                        {{csrf_field()}}
                    <div class="row mb-3 mt-3">
                        <div class="parsley-input col-md-4" id="fnWrapper">
                            <label> اسم المستخدم : <span class="tx-danger"> </span></label>
                            <input class="form-control mg-b-20"
                                   data-parsley-class-handler="#lnWrapper" name="name" required=""
                                   type="text" value="{{$user->name}}">
                        </div>
                        <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                            <label> البريد الالكترونى : <span class="tx-danger"> </span></label>
                            <input class="form-control mg-b-20"
                                   data-parsley-class-handler="#lnWrapper" name="email" required=""
                                   type="email" value="{{$user->email}}">
                        </div>
                        <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                            <label> الصلاحية </label>
                            <select data-live-search="true" data-style="btn-dark" title="اختر الصلاحية"
                                    class="form-control selectpicker" name="role_id" id="role_id">
                                @foreach($roles as $role)
                                    <option
                                        @if(in_array($role->id, $userRole))
                                        selected
                                        @endif
                                        value="{{$role->id}}">{{$role->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row  mb-3 mt-3">
                        <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                            <label> كلمة المرور : <span class="tx-danger"> </span></label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                        <span class="input-group-text showPassword" id="basic-addon1">
                                            <i class="fa fa-eye basic-addon1"></i>
                                        </span>
                                </div>
                                <input id="password" type="password"
                                       class="form-control @error('password') is-invalid @enderror text-left"
                                       dir="ltr" name="password"
                                       aria-describedby="basic-addon1">
                            </div>
                        </div>

                        <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                            <label> تأكيد كلمة المرور : <span class="tx-danger"> </span></label>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                        <span class="input-group-text showPassword2"
                                              id="basic-addon2">
                                            <i class="fa fa-eye basic-addon2"></i>
                                        </span>
                                </div>
                                <input id="confirm-password" type="password"
                                       class="form-control @error('password') is-invalid @enderror text-left"
                                       dir="ltr" name="confirm-password"
                                       aria-describedby="basic-addon2">
                            </div>
                        </div>
                        <div class="parsley-input branch col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper" style="@if($user->is_admin) display:none; @endif">
                            <label class="form-label"> الفرع </label>
                            <select data-live-search="true" data-style="btn-dark" title="اختر الفرع"
                                    class="form-control selectpicker" name="branch_id" id="branch_id">
                                    @if(empty(Auth::user()->branch_id))
                                        <option value="">كل الفروع</option>
                                    @endif
                                @foreach($branches as $branch)
                                    <option
                                        @if($user ->branch_id == $branch->id)
                                        selected
                                        @endif
                                        value="{{$branch->id}}">{{$branch->name}}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="parsley-input branch col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper" style="@if($user->is_admin) display:none; @endif">
                            <label class="form-label"> حالة المستخدم </label>
                            <select class="form-control" name="status" id="status"> 
                                <option value="1" @if($user->status) selected  @endif>مفعل</option> 
                                <option value="0" @if(!$user->status) selected  @endif>غير مفعل</option> 
                            </select>
                        </div>
                    </div> 
                    <div class="col-lg-12 text-center mt-3 mb-3">
                        <button class="btn btn-info btn-md" type="submit"><i class="fa fa-edit"></i>  تعديل</button>
                    </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- main-content closed -->
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
        // $('#role_name').on('change', function () {
        //     let role_name = $(this).val();
        //     if (role_name == "مدير النظام") {
        //         $('.branch').hide();
        //         $('#branch_id').val('').selectpicker('refresh');
        //         $('#branch_id').attr('required', false);
        //     } else {
        //         $('.branch').show();
        //         $('#branch_id').val('').selectpicker('refresh');
        //         $('#branch_id').attr('required', true);
        //     }
        // });
    </script>

@endsection
