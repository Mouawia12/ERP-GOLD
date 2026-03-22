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
                    <h4 class="alert alert-primary text-center">إعدادات تسجيل الدخول</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.login-mode.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label class="d-block font-weight-bold mb-3">وضع تسجيل الدخول</label>

                            <div class="custom-control custom-radio mb-3">
                                <input
                                    type="radio"
                                    id="multi_device"
                                    name="login_mode"
                                    value="multi_device"
                                    class="custom-control-input"
                                    {{ $loginMode === 'multi_device' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="multi_device">
                                    السماح بتسجيل الدخول من أكثر من جهاز في نفس الوقت
                                </label>
                            </div>

                            <div class="custom-control custom-radio">
                                <input
                                    type="radio"
                                    id="single_device"
                                    name="login_mode"
                                    value="single_device"
                                    class="custom-control-input"
                                    {{ $loginMode === 'single_device' ? 'checked' : '' }}
                                >
                                <label class="custom-control-label" for="single_device">
                                    السماح بجهاز واحد فقط وإنهاء الجلسة السابقة عند تسجيل دخول جديد
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
