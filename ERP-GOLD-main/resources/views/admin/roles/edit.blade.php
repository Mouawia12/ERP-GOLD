@extends('admin.layouts.master')

@section('content')
    @include('admin.partials.validation-alert', [
        'title' => 'تعذر تحديث مجموعة الصلاحيات بسبب الأخطاء التالية:',
    ])

    {!! Form::model($role, ['method' => 'PATCH', 'route' => ['admin.roles.update', $role->id]]) !!}
    <div class="row">
        <div class="col-md-12">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="col-12">
                        <h4 class="alert alert-warning text-center" style="color:#fff;">
                            تعديل مجموعة صلاحيات
                        </h4>
                    </div>

                    <div class="clearfix"></div>
                    <br>

                    <div class="main-content-label mg-b-5">
                        <div class="row">
                            <div class="form-group col-lg-12 text-center">
                                <p>اسم المجموعة</p>
                                <input
                                    type="text"
                                    value="{{ old('name', $role->name) }}"
                                    name="name"
                                    class="form-control text-center {{ $errors->has('name') ? 'is-invalid' : '' }}"
                                    style="font-size:16px;"
                                >
                                @error('name')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                                <small class="text-muted d-block mt-2">
                                    غيّر اسم المجموعة إذا أردت، وسيبقى إسنادها للمستخدمين من شاشة المستخدم.
                                </small>
                            </div>
                        </div>
                    </div>

                    @include('admin.roles.partials.permissions-matrix')

                    <div class="row">
                        <div class="col-xs-12 col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-edit"></i>
                                حفظ تحديث مجموعة الصلاحيات
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection
