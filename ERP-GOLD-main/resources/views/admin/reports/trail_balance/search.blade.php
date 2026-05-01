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
                            {{__('main.balance_report')}}
                        </h4>
                    </div>
                    <div class="clearfix"></div> 
                </div>   
                <div class="card-body px-0 pt-0 pb-2">

                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <form id="trail-balance-form" method="POST" action="{{ route('trail_balance.search') }}"
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
                                            'branchFieldId' => 'trail_balance_branch_ids',
                                            'branchHiddenFieldId' => 'trail_balance_branch_id',
                                            'branchLabelText' => 'الفرع',
                                            'branchHelpText' => 'اتركه بدون اختيار لاحتساب جميع الفروع المتاحة لك. عند تحديد فرع أو مجموعة فروع، تُحتسب فقط حركاتها دون توزيع الأرصدة الافتتاحية العامة على فرع منفرد.',
                                        ])
                                    </div>
                                </div>


                                <div class="row mt-2">
                                    <div class="col-6">
                                        <div class="form-group">
                                            <label>مستوى الحساب</label>
                                            <select name="account_level" class="form-control">
                                                <option value="">تفصيلي (آخر مستوى)</option>
                                                @foreach($availableLevels ?? [] as $lvl)
                                                    <option value="{{ $lvl }}">مستوى {{ $lvl }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12" style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; margin: 20px auto; text-align: center;">
                                        <button
                                            type="button"
                                            class="btn btn-labeled btn-primary"
                                            data-print-open
                                            data-print-form="#trail-balance-form"
                                            data-print-url="{{ route('trail_balance.print') }}"
                                        >
                                            عرض التقرير
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-labeled btn-success"
                                            data-print-open
                                            data-print-form="#trail-balance-form"
                                            data-print-url="{{ route('trail_balance.print') }}"
                                            data-auto-print="1"
                                            data-print-target="_iframe"
                                        >
                                            طباعة
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-labeled btn-secondary"
                                            data-print-open
                                            data-print-form="#trail-balance-form"
                                            data-print-url="{{ route('trail_balance.pdf') }}"
                                        >
                                            حفظ PDF
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
    document.addEventListener('DOMContentLoaded', function () {
        var startToggle = document.getElementById('isStartDate');
        var endToggle = document.getElementById('isEndDate');
        var startDate = document.getElementById('StartDate');
        var endDate = document.getElementById('EndDate');

        function syncDateToggle(toggle, field) {
            if (!toggle || !field) {
                return;
            }

            field.disabled = !toggle.checked;
        }

        if (startToggle) {
            startToggle.checked = false;
            startToggle.addEventListener('change', function () {
                syncDateToggle(startToggle, startDate);
            });
        }

        if (endToggle) {
            endToggle.checked = false;
            endToggle.addEventListener('change', function () {
                syncDateToggle(endToggle, endDate);
            });
        }

        syncDateToggle(startToggle, startDate);
        syncDateToggle(endToggle, endDate);
    });
</script>
@endsection
