@extends('admin.layouts.master')

@section('content')
    <div class="row text-center">
        <div class="col-lg-12 mt-5">
            <p class="alert alert-info alert-md text-center">عرض بيانات الفرع</p>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive hoverable-table mb-4">
                <table class="table table-striped table-condensed table-bordered text-center">
                    <thead>
                    <tr>
                        <th class="border-bottom-0 text-center">اسم الفرع</th>
                        <th class="border-bottom-0 text-center">البريد الالكتروني</th>
                        <th class="border-bottom-0 text-center">رقم الجوال</th>
                        <th class="border-bottom-0 text-center">الرقم الضريبي</th>
                        <th class="border-bottom-0 text-center">العنوان</th>
                        <th class="border-bottom-0 text-center">الحالة</th>
                        <th class="border-bottom-0 text-center">عدد المستخدمين</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ $branch->branch_name }}</td>
                        <td>{{ $branch->email ?: '-' }}</td>
                        <td>{{ $branch->phone ?: '-' }}</td>
                        <td>{{ $branch->tax_number ?: '-' }}</td>
                        <td>{{ $branch->short_address ?: $branch->full_address ?: '-' }}</td>
                        <td>{{ $branch->status ? 'مفعل' : 'موقوف' }}</td>
                        <td>{{ $branch->users_count }}</td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h5 class="mb-0">المستخدمون المرتبطون بهذا الفرع</h5>
                        @can('employee.users.add')
                            <a
                                href="{{ route('admin.users.create', ['branch_id' => $branch->id, 'return_branch_id' => $branch->id]) }}"
                                class="btn btn-sm btn-primary mt-2 mt-md-0"
                            >
                                <i class="fa fa-plus"></i>
                                إضافة مستخدم لهذا الفرع
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive hoverable-table">
                        <table class="table table-striped table-condensed table-bordered text-center">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>اسم المستخدم</th>
                                <th>البريد الالكتروني</th>
                                <th>الصلاحية</th>
                                <th>الحالة</th>
                                <th>الإدارة</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($branch->activeAssignedUsers as $linkedUser)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $linkedUser->name }}</td>
                                    <td>{{ $linkedUser->email }}</td>
                                    <td>
                                        {{ $linkedUser->roles->pluck('name')->filter()->implode('، ') ?: '-' }}
                                        @if($linkedUser->pivot?->is_default)
                                            <span class="badge badge-info">افتراضي</span>
                                        @endif
                                    </td>
                                    <td>{{ $linkedUser->status ? 'مفعل' : 'موقوف' }}</td>
                                    <td>
                                        @can('employee.users.show')
                                            <a
                                                href="{{ route('admin.users.show', $linkedUser->id) }}"
                                                class="btn btn-sm btn-outline-primary"
                                                title="عرض المستخدم"
                                            >
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('employee.users.edit')
                                            <a
                                                href="{{ route('admin.users.edit', ['user' => $linkedUser->id, 'return_branch_id' => $branch->id]) }}"
                                                class="btn btn-sm btn-outline-info"
                                                title="تعديل المستخدم"
                                            >
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan
                                        @cannot('employee.users.show')
                                            @cannot('employee.users.edit')
                                                <span class="text-muted">-</span>
                                            @endcannot
                                        @endcannot
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">لا يوجد مستخدمون مرتبطون بهذا الفرع.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
