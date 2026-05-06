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
                    <h4 class="alert alert-primary text-center">إعدادات طباعة الفواتير</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border text-center">
                        يتم حفظ هذه الإعدادات للحساب الحالي فقط، ولن تؤثر على إعدادات بقية المستخدمين.
                    </div>

                    <form method="POST" action="{{ route('admin.system-settings.invoice-print.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-3">مقاس الفاتورة</label>
                            @foreach ($availableFormats as $format)
                                <div class="custom-control custom-radio mb-3">
                                    <input
                                        type="radio"
                                        id="format_{{ $format }}"
                                        name="format"
                                        value="{{ $format }}"
                                        class="custom-control-input"
                                        {{ $printSettings['format'] === $format ? 'checked' : '' }}
                                    >
                                    <label class="custom-control-label" for="format_{{ $format }}">
                                        {{ strtoupper($format) }}
                                    </label>
                                </div>
                            @endforeach
                        </div>

	                        <div class="form-group mb-4">
	                            <label class="d-block font-weight-bold mb-3">قالب الطباعة</label>
                            <div class="row">
                                @foreach ($availableTemplates as $templateKey => $templateLabel)
                                    <div class="col-md-4 mb-3">
                                        <div class="card border h-100">
                                            <div class="card-body">
                                                <div class="custom-control custom-radio">
                                                    <input
                                                        type="radio"
                                                        id="template_{{ $templateKey }}"
                                                        name="template"
                                                        value="{{ $templateKey }}"
                                                        class="custom-control-input"
                                                        {{ $printSettings['template'] === $templateKey ? 'checked' : '' }}
                                                    >
                                                    <label class="custom-control-label font-weight-bold" for="template_{{ $templateKey }}">
                                                        {{ $templateLabel }}
                                                    </label>
                                                </div>
                                                <small class="text-muted d-block mt-2">
                                                    @switch($templateKey)
                                                        @case('compact')
                                                            مناسب للطباعة المختصرة والمساحات المحدودة.
                                                            @break
                                                        @case('modern')
                                                            ترويسة أوضح وتباين بصري أعلى للعرض الرسمي.
                                                            @break
                                                        @default
                                                            تنسيق متوازن ومناسب للاستخدام العام.
                                                    @endswitch
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
	                        </div>

	                        <div class="form-group mb-4">
	                            <label class="d-block font-weight-bold mb-3">اتجاه طباعة A5</label>
	                            <div class="row">
	                                @foreach ($availableOrientations as $orientationKey => $orientationLabel)
	                                    <div class="col-md-6 mb-3">
	                                        <div class="card border h-100">
	                                            <div class="card-body">
	                                                <div class="custom-control custom-radio">
	                                                    <input
	                                                        type="radio"
	                                                        id="orientation_{{ $orientationKey }}"
	                                                        name="orientation"
	                                                        value="{{ $orientationKey }}"
	                                                        class="custom-control-input"
	                                                        {{ ($printSettings['orientation'] ?? 'portrait') === $orientationKey ? 'checked' : '' }}
	                                                    >
	                                                    <label class="custom-control-label font-weight-bold" for="orientation_{{ $orientationKey }}">
	                                                        {{ $orientationLabel }}
	                                                    </label>
	                                                </div>
	                                                <small class="text-muted d-block mt-2">
	                                                    @if($orientationKey === 'landscape')
	                                                        مناسب أكثر للطباعة على الأوراق الجاهزة ذات الترويسة والتذييل المطبوعة مسبقًا.
	                                                    @else
	                                                        مناسب للطباعة التقليدية أو عندما تكون مساحة الفاتورة عمودية.
	                                                    @endif
	                                                </small>
	                                            </div>
	                                        </div>
	                                    </div>
	                                @endforeach
	                            </div>
	                        </div>

	                        <div class="form-group mb-4">
	                            <label class="d-block font-weight-bold mb-3">عناصر الطباعة</label>

                            <div class="custom-control custom-checkbox mb-3">
                                <input
                                    type="checkbox"
                                    id="show_header"
                                    name="show_header"
                                    value="1"
                                    class="custom-control-input"
                                    {{ $printSettings['show_header'] ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="show_header">
                                    إظهار رأس الفاتورة
                                </label>
                            </div>

                            <div class="custom-control custom-checkbox">
                                <input
                                    type="checkbox"
                                    id="show_footer"
                                    name="show_footer"
                                    value="1"
                                    class="custom-control-input"
                                    {{ $printSettings['show_footer'] ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="show_footer">
                                    إظهار تذييل الفاتورة
                                </label>
                            </div>
                        </div>

                        @php
                            $dimensions = $printSettings['dimensions'] ?? [];
                            $a4Dim = $dimensions['a4'] ?? [];
                            $a5Dim = $dimensions['a5'] ?? [];
                            $fontScale = $dimensions['font_scale'] ?? 1.0;
                        @endphp

                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-3">
                                ضبط أبعاد الطباعة (بالملي&zwnj;متر)
                            </label>
                            <small class="text-muted d-block mb-3">
                                اضبط الهوامش وارتفاعات الترويسة والتذييل وإزاحة المحتوى لكل مقاس على حدة. اتركها فارغة لاستخدام الافتراضي.
                            </small>

                            <ul class="nav nav-tabs mb-3" role="tablist">
                                <li class="nav-item">
                                    <a class="nav-link active" data-toggle="tab" href="#dim-a4" role="tab">A4</a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link" data-toggle="tab" href="#dim-a5" role="tab">A5</a>
                                </li>
                            </ul>

                            <div class="tab-content border rounded p-3 bg-light">
                                @foreach (['a4' => $a4Dim, 'a5' => $a5Dim] as $fmt => $values)
                                    <div class="tab-pane fade {{ $fmt === 'a4' ? 'show active' : '' }}" id="dim-{{ $fmt }}" role="tabpanel">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <label class="small font-weight-bold">هامش علوي</label>
                                                <input type="number" step="0.5" min="0" max="30"
                                                    name="dimensions[{{ $fmt }}][margin_top]"
                                                    value="{{ $values['margin_top'] ?? '' }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="small font-weight-bold">هامش يميني</label>
                                                <input type="number" step="0.5" min="0" max="30"
                                                    name="dimensions[{{ $fmt }}][margin_right]"
                                                    value="{{ $values['margin_right'] ?? '' }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="small font-weight-bold">هامش سفلي</label>
                                                <input type="number" step="0.5" min="0" max="30"
                                                    name="dimensions[{{ $fmt }}][margin_bottom]"
                                                    value="{{ $values['margin_bottom'] ?? '' }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <label class="small font-weight-bold">هامش يساري</label>
                                                <input type="number" step="0.5" min="0" max="30"
                                                    name="dimensions[{{ $fmt }}][margin_left]"
                                                    value="{{ $values['margin_left'] ?? '' }}"
                                                    class="form-control form-control-sm">
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="small font-weight-bold">ارتفاع الترويسة المطبوعة</label>
                                                <input type="number" step="0.5" min="0" max="80"
                                                    name="dimensions[{{ $fmt }}][header_height]"
                                                    value="{{ $values['header_height'] ?? '' }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="0 = استخدام الترويسة الرقمية">
                                                <small class="text-muted">للورق المطبوع مسبقاً — يحجز فراغاً علوياً</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="small font-weight-bold">ارتفاع التذييل المطبوع</label>
                                                <input type="number" step="0.5" min="0" max="60"
                                                    name="dimensions[{{ $fmt }}][footer_height]"
                                                    value="{{ $values['footer_height'] ?? '' }}"
                                                    class="form-control form-control-sm"
                                                    placeholder="0 = استخدام التذييل الرقمي">
                                                <small class="text-muted">للورق المطبوع مسبقاً — يحجز فراغاً سفلياً</small>
                                            </div>
                                            <div class="col-md-4 mb-3">
                                                <label class="small font-weight-bold">إزاحة محتوى علوية</label>
                                                <input type="number" step="0.5" min="0" max="80"
                                                    name="dimensions[{{ $fmt }}][content_offset_top]"
                                                    value="{{ $values['content_offset_top'] ?? '' }}"
                                                    class="form-control form-control-sm">
                                                <small class="text-muted">تنزل المحتوى للأسفل دون تغيير الهوامش</small>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label class="small font-weight-bold">معامل تكبير الخط</label>
                                    <input type="number" step="0.05" min="0.7" max="1.6"
                                        name="dimensions[font_scale]"
                                        value="{{ $fontScale }}"
                                        class="form-control form-control-sm">
                                    <small class="text-muted">1.00 = الحجم الافتراضي. 1.20 = أكبر بنسبة 20%</small>
                                </div>
                            </div>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center">
                                <button type="submit" class="btn btn-info btn-md">
                                    حفظ إعدادات الطباعة
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
