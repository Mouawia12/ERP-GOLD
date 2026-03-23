@extends('admin.layouts.master')

@section('content')
@can('employee.users.add')
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

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">اضافة مستخدم جديد</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.users.store') }}" method="post" enctype="multipart/form-data">
                        @csrf
                        @if(!empty($returnBranchId))
                            <input type="hidden" name="return_branch_id" value="{{ $returnBranchId }}">
                        @endif

                        <div class="row m-t-3 mb-3">
                            <div class="parsley-input col-md-4" id="fnWrapper">
                                <label>اسم المستخدم</label>
                                <input
                                    class="form-control mg-b-20"
                                    data-parsley-class-handler="#lnWrapper"
                                    name="name"
                                    value="{{ old('name') }}"
                                    required
                                    type="text"
                                >
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label>البريد الالكتروني</label>
                                <input
                                    class="form-control mg-b-20 @error('email') is-invalid @enderror"
                                    style="text-align: left; direction: ltr;"
                                    data-parsley-class-handler="#lnWrapper"
                                    name="email"
                                    value="{{ old('email') }}"
                                    required
                                    type="email"
                                >

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="roleWrapper">
                                <label class="form-label">الصلاحية</label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الصلاحية"
                                        class="form-control selectpicker" required id="role_id" name="role_id">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>
                                            {{ $role->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="row m-t-3 mb-3">
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
                                        required
                                        aria-describedby="basic-addon1"
                                    >
                                </div>
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
                                        required
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
                                    required
                                    id="branch_ids"
                                    multiple
                                    data-actions-box="true"
                                >
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(in_array($branch->id, old('branch_ids', $selectedBranchIds ?? [])))>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">يمكنك اختيار أكثر من فرع لهذا المستخدم.</small>
                                @if(!empty($returnBranchId))
                                    <small class="text-info d-block mt-1">سيتم إعادتك إلى شاشة هذا الفرع بعد الحفظ.</small>
                                @endif
                            </div>

                            <div class="parsley-input col-md-6 mg-t-20 mg-md-t-0" id="branchWrapper">
                                <label class="form-label">الفرع الافتراضي / النشط أول تسجيل</label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الفرع الافتراضي"
                                        class="form-control selectpicker" name="branch_id" required id="branch_id">
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', $selectedBranchId) == $branch->id)>
                                            {{ $branch->branch_name }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">سيبدأ المستخدم بهذا الفرع ويمكنه التبديل لاحقًا بين الفروع المربوطة به.</small>
                            </div>
                        </div>

                        <div class="card bg-light border mb-4">
                            <div class="card-body">
                                <h5 class="mb-2">صلاحيات مباشرة للمستخدم</h5>
                                <p class="text-muted mb-3">
                                    هذه الصلاحيات تضاف فوق الصلاحيات الموروثة من الدور المختار، وتستخدم للحالات الخاصة فقط.
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
