@extends('admin.layouts.master')
<style>
    span.float-right > i.fa {
        font-size: 40px !important;
    }

    h3 {
        font-size: 15px !important;
    }

    <style>
        .quick-button.small {
            padding: 15px 0px 1px 0px;
            font-size: 13px;
            border-radius: 15px !importants;
            box-shadow: rgba(0, 0, 0, 0.35) 0px 5px 15px;
            width: 100%;
            height: 100%;
        }
        .quick-button.small:hover{
            transform: scale(1.1);
        }
        .quick-button {
            margin-bottom: -1px;
            padding: 30px 0px 10px 0px;
            font-size: 15px;
            border-radius: 10px;
            display: block;
            text-align: center;
            cursor: pointer;
            position: relative;
            transition: all 0.3s ease;
            opacity: 0.9;
        }
        .bblue {
            background: darkgoldenrod !important;
        }.white {
             color: white !important;
         }
        .bdarkGreen {
            background: #78cd51 !important;
        }
        .blightOrange {
            background: #fabb3d !important;
        }.bred {
             background: #ff5454 !important;
         }
        .bpink {
            background: #e84c8a !important;
        }
        .bgrey {
            background: #b2b8bd !important;
        }
        .blightBlue {
            background: #5BC0DE !important;
        }
        .padding1010 {
            padding: 10px;
        }

 

