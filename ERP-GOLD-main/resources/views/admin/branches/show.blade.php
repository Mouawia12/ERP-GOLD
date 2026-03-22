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
                    <h5 class="mb-0">المستخدمون المرتبطون بهذا الفرع</h5>
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
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($branch->users as $linkedUser)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $linkedUser->name }}</td>
                                    <td>{{ $linkedUser->email }}</td>
                                    <td>{{ $linkedUser->roles->pluck('name')->filter()->implode('، ') ?: '-' }}</td>
                                    <td>{{ $linkedUser->status ? 'مفعل' : 'موقوف' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">لا يوجد مستخدمون مرتبطون بهذا الفرع.</td>
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
