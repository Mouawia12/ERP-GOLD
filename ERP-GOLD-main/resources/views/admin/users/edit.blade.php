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
                        @if(request()->filled('return_branch_id'))
                            <input type="hidden" name="return_branch_id" value="{{ request('return_branch_id') }}">
                        @endif

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
                                    <option value="" @selected(blank(old('role_id', $userRole[0] ?? null)))>
                                        بدون دور - صلاحيات مباشرة فقط
                                    </option>
                                    @foreach ($roles as $role)
                                        <option
                                            value="{{ $role->id }}"
                                            @selected(in_array($role->id, old('role_id') ? [old('role_id')] : $userRole))
                                        >
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted d-block mt-1">يمكن إزالة الدور والاعتماد على الصلاحيات المباشرة فقط.</small>
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

                            <div class="parsley-input col-md-6 mg-t-20 mg-md-t-0" id="branchesWrapper">
                                <label class="form-label">الفروع المسموح بها</label>
                                <select
                                    data-live-search="true"
                                    data-style="btn-dark"
                                    title="اختر الفروع"
                                    class="form-control selectpicker"
                                    name="branch_ids[]"
                                    id="branch_ids"
                                    required
                                    multiple
                                    data-actions-box="true"
                                >
                                    @foreach ($branches as $branch)
                                        <option
                                            value="{{ $branch->id }}"
                                            @selected(in_array($branch->id, old('branch_ids', $selectedBranchIds ?? [])))
                                        >
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">يمكن تعديل قائمة الفروع التي يستطيع هذا المستخدم التبديل بينها.</small>
                            </div>

                            <div class="parsley-input col-md-6 mg-t-20 mg-md-t-0" id="branchWrapper">
                                <label class="form-label">الفرع الافتراضي / النشط الحالي</label>
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
                                <small class="text-muted">هذا الفرع سيكون الافتراضي عند تسجيل الدخول، ويمكن تغييره أيضًا من الواجهة إذا كان للمستخدم عدة فروع.</small>
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

                        <div class="card bg-light border mb-4">
                            <div class="card-body">
                                <h5 class="mb-2">صلاحيات مباشرة للمستخدم</h5>
                                <p class="text-muted mb-3">
                                    استخدمها عند الحاجة لإضافة استثناءات على صلاحيات الدور الحالي أو تقييد الوصول عمليًا على مستوى المستخدم.
                                </p>

                                @include('admin.roles.partials.permissions-matrix', [
                                    'permissionGroups' => $permissionGroups,
                                    'selectedPermissions' => $selectedPermissions,
                                    'permissionInputName' => 'direct_permissions[]',
                                    'permissionSearchId' => 'user-direct-permissions-search',
                                    'permissionCheckAllId' => 'user-direct-permissions-check-all',
                                    'permissionUncheckAllId' => 'user-direct-permissions-uncheck-all',
                                    'permissionScope' => 'user-direct-permissions',
                                ])
                            </div>
                        </div>

                        <div class="col-lg-12 text-center mt-3 mb-3">
                            <button class="btn btn-info btn-md" type="submit">
                                <i class="fa fa-edit"></i>
                                تعديل
                            </button>
                            @if(request()->filled('return_branch_id'))
                                <a href="{{ route('admin.branches.show', request('return_branch_id')) }}" class="btn btn-outline-secondary btn-md">
                                    رجوع إلى الفرع
                                </a>
                            @endif
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
        function syncDefaultBranchOptions() {
            const selectedBranches = $('#branch_ids').val() || [];
            const defaultBranchSelect = $('#branch_id');
            const currentValue = defaultBranchSelect.val();

            defaultBranchSelect.find('option').each(function () {
                const optionValue = $(this).attr('value');
                const shouldDisable = selectedBranches.length > 0 && !selectedBranches.includes(optionValue);
                $(this).prop('disabled', shouldDisable);
            });

            if (selectedBranches.length > 0 && !selectedBranches.includes(currentValue)) {
                defaultBranchSelect.val(selectedBranches[0]);
            }

            $('.selectpicker').selectpicker('refresh');
        }

        $(document).ready(function () {
            syncDefaultBranchOptions();

            $('#branch_ids').on('changed.bs.select', function () {
                syncDefaultBranchOptions();
            });
        });

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
