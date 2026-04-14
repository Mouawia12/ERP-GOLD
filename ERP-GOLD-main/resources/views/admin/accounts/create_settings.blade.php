@extends('admin.layouts.master')
@section('content')
@can('employee.accounts.edit')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0">
                <div class="col-lg-12 margin-tb">
                    <h4 class="alert alert-primary text-center">
                        {{__('main.account_settings')}} / {{__('main.account_settings_create')}}
                    </h4>
                </div>
                <div class="clearfix"></div>
            </div>
            <div class="card-body px-0 pt-0 pb-2">
                <form method="POST" action="{{ route('accounts.settings.store') }}">
                    @csrf
                    <div class="row" style="padding: 20px">
                        <div class="col-md-12 col-sm-12 row">

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('الفرع') }}</label>
                                        <select class="js-example-basic-single w-100" name="branch_id">
                                            <option value="">-- اختر الفرع --</option>
                                            @foreach($branchs as $branch)
                                                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.safe_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="safe_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.bank_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="bank_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.sales_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.return_sales_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="return_sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.stock_account_crafted') }}</label>
                                        <select class="js-example-basic-single w-100" name="stock_account_crafted">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.stock_account_scrap') }}</label>
                                        <select class="js-example-basic-single w-100" name="stock_account_scrap">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.stock_account_pure') }}</label>
                                        <select class="js-example-basic-single w-100" name="stock_account_pure">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.sales_tax_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="sales_tax_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.purchase_tax_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="purchase_tax_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.cost_account_crafted') }}</label>
                                        <select class="js-example-basic-single w-100" name="cost_account_crafted">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.profit_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="profit_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.reverse_profit_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="reverse_profit_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.clients_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="clients_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>{{ __('main.suppliers_account') }}</label>
                                        <select class="js-example-basic-single w-100" name="suppliers_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- حسابات المقتنيات --}}
                            <div class="col-12 mt-3 mb-2">
                                <h5 class="alert alert-warning text-center">حسابات المقتنيات</h5>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مبيعات المقتنيات</label>
                                        <select class="js-example-basic-single w-100" name="collectible_sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مرتجعات مبيعات المقتنيات</label>
                                        <select class="js-example-basic-single w-100" name="collectible_return_sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مشتريات المقتنيات</label>
                                        <select class="js-example-basic-single w-100" name="collectible_purchase_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مرتجعات مشتريات المقتنيات</label>
                                        <select class="js-example-basic-single w-100" name="collectible_purchase_return_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            {{-- حسابات الفضة --}}
                            <div class="col-12 mt-3 mb-2">
                                <h5 class="alert alert-secondary text-center">حسابات الفضة</h5>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مبيعات الفضة</label>
                                        <select class="js-example-basic-single w-100" name="silver_sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مرتجعات مبيعات الفضة</label>
                                        <select class="js-example-basic-single w-100" name="silver_return_sales_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مشتريات الفضة</label>
                                        <select class="js-example-basic-single w-100" name="silver_purchase_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row col-6">
                                <div class="col-12">
                                    <div class="form-group">
                                        <label>حساب مرتجعات مشتريات الفضة</label>
                                        <select class="js-example-basic-single w-100" name="silver_purchase_return_account">
                                            <option value="">-- اختر الحساب --</option>
                                            @foreach($accounts as $account)
                                                <option value="{{ $account->id }}">{{ $account->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                            <button type="submit" class="btn btn-labeled btn-primary">
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
<script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
        var options = { damping: '0.5' }
        Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
</script>
@endsection
