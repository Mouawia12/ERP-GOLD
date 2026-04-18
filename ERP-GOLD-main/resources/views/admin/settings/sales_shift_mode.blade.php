@extends('admin.layouts.master')

@section('content')
@can('employee.system_settings.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

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
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">إعدادات اعتماد البيع بالشفت</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.sales-shift.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-3">اعتماد عمليات البيع على الشفت</label>

                            <div class="custom-control custom-radio mb-3">
                                <input
                                    type="radio"
                                    id="sales_shift_enabled"
                                    name="sales_shift_mode"
                                    value="enabled"
                                    class="custom-control-input"
                                    {{ $salesShiftMode === 'enabled' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="sales_shift_enabled">
                                    تفعيل الاعتماد على الشفت وإلزام فتح شفت نشط قبل حفظ البيع ومرتجع البيع
                                </label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input
                                    type="radio"
                                    id="sales_shift_disabled"
                                    name="sales_shift_mode"
                                    value="disabled"
                                    class="custom-control-input"
                                    {{ $salesShiftMode === 'disabled' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="sales_shift_disabled">
                                    تعطيل الاعتماد على الشفت والسماح بحفظ البيع ومرتجع البيع بدون شفت
                                </label>
                            </div>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center">
                                <button type="submit" class="btn btn-info btn-md">
                                    حفظ الإعداد
                                </button>
                            </div>
                        @endcan
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
