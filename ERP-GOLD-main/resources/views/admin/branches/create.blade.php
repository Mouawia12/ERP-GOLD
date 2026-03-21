@extends('admin.layouts.master') 
@section('content')
@can('employee.branches.add') 
    <!-- main-content closed -->
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

    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary  text-center">
                        اضافة فرع جديد
                    </h4>
                </div>
                <div class="card-body" style="padding:5%;">
                    <form action="{{route('admin.branches.store')}}" method="post"
                          enctype="multipart/form-data">
                        {{csrf_field()}}
                        <div class="row m-t-3 mb-3">
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.name')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="name" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.email')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="email" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.phone')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="phone" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.commercial_register')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="commercial_register" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.tax_number')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="tax_number" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.street_name')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="street_name" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.building_number')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="building_number" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.plot_identification')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="plot_identification" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.country')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="country" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.region')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="region" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.city')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="city" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.district')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="district" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.postal_code')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="postal_code" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.short_address')}} <span class="text-danger"> </span></label>
                            <input  class="form-control mg-b-20" name="short_address" required="" type="text">
                        </div>
                            <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                                <button class="btn btn-info pd-x-20" type="submit">
                                    <i class="fa fa-plus"></i> اضافة
                                </button> 
                            </div> 
                        </div>   
     
                    </form>
                </div>
            </div>
        </div>
    </div> 
@endcan 
@endsection  
