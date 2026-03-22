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
                    <h4 class="alert alert-primary text-center">إعدادات الشعار الرئيسي</h4>
                </div>
                <div class="card-body">
                    <div class="text-center mb-4">
                        <img
                            src="{{ $brandLogoUrl }}"
                            alt="الشعار الحالي"
                            style="max-width: 220px; max-height: 140px; object-fit: contain;"
                        >
                    </div>

                    <form method="POST" action="{{ route('admin.system-settings.branding.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label for="brand_logo" class="font-weight-bold">اختر شعارًا جديدًا</label>
                            <input
                                type="file"
                                id="brand_logo"
                                name="brand_logo"
                                class="form-control"
                                accept="image/*"
                                required
                            >
                            <small class="text-muted d-block mt-2">
                                سيتم استخدام هذا الشعار في تسجيل الدخول، الهيدر، القائمة الجانبية، والطباعة.
                            </small>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center">
                                <button type="submit" class="btn btn-info btn-md">
                                    حفظ الشعار
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
