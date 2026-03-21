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
                           {{__('main.sales_report')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div> 
            </div>   
                <div class="card-body px-0 pt-0 pb-2">

                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <form   method="POST" action="{{ route('reports.sales_report.index') }}"
                                    enctype="multipart/form-data" >
                                @csrf
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group"> 
                                            <label>{{ __('main.branch') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            @if(Auth::user()->is_admin)
                                                <select required  class="js-example-basic-single w-100" name="branch_id" id="branch_id">
                                                    <option value="0">{{__('main.all_branches')}}</option>
                                                    @foreach($branches as $branch)
                                                        <option value="{{$branch->id}}">{{$branch->name}}</option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <input class="form-control" type="text" readonly
                                                       value="{{Auth::user()->branch->name}}"/>
                                                <input required class="form-control" type="hidden" id="branch_id"
                                                       name="branch_id"
                                                       value="{{Auth::user()->branch_id}}"/>
                                            @endif
                    
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label> تاريخ البداية <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="checkbox" id="isStartDate" name="isStartDate">
                                            <input type="date" id="date_from" name="date_from"  class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-3">
                                        <div class="form-group">
                                            <label> تاريخ النهاية <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="checkbox" id="isEndDate" name="isEndDate">
                                            <input type="date" id="date_to" name="date_to"  class="form-control">
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>من رقم فاتورة <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="FromBillNumber" name="FromBillNumber"  class="form-control">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>إلي رقم فاتورة <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="text" id="ToBillNumber" name="ToBillNumber"  class="form-control">
                                        </div>
                                    </div>
                                </div>


                                <div class="row">
                                    <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                                        <button type="submit" class="btn btn-labeled btn-primary"  >
                                            {{__('main.search_btn')}}</button>
                                    </div>
                                </div>


                            </form>

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

<div class="show_modal">

</div>
<!-- End of Page Wrapper -->
  
@endsection
<script src="{{asset('assets/js/jquery.min.js')}}"></script> 
  
<script>
    $(document).ready(function (){
        var now = new Date();

        var day = ("0" + now.getDate()).slice(-2);
        var month = ("0" + (now.getMonth() + 1)).slice(-2);
        var today = now.getFullYear()+"-"+(month)+"-"+(day) ;
        $('#isStartDate').prop('checked', false);
        $('#isEndDate').prop('checked', false);
        $('#StartDate').prop('disabled', true);
        $('#EndDate').prop('disabled', true);
        $('#StartDate').val(today);
        $('#EndDate').val(today);

        $('#isStartDate').change(function (){
            if(this.checked){
                $('#StartDate').prop('disabled', false);
            } else {
                $('#StartDate').prop('disabled', true);
            }
        });

        $('#isEndDate').change(function (){
            if(this.checked){
                $('#EndDate').prop('disabled', false);
            } else {
                $('#EndDate').prop('disabled', true);
            }
        });
    });
</script>
 
