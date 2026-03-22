@extends('admin.layouts.master')

@section('content')
@can('employee.system_settings.show')
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-10 mx-auto">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">تعديل الحساب البنكي</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.bank-accounts.update', $bankAccount) }}">
                        @method('PATCH')
                        @include('admin.settings.bank_accounts._form', ['submitLabel' => 'حفظ التعديلات'])
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
