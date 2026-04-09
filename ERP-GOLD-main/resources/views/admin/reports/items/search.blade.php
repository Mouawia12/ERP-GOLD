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
                                        @include('admin.reports.partials.branch_filter', [
                                            'branches' => $branches,
                                            'defaultFilters' => $defaultFilters,
                                            'branchFieldId' => 'item_list_branch_ids',
                                            'branchHiddenFieldId' => 'item_list_branch_id',
                                            'branchLabelText' => 'الفرع',
                                        ])
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>تصنيف الصنف</label>
                                            <select id="inventory_classification" name="inventory_classification" class="form-control">
                                                <option value="">الكل</option>
                                                @foreach($inventoryClassifications as $value => $label)
                                                    <option value="{{ $value }}" @selected(($defaultFilters['inventory_classification'] ?? '') === $value)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.carats') }}</label>
                                            <select id="karat" name="carat" class="form-control">
                                                <option value="">الكل</option>
                                                @foreach($carats as $carat)
                                                    <option value="{{$carat -> id}}" @selected(($defaultFilters['carat'] ?? '') == $carat->id)> {{$carat -> title}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.category') }}</label>
                                            <select id="category" name="category" class="form-control">
                                                <option value="">الكل</option>
                                                @foreach($categories as $category)
                                                    <option value="{{$category -> id}}" @selected(($defaultFilters['category'] ?? '') == $category->id)> {{$category -> title}}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.code') }}</label>
                                            <input type="text" id="code" name="code" placeholder="كود الصنف" class="form-control" value="{{ $defaultFilters['code'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.name') }}</label>
                                            <input type="text" id="name" name="name" placeholder="إسم الصنف عربي" class="form-control" value="{{ $defaultFilters['name'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.fcode') }}</label>
                                            <input type="text" id="fcode" name="fcode" placeholder="من كود صنف" class="form-control" value="{{ $defaultFilters['fcode'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>{{ __('main.tcode') }}</label>
                                            <input type="text" id="tcode" name="tcode" placeholder="إلي كود صنف " class="form-control" value="{{ $defaultFilters['tcode'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>الحالة</label>
                                            <select id="status" name="status" class="form-control">
                                                <option value="">الكل</option>
                                                <option value="1" @selected(($defaultFilters['status'] ?? '') === '1')>{{ __('main.state1') }}</option>
                                                <option value="0" @selected(($defaultFilters['status'] ?? '') === '0')>{{ __('main.state2') }}</option>
                                            </select>
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
 
