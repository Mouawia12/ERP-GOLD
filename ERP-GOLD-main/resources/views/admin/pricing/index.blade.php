@extends('admin.layouts.master')
@section('content')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('error') }}
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4 class="alert alert-primary text-center mb-0">{{ __('main.prices') }}</h4>
                    </div>
                </div>

                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-center mb-4" style="gap: 12px;">
                        @can('employee.gold_prices.edit')
                            <form method="POST" action="{{ route('updatePrices') }}" class="mb-0">
                                @csrf
                                <input type="hidden" name="currency" value="SAR">
                                <button type="submit" class="btn btn-sm btn-primary shadow-sm" style="border-radius: 10px;">
                                    <i class="fas fa-cloud-download-alt ml-1"></i>
                                    تحديث الأسعار من الخدمة الخارجية
                                </button>
                            </form>

                            <button type="button" id="openManualPricingModal" class="btn btn-sm btn-info shadow-sm" style="border-radius: 10px;">
                                <i class="fas fa-pen ml-1"></i>
                                تحديث يدوي
                            </button>
                        @endcan

                        <a href="{{ route('gold.stock.market.prices') }}" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 10px;">
                            <i class="fas fa-chart-line ml-1"></i>
                            عرض آخر بيانات السوق
                        </a>
                    </div>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="alert alert-info text-center">السعر الحالي داخل النظام</h5>

                                    @if($currentGoldPrice)
                                        <table class="table table-bordered text-center mb-0">
                                            <thead>
                                            <tr>
                                                <th>عيار 14</th>
                                                <th>عيار 18</th>
                                                <th>عيار 21</th>
                                                <th>عيار 22</th>
                                                <th>عيار 24</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <tr>
                                                <td>{{ number_format($currentGoldPrice->ounce_14_price, 2) }}</td>
                                                <td>{{ number_format($currentGoldPrice->ounce_18_price, 2) }}</td>
                                                <td>{{ number_format($currentGoldPrice->ounce_21_price, 2) }}</td>
                                                <td>{{ number_format($currentGoldPrice->ounce_22_price, 2) }}</td>
                                                <td>{{ number_format($currentGoldPrice->ounce_24_price, 2) }}</td>
                                            </tr>
                                            </tbody>
                                        </table>

                                        <div class="mt-3 text-center text-muted">
                                            <div>سعر الأونصة: {{ number_format($currentGoldPrice->ounce_price, 2) }} {{ $currentGoldPrice->currency }}</div>
                                            <div>المصدر: {{ $currentGoldPrice->source_label }}</div>
                                            <div>آخر تحديث: {{ optional($currentGoldPrice->last_update)->format('Y-m-d H:i:s') }}</div>
                                        </div>
                                    @else
                                        <div class="alert alert-warning text-center mb-0">
                                            لا توجد أسعار محفوظة بعد. ابدأ بالتحديث الخارجي أو الإدخال اليدوي.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="alert alert-secondary text-center">آخر Snapshot خارجي محفوظ</h5>

                                    @if($latestMarketSnapshot)
                                        <div class="table-responsive">
                                            <table class="table table-bordered text-center mb-0">
                                                <thead>
                                                <tr>
                                                    <th>العملة</th>
                                                    <th>الأونصة</th>
                                                    <th>عيار 21</th>
                                                    <th>عيار 24</th>
                                                    <th>التوقيت</th>
                                                </tr>
                                                </thead>
                                                <tbody>
                                                <tr>
                                                    <td>{{ $latestMarketSnapshot->currency }}</td>
                                                    <td>{{ number_format($latestMarketSnapshot->ounce_price, 2) }}</td>
                                                    <td>{{ number_format($latestMarketSnapshot->ounce_21_price, 2) }}</td>
                                                    <td>{{ number_format($latestMarketSnapshot->ounce_24_price, 2) }}</td>
                                                    <td>{{ optional($latestMarketSnapshot->synced_at)->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    @else
                                        <div class="alert alert-light text-center mb-0">
                                            لا يوجد Snapshot خارجي محفوظ بعد. نفّذ تحديثًا خارجيًا أولًا.
                                        </div>
                                    @endif
                                </div>
                            </div>

                            <div class="card shadow-sm">
                                <div class="card-body">
                                    <h5 class="alert alert-light text-center">سجل التحديثات الأخيرة</h5>

                                    <div class="table-responsive">
                                        <table class="table table-bordered text-center mb-0">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>المصدر</th>
                                                <th>العملة</th>
                                                <th>عيار 21</th>
                                                <th>بواسطة</th>
                                                <th>التوقيت</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            @forelse($priceHistory as $historyRow)
                                                <tr>
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>{{ $historyRow->source_label }}</td>
                                                    <td>{{ $historyRow->currency }}</td>
                                                    <td>{{ number_format($historyRow->ounce_21_price, 2) }}</td>
                                                    <td>{{ $historyRow->actor?->name ?? 'النظام' }}</td>
                                                    <td>{{ optional($historyRow->synced_at)->format('Y-m-d H:i:s') }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="6">لا يوجد سجل تحديثات حتى الآن.</td>
                                                </tr>
                                            @endforelse
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
    </div>

    @can('employee.gold_prices.edit')
        <div class="modal fade" id="manualPricingModal" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <label class="modelTitle">تحديث أسعار الذهب يدويًا</label>
                        <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('updatePricesManual') }}">
                            @csrf
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>العملة</label>
                                        <input type="text" name="currency" class="form-control" value="{{ old('currency', $currentGoldPrice->currency ?? 'SAR') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>عيار 14</label>
                                        <input type="number" step="0.01" min="0" name="price14" class="form-control" value="{{ old('price14', $currentGoldPrice->ounce_14_price ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>عيار 18</label>
                                        <input type="number" step="0.01" min="0" name="price18" class="form-control" value="{{ old('price18', $currentGoldPrice->ounce_18_price ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>عيار 21</label>
                                        <input type="number" step="0.01" min="0" name="price21" class="form-control" value="{{ old('price21', $currentGoldPrice->ounce_21_price ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>عيار 22</label>
                                        <input type="number" step="0.01" min="0" name="price22" class="form-control" value="{{ old('price22', $currentGoldPrice->ounce_22_price ?? '') }}" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>عيار 24</label>
                                        <input type="number" step="0.01" min="0" name="price24" class="form-control" value="{{ old('price24', $currentGoldPrice->ounce_24_price ?? '') }}" required>
                                    </div>
                                </div>
                            </div>

                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-primary px-5">{{ __('main.save_btn') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endsection

@section('js')
    <script type="text/javascript">
        $(document).ready(function () {
            $(document).on('click', '#openManualPricingModal', function () {
                $('#manualPricingModal').modal('show');
            });
        });
    </script>
@endsection
