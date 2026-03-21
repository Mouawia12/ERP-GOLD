@extends('admin.layouts.master')
@section('content')
@can('employee.accounting_reports.show')  
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
        <!-- row opened -->
    <style>
        table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
            direction: rtl;
            text-align:center;
        }
        body{
            direction: rtl; 
        }
    </style>
 
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card"> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-3 ">
                        <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                          <div class="row">
                            <div class="col-3"> 
                                {{''}}
                               <br>  س.ت : {{''}}
                               <br>  ر.ض :  {{''}}
                               <br>  تليفون :   {{''}}  
                            </div>   
                            <div class="col-6 title text-center">   
                                <h4  class="alert alert-primary text-center">
                                   {{__('main.balance_report')}}
                                </h4>
                                <h4 class="text-center"> [ {{$periodFrom}} - {{$periodTo}} ] </h4>
                            </div>
                            <div class="col-3 text-left">  
                                 <img src=""   id="profile-img-tag" width="70px" height="70px" class="profile-img"/>
                            </div>
                          </div>
                        </div> 
                    </div>
                </div>  
                <div class="card-body"> 
                    <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                        <table class="display w-100 table-bordered  caption-top" id="example1" 
                           style="text-align: center;direction: rtl;"> 
                            <thead> 
                                <tr>
                                    <th rowspan="2" data-dt-order="disable">{{__('main.account_name')}}</th>
                                    <th rowspan="1" colspan="2" data-dt-order="disable">{{__('main.Before_Debit')}}</th>
                                    <th rowspan="1" colspan="2" data-dt-order="disable">{{__('main.movement')}}</th>
                                    <th rowspan="1" colspan="2" data-dt-order="disable"> {{__('الاغلاق')}}</th> 
                                </tr>
                                <tr> 
                                    <th rowspan="1" >{{__('main.Debit')}}</th>
                                    <th rowspan="1" >{{__('main.Credit')}}</th>
                                    <th rowspan="1" >{{__('main.Debit')}}</th>
                                    <th rowspan="1" >{{__('main.Credit')}}</th>
                                    <th rowspan="1" >{{__('main.Debit')}}</th>
                                    <th rowspan="1" >{{__('main.Credit')}}</th> 
                                    <th rowspan="1" >{{__('الرصيد')}}</th>     
                                </tr>
                            </thead>
                            <tbody>
                            <?php
                            $opening_debit = 0;
                            $opening_credit = 0;
                            $period_debit = 0;
                            $period_credit = 0;
                            $closing_debit = 0;
                            $closing_credit = 0;
                            $closing_balance = 0;
                            ?>

                            @foreach($accounts as $key => $account)
                            <?php
                            $account_opening_debit = $account->openingBalance($periodFrom, $periodTo, 'debit');
                            $account_opening_credit = $account->openingBalance($periodFrom, $periodTo, 'credit');
                            $account_period_debit = $account->currentTransaction($periodFrom, $periodTo, 'debit');
                            $account_period_credit = $account->currentTransaction($periodFrom, $periodTo, 'credit');
                            $account_closing_debit = $account->closingBalance($periodFrom, $periodTo, 'debit');
                            $account_closing_credit = $account->closingBalance($periodFrom, $periodTo, 'credit');
                            $account_closing_balance = $account->closingBalance($periodFrom, $periodTo);
                            ?>
                                <tr>
                                    <td>{{ $account->name . ' - ' . $account->code }}</td>
                                    <td>{{ number_format(abs($account_opening_debit), 2) }}</td>
                                    <td>{{ number_format(abs($account_opening_credit), 2) }}</td>
                                    <td>{{ number_format(abs($account_period_debit), 2) }}</td>
                                    <td>{{ number_format(abs($account_period_credit), 2) }}</td>
                                    <td>{{ number_format(abs($account_closing_debit), 2) }}</td>
                                    <td>{{ number_format(abs($account_closing_credit), 2) }}</td>
                                    <td>{{ number_format(abs($account_closing_balance), 2) }} {{ $account_closing_balance != 0 ? ' / ' . ($account_closing_balance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                </tr> 
                                <?php
                                $opening_debit += $account_opening_debit;
                                $opening_credit += $account_opening_credit;
                                $period_debit += $account_period_debit;
                                $period_credit += $account_period_credit;
                                $closing_debit += $account_closing_debit;
                                $closing_credit += $account_closing_credit;
                                $closing_balance += $account_closing_balance;
                                ?>
                            @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="bg-primary text-white">
                                    <td class="text-center"> اجمالي الميزان  </td>
                                    <td class="text-center">{{ number_format(abs($opening_debit), 2) }}</td>
                                    <td class="text-center">{{ number_format(abs($opening_credit), 2) }}</td>
                                    <td class="text-center">{{ number_format(abs($period_debit), 2) }}</td>
                                    <td class="text-center">{{ number_format(abs($period_credit), 2) }}</td>
                                    <td class="text-center">{{ number_format(abs($closing_debit), 2) }}</td>
                                    <td class="text-center">{{ number_format(abs($closing_credit), 2) }}</td> 
                                    <td class="text-center">{{ number_format(abs($closing_balance), 2) }} {{ $closing_balance != 0 ? ' / ' . ($closing_balance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td> 
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div> 
            </div> 
        </div> 
         <!-- End of Main Content --> 
    </div>
    <!-- End of Page Wrapper --> 

@endcan 
@endsection 
@section('js') 
<script type="text/javascript">
    document.title = "{{__('main.balance_report')}}";
    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            window.print(); 
        }); 
    }); 
</script> 
@endsection 

