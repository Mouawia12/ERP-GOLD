@extends('admin.layouts.master')

@section('content')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="d-flex flex-wrap justify-content-between align-items-center">
                        <h4 class="alert alert-primary text-center flex-grow-1 mb-0">قائمة المشتركين</h4>
                        @can('employee.subscribers.add')
                            <a href="{{ route('admin.subscribers.create') }}" class="btn btn-info mt-2 mt-md-0">
                                <i class="fa fa-plus"></i>
                                إضافة مشترك
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive hoverable-table">
                        <table class="table table-bordered table-hover display w-100" id="subscribers-table" style="text-align: center;">
                            <thead>
                            <tr>
                                <th>#</th>
                                <th>المشترك</th>
                                <th>بريد الدخول</th>
                                <th>مدير الحساب</th>
                                <th>الفروع</th>
                                <th>المستخدمون</th>
                                <th>الاشتراك</th>
                                <th>الحالة</th>
                                <th>الإعدادات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($subscribers as $subscriber)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $subscriber->name }}</td>
                                    <td>{{ $subscriber->login_email }}</td>
                                    <td>{{ $subscriber->adminUser?->name ?? '-' }}</td>
                                    <td>
                                        {{ $subscriber->branches_count }}
                                        <small class="text-muted d-block">الحد: {{ $subscriber->max_branches }}</small>
                                    </td>
                                    <td>
                                        {{ $subscriber->users_count }}
                                        <small class="text-muted d-block">الحد: {{ $subscriber->max_users }}</small>
                                    </td>
                                    <td>
                                        <div>{{ optional($subscriber->starts_at)->format('Y-m-d') ?? '-' }}</div>
                                        <div>{{ optional($subscriber->ends_at)->format('Y-m-d') ?? '-' }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $subscriber->status ? 'success' : 'danger' }}">
                                            {{ $subscriber->status ? 'نشط' : 'موقوف' }}
                                        </span>
                                        @if($subscriber->is_trial)
                                            <span class="badge badge-warning">تجريبي</span>
                                        @endif
                                    </td>
                                    <td>
                                        @can('employee.subscribers.show')
                                            <a href="{{ route('admin.subscribers.show', $subscriber) }}" class="btn btn-info">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('employee.subscribers.edit')
                                            <a href="{{ route('admin.subscribers.edit', $subscriber) }}" class="btn btn-warning">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">لا يوجد مشتركون بعد.</td>
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

@section('js')
    <script>
        $(document).ready(function () {
            $('#subscribers-table').DataTable({
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                order: [[0, 'asc']],
            });
        });
    </script>
@endsection
