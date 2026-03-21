@extends('admin.layouts.master') 
@section('content') 
@can('employee.gold_prices.show')
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
                
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <h4  class="alert alert-primary text-center">
                                {{__(' اسعار البورصة العالمية(ذهب)')}}
                            </h4>
                            <div class="table-responsive hoverable-table">
                                <table class="display w-100 table-bordered" id="stock_market" 
                                   style="text-align: center;"> 
                                    <tbody> 
                                        <tr>
                                            <td class="text-center">الطابع الزمني (timestamp)</td>
                                            <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">المعدن (metal)</td>
                                            <td class="text-center">{{$stock_market_usd->metal}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">العملة  (currency)</td>
                                            <td class="text-center">{{$stock_market_usd->currency}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">سعر الاونصة   (Ounce price)</td>
                                            <td class="text-center">{{$stock_market_usd->price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اغلاق سابق  (prev close price)</td>
                                            <td class="text-center">{{$stock_market_usd->prev_close_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">الفتح  (open price)</td>
                                            <td class="text-center">{{$stock_market_usd->open_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اقل سعر  (low price)</td>
                                            <td class="text-center">{{$stock_market_usd->low_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">اعلى سعر  (high price)</td>
                                            <td class="text-center">{{$stock_market_usd->high_price}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">طلب  (ask)</td>
                                            <td class="text-center">{{$stock_market_usd->ask}}</td>
                                        </tr> 
                                        <tr>
                                            <td class="text-center">عرض  (bid)</td>
                                            <td class="text-center">{{$stock_market_usd->bid}}</td>
                                        </tr>  
                                   </tbody>
                                </table>
                            </div>
                        </div> 
                        <div class="row">
                            <div class="card-body col-md-6">
                                <h4  class="alert alert-primary text-center">
                                   سعر جرام الذهب (دولار) 
                                </h4>
                                <div class="table-responsive hoverable-table">
                                    <table class="display w-100 table-bordered" id="stock_market" 
                                       style="text-align: center;"> 
                                       <thead>
                                           <tr> 
                                               <th>{{__('نوع العيار')}}</th>
                                               <th>{{__('السعر')}}</th>
                                               <th>{{__('التحديث')}} </th> 
                                           </tr>
                                       </thead>
                                       <tbody>
                                            <tr> 
                                                <td class="text-center">{{__('عيار 24')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_24k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 22')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_22k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 21')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_21k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 20')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_20k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 18')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_18k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 16')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_16k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 14')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_14k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 10')}}</td>
                                                <td class="text-center">{{round($stock_market_usd->price_gram_10k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_usd->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                       </tbody>
                                    </table>
                                </div>
                            </div> 
                            <div class="card-body col-md-6">
                                <h4  class="alert alert-primary text-center">
                                سعر جرام الذهب (ريال سعودي)
                                </h4>
                                <div class="table-responsive hoverable-table">
                                    <table class="display w-100 table-bordered" id="stock_market" 
                                       style="text-align: center;"> 
                                       <thead>
                                           <tr> 
                                               <th>{{__('نوع العيار')}}</th>
                                               <th>{{__('السعر')}}</th>
                                               <th>{{__('التحديث')}} </th> 
                                           </tr>
                                       </thead>
                                       <tbody>
                                            <tr> 
                                                <td class="text-center">{{__('عيار 24')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_24k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 22')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_22k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 21')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_21k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 20')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_20k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 18')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_18k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 16')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_16k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 14')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_14k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                            <tr> 
                                                <td class="text-center">{{__('عيار 10')}}</td>
                                                <td class="text-center">{{round($stock_market_sar->price_gram_10k,2)}}</td>
                                                <td class="text-center">{{\Carbon\Carbon::parse($stock_market_sar->timestamp) -> format('Y-m-d\Th:i:s')}}</td>
                                            </tr> 
                                       </tbody>
                                    </table>
                                </div>
                            </div> 
                        </div>  
                    </div> 
                </div>
            </div>
        </div>
    </div>
 
 

@endcan 
@endsection 
@section('js') 
<script type="text/javascript">
    document.title = "{{__(' اسعار البورصة العالمية(ذهب)')}}";
</script>
@endsection 
