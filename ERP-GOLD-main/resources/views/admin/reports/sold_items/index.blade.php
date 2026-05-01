@extends('admin.layouts.master')
@section('content')
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
        @media print {
            @page {
                size: A4 landscape;
                margin: 10mm;
            }
            table {
                page-break-inside: auto;
            }
            thead {
                display: table-header-group;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
    @include('admin.reports.partials.result_print_styles')
<div class="row row-sm erp-print-report">
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
                                    {{__('main.sold_items_report')}}
                                </h4>
                                <h5 class="text-center"> [ {{ $branchLabel ?? ($branch?->name ?: 'جميع الفروع') }} ] </h5>
                                @if(isset($selectedUser) && $selectedUser)
                                <h6 class="text-center"> المستخدم: {{ $selectedUser->name }} </h6>
                                @endif
                                @if(!empty($filters['inventory_classification'] ?? null))
                                <h6 class="text-center text-muted">التصنيف: {{ \App\Models\Item::inventoryClassificationOptions()[$filters['inventory_classification']] ?? $filters['inventory_classification'] }}</h6>
                                @endif
                                <h5 class="text-center">  {{$periodFrom}} - {{$periodTo}} </h5>
                            </div>
                            <div class="col-3 text-left">
                                <img src="{{ $brandLogoUrl }}" id="profile-img-tag" width="70px" height="70px" class="profile-img"/>
                                <button type="button" class="btn btn-primary no-print d-block mt-1" id="btnPrint">
                                    <i class="fa fa-print"></i> طباعة
                                </button>
                            </div>
                          </div>
                        </div>
                        <div class="card-body" style="direction: rtl;"> 
                            <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                                <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;direction: rtl;">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th>{{__('main.bill_no')}}</th>
                                            <th>{{__('main.date')}}</th> 
                                            <th>الوقت</th>
                                            <th>الفرع</th>
                                            <th>المستخدم</th>
                                            <th>{{__('main.code')}}</th>
                                            <th>{{__('main.name_ar')}}</th>
                                            <th>التصنيف</th>
                                            <th> {{__('main.carats')}} </th>
                                            <th> {{__('main.weight')}} </th> 
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php $total = 0; ?>
                                    @foreach($itemsTransactions??[] as $transaction)
                                        <tr>
                                            <td class="text-center">{{$loop -> iteration}}</td>
                                            <td class="text-center">{{ $transaction->invoice?->bill_number }}</td>
                                            <td class="text-center">{{ \Carbon\Carbon::parse($transaction -> invoice -> date) -> format('d-m-Y')  }}</td>
                                            <td class="text-center">{{ $transaction->invoice?->time }}</td>
                                            <td class="text-center">{{ $transaction->invoice?->branch?->name ?? '-' }}</td>
                                            <td class="text-center">{{ $transaction->invoice?->user?->name ?? '-' }}</td>
                                            <td class="text-center">{{$transaction -> item -> code}}</td>
                                            <td class="text-center">{{$transaction -> item -> title}}</td>
                                            <td class="text-center">{{ $transaction->item?->inventory_classification_label ?? '-' }}</td>
                                            <td class="text-center">{{ $transaction->carat?->title ?? $transaction->item?->inventory_classification_label ?? '-' }}</td>
                                            <td class="text-center">{{$transaction -> out_weight}}</td> 
                                        </tr>
                                        <?php $total += $transaction->out_weight; ?>
                                    @endforeach 
                                    </tbody> 
                                    <tfoot>  
                                        <tr class="text-white bg-primary">
                                            <td></td>
                                            <td>الإجمالي</td>
                                            <td colspan="8" class="text-center"></td>
                                            <td >{{$total}} </td>  
                                        </tr>
                                    </tfoot>   
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
 
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script> 
  
<!-- Page level custom scripts -->

<script type="text/javascript">
    let id = 0;


    $(document).ready(function () {
        $(document).on('click', '#btnPrint', function (event) {
            window.ErpPrint.printCurrentPage();

        });

    });
</script>
<script>
    $(document).ready(function () {
        document.title = "{{__('main.sold_items_report')}}";
    });
</script>
 
