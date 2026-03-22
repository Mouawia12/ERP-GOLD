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

    <div class="row">
        <div class="col-md-12">
            <div class="card mg-b-20">
                <div class="card-body">
                    <div class="col-12">
                        <h4 class="alert alert-primary text-center">
                            اضافة صلاحية جديدة
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
                                    <p>اسم مجموعة الصلاحية</p>
                                    {!! Form::text('name', old('name'), ['class' => 'form-control', 'required']) !!}
                                </div>
                            </div>
                        </div>
                    </div>

                    @include('admin.roles.partials.permissions-matrix')

                    <div class="row">
                        <div class="col-xs-12 col-md-12 text-center">
                            <button type="submit" class="btn btn-info">
                                <i class="fa fa-plus"></i>
                                تأكيد واضافة الصلاحيات
                            </button>
                        </div>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endsection
