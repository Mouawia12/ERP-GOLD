@php
    $assignmentTitle = $assignmentTitle ?? 'إسناد الصلاحيات';
    $assignmentDescription = $assignmentDescription ?? 'اختر مجموعة صلاحيات جاهزة أو أضف صلاحيات مباشرة عند الحاجة.';
    $roleFieldName = $roleFieldName ?? 'role_id';
    $roleFieldId = $roleFieldId ?? $roleFieldName;
    $selectedRoleId = old($roleFieldName, $selectedRoleId ?? null);
    $permissionInputName = $permissionInputName ?? 'direct_permissions[]';
    $permissionSearchId = $permissionSearchId ?? 'user-direct-permissions-search';
    $permissionCheckAllId = $permissionCheckAllId ?? 'user-direct-permissions-check-all';
    $permissionUncheckAllId = $permissionUncheckAllId ?? 'user-direct-permissions-uncheck-all';
    $permissionScope = $permissionScope ?? 'user-direct-permissions';
@endphp

<div class="card bg-light border mb-4">
    <div class="card-body">
        <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
            <div class="mb-2">
                <h5 class="mb-1">{{ $assignmentTitle }}</h5>
                <p class="text-muted mb-0">{{ $assignmentDescription }}</p>
            </div>

            @if(!empty($assignmentManageUrl))
                <a href="{{ $assignmentManageUrl }}" class="btn btn-outline-primary btn-sm">
                    <i class="fa fa-key"></i>
                    شاشة إسناد مستقلة
                </a>
            @endif
        </div>

        <div class="row mb-4">
            <div class="col-lg-6 mb-3 mb-lg-0">
                <label class="font-weight-bold">مجموعة الصلاحيات</label>
                <select
                    data-live-search="true"
                    data-style="btn-dark"
                    title="اختر مجموعة الصلاحيات"
                    class="form-control selectpicker @error($roleFieldName) is-invalid @enderror"
                    id="{{ $roleFieldId }}"
                    name="{{ $roleFieldName }}"
                >
                    <option value="" @selected(blank($selectedRoleId))>
                        بدون مجموعة - صلاحيات مباشرة فقط
                    </option>
                    @foreach ($roles as $role)
                        @php
                            $roleName = is_array($role->name)
                                ? ($role->name['ar'] ?? $role->name['en'] ?? reset($role->name))
                                : $role->name;
                        @endphp
                        <option value="{{ $role->id }}" @selected((string) $selectedRoleId === (string) $role->id)>
                            {{ $roleName }}
                        </option>
                    @endforeach
                </select>
                @error($roleFieldName)
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
                <small class="text-muted d-block mt-2">
                    عرّف مجموعة الصلاحيات مرة واحدة من شاشة مجموعات الصلاحيات، ثم اسندها لأي مستخدم من هنا.
                </small>
            </div>

            <div class="col-lg-6">
                <div class="alert alert-light border text-right mb-0">
                    <div class="font-weight-bold mb-1">الصلاحيات المباشرة</div>
                    <div class="text-muted mb-0">
                        استخدمها فقط للاستثناءات الخاصة. الصلاحيات المباشرة تضاف فوق ما يرثه المستخدم من المجموعة المختارة.
                    </div>
                </div>
            </div>
        </div>

        @include('admin.roles.partials.permissions-matrix', [
            'permissionGroups' => $permissionGroups,
            'selectedPermissions' => $selectedPermissions,
            'permissionInputName' => $permissionInputName,
            'permissionSearchId' => $permissionSearchId,
            'permissionCheckAllId' => $permissionCheckAllId,
            'permissionUncheckAllId' => $permissionUncheckAllId,
            'permissionScope' => $permissionScope,
        ])
    </div>
</div>
