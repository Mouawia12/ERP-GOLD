@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<!-- row opened -->
<div class="row row-sm">
    <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0 text-center">
                    <div class="col-lg-12 margin-tb ">
                        <h4  class="alert alert-primary text-center"> 
                        {{__('main.item_list_report')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div> 
                </div>  
            </div>  
                <div class="card-body px-0 pt-0 pb-2">

                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <form   method="POST" action="{{ route('reports.items.list.search') }}"
                                    enctype="multipart/form-data" >
                                @csrf
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group"> 
                                            <label>{{ __('الفرع') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            @if(empty(Auth::user()->branch_id))
                                                <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id">
                                                    <option value="0">جميع الفروع</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{$branch->id}}">{{$branch->name}}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input class="form-control" type="text" readonly
                                                       value="{{Auth::user()->branch->name}}"/>
                                                <input required class="form-control" type="hidden" id="branch_id"
                                                       name="branch_id"
                                                       value="{{Auth::user()->branch_id}}"/>
                                            @endif
                    
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.carats') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select id="karat" name="carat" class="form-control">
                                                <option value="0"> select...</option>
                                                @foreach($carats as $carat)
                                                    <option value="{{$carat -> id}}"> {{$carat -> title}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.category') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select id="category" name="category" class="form-control">
                                                <option value=""> select...</option>
                                                @foreach($categories as $category)
                                                    <option value="{{$category -> id}}"> {{$category -> title}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.code') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="code" name="code" placeholder="كود الصنف" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.name') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="name" name="name" placeholder="إسم الصنف عربي" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.fcode') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="fcode" name="fcode" placeholder="من كود صنف" class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.tcode') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="tcode" name="tcode" placeholder="إلي كود صنف " class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12" style="display: block; margin: 20px auto; text-align: center;">
                                        <button type="submit" class="btn btn-labeled btn-primary"  >
                                            {{__('main.search_btn')}}
                                        </button>
                                    </div>
                                </div>  
                            </form> 
                        </div>
                    </div> 
                </div> 
            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content -->

        <!-- Footer --> 

    </div>
    <!-- End of Content Wrapper -->

</div>

<div class="show_modal">

</div>
<!-- End of Page Wrapper -->
 
@endsection 
 