</style>
@section('page-header')
    <!-- breadcrumb -->
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1"> 
                </h2>
                <hr>
            </div>
        </div>
    </div>
    <!-- /breadcrumb -->

    <!-- Begin Page Content -->
    <div class="container-fluid">   
        <!-- Content Row -->
        <div class="row mt-3 mb-3"> 
            <div class="col-xl-12"> 
                <div class="card" style="width: 100%; margin: auto"> 
                    <div class="card-body" style="display: flex;flex-flow: wrap;">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                                <thead>
                                    <tr>
                                        <th>{{__('main.ounce')}}</th>
                                        <th>{{__('main.k14')}}</th>
                                        <th> {{__('main.k18')}} </th>
                                        <th> {{__('main.k21')}} </th>
                                        <th> {{__('main.k22')}} </th>
                                        <th> {{__('main.k24')}} </th>
                                        <th> {{__('main.currency')}} </th>
                                        <th> {{__('main.last_update')}} </th>
                                    </tr>
                                </thead>
                                <tbody>
                                @foreach($pricings as $pricing)
                                    <tr>
                                        <td class="text-center">{{number_format($pricing -> ounce_price  , 2)}}</td>
                                        <td class="text-center">{{number_format($pricing -> ounce_14_price  , 2)}}</td>
                                        <td class="text-center">{{number_format($pricing -> ounce_18_price  , 2)}}</td>
                                        <td class="text-center">{{number_format($pricing -> ounce_21_price  , 2)}}</td>
                                        <td class="text-center">{{number_format($pricing -> ounce_22_price  , 2)}}</td>
                                        <td class="text-center">{{number_format($pricing -> ounce_24_price  , 2)}}</td>
                                        <td class="text-center">{{$pricing -> currency}}</td>
                                        <td class="text-center">{{$pricing -> last_update}}</td>
                                    </tr>
                                @endforeach
                                </tbody>
    
                            </table>
                        </div>
                    </div>
                </div> 
            </div>
        </div>
        <div class="row mt-3 mb-3">
            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">  
                <div class="card overflow-hidden sales-card bg-success-gradient"> 
                    <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                        <div class="">
                            <h3 class="mb-3 text-white">مبيعات اليوم
                                <a href="{{route('sales.index','simplified')}}">
                                    <i class="fa fa-plus text-white"></i>
                                </a>
                            </h3>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div class="">
                                    <h1 class="tx-30 font-weight-bold mb-1 text-white">{{round($sales , 2)}}</h1>
                                </div>
                                <span class="float-right my-auto mr-auto"> 
                                    <i class="fa fa-money-bill fa-2x text-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12"> 
                <div class="card overflow-hidden sales-card bg-warning-gradient"> 
                    <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                        <div class="">
                            <h3 class="mb-3 text-white">مرتجع مبيعات اليوم</h3>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div class="">
                                    <h1 class="tx-30 font-weight-bold mb-1 text-white">{{round($sales_return, 2)}}</h1>
                                </div>
                                <span class="float-right my-auto mr-auto">  
                                    <i class="fa-solid fa-money-bill-transfer fa-2x text-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div> 
            </div>
            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12"> 
                <div class="card overflow-hidden sales-card bg-info-gradient"> 
                    <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                        <div class="">
                            <h3 class="mb-3 text-white">مشتريات اليوم</h3>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div class="">
                                    <h1 class="tx-30 font-weight-bold mb-1 text-white">{{round($purchases, 2)}}</h1>
                                </div>
                                <span class="float-right my-auto mr-auto"> 
                                    <i class="fa fa-cart-shopping fa-2x text-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div> 
            </div> 
            <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12"> 
                <div class="card overflow-hidden sales-card bg-danger-gradient"> 
                    <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                        <div class="">
                            <h3 class="mb-3 text-white">الاصناف</h3>
                        </div>
                        <div class="pb-0 mt-0">
                            <div class="d-flex">
                                <div class="">
                                    <h1 class="tx-30 font-weight-bold mb-1 text-white">{{$items}}</h1>
                                </div>
                                <span class="float-right my-auto mr-auto"> 
                                    <i class="fa fa-pie-chart fa-2x text-white"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div> 
            </div> 
                <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                    <a href="@can('employee.customers.show'){{route('customers' , 'customer')}} @endcan">
                        <a class="get_details" 
                           href="@can('employee.customers.show'){{route('customers' , 'customer')}} @endcan">
                            <div class="card overflow-hidden sales-card bg-primary-gradient">
    
                                <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
    
                                    <div class="">
                                        <h3 class="mb-3 text-white">العملاء</h3>
                                    </div>
                                    <div class="pb-0 mt-0">
                                        <div class="d-flex">
                                            <div class="">
                                                <h1 class="tx-30 font-weight-bold mb-1 text-white">{{count($clients)}}</h1>
                                            </div>
                                            <span class="float-right my-auto mr-auto"> 
                                                <i class="fa fa-user-plus fa-2x text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a> 
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                    <a href="@can('employee.suppliers.show') {{route('customers' , 'supplier')}} @endcan ">
                        <a class="get_details" 
                           href="@can('employee.suppliers.show') {{route('customers' , 'supplier')}} @endcan ">
                            <div class="card overflow-hidden sales-card bg-secondary-gradient"> 
                                <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                                    <div class="">
                                        <h3 class="mb-3 text-white">الموردين</h3>
                                    </div>
                                    <div class="pb-0 mt-0">
                                        <div class="d-flex">
                                            <div class="">
                                                <h1 class="tx-30 font-weight-bold mb-1 text-white">{{count($suppliers)}}</h1>
                                            </div>
                                            <span class="float-right my-auto mr-auto">  
                                                <i class="fa fa-cart-plus fa-2x text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a> 
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                    <a href="@can('employee.users.show'){{route('admin.users.index')}} @endcan">
                        <a class="get_details" 
                           href="@can('employee.users.show'){{route('admin.users.index')}} @endcan">
                            <div class="card overflow-hidden sales-card bg-dark"> 
                                <div class="pl-3 pt-3 pr-3 pb-2 pt-0"> 
                                    <div class="">
                                        <h3 class="mb-3 text-white">مستخدمين</h3>
                                    </div>
                                    <div class="pb-0 mt-0">
                                        <div class="d-flex">
                                            <div class="">
                                                <h1 class="tx-30 font-weight-bold mb-1 text-white">{{count($users)}}</h1>
                                            </div>
                                            <span class="float-right my-auto mr-auto">
                                                <i class="fa fa-users fa-2x text-white"></i>
                                               
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a> 
                    </a>
                </div>
                <div class="col-xl-3 col-lg-6 col-md-6 col-xm-12">
                    <a href="@can('employee.branches.show') {{route('admin.branches.index')}} @endcan">
                        <a class="get_details" 
                           href="@can('employee.branches.show') {{route('admin.branches.index')}} @endcan">
                            <div class="card overflow-hidden sales-card bg-success">
    
                                <div class="pl-3 pt-3 pr-3 pb-2 pt-0">
    
                                    <div class="">
                                        <h3 class="mb-3 text-white">فروع</h3>
                                    </div>
                                    <div class="pb-0 mt-0">
                                        <div class="d-flex">
                                            <div class="">
                                                <h1 class="tx-30 font-weight-bold mb-1 text-white">{{count($branches)}}</h1>
                                            </div>
                                            <span class="float-right my-auto mr-auto">
                                                <i class="fa fa-code-branch fa-2x text-white"></i>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </a> 
                    </a>
                </div>
            </div> 
    </div> 
 
@endsection

@section('content')
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif 
    <!-- row closed --> 
@endsection
 
