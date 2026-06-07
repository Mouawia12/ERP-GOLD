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
                    <h4 class="alert alert-primary text-center">الإعدادات التلقائية للمبيعات</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border mb-4">
                        <i class="fa fa-info-circle"></i>
                        نوع البيع المحدد هنا سيُختار تلقائيًا عند فتح صفحة إضافة فاتورة مبيعات (مبسطة أو ضريبية)، مع إمكانية تغييره أثناء الإدخال.
                    </div>

                    <form method="POST" action="{{ route('admin.system-settings.default-sales-settings.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="font-weight-bold">نوع البيع الافتراضي</label>
                                    <select name="sale_classification" class="form-control">
                                        @foreach($saleClassifications as $value => $label)
                                            <option value="{{ $value }}" @selected(($settings['sale_classification'] ?? '') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary px-5">
                                    <i class="fa fa-save"></i> حفظ الإعدادات
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
