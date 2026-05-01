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
                            {{__('main.Balance_Sheet')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div> 
                </div>  
                <div class="card-body px-0 pt-0 pb-2">

                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <form id="balance-sheet-form" method="POST" action="{{ route('balance_sheet.search') }}"
                                    enctype="multipart/form-data" >
                                @csrf
                                <div class="row">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label> تاريخ البداية <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="checkbox" id="isStartDate" name="isStartDate">
                                            <input type="date" id="StartDate" name="date_from"  class="form-control" value="{{ $defaultFilters['date_from'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label> تاريخ النهاية <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                            <input type="checkbox" id="isEndDate" name="isEndDate">
                                            <input type="date" id="EndDate" name="date_to"  class="form-control" value="{{ $defaultFilters['date_to'] ?? '' }}">
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        @include('admin.reports.partials.branch_filter', [
                                            'branches' => $branches,
                                            'defaultFilters' => $defaultFilters,
                                            'branchFieldId' => 'balance_sheet_branch_ids',
                                            'branchHiddenFieldId' => 'balance_sheet_branch_id',
                                            'branchLabelText' => 'الفرع',
                                            'branchHelpText' => 'اتركه بدون اختيار لعرض جميع الفروع المتاحة لك. عند تحديد فرع أو مجموعة فروع، تُحتسب فقط حركاتها بينما تبقى الأرصدة الافتتاحية العامة غير موزعة على الفروع في النسخة الحالية.',
                                        ])
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 20px auto; text-align: center;">
                                        <button
                                            type="button"
                                            class="btn btn-labeled btn-primary"
                                            data-print-open
                                            data-print-form="#balance-sheet-form"
                                            data-print-url="{{ route('balance_sheet.print') }}"
                                        >
                                            عرض التقرير
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-labeled btn-success"
                                            data-print-open
                                            data-print-form="#balance-sheet-form"
                                            data-print-url="{{ route('balance_sheet.print') }}"
                                            data-auto-print="1"
                                            data-print-target="_iframe"
                                        >
                                            طباعة
                                        </button>
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
@section('js')
<script>
    $(document).ready(function () {
        $('#isStartDate').prop('checked', false);
        $('#isEndDate').prop('checked', false);
        $('#StartDate').prop('disabled', true);
        $('#EndDate').prop('disabled', true);

        $('#isStartDate').change(function () {
            $('#StartDate').prop('disabled', !this.checked);
        }).trigger('change');

        $('#isEndDate').change(function () {
            $('#EndDate').prop('disabled', !this.checked);
        }).trigger('change');
    });
</script>
@endsection
 
