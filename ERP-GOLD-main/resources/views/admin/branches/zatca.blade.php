@extends('admin.layouts.master') 
@section('content')
@can('employee.branches.edit') 
    <!-- row -->
    <div class="row">
        <div class="col-lg-12 col-md-12">
            @if (session('success'))
                <div class="alert alert-success  fade show">
                    <button class="close" data-dismiss="alert" aria-label="Close">×</button>
                    {{ session('success') }}
                </div>
            @endif
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
                           اعدادات الربط مع هيئه الزكاه
                        </h4>
                        <div class="clearfix"></div>
                    </div>
                    <br>
                    {!! Form::model($branch, ['method' => 'PATCH','enctype' => 'multipart/form-data','route' => ['admin.branches.zatca.update', $branch->id]]) !!}
                    <div class="row m-t-3 mb-3">
                        <div class="col-md-4">
                        <label> {{__('dashboard.tax_settings.invoice_type')}} <span class="text-danger"> </span></label>
                            <select name="invoice_type" class="form-control mg-b-20" required="">
                                    @foreach (config('settings.invoices_issuing_types') as $invoice_type)
                                        <option value="{{ $invoice_type }}" {{ $branch->zatca_settings?->invoice_type == $invoice_type ? 'selected' : '' }}>{{__('dashboard.tax_settings.invoices_issuing_types.'.$invoice_type)}}</option>
                                    @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.business_category')}} <span class="text-danger"> </span></label>
                            <input value="{{$branch->zatca_settings?->business_category}}" class="form-control mg-b-20" name="business_category" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.otp')}} <span class="text-danger"> </span></label>
                            <input value="{{$branch->zatca_settings?->otp}}" class="form-control mg-b-20" name="otp" required="" type="text">
                        </div>
                        <div class="col-md-4">
                            <label> {{__('dashboard.tax_settings.stage')}} <span class="text-danger"> </span></label>
                            <select name="stage" class="form-control mg-b-20" required="">
                                @foreach (config('settings.zatca_stages') as $stage)
                                    <option value="{{ $stage }}" {{ $branch->zatca_settings?->zatca_stage == $stage ? 'selected' : '' }}>{{__('dashboard.tax_settings.zatca_stages.'.$stage)}}</option>
                                @endforeach
                            </select>
                        </div>
                        
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
