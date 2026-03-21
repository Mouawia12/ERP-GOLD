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
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 " id="head-right"  style="direction: rtl;border:solid 1px gray"> 
                            <header>
                                <div class="row" style="direction: ltr;">
                                    <div class="col-4 text-left">    
                                    <img src=""   id="profile-img-tag" width="70px" height="70px" class="profile-img"/>
                                    </div>
                                    <div class="col-4 c">
                                        <h4 class="alert alert-primary text-center">
                                             {{__('main.account_movement_report')}}
                                        </h4>
                                        <h5 class="text-center"> [ {{$periodFrom}} - {{$periodTo}} ]</h5>
                                        <h4 class="text-center"><strong>{{$account->name}} </strong></h4>   
                                    </div>
                                    <div class="col-4 c">
                                        <span style="text-align: right;">
                                            {{''}}
                                            <br>  س.ت : {{''}}
                                            <br>  ر.ض :  {{''}}
                                            <br>  تليفون :   {{''}}
                                        </span>
                                    </div>
                                </div>
                            </header> 
                        </div>
                    </div>          
          
                    <div class="card-body">  
                        <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                            <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                tyle="text-align: center;direction: rtl;">
                                <thead> 
                                    <th class="text-center">#</th>
                                        <th class="text-center">{{__('main.date')}}</th> 
                                        <th class="text-center">{{__('main.document_type')}}</th>
                                        <th class="text-center">{{__('main.Debit')}}</th>
                                        <th class="text-center">{{__('main.Credit')}}</th>
                                        <th class="text-center">{{__('main.balance')}}</th>
                                    </tr> 
                                </thead>
                                <tbody>
                                    @php
                                        $openingBalance = $account->openingBalance($periodFrom,$periodTo);
                                    @endphp
                                    @if($openingBalance != 0)
                                        <tr>
                                            <td class="text-center">1</td>
                                            <td class="text-center">--</td> 
                                            <td class="text-center">رصيد اول المده</td> 
                                            <td class="text-center">{{number_format($account->openingBalance($periodFrom,$periodTo,'debit'),2)}}</td>
                                            <td class="text-center">{{number_format($account->openingBalance($periodFrom,$periodTo,'credit'),2)}}</td>
                                            <td class="text-center">{{number_format(abs($openingBalance),2)}} {{ $openingBalance != 0 ? ' / ' . ($openingBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                        </tr> 
                                    @endif
                
                                    @foreach($documents??[] as $document)

                                    @php
                                        $currentBalance = $document->debit - $document->credit;
                                        $balance = $openingBalance + $currentBalance;
                                        $openingBalance = $balance;
                                    @endphp
                                        <tr>
                                            <td class="text-center">{{$loop -> iteration}}</td>
                                            <td class="text-center"> {{\Carbon\Carbon::parse($document->document_date) -> format('d-m-Y')}}</td>  
                                            <td class="text-center"> {{$document->journal_entry->custom_notes??''}}</td> 
                                            <td class="text-center">{{number_format($document->debit,2)}}</td>
                                            <td class="text-center">{{number_format($document->credit,2)}}</td>  
                                            <td class="text-center">{{number_format(abs($balance),2) }} {{ $balance != 0 ? ' / ' . ($balance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td> 
                                        </tr>
                 
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    @php
                                        $totalBalance = $openingBalance;
                                    @endphp
                                    <tr style="background: #c3e6cb">
                                       <td class="text-center" colspan="2"></td>
                                        <td class="text-center">{{__('main.total_balance')}}</td>
                                        <td class="text-center" colspan="2"></td>
                                        <td class="text-center" >{{number_format(abs($totalBalance),2)}} {{ $totalBalance != 0 ? ' / ' . ($totalBalance > 0 ? __('main.debit') : __('main.credit')) : '' }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div> 
        </div>
        <!-- End of Main Content --> 
    </div>
    <!-- End of Content Wrapper --> 

@endcan 
@endsection 
@section('js') 
<script type="text/javascript">

    let id = 0; 
    document.title = "{{__('main.account_movement_report') .'-'. $account->name}} ";

    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            printPage();
        });
    });

    function printPage() {
        var css = '@page { size: landscape; }',
            head = document.head || document.getElementsByTagName('head')[0],
            style = document.createElement('style');

        style.type = 'text/css';
        style.media = 'print';

        if (style.styleSheet) {
            style.styleSheet.cssText = css;
        } else {
            style.appendChild(document.createTextNode(css));
        }

        head.appendChild(style);
        document.getElementById("main-header").style.display = 'none';
        document.getElementById("main-footer").style.display = 'none';
        document.getElementById("card-header").style.display = 'none'; 
        window.print();
        document.getElementById("main-header").style.display = 'block';
        document.getElementById("main-footer").style.display = 'block';
        document.getElementById("card-header").style.display = 'block'; 
    }
 
</script> 
@endsection 
 

