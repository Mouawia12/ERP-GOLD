@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <style>
        .itemCaRD{
            background: white;
            width: 90%;
            display: block;
            margin: 50px auto;
            border-radius: 25px;
        }
    </style>
<!-- row opened -->
@can('employee.accounts.add')
<div class="row row-sm">
    <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0 text-center">
                    <div class="col-lg-12 margin-tb ">
                        <h4  class="alert alert-primary text-center"> 
                            {{__('main.add_account')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div> 
                </div>  
            </div>  
                <div class="card-body px-0 pt-0 pb-2">
                  <div class="card shadow mb-4"> 
                    <form   method="POST" action="{{ (isset($account)) ? route('accounts.update', $account->id) : route('accounts.store') }}">
                        @csrf

                        <div class="row" style="padding: 20px">

                            <div class="col-md-12 col-sm-12"> 
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.code') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text"  id="code" name="code"
                                                   disabled
                                                   value="{{ @$account->code }}"
                                                   class="form-control @error('code') is-invalid @enderror"
                                                   placeholder="{{ __('main.code') }}"  />
                                            @error('code')
                                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-6" >
                                        <div class="form-group">
                                            <label>{{ __('main.name') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span>  </label>
                                            <input type="text"  id="name" name="name"
                                                   value="{{ @$account->name }}"
                                                   class="form-control @error('name') is-invalid @enderror"
                                                   placeholder="{{ __('main.name') }}"  />
                                            @error('name')
                                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="row"> 

                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.account_type') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select class="form-control @error('type') is-invalid @enderror" id="account_type" name="type" required>
                                            @foreach(config('settings.accounts_categories') as $key => $value)
                                                <option value="{{$value}}" @if(isset($account) && is_null($account->parent_account_id) && $value == 'parent') selected @endif>{{__('main.accounts_categories.'.$value)}}</option>
                                            @endforeach    
                                               
                                            </select>
                                            @error('type')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
									
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label>{{ __('main.parent_id') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select class="js-example-basic-single w-100 @error('brand') is-invalid @enderror" id="parent_id" name="parent_account_id" disabled>
                                                @foreach($accounts as $accountw)
                                                    <option value="{{$accountw->id}}" @if(@$account->parent_account_id == $accountw->id) selected @endif>{{$accountw->name . ' - ' . $accountw->code}}</option>
                                                @endforeach
                                            </select>
                                            @error('brand')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div> 
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>{{ __('main.account_list') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select class="form-control @error('accounts_type') is-invalid @enderror" id="list" name="accounts_type">
                                                @foreach(config('settings.accounts_types') as $key => $value)
                                                    <option value="{{$value}}" @if(@$account->account_type == $value) selected @endif>{{__('main.accounts_types.'.$value)}}</option>
                                                @endforeach
                                            </select>
                                            @error('accounts_type')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>{{ __('main.account_department') }}<span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select class="form-control @error('transfers_side') is-invalid @enderror" id="department" name="transfers_side">
                                                @foreach(config('settings.transfers_sides') as $key => $value)
                                                    <option value="{{$value}}" @if(@$account->transfer_side == $value) selected @endif>{{__('main.transfers_sides.'.$value)}}</option>
                                                @endforeach
                                            </select>
                                            @error('transfers_side')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div> 
                        </div> 
                        <div class="col-12" style="text-align: center;margin:20px auto;">
                            <button type="submit" class="btn btn-labeled btn-primary"  >
                                {{__('main.save_btn')}}
                            </button>
                        </div> 
                    </form>
                </div> 
            </div> 
        </div>
        <!-- /.container-fluid -->
        <input id="local" value="{{Config::get('app.locale')}}" hidden>
    </div>
        <!-- End of Main Content -->
 

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper --> 
@endcan 
@endsection 
@section('js')
<script type="text/javascript">
$(document).ready(function () {

    function generateCode(parent_id = null){
        var url = "{{route('accounts.excepted_code')}}";
        $.ajax({
            type: "post", async: false,
            url: url,
            dataType: "json",
            data: {
                parent_id: parent_id
            },
            success: function (data) {
                if(data.code){
                    $('#code').val(data.code);
                }

            }
        });
    }
    @if(!@$account)
    generateCode();
    @endif

    $('#account_type').change(function () {
        var t = $(this).val();
        $('#parent_id').val(0).trigger('change');
        if (t == 'parent') {
            $('#parent_id').attr('disabled', true);
        }
        else {
            $('#parent_id').attr('disabled', false);
        }
    });


    $('#parent_id').change(function () {
        var parent = $(this).val();
        generateCode(parent);
    });
});
</script>
@endsection 