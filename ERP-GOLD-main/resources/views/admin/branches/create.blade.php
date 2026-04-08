@extends('admin.layouts.master')

@section('content')
@can('employee.branches.add')
    <style>
        .branch-form-card .form-control.is-invalid {
            border: 1px solid #dc3545 !important;
            background: #fff5f5 !important;
        }

        .branch-form-card .invalid-feedback {
            font-size: 13px;
            font-weight: 600;
            text-align: right;
        }
    </style>

    @include('admin.partials.validation-alert', [
        'title' => 'تعذر حفظ الفرع. صحح الحقول التالية وسيبقى ما كتبته محفوظًا.',
    ])

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card branch-form-card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">
                        إضافة فرع جديد
                    </h4>
                </div>
                <div class="card-body" style="padding:5%;">
                    <form
                        action="{{ route('admin.branches.store') }}"
                        method="post"
                        enctype="multipart/form-data"
                        class="branch-validation-form"
                        novalidate
                        data-branch-validation-form="create"
                    >
                        @csrf

                        @include('admin.branches.partials.form-fields')

                        <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                            <button class="btn btn-info pd-x-20" type="submit">
                                <i class="fa fa-plus"></i> إضافة
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
    @include('admin.branches.partials.form-validation-script')
@endsection
