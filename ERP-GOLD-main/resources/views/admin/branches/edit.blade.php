@extends('admin.layouts.master')

@section('content')
@can('employee.branches.edit')
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 25px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 5px;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 0;
        bottom: 0;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

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

    <div class="row">
        <div class="col-lg-12 col-md-12">
            @include('admin.partials.validation-alert', [
                'title' => 'تعذر حفظ التعديلات. صحح الحقول التالية وسيبقى ما كتبته محفوظًا.',
            ])

            <div class="card branch-form-card">
                <div class="card-body" style="padding:5%;">
                    <div class="col-lg-12">
                        <h4 class="alert alert-warning text-center" style="color:#fff;">
                            تعديل بيانات الفرع
                        </h4>
                        <div class="clearfix"></div>
                    </div>
                    <br>

                    {!! Form::model($branch, [
                        'method' => 'PATCH',
                        'enctype' => 'multipart/form-data',
                        'route' => ['admin.branches.update', $branch->id],
                        'class' => 'branch-validation-form',
                        'novalidate' => true,
                        'data-branch-validation-form' => 'edit',
                    ]) !!}

                    @include('admin.branches.partials.form-fields', ['branch' => $branch])

                    <div class="col-md-12">
                        <label>الحالة</label>
                        <label class="switch">
                            <input
                                type="checkbox"
                                id="status"
                                name="status"
                                value="{{ old('status', $branch->status ? 1 : 0) }}"
                                @checked(old('status', $branch->status))
                            >
                            <span class="slider round"></span>
                        </label>
                    </div>

                    <div class="col-lg-12 text-center mt-3 mb-3">
                        <button class="btn btn-info btn-md" type="submit">
                            <i class="fa fa-edit"></i> تعديل
                        </button>
                    </div>

                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
    <script>
        $('#status').click(function () {
            if (document.getElementById("status").value === "1") {
                document.getElementById("status").value = "0";
            } else {
                document.getElementById("status").value = "1";
            }
        });
    </script>
    @include('admin.branches.partials.form-validation-script')
@endsection
