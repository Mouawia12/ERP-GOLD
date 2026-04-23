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
        <div class="col-xl-10 mx-auto">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">الإعدادات الافتراضية لإضافة الأصناف</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info border mb-4">
                        <i class="fa fa-info-circle"></i>
                        القيم المحددة هنا ستُملأ تلقائيًا عند فتح صفحة إضافة صنف جديد، مع إمكانية تعديلها في أي وقت أثناء الإدخال.
                    </div>

                    <form method="POST" action="{{ route('admin.system-settings.default-item-settings.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="row">
                            {{-- تصنيف الصنف --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">تصنيف الصنف الافتراضي</label>
                                    <select name="inventory_classification" class="form-control">
                                        <option value="">-- بدون تحديد --</option>
                                        @foreach($inventoryClassifications as $value => $label)
                                            <option value="{{ $value }}" @selected(($settings['inventory_classification'] ?? '') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- طريقة البيع --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">طريقة البيع الافتراضية</label>
                                    <select name="sale_mode" class="form-control">
                                        <option value="">-- بدون تحديد --</option>
                                        @foreach($saleModes as $value => $label)
                                            <option value="{{ $value }}" @selected(($settings['sale_mode'] ?? '') === $value)>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- نوع العيار --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">نوع العيار الافتراضي</label>
                                    <select name="gold_carat_type_id" class="form-control">
                                        <option value="">-- بدون تحديد --</option>
                                        @foreach($caratTypes as $caratType)
                                            <option value="{{ $caratType->id }}" @selected((string)($settings['gold_carat_type_id'] ?? '') === (string)$caratType->id)>
                                                {{ $caratType->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            {{-- العيار --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">عيار الذهب الافتراضي</label>
                                    <select name="gold_carat_id" class="form-control">
                                        <option value="">-- بدون تحديد --</option>
                                        @foreach($carats as $carat)
                                            <option value="{{ $carat->id }}" @selected((string)($settings['gold_carat_id'] ?? '') === (string)$carat->id)>
                                                {{ $carat->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">يُطبَّق على أصناف الذهب فقط</small>
                                </div>
                            </div>

                            {{-- نوع ما خلا المعدن --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">نوع ما خلا المعدن الافتراضي</label>
                                    <select name="no_metal_type" class="form-control">
                                        <option value="fixed" @selected(($settings['no_metal_type'] ?? 'fixed') === 'fixed')>{{ __('main.no_metal_type1') }}</option>
                                        <option value="percent" @selected(($settings['no_metal_type'] ?? '') === 'percent')>{{ __('main.no_metal_type2') }}</option>
                                    </select>
                                </div>
                            </div>

                            {{-- قيمة ما خلا المعدن --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('main.no_metal') }} الافتراضي</label>
                                    <input type="number" step="any" min="0" name="no_metal" class="form-control"
                                           placeholder="0" value="{{ $settings['no_metal'] ?? '' }}" />
                                </div>
                            </div>

                            {{-- الصنعة --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('main.made_Value') }} / جرام الافتراضي</label>
                                    <input type="number" step="any" min="0" name="labor_cost_per_gram" class="form-control"
                                           placeholder="0" value="{{ $settings['labor_cost_per_gram'] ?? '' }}" />
                                </div>
                            </div>

                            {{-- هامش الربح --}}
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold">{{ __('main.profit_margin_per_gram') }} / جرام الافتراضي</label>
                                    <input type="number" step="any" min="0" name="profit_margin_per_gram" class="form-control"
                                           placeholder="0" value="{{ $settings['profit_margin_per_gram'] ?? '' }}" />
                                </div>
                            </div>
                        </div>

                        @can('employee.system_settings.edit')
                            <hr>
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary btn-md px-5">
                                    <i class="fa fa-save"></i> حفظ الإعدادات الافتراضية
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
