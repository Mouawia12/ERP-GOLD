@extends('admin.layouts.master')

@section('content')
@can('employee.users.edit')
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>الاخطاء:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12 col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="col-lg-12 margin-tb">
                        <h4 class="alert alert-warning text-center" style="color:#fff;">
                            تعديل بيانات المستخدم
                        </h4>
                        <div class="clearfix"></div>
                    </div>
                    <br>

                    <form action="{{ route('admin.users.update', $user->id) }}" method="post" enctype="multipart/form-data">
                        @method('PATCH')
                        @csrf

                        <div class="row mb-3 mt-3">
                            <div class="parsley-input col-md-4" id="fnWrapper">
                                <label>اسم المستخدم</label>
                                <input
                                    class="form-control mg-b-20"
                                    data-parsley-class-handler="#lnWrapper"
                                    name="name"
                                    required
                                    type="text"
                                    value="{{ old('name', $user->name) }}"
                                >
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="emailWrapper">
                                <label>البريد الالكتروني</label>
                                <input
                                    class="form-control mg-b-20"
                                    data-parsley-class-handler="#emailWrapper"
                                    name="email"
                                    required
                                    type="email"
                                    value="{{ old('email', $user->email) }}"
                                >
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="roleWrapper">
                                <label>الصلاحية</label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الصلاحية"
                                        class="form-control selectpicker" name="role_id" id="role_id">
                                    @foreach ($roles as $role)
                                        <option
                                            value="{{ $role->id }}"
                                            @selected(in_array($role->id, old('role_id') ? [old('role_id')] : $userRole))
                                        >
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 mt-3">
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="passwordWrapper">
                                <label>كلمة المرور</label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword" id="basic-addon1">
                                            <i class="fa fa-eye basic-addon1"></i>
                                        </span>
                                    </div>
                                    <input
                                        id="password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror text-left"
                                        dir="ltr"
                                        name="password"
                                        aria-describedby="basic-addon1"
                                    >
                                </div>
                                <small class="text-muted">اترك الحقل فارغًا إذا لم تكن تريد تغيير كلمة المرور.</small>
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="confirmPasswordWrapper">
                                <label>تأكيد كلمة المرور</label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword2" id="basic-addon2">
                                            <i class="fa fa-eye basic-addon2"></i>
                                        </span>
                                    </div>
                                    <input
                                        id="confirm-password"
                                        type="password"
                                        class="form-control @error('password') is-invalid @enderror text-left"
                                        dir="ltr"
                                        name="confirm-password"
                                        aria-describedby="basic-addon2"
                                    >
                                </div>
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="branchWrapper">
                                <label class="form-label">الفرع</label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الفرع"
                                        class="form-control selectpicker" name="branch_id" id="branch_id" required>
                                    @foreach ($branches as $branch)
                                        <option
                                            value="{{ $branch->id }}"
                                            @selected(old('branch_id', $user->branch_id) == $branch->id)
                                        >
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3 mt-3">
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="statusWrapper">
                                <label class="form-label">حالة المستخدم</label>
                                <select class="form-control" name="status" id="status">
                                    <option value="1" @selected(old('status', (int) $user->status) == 1)>مفعل</option>
                                    <option value="0" @selected(old('status', (int) $user->status) == 0)>غير مفعل</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-12 text-center mt-3 mb-3">
                            <button class="btn btn-info btn-md" type="submit">
                                <i class="fa fa-edit"></i>
                                تعديل
                            </button>
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
            if ($("#password").attr("type") === "password") {
                $("#password").attr("type", "text");
                $(".showPassword").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            } else {
                $("#password").attr("type", "password");
                $(".showPassword").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            }
        });

        $(".showPassword2").click(function () {
            if ($("#confirm-password").attr("type") === "password") {
                $("#confirm-password").attr("type", "text");
                $(".showPassword2").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            } else {
                $("#confirm-password").attr("type", "password");
                $(".showPassword2").find('i.fa').toggleClass('fa-eye fa-eye-slash');
            }
        });
    </script>
@endsection
