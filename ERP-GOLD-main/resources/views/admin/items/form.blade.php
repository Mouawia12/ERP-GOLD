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
                    @php
                        $selectedPublishedBranchIds = isset($item)
                            ? $item->publishedBranches->pluck('id')->map(fn ($id) => (int) $id)->all()
                            : [];
                        $branchSalePriceOverrides = isset($item)
                            ? $item->publishedBranches->mapWithKeys(fn ($branch) => [$branch->id => $branch->pivot->sale_price_per_gram])->all()
                            : [];
                    @endphp

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
                        <div class="col-md-8">
                            <div class="form-group">
                                <label class="d-block">نشر الصنف على الفروع</label>
                                @if(Auth::user()->is_admin)
                                    <div class="border rounded p-2" style="max-height: 220px; overflow-y: auto;">
                                        @foreach($branches as $publicationBranch)
                                            @php
                                                $ownerBranchId = (int) (isset($item) ? $item->branch_id : (old('branch_id', Auth::user()->branch_id ?? 0)));
                                                $branchId = (int) $publicationBranch->id;
                                                $isOwnerBranch = $ownerBranchId === $branchId;
                                                $isPublished = in_array($branchId, $selectedPublishedBranchIds, true) || $isOwnerBranch;
                                            @endphp
                                            <div class="row align-items-center publication-row py-1 border-bottom" data-branch-id="{{ $branchId }}">
                                                <div class="col-md-5">
                                                    <div class="custom-control custom-checkbox">
                                                        <input
                                                            type="checkbox"
                                                            class="custom-control-input publication-checkbox"
                                                            id="publish_branch_{{ $branchId }}"
                                                            data-branch-id="{{ $branchId }}"
                                                            value="{{ $branchId }}"
                                                            @checked($isPublished)
                                                            @disabled($isOwnerBranch)
                                                        >
                                                        <label class="custom-control-label" for="publish_branch_{{ $branchId }}">
                                                            {{ $publicationBranch->name }}
                                                            <span class="badge badge-primary owner-branch-badge" @if(! $isOwnerBranch) style="display: none;" @endif>الفرع المالك</span>
                                                        </label>
                                                    </div>
                                                    @if($isPublished)
                                                        <input type="hidden" name="published_branch_ids[]" value="{{ $branchId }}" class="publication-hidden-input">
                                                    @endif
                                                </div>
                                                <div class="col-md-7">
                                                    <input
                                                        type="number"
                                                        step="any"
                                                        min="0"
                                                        class="form-control publication-price-input"
                                                        name="branch_sale_prices[{{ $branchId }}]"
                                                        placeholder="سعر بيع محلي لهذا الفرع - اختياري"
                                                        value="{{ old('branch_sale_prices.' . $branchId, $branchSalePriceOverrides[$branchId] ?? '') }}"
                                                        @disabled(! $isPublished)
                                                    />
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <small class="text-muted d-block mt-1">يُنشر الصنف على الفرع المالك تلقائيًا، ويمكن تفعيله على فروع أخرى مع سعر بيع محلي اختياري.</small>
                                @else
                                    <input type="hidden" name="published_branch_ids[]" value="{{ Auth::user()->branch_id }}">
                                    <div class="alert alert-light mb-0">
                                        سيتم نشر الصنف تلقائيًا على فرعك الحالي فقط.
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>تصنيف الصنف <span style="color:red; ">*</span></label>
                                <select class="form-control" id="inventory_classification" name="inventory_classification" required>
                                    @foreach($inventoryClassifications as $value => $label)
                                        <option value="{{ $value }}" @selected((isset($item) ? $item->inventory_classification : \App\Models\Item::CLASSIFICATION_GOLD) === $value)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('main.item_type') }} <span
                                        style="color:red; " class="classification-gold-only">*</span> </label>
                                <select class="form-control" id="item_type" name="item_type">
                                    <option value="">select...</option>
                                    @foreach($caratTypes as $caratType)
                                        <option {{isset($item) ? $item->gold_carat_type_id == $caratType->id ? 'selected' : '' : ''}} value="{{$caratType->id}}">{{$caratType->title}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-md-4">
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
                                        style="color:red; " class="classification-gold-only">*</span> </label>
                                <select class="form-control" id="carats_id" name="carats_id">
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
                                        style="color:red; " class="classification-gold-only">*</span> </label>
                                <input type="text" step="any" id="tax" name="tax"
                                       class="form-control"
                                       value="{{isset($item) ? $item->goldCarat?->tax?->rate : ''}}"
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
    const isEditing = Boolean($('#id').val());

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
    
    function fetchTaxForSelectedCarat() {
        var caratId = $("#carats_id").val();

        if (!caratId) {
            $("#tax").val('');
            return;
        }

        var route = "{!!route('carats.show',':id')!!}";
        route = route.replace(':id', caratId);
        $.ajax({
            type: 'get',
            url: route,
            dataType: 'json',
            success: function (response) {
                $("#tax").val(response.tax_percentage);
            }
        });
    }

    function toggleGoldOnlyFields() {
        var classification = $("#inventory_classification").val();
        var isGold = classification === "{{ \App\Models\Item::CLASSIFICATION_GOLD }}";

        $("#carats_id").prop('required', isGold).prop('disabled', !isGold);
        $("#item_type").prop('required', isGold).prop('disabled', !isGold);
        $(".classification-gold-only").toggle(isGold);

        if (!isGold) {
            $("#carats_id").val('');
            $("#item_type").val('');
            $("#tax").val('');
            $("#made_Value").prop('readonly', false);
            return;
        }

        updateMadeValueReadonly();
        fetchTaxForSelectedCarat();
    }

    function updateMadeValueReadonly() {
        var classification = $("#inventory_classification").val();
        var itemType = $("#item_type").val();
        var shouldLockMadeValue = classification === "{{ \App\Models\Item::CLASSIFICATION_GOLD }}" && (itemType == 2 || itemType == 3);
        $("#made_Value").prop('readonly', shouldLockMadeValue);
    }

    function syncPublicationInputs(checkbox) {
        var $checkbox = $(checkbox);
        var row = $checkbox.closest('.publication-row');
        var branchId = $checkbox.data('branch-id');
        var hiddenInput = row.find('.publication-hidden-input');
        var priceInput = row.find('.publication-price-input');

        if ($checkbox.is(':checked')) {
            if (!hiddenInput.length) {
                row.find('.custom-control').append('<input type="hidden" name="published_branch_ids[]" value="' + branchId + '" class="publication-hidden-input">');
            }
            priceInput.prop('disabled', false);
            return;
        }

        hiddenInput.remove();
        priceInput.prop('disabled', true).val('');
    }

    function syncOwnerBranchPublication() {
        var ownerBranchId = parseInt($("#branch_id").val() || 0, 10);

        $(".publication-row").each(function () {
            var row = $(this);
            var branchId = parseInt(row.data('branch-id'), 10);
            var checkbox = row.find('.publication-checkbox');
            var badge = row.find('.owner-branch-badge');

            if (branchId === ownerBranchId) {
                checkbox.prop('checked', true).prop('disabled', true);
                badge.show();
                syncPublicationInputs(checkbox);
                return;
            }

            checkbox.prop('disabled', false);
            badge.hide();
            syncPublicationInputs(checkbox);
        });
    }

    if (!isEditing) {
        var route = "{{route('items.get_code')}}";  
        $.ajax({
            type: 'get',
            url: route,
            dataType: 'json',
            success: function (response) { 
                $("#code").val(response);
            }
        });
    }

    $("#carats_id").change(function (){
        fetchTaxForSelectedCarat();
    });

    $("#inventory_classification").change(function (){
        toggleGoldOnlyFields();
    });

    $("#item_type").change(function (){ 
        updateMadeValueReadonly();
    });

    $(".publication-checkbox").change(function () {
        syncPublicationInputs(this);
    });

    $("#branch_id").change(function () {
        syncOwnerBranchPublication();
    });

    syncOwnerBranchPublication();

    toggleGoldOnlyFields();
});
    
    
    
</script> 
@endsection
