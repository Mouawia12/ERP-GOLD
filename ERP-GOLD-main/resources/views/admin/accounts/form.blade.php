@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('error') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
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
                                                <option value="{{$value}}"
                                                    @if(isset($account) && ((is_null($account->parent_account_id) && $value == 'parent') || (!is_null($account->parent_account_id) && $value == 'child'))) selected @endif>{{__('main.accounts_categories.'.$value)}}</option>
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
                                </div> 
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>{{ __('main.parent_id') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <select class="js-example-basic-single w-100 @error('brand') is-invalid @enderror" id="parent_id" name="parent_account_id">
                                                <option value="">{{ __('بدون أب — حساب رئيسي') }}</option>
                                                @foreach($accounts as $accountw)
                                                    <option value="{{$accountw->id}}" data-account-type="{{ $accountw->account_type }}" @if(@$account->parent_account_id == $accountw->id) selected @endif>{{$accountw->name . ' - ' . $accountw->code}}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1">تعرض حسابات «قائمة الحساب» المختارة فقط. تغيير الحساب الأب يصرف للحساب كودًا جديدًا تحت الأب الجديد ويعيد ترقيم حساباته الفرعية.</small>
                                            @error('brand')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>{{ __('main.account_department') }}</label>
                                            <select class="form-control" id="department" disabled>
                                                @foreach(config('settings.transfers_sides') as $key => $value)
                                                    <option value="{{$value}}" @if(@$account->transfer_side == $value) selected @endif>{{__('main.transfers_sides.'.$value)}}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1">يُحدَّد تلقائيًا من «قائمة الحساب»: الأصول والالتزامات (الخصوم) وحقوق الملكية مركز مالي، والإيرادات والمصروفات قائمة دخل.</small>
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

    var accountId = {{ isset($account) ? (int) $account->id : 'null' }};
    var currentParentId = {{ isset($account) && $account->parent_account_id ? (int) $account->parent_account_id : 'null' }};
    var currentCode = "{{ @$account->code }}";

    function generateCode(parent_id = null){
        var url = "{{route('accounts.excepted_code')}}";
        $.ajax({
            type: "post", async: false,
            url: url,
            dataType: "json",
            data: {
                parent_id: parent_id,
                account_id: accountId,
                _token: "{{ csrf_token() }}"
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

    // «نوع الحساب» يزامن قائمة الأب فقط ولا يعطّلها: اختيار «رئيسي» يفرّغ الأب،
    // واختيار أب يضبط النوع على «فرعي». تعطيل القائمة كان يمنع تعديل أب أي حساب
    // رئيسي فيبدو الحفظ وكأنه لم ينفّذ.
    $('#account_type').change(function () {
        if ($(this).val() == 'parent') {
            $('#parent_id').val('').trigger('change');
        }
    });


    // «قسم الحساب» مشتق من «قائمة الحساب» بقاعدة محاسبية ثابتة، فيُملأ تلقائيًا
    // ويُعرض معطّلًا. الخادم يشتقّه بنفسه عند الحفظ فلا يُرسَل من الشاشة.
    var sideByList = @json(app(\App\Services\Accounts\AccountStatementSideResolver::class)->map());

    function syncDepartmentWithList() {
        $('#department').val(sideByList[$('#list').val()] || 'not_have');
    }

    $('#list').change(syncDepartmentWithList);
    syncDepartmentWithList();

    // «قائمة الحساب» تحدّد أي الحسابات يصح أن يصبّ فيها هذا الحساب، فلا تُعرض في
    // «يصب في» إلا حسابات القائمة نفسها. «لا يوجد» تعني غير محدّدة فتُعرض الكل.
    var allParentOptions = $('#parent_id option').map(function () {
        return {
            value: this.value,
            text: this.text,
            type: $(this).attr('data-account-type') || ''
        };
    }).get();

    function filterParentsByList() {
        var list = $('#list').val();
        var selected = $('#parent_id').val();
        var $parent = $('#parent_id');

        $parent.empty();

        allParentOptions.forEach(function (option) {
            var keep = option.value === ''
                || !list
                || list === 'not_have'
                || option.type === list
                // الأب المحفوظ يبقى معروضًا دائمًا حتى لا يُفقد بصمت عند التعديل.
                || (currentParentId !== null && option.value === String(currentParentId));

            if (keep) {
                $parent.append(new Option(option.text, option.value, false, option.value === selected));
            }
        });

        if ($parent.val() === selected) {
            $parent.trigger('change.select2');
        } else {
            // سقط الأب المختار خارج القائمة الجديدة، فيُعاد ضبط النوع والكود.
            $parent.trigger('change');
        }
    }

    $('#list').change(filterParentsByList);
    filterParentsByList();

    $('#parent_id').change(function () {
        var parent = $(this).val() || null;

        $('#account_type').val(parent ? 'child' : 'parent');

        // عند التعديل: الكود الحالي يبقى كما هو ما دام الأب لم يتغيّر،
        // ويُعرض الكود الجديد فور اختيار أب مختلف.
        if (accountId && parent == currentParentId) {
            $('#code').val(currentCode);
            return;
        }

        generateCode(parent);
    });
});
</script>
@endsection 