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
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 0px;
        bottom: 0px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
    span.select2-selection.select2-selection--single{
        padding:2px;
    }

</style>
    <!-- row -->
    <div class="row">
        <div class="col-lg-12 col-md-12">

            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <strong>الاخطاء :</strong>
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif 
            <div class="card">
                <div class="card-body"  style="padding:5%;">
                    <div class="col-lg-12">
                        <h4  class="alert alert-warning  text-center" style="color:#fff;">
                            تعديل بيانات الفرع
                        </h4>
                        <div class="clearfix"></div>
                    </div>
                    <br>
                    {!! Form::model($branch, ['method' => 'PATCH','enctype' => 'multipart/form-data','route' => ['admin.branches.update', $branch->id]]) !!}
                    <div class="row m-t-3 mb-3">
                    <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.name')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->name }}" name="name" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.email')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->email }}" name="email" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.phone')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->phone }}" name="phone" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.commercial_register')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->commercial_register }}" name="commercial_register" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.tax_number')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->tax_number }}" name="tax_number" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.street_name')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->street_name }}" name="street_name" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.building_number')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->building_number }}" name="building_number" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.plot_identification')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->plot_identification }}" name="plot_identification" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.country')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->country }}" name="country" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.region')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->region }}" name="region" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.city')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->city }}" name="city" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.district')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->district }}" name="district" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.postal_code')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->postal_code }}" name="postal_code" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.short_address')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" value="{{ $branch->short_address }}" name="short_address" required="" type="text">
                        </div>
                    </div>
           
                    <div class="col-md-12">
                        <label> الحالة<span class="text-danger"> </span></label>
						  <label class="switch">
                                <input type="checkbox" id="status" name="status" 
                                @if($branch->status)
                                     checked 
                                    @endif
								value="{{$branch->status}}" >	
                                <span class="slider round"></span>
                            </label> 
                    </div> 

                    <div class="col-lg-12 text-center mt-3 mb-3 text-center">
                        <button class="btn btn-info btn-md" type="submit"><i class="fa fa-edit"></i> تعديل</button>
                    </div>
                    {!! Form::close() !!}
                </div>
            </div>
        </div>
    </div>
    <!-- main-content closed -->
@endcan 
@endsection 
@section('js') 
    <script>
        $('#status').click(function () {
			if (document.getElementById("status").value=="1") {
			    document.getElementById("status").value= "0"; 
			}else{
			    document.getElementById("status").value= "1"; 
			} 
        });
    </script>
@endsection
