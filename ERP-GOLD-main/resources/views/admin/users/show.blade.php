@extends('admin.layouts.master')

@section('content')
    <div class="row text-center">
        <div class="col-lg-12 mt-5">
            <p class="alert alert-info alert-md text-center">عرض بيانات المستخدم</p>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive hoverable-table">
                <table class="table table-striped table-condensed table-bordered text-center">
                    <thead>
                    <tr>
                        <th class="border-bottom-0 text-center">اسم المستخدم</th>
                        <th class="border-bottom-0 text-center">البريد الالكتروني</th>
                        <th class="border-bottom-0 text-center">الفرع</th>
                        <th class="border-bottom-0 text-center">الصلاحية</th>
                        <th class="border-bottom-0 text-center">الحالة</th>
                        <th class="border-bottom-0 text-center">الصورة الشخصية</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->branch?->branch_name ?? '-' }}</td>
                        <td>{{ $user->roles->pluck('name')->filter()->implode('، ') ?: '-' }}</td>
                        <td>{{ $user->status ? 'مفعل' : 'موقوف' }}</td>
                        <td>
                            @if (empty($user->profile_pic))
                                <img
                                    src="{{ asset('assets/img/avatar.png') }}"
                                    style="width: 70px; height: 70px; border-radius: 100%; padding: 3px; border: 1px solid #aaa; object-fit: cover;"
                                    alt="{{ $user->name }}"
                                >
                            @else
                                <img
                                    src="{{ asset($user->profile_pic) }}"
                                    style="width: 70px; height: 70px; border-radius: 100%; padding: 3px; border: 1px solid #aaa; object-fit: cover;"
                                    alt="{{ $user->name }}"
                                >
                            @endif
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">سجل التعديلات على المستخدم</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mb-0 text-center">
                            <thead>
                            <tr>
                                <th>الحدث</th>
                                <th>الملخص</th>
                                <th>تم بواسطة</th>
                                <th>التاريخ</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($user->auditLogs as $auditLog)
                                <tr>
                                    <td>{{ $auditLog->event_label }}</td>
                                    <td>{{ $auditLog->summary }}</td>
                                    <td>{{ $auditLog->actor?->name ?? '-' }}</td>
                                    <td>{{ $auditLog->created_at?->format('Y-m-d H:i:s') ?? '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-muted">لا توجد تعديلات مسجلة على هذا المستخدم بعد.</td>
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
