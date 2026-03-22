@extends('admin.layouts.master')

@section('content')
    @if (count($errors) > 0)
        <div class="alert alert-danger">
            <strong>Errors :</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

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
                                    value="{{ $role->name }}"
                                    readonly
                                    name="name"
                                    class="form-control text-center"
                                    style="font-size:16px;"
                                >
                            </div>
                        </div>
                    </div>

                    @include('admin.roles.partials.permissions-matrix')

                    <div class="row">
                        <div class="col-xs-12 col-md-12 text-center">
                            <button type="submit" class="btn btn-primary">
                                <i class="fa fa-edit"></i>
                                تأكيد وتعديل الصلاحيات
                            </button>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {!! Form::close() !!}
@endsection
