@extends('admin.layouts.master')

@section('content')
@can('employee.system_settings.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">الحسابات البنكية</h4>
                    @can('employee.system_settings.edit')
                        <a href="{{ route('admin.system-settings.bank-accounts.create') }}" class="btn btn-info btn-sm">
                            إضافة حساب بنكي
                        </a>
                    @endcan
                </div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-striped mb-0 text-center">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>الفرع</th>
                                <th>الاسم</th>
                                <th>البنك</th>
                                <th>الحساب المحاسبي</th>
                                <th>الدعم</th>
                                <th>الحالة</th>
                                <th>افتراضي</th>
                                <th>إجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bankAccounts as $bankAccount)
                                <tr>
                                    <td>{{ $bankAccount->id }}</td>
                                    <td>{{ $bankAccount->branch?->name ?? '-' }}</td>
                                    <td class="text-right">{{ $bankAccount->account_name }}</td>
                                    <td class="text-right">{{ $bankAccount->bank_name }}</td>
                                    <td class="text-right">{{ $bankAccount->ledgerAccount?->name ?? '-' }}</td>
                                    <td>
                                        @if($bankAccount->supports_credit_card)
                                            <span class="badge badge-info">شبكة</span>
                                        @endif
                                        @if($bankAccount->supports_bank_transfer)
                                            <span class="badge badge-primary">تحويل</span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="badge {{ $bankAccount->is_active ? 'badge-success' : 'badge-secondary' }}">
                                            {{ $bankAccount->is_active ? 'نشط' : 'موقف' }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge {{ $bankAccount->is_default ? 'badge-warning' : 'badge-light' }}">
                                            {{ $bankAccount->is_default ? 'نعم' : 'لا' }}
                                        </span>
                                    </td>
                                    <td>
                                        @can('employee.system_settings.edit')
                                            <a href="{{ route('admin.system-settings.bank-accounts.edit', $bankAccount) }}" class="btn btn-sm btn-outline-primary">
                                                تعديل
                                            </a>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9">لا توجد حسابات بنكية معرفة بعد.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
