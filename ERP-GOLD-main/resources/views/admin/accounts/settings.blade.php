@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
@can('employee.accounts.show')       
<!-- row opened -->
<div class="row row-sm">
    <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                            {{__('main.account_settings')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div> 
                </div> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('الفرع')}}</th>
                                            <th>{{__('main.safe_account')}}</th>
                                            <th>{{__('main.sales_account')}}</th>
                                            <th>{{__('main.return_sales_account')}}</th>
                                            <th>{{__('main.sales_discount_account')}}</th>
                                            <th>{{__('main.sales_tax_account')}}</th>
                                            <th>{{__('main.purchase_tax_account')}}</th>
                                            <th>{{__('main.cost_account')}}</th>
                                            <th>{{__('main.profit_account')}}</th>
                                            <th>{{__('main.reverse_profit_account')}}</th>
                                            <th>{{__('main.bank_account')}}</th>
                                            <th>{{__('main.made_account')}}</th>
                                            <th>{{__('main.clients_account')}}</th>
                                            <th>{{__('main.suppliers_account')}}</th>
                                            <th>{{__('main.actions')}}</th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($accounts as $account)
                                        <tr>
                                            <td class="text-center">{{$account->id}}</td>
                                            <td class="text-center">{{$account->branch_name}}</td>
                                            <td class="text-center">{{$account->safe_account_name}}</td>
                                            <td class="text-center">{{$account->sales_account_name}}</td>
                                            <td class="text-center">{{$account->return_sales_account_name}}</td>
                                            <td class="text-center">{{$account->sales_discount_account_name}}</td>
                                            <td class="text-center">{{$account->sales_tax_account_name}}</td>
                                            <td class="text-center">{{$account->purchase_tax_account_name}}</td>
                                            <td class="text-center">{{$account->cost_account_name}}</td>
                                            <td class="text-center">{{$account->profit_account_name}}</td>
                                            <td class="text-center">{{$account->reverse_profit_account_name}}</td>
                                            <td class="text-center">{{$account->bank_account_name}}</td>
                                            <td class="text-center">{{$account->made_account_name}}</td>
                                            <td class="text-center">{{$account->clients_account_name}}</td>
                                            <td class="text-center">{{$account->suppliers_account_name}}</td>
                                            <td class="text-center">
                                                <a href="{{route('accounts.settings.edit' , $account -> id)}}">
                                                    <button type="button" class="btn btn-labeled btn-secondary ">
                                                        <i class="fa fa-pen"></i>{{__('main.edit')}}
                                                    </button>
                                                </a>
                                               
                                            </td>
                                        </tr>
                                    @endforeach
                                    </tbody>

                                </table>
                            </div>
                        </div>
                    </div>

                </div>


            </div>
            <!-- /.container-fluid -->

        </div>
        <!-- End of Main Content --> 

    </div>
    <!-- End of Content Wrapper -->

</div>
<!-- End of Page Wrapper -->


<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel"
     aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"
                        style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
                <img src="../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal">
                            <span class="btn-label" style="margin-right: 10px;"><i
                                    class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endcan 
@endsection 
@section('js') 

@endsection 
