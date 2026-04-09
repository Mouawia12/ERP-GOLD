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
