@extends('admin.layouts.master')

@section('content')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row">
        <div class="col-lg-12 mb-3">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">بيانات المشترك</h4>
                <div>
                    <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary">رجوع</a>
                    @can('employee.subscribers.edit')
                        <a href="{{ route('admin.subscribers.edit', $subscriber) }}" class="btn btn-primary">تعديل</a>
                    @endcan
                </div>
            </div>
        </div>

        <div class="col-lg-12">
            <div class="table-responsive hoverable-table mb-4">
                <table class="table table-striped table-condensed table-bordered text-center">
                    <thead>
                    <tr>
                        <th>المشترك</th>
                        <th>الكود</th>
                        <th>بريد الدخول</th>
                        <th>هاتف التواصل</th>
                        <th>الحالة</th>
                        <th>فترة الاشتراك</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr>
                        <td>{{ $subscriber->name }}</td>
                        <td>{{ $subscriber->code }}</td>
                        <td>{{ $subscriber->login_email }}</td>
                        <td>{{ $subscriber->contact_phone ?: '-' }}</td>
                        <td>
                            {{ $subscriber->status ? 'نشط' : 'موقوف' }}
                            @if($subscriber->is_trial)
                                <span class="badge badge-warning">تجريبي</span>
                            @endif
                        </td>
                        <td>
                            {{ optional($subscriber->starts_at)->format('Y-m-d') ?? '-' }}
                            /
                            {{ optional($subscriber->ends_at)->format('Y-m-d') ?? '-' }}
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">حساب الدخول الأول</h5>
                </div>
                <div class="card-body">
                    <p><strong>الاسم:</strong> {{ $subscriber->adminUser?->name ?? '-' }}</p>
                    <p><strong>البريد:</strong> {{ $subscriber->adminUser?->email ?? '-' }}</p>
                    <p><strong>الفرع الافتراضي:</strong> {{ $subscriber->adminUser?->branch?->branch_name ?? '-' }}</p>
                    <p class="mb-0"><strong>الحالة:</strong> {{ $subscriber->adminUser?->status ? 'مفعل' : 'موقوف' }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">حدود الحساب</h5>
                </div>
                <div class="card-body">
                    <p><strong>الحد الأقصى للمستخدمين:</strong> {{ $subscriber->max_users }}</p>
                    <p><strong>الحد الأقصى للفروع:</strong> {{ $subscriber->max_branches }}</p>
                    <p><strong>عدد الفروع الحالية:</strong> {{ $subscriber->branches->count() }}</p>
                    <p class="mb-0"><strong>عدد المستخدمين الحالي:</strong> {{ $subscriber->users->count() }}</p>
                </div>
            </div>
        </div>

        <div class="col-lg-12 mt-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">الفروع التابعة للمشترك</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive hoverable-table">
                        <table class="table table-bordered text-center">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>الفرع</th>
                                <th>البريد</th>
                                <th>الهاتف</th>
                                <th>عدد المستخدمين النشطين</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($subscriber->branches as $branch)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $branch->branch_name }}</td>
                                    <td>{{ $branch->email ?: '-' }}</td>
                                    <td>{{ $branch->phone ?: '-' }}</td>
                                    <td>{{ $branch->active_assigned_users_count }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">لا توجد فروع مرتبطة بهذا المشترك.</td>
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
