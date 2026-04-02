@extends('admin.layouts.master')

@section('content')
@can('employee.users.edit')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    @include('admin.partials.validation-alert', [
        'title' => 'تعذر حفظ إسناد الصلاحيات بسبب الأخطاء التالية:',
    ])

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">إسناد صلاحيات للمستخدم</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-lg-4 mb-3">
                            <div class="card h-100 border">
                                <div class="card-body text-right">
                                    <div class="text-muted mb-1">المستخدم</div>
                                    <div class="font-weight-bold">{{ $user->name }}</div>
                                    <div class="text-muted mt-2">{{ $user->email }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <div class="card h-100 border">
                                <div class="card-body text-right">
                                    <div class="text-muted mb-1">الفرع الافتراضي</div>
                                    <div class="font-weight-bold">{{ $user->branch?->branch_name ?? '-' }}</div>
                                    <div class="text-muted mt-2">الحالة: {{ $user->status ? 'مفعل' : 'موقوف' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4 mb-3">
                            <div class="card h-100 border">
                                <div class="card-body text-right">
                                    <div class="text-muted mb-1">الوضع الحالي</div>
                                    <div class="font-weight-bold">المجموعة: {{ $currentRoleName ?? 'بدون مجموعة' }}</div>
                                    <div class="text-muted mt-2">
                                        مباشرة: {{ $directPermissions->count() }} |
                                        موروثة: {{ $rolePermissions->count() }} |
                                        فعالة: {{ $effectivePermissions->count() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.users.permissions.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PATCH')

                        @include('admin.users.partials.permission-assignment-panel', [
                            'roles' => $roles,
                            'permissionGroups' => $permissionGroups,
                            'selectedRoleId' => $selectedRoleId,
                            'selectedPermissions' => $selectedPermissions,
                            'assignmentTitle' => 'تعيين مجموعة الصلاحيات والصلاحيات المباشرة',
                            'assignmentDescription' => 'هذه الشاشة مخصصة فقط لإدارة وصول المستخدم، دون تعديل البريد أو الفروع أو كلمة المرور.',
                            'roleFieldName' => 'role_id',
                            'roleFieldId' => 'assignment_role_id',
                            'permissionInputName' => 'direct_permissions[]',
                            'permissionSearchId' => 'assignment-direct-permissions-search',
                            'permissionCheckAllId' => 'assignment-direct-permissions-check-all',
                            'permissionUncheckAllId' => 'assignment-direct-permissions-uncheck-all',
                            'permissionScope' => 'assignment-direct-permissions',
                        ])

                        <div class="text-center">
                            <button class="btn btn-primary btn-md" type="submit">
                                <i class="fa fa-save"></i>
                                حفظ إسناد الصلاحيات
                            </button>
                            <a href="{{ route('admin.users.show', $user->id) }}" class="btn btn-outline-secondary btn-md">
                                عرض المستخدم
                            </a>
                            <a href="{{ route('admin.users.edit', $user->id) }}" class="btn btn-outline-info btn-md">
                                تعديل بيانات المستخدم
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
