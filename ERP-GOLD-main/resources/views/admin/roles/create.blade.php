@extends('admin.layouts.master')

@section('content')
    @include('admin.partials.validation-alert', [
        'title' => 'تعذر إنشاء مجموعة الصلاحيات بسبب الأخطاء التالية:',
    ])

    <div class="row">
        <div class="col-md-12">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="col-12">
                        <h4 class="alert alert-primary text-center">
                            إضافة مجموعة صلاحيات جديدة
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                    <br>

                    {!! Form::open(['route' => 'admin.roles.store', 'method' => 'POST']) !!}
                    <input type="hidden" value="admin-web" name="guard_name"/>

                    <div class="main-content-label mg-b-5">
                        <div class="row">
                            <div class="col-md-6 col-md-offset-6 mx-auto">
                                <div class="form-group text-center">
                                    <p>اسم مجموعة الصلاحيات</p>
                                    {!! Form::text('name', old('name'), ['class' => 'form-control '.($errors->has('name') ? 'is-invalid' : ''), 'required']) !!}
                                    @error('name')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    <small class="text-muted d-block mt-2">
                                        هذا الاسم يمثل مجموعة جاهزة ستقوم لاحقًا بإسنادها للمستخدمين.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('admin.roles.partials.permissions-matrix')

                    <div class="row">
                        <div class="col-xs-12 col-md-12 text-center">
                            <button type="submit" class="btn btn-info">
                                <i class="fa fa-plus"></i>
                                حفظ مجموعة الصلاحيات
                            </button>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
