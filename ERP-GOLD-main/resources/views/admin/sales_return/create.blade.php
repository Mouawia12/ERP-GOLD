@extends('admin.layouts.master')
@section('content')
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
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                        [ {{__('main.return_sales')}}  {{__('main.sales_'.$type)}} ]
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div> 
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="home" role="tabpanel" aria-labelledby="home-tab">
                            <form method="POST" action="{{ route('sales_return.store',['type'=>$type,'id'=>$invoice->id]) }}"
                                  enctype="multipart/form-data" id="pos_sales_form">
                                @csrf
                                <div class="row">
                                    <div class="card shadow mb-4 col-9"> 
                                        <div class="card-body">
                                            <div class="row"> 
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_date') }} <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text"
                                                               class="form-control" value="{{$invoice -> date}}" readonly
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_number') }} <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text" value="{{$invoice -> bill_number}}"
                                                               class="form-control" placeholder="bill_number" readonly
                                                        />
                                                        <input type="hidden" value="{{$invoice -> bill_number}}" id="bill_id" name="bill_id"
                                                               class="form-control" placeholder="bill_id" readonly
                                                        />
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="form-group">
                                                        <label style="float: right;">{{ __('main.bill_client_name') }}
                                                            <span
                                                                style="color:red; font-size:20px; font-weight:bold;">*</span>
                                                        </label>
                                                        <input type="text" name="bill_client_name" id="bill_client_name"
                                                               class="form-control"
                                                               value="{{$invoice -> customer->name}}" readonly>


                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-12">


                                                    <div class="card mb-4">
                                                        <div class="card-header pb-0">
                                                            <h4 class="table-label text-center">{{__('main.items')}} </h4>

                                                            <div class="row">

                                                            </div>

                                                        </div>

                                                        <div class="card-body px-0 pt-0 pb-2">
                                                            <div class="table-responsive p-0">
                                                                <table id="sTable" class="table items table-striped table-bordered table-condensed table-hover">
                                                                    <thead>
                                                                        <tr>
                                                                            <th>{{__('main.item_name')}}</th>
                                                                            <th>{{__('main.item_carats')}}</th>
                                                                            <th>{{__('main.item_weight')}}</th>
                                                                            <th>{{__('main.price_gram')}} </th>
                                                                            <th>{{__('main.item_amount')}}</th>
                                                                            <th>{{__('main.item_tax')}}</th>
                                                                            <th>{{__('main.item_total')}}</th>
                                                                            <th class="text-center">
                                                                                <input class="form-control" id="checkAll"
                                                                                        name="checkAll" type="checkbox">
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody id="tbody">
                                                                    @foreach($invoice -> details()->whereNotIn('id', $invoice->returnInvoicesDetailsIds)->get()??[] as $detail)
                                                                        <tr>
                                                                            <td class="text-center"> {{$detail -> item->title}} </td>
                                                                            <td class="text-center"> {{$detail -> carat->title}} </td>
                                                                            <td class="text-center"> {{$detail -> out_weight}} </td>
                                                                            <td class="text-center"> {{$detail -> unit_price}} </td>
                                                                            <td class="text-center"> {{$detail -> line_total}} </td>
                                                                            <td class="text-center"> {{$detail -> line_tax}} </td>
                                                                            <td class="text-center"> {{$detail -> net_total}} </td>
                                                                            <td class="text-center"><input
                                                                                    class="form-control checkDetail"
                                                                                    name="checkDetail[]" type="checkbox"
                                                                                    value="{{$detail -> id}}"></td>
                                                                        </tr>
                                                                    @endforeach
                                                                    </tbody>
                                                                    <tfoot></tfoot>
                                                                </table>

                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>
                                            </div>


                                        </div>
                                    </div>
                                    <div class="card shadow mb-4 col-3">
                                        <div class="card-header py-3">
                                            <h6 class="m-0 font-weight-bold text-primary">{{__('main.sales_invoice_total')}}</h6>
                                        </div>
                                        <div class="card-body ">
                                            <div class="row document_type1"
                                                 style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.items_count')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="items_count"
                                                           value="{{count($invoice -> details) }}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.total_weight21')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control"
                                                           id="total_weight21" name="total_weight21"
                                                           value="{{$invoice -> stock_carat_weight ?? 0}}">
                                                </div>
                                            </div>


                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.additional_tax')  }} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="tax" name="tax"
                                                           value="{{$invoice -> taxes_total ?? 0}}">
                                                </div>
                                            </div>
                                            <div class="row" style="align-items: baseline; margin-bottom: 10px;">
                                                <div class="col-6">

                                                    <label
                                                        style="text-align: right;float: right;"> {{__('main.discount')}} </label>


                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="discount"
                                                           name="discount" placeholder="0"
                                                           value="{{$invoice -> discount_total ?? 0}}">
                                                </div>

                                            </div>
                                            <div class="row" style="align-items: center; margin-bottom: 10px;">
                                                <div class="col-6">
                                                    <label style="text-align: right;float: right;"
                                                    > {{__('main.net')}} </label>
                                                </div>
                                                <div class="col-6">
                                                    <input type="text" readonly class="form-control" id="net_sales"
                                                           value="{{$invoice -> net_total}}">
                                                </div>
                                            </div>
                                            <hr class="sidebar-divider d-none d-md-block">


                                            <hr class="sidebar-divider d-none d-md-block">


                                            <div class="show_modal1">

                                            </div>

                                            <div class="row">
                                                <div class="col-md-6 text-center" style="display: block; margin: auto;">
                                                    <input type="button" class="btn btn-primary" id="return_btn"
                                                           tabindex="-1"
                                                           style="width: 150px;
                                                   margin: 30px auto;" value="{{__('main.return_bill')}}"></input>

                                                </div>
                                            </div>

                                        </div>


                                    </div>

                                </div>


                            </form>
                        </div>


                    </div>
                </div>


            </div>
            <!-- /.container-fluid -->
            <input id="local" value="{{Config::get('app.locale')}}" hidden>
        </div>
        <!-- End of Main Content -->

        <!-- Footer -->
    

    </div>
    <!-- End of Content Wrapper -->


</div>
<!-- End of Page Wrapper -->
  
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script>

<script type="text/javascript">
    $(document).ready(function () {
        $('#checkAll').change(function () {
            $("input:checkbox.checkDetail").prop('checked', this.checked);

        });

        $(document).on('click', '#return_btn', function () {
            var checkList = [];
            console.log('clicked');
            $('#tbody tr').each(function (index) {
                var row = $(this).closest('tr');
                var cell = row[0].cells[7].firstChild.checked;
                if (cell) {
                    checkList.push(row[0].cells[7].firstChild.value);
                }

            });

            if (checkList.length > 0) {
                document.getElementById('pos_sales_form').submit();
            } else {
                alert('select at least one item to return');

            }


        });


    });
</script>
 