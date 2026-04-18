@extends('admin.layouts.master')

@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header py-3">
                <h4 class="alert alert-primary text-center mb-0">الباركود المفقود</h4>
            </div>
            <div class="card-body">
                <div class="response_container"></div>

                <form id="lost_barcode_search_form" class="mb-4">
                    @csrf
                    <div class="row justify-content-center align-items-end">
                        @if(($branches ?? collect())->count() > 1)
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                                <div class="form-group mb-0">
                                    <label>الفرع</label>
                                    <select class="form-control" name="branch_id" id="lost_barcode_branch_id" required>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected((int) $selectedBranchId === (int) $branch->id)>
                                                {{ $branch->getTranslation('name', 'ar') }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        @else
                            <input type="hidden" name="branch_id" id="lost_barcode_branch_id" value="{{ $selectedBranchId }}">
                        @endif

                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="form-group mb-0">
                                <label>الوزن (جرام)</label>
                                <input
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    class="form-control"
                                    name="weight"
                                    id="lost_barcode_weight"
                                    placeholder="مثال: 5.250"
                                    required
                                >
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-4 col-sm-6 mb-3">
                            <div class="form-group mb-0">
                                <label>مقاس ورق الباركود</label>
                                <select class="form-control" id="lost_barcode_paper_profile">
                                    @foreach($paperProfiles as $profile)
                                        <option value="{{ $profile['key'] }}" @selected($defaultPaperProfile['key'] === $profile['key'])>
                                            {{ $profile['label'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-lg-2 col-md-4 col-sm-6 mb-3">
                            <div class="form-group mb-0">
                                <label>&nbsp;</label>
                                <button type="submit" class="btn btn-primary btn-block" id="lost_barcode_search_btn">
                                    بحث
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="row justify-content-start mb-3">
                    <div class="col-lg-4 col-md-6 col-sm-8">
                        <div class="form-group mb-0">
                            <label for="lost_barcode_results_filter">فلترة النتائج</label>
                            <input
                                type="text"
                                class="form-control"
                                id="lost_barcode_results_filter"
                                placeholder="اكتب اسم القطعة أو الكود أو الباركود"
                                autocomplete="off"
                            >
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-hover text-center" id="lost_barcode_results_table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>الكود</th>
                                <th>الاسم العربي</th>
                                <th>الاسم الإنجليزي</th>
                                <th>العيار / التصنيف</th>
                                <th>الوزن</th>
                                <th>الباركود</th>
                                <th>الفرع</th>
                                <th>الإجراء</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="lost-barcode-empty-row">
                                <td colspan="9" class="text-muted">اكتب وزن القطعة ثم اضغط بحث لعرض النتائج.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script type="text/javascript">
    $(document).ready(function () {
        var latestLostBarcodeRows = [];

        function renderEmptyRow(message) {
            $('#lost_barcode_results_table tbody').html(
                '<tr class="lost-barcode-empty-row"><td colspan="9" class="text-muted">' + message + '</td></tr>'
            );
        }

        function escapeHtml(value) {
            return $('<div>').text(value == null ? '' : value).html();
        }

        function rowMatchesFilter(row, filterValue) {
            if (!filterValue) {
                return true;
            }

            var normalizedFilter = filterValue.toLowerCase().trim();
            var haystack = [
                row.item_code,
                row.name_ar,
                row.name_en,
                row.carat_label,
                row.weight,
                row.barcode,
                row.branch_name
            ]
                .join(' ')
                .toLowerCase();

            return haystack.indexOf(normalizedFilter) !== -1;
        }

        function renderRows(rows, emptyMessage) {
            var filterValue = $('#lost_barcode_results_filter').val() || '';
            var filteredRows = rows.filter(function (row) {
                return rowMatchesFilter(row, filterValue);
            });
            var html = '';

            if (!filteredRows.length) {
                renderEmptyRow(emptyMessage || 'لا توجد نتائج مطابقة.');
                return;
            }

            filteredRows.forEach(function (row, index) {
                html += '<tr>';
                html += '<td>' + (index + 1) + '</td>';
                html += '<td>' + escapeHtml(row.item_code) + '</td>';
                html += '<td>' + escapeHtml(row.name_ar) + '</td>';
                html += '<td>' + escapeHtml(row.name_en || '-') + '</td>';
                html += '<td>' + escapeHtml(row.carat_label) + '</td>';
                html += '<td>' + escapeHtml(row.weight) + '</td>';
                html += '<td><code>' + escapeHtml(row.barcode) + '</code></td>';
                html += '<td>' + escapeHtml(row.branch_name) + '</td>';
                html += '<td><button type="button" class="btn btn-sm btn-primary lost-barcode-print-btn" data-url="' + escapeHtml(row.print_url) + '">طباعة الباركود</button></td>';
                html += '</tr>';
            });

            $('#lost_barcode_results_table tbody').html(html);
        }

        $(document).on('input', '#lost_barcode_results_filter', function () {
            if (!latestLostBarcodeRows.length) {
                return;
            }

            renderRows(latestLostBarcodeRows, 'لا توجد نتائج مطابقة لفلترة البحث.');
        });

        $(document).on('submit', '#lost_barcode_search_form', function (event) {
            event.preventDefault();

            var $form = $(this);
            var $button = $('#lost_barcode_search_btn');
            $('.response_container').html('');

            $.ajax({
                url: "{{ route('items.lost_barcodes.search') }}",
                type: 'POST',
                data: $form.serialize(),
                beforeSend: function () {
                    latestLostBarcodeRows = [];
                    $button.prop('disabled', true).text('جاري البحث...');
                    renderEmptyRow('جاري البحث عن القطع المطابقة...');
                },
                success: function (response) {
                    latestLostBarcodeRows = response.data || [];

                    if (!latestLostBarcodeRows.length) {
                        renderEmptyRow(response.message || 'لا توجد نتائج مطابقة.');
                        return;
                    }

                    renderRows(latestLostBarcodeRows, 'لا توجد نتائج مطابقة لفلترة البحث.');
                },
                error: function (jqXHR) {
                    latestLostBarcodeRows = [];
                    var errors = jqXHR.responseJSON && jqXHR.responseJSON.errors
                        ? jqXHR.responseJSON.errors
                        : ['حدث خطأ غير متوقع أثناء البحث.'];
                    var message = '<div class="alert alert-danger"><ul style="margin:0;">';

                    errors.forEach(function (error) {
                        message += '<li>' + escapeHtml(error) + '</li>';
                    });

                    message += '</ul></div>';
                    $('.response_container').html(message);
                    renderEmptyRow('تعذر تحميل النتائج.');
                },
                complete: function () {
                    $button.prop('disabled', false).text('بحث');
                }
            });
        });

        $(document).on('click', '.lost-barcode-print-btn', function () {
            var baseUrl = $(this).data('url');
            var paperProfile = $('#lost_barcode_paper_profile').val();
            var url = baseUrl + '?paper_profile=' + encodeURIComponent(paperProfile);
            window.open(url, '_blank');
        });
    });
</script>
@endsection
