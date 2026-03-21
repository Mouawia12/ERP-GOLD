@extends('admin.layouts.master')
@section('content')
@can('employee.items.add')
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
            <div class="card-header py-3">
                <div class="row">
                   <div class="col-12"> 
                        <h4  class="alert alert-primary text-center">
                         {{isset($item) ? 'تعديل صنف' : 'اضافة صنف جديد'}}
                        </h4> 
                    </div> 
                </div>  
            </div>
            <div class="card-body">  
                <div class="response_container"></div>
                <form method="POST" action="{{ route('items.store') }}"
                      enctype="multipart/form-data" id="items_form">
                    <input type="hidden" id="form_type" name="form_type" value="1">
                    <input type="hidden" id="id" name="id" value="{{isset($item) ? $item->id : null}}">
                    @csrf

                    <div class="row">
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.code') }} <span style="color:red; ">*</span>
                                </label>
                                <input type="text" id="code" name="code"
                                       class="form-control" value="{{isset($item) ? $item->code : ''}}" required readonly/> 
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label class="d-block">
                                     الفرع
                                </label>
                                @if(Auth::user()->is_admin)
                                    <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id"> 
                                        @foreach($branches as $branch)
                                            <option {{isset($item) ? $item->branch_id == $branch->id ? 'selected' : '' : ''}} value="{{$branch->id}}">{{$branch->name}}</option>
                                        @endforeach
                                    </select>
                                @else
                                    <input class="form-control" type="text" readonly
                                           value="{{Auth::user()->branch->name}}"/>
                                           
                                    <input required class="form-control" type="hidden" id="branch_id"
                                           name="branch_id"
                                           {{isset($item) ? $item->branch_id == Auth::user()->branch_id ? 'selected' : '' : ''}}
                                           value="{{Auth::user()->branch_id}}"/>
                                @endif
                    
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.item_type') }} <span
                                        style="color:red; ">*</span> </label>
                                <select class="form-control" id="item_type" name="item_type" required="" >
                                    @foreach($caratTypes as $caratType)
                                        <option {{isset($item) ? $item->gold_carat_type_id == $caratType->id ? 'selected' : '' : ''}} value="{{$caratType->id}}">{{$caratType->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-md-5">
                            <div class="form-group">
                                <label>{{ __('main.name_ar') }} <span
                                        style="color:red; ">*</span> </label>
                                <input type="text" id="name_ar" name="name_ar" value="{{isset($item) ? $item->getTranslation('title', 'ar') : ''}}"
                                       class="form-control" required />
                            </div>
                        </div>
                        <div class="col-md-3" hidden>
                            <div class="form-group">
                                <label>{{ __('main.name_en') }}  </label>
                                <input type="text" id="name_en" name="name_en" value="{{isset($item) ? $item->getTranslation('title', 'en') : ''}}"
                                       class="form-control"  />
                            </div>
                        </div> 
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.category') }} <span
                                        style="color:red; ">*</span> </label>
                                <select class="js-example-basic-single w-100" id="category_id" name="category_id" required="" >
                                    <option value=""> select...</option>
                                    @foreach($categories as $category)
                                        <option {{isset($item) ? $item->category_id == $category->id ? 'selected' : '' : ''}} value="{{$category -> id}}">{{$category -> getTranslation('title', 'ar')}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.carats') }} <span
                                        style="color:red; ">*</span> </label>
                                <select class="form-control" id="carats_id" name="carats_id" required="" >
                                    <option value=""> select...</option>
                                    @foreach($carats as $carat)
                                        <option {{isset($item) ? $item->gold_carat_id == $carat->id ? 'selected' : '' : ''}}
                                            value="{{$carat -> id}}">{{$carat -> title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.weight') }} <span
                                        style="color:red; ">*</span> </label>
                                <input type="number"  step="any" id="weight" name="weight"
                                       class="form-control"
                                       placeholder="0" @if(@$item && @$item->defaultUnit) readonly @endif value="{{(@$item && @$item->defaultUnit) ? @$item->defaultUnit?->weight : ''}}"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.no_metal') }}  </label>
                                <input type="number" step="any" id="no_metal" name="no_metal"
                                       class="form-control"
                                       placeholder="0" value="{{isset($item) ? $item->no_metal : ''}}"/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.no_metal_type') }} </label>
                                <select class="form-control" id="no_metal_type" name="no_metal_type">
                                    <option {{isset($item) ? $item->no_metal_type == 'fixed' ? 'selected' : '' : ''}} value="fixed">{{__('main.no_metal_type1')}}</option>
                                    <option {{isset($item) ? $item->no_metal_type == 'percent' ? 'selected' : '' : ''}} value="percent">{{__('main.no_metal_type2')}}</option>
                                </select>
                            </div>
                        </div> 
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.stamp_value') }} <span
                                        style="color:red; ">*</span> </label>
                                <input type="text" step="any" id="tax" name="tax"
                                       class="form-control"
                                       value="{{isset($item) ? $item->goldCarat->tax->rate : ''}}"
                                       placeholder="0" readonly/>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.made_Value') }} <span
                                        style="color:red; ">*</span> </label>
                                <input type="number" step="any" id="made_Value" name="labor_cost_per_gram"
                                       class="form-control"
                                       placeholder="0" value="{{isset($item) ? $item->labor_cost_per_gram : ''}}" />
                            </div>
                        </div>  
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.cost') }} / جرام  <span
                                style="color:red; ">*</span></label>
                                <input type="number" step="any" id="cost" name="cost_per_gram"
                                       class="form-control"
                                       placeholder="0" @if(@$item && @$item->defaultUnit) readonly @else required @endif value="{{(@$item && @$item->defaultUnit) ? @$item->defaultUnit?->average_cost_per_gram : ''}}" />
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('main.profit_margin_per_gram') }} / جرام   </label>
                                <input type="number" step="any" id="profit_margin_per_gram" name="profit_margin_per_gram"
                                       class="form-control"
                                       placeholder="0" value="{{isset($item) ? $item->profit_margin_per_gram : ''}}" />
                            </div>
                        </div>              
                    </div>
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>{{ __('main.img') }}</label>
                            <div class="row"> 
                                <div class="col-md-6">
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="img" name="img"
                                               accept="image/png, image/jpeg" >
                                        <label class="custom-file-label" for="img"
                                               id="path">{{__('main.img_choose')}} 
                                        </label>
                                    </div>
                                    <br> 
                                    <span style="font-size: 9pt ; color:gray;">{{ __('main.img_hint') }}</span>

                                </div>
                                <div class="col-md-6 text-right">
                                
                                    <img src="{{asset('assets/img/photo.png')}}" id="profile-img-tag" width="150px"
                                         height="150px" class="profile-img"/>
                                </div>
                            </div>
                            @error('printer')
                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                            @enderror
                        </div>
                        <div class="col-md-6 text-left" style="display: block; margin: 20px auto; text-align: center;">
                            <button type="submit" class="btn btn-labeled btn-primary" id="submit_modal_btn">
                                {{__('main.save_btn')}}
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
@section('js')  

<script type="text/javascript"> 
id = 0;
document.title = "{{__('اضافة صنف جديد')}}";

$(document).ready(function () { 
    $(document).on('submit', '#items_form', function(event) {
            id = 0 ;
            event.preventDefault();
            var thisme = $(this);
            let href = $(this).attr('action');
            let method = $(this).attr('method');
            $.ajax({
                url: href,
                type: method,
                data: $(this).serialize(),
                beforeSend: function() {
                    $('.response_container').html('');
                    $('#loader').show();
                },
                success: function(result) {
                    var message = "<div class='alert alert-success'><ul style='margin: 0;'>";
                    message += "<li>" + result.message + "</li>";
                    message += "</ul></div>";
                    $('.response_container').append(message);
                  setTimeout(function() {
                    $('#createModal').modal("hide");
                    $('.response_container').html('');
                    window.location.reload();
                  }, 2000);
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    var errors = "<div class='alert alert-danger'><ul style='margin: 0;'>";
                    jqXHR.responseJSON.errors.forEach(function(error) {
                        errors += "<li>" + error + "</li>";
                    });
                    errors += "</ul></div>";
                    $('.response_container').append(errors);
                },
                timeout: 8000
            })
        });
    


    var route = "{{route('items.get_code')}}";  
    $.ajax({
        type: 'get',
        url: route,
        dataType: 'json',

        success: function (response) { 
            $("#code").val(response);
        }
    });

    $("#carats_id").change(function (){
        var route = "{!!route('carats.show',':id')!!}";  
        var id = this.value;
        route = route.replace(':id', id);
        $.ajax({
            type: 'get',
            url: route,
            dataType: 'json', 
            success: function (response) { 
                $("#tax").val(response.tax_percentage); 
            }
        });
    });

    $("#item_type").change(function (){ 
        if(this.value == 2  ){
            $("#made_Value").prop('readonly', true);
        } else if(this.value == 3){ 
            $("#made_Value").prop('readonly', true);
        }else{
            $("#made_Value").prop('readonly', false);
        }
    });

    @if(empty(Auth::user()->branch_id))
        $("#branch_id").val(1).trigger("change");  
    @endif
});
    
    
    
</script> 
@endsection
