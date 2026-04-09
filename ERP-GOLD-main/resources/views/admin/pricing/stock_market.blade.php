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
                        <h4 class="alert alert-primary text-center mb-0">أسعار بورصة الذهب المحفوظة</h4>
                    </div>
                </div>

                <div class="card-body">
                    @can('employee.gold_prices.edit')
                        <div class="d-flex flex-wrap justify-content-center mb-4" style="gap: 12px;">
                            <form method="POST" action="{{ route('updatePrices') }}" class="mb-0">
                                @csrf
                                <input type="hidden" name="currency" value="USD">
                                <input type="hidden" name="redirect_to" value="stock_market">
                                <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 10px;">
                                    تحديث Snapshot الدولار
                                </button>
                            </form>
                            <form method="POST" action="{{ route('updatePrices') }}" class="mb-0">
                                @csrf
                                <input type="hidden" name="currency" value="SAR">
                                <input type="hidden" name="redirect_to" value="stock_market">
                                <button type="submit" class="btn btn-sm btn-primary shadow-sm" style="border-radius: 10px;">
                                    تحديث Snapshot الريال
                                </button>
                            </form>
                        </div>
                    @endcan

                    <div class="row">
                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="alert alert-info text-center">آخر Snapshot بالدولار</h5>

                                    <table class="table table-bordered text-center mb-0 {{ $latestUsdSnapshot ? '' : 'd-none' }}" id="stock-market-usd-table">
                                        <thead>
                                        <tr>
                                            <th>الأونصة</th>
                                            <th>عيار 21</th>
                                            <th>عيار 24</th>
                                            <th>التوقيت</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td id="stock-market-usd-ounce">{{ $latestUsdSnapshot ? number_format($latestUsdSnapshot->ounce_price, 2) : '--' }}</td>
                                            <td id="stock-market-usd-21">{{ $latestUsdSnapshot ? number_format($latestUsdSnapshot->ounce_21_price, 2) : '--' }}</td>
                                            <td id="stock-market-usd-24">{{ $latestUsdSnapshot ? number_format($latestUsdSnapshot->ounce_24_price, 2) : '--' }}</td>
                                            <td id="stock-market-usd-updated">{{ $latestUsdSnapshot ? optional($latestUsdSnapshot->synced_at)->format('Y-m-d H:i:s') : 'لا يوجد تحديث' }}</td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <div class="alert alert-light text-center mb-0 {{ $latestUsdSnapshot ? 'd-none' : '' }}" id="stock-market-usd-empty">لا يوجد Snapshot بالدولار حتى الآن.</div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="alert alert-info text-center">آخر Snapshot بالريال السعودي</h5>

                                    <table class="table table-bordered text-center mb-0 {{ $latestSarSnapshot ? '' : 'd-none' }}" id="stock-market-sar-table">
                                        <thead>
                                        <tr>
                                            <th>الأونصة</th>
                                            <th>عيار 21</th>
                                            <th>عيار 24</th>
                                            <th>التوقيت</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <tr>
                                            <td id="stock-market-sar-ounce">{{ $latestSarSnapshot ? number_format($latestSarSnapshot->ounce_price, 2) : '--' }}</td>
                                            <td id="stock-market-sar-21">{{ $latestSarSnapshot ? number_format($latestSarSnapshot->ounce_21_price, 2) : '--' }}</td>
                                            <td id="stock-market-sar-24">{{ $latestSarSnapshot ? number_format($latestSarSnapshot->ounce_24_price, 2) : '--' }}</td>
                                            <td id="stock-market-sar-updated">{{ $latestSarSnapshot ? optional($latestSarSnapshot->synced_at)->format('Y-m-d H:i:s') : 'لا يوجد تحديث' }}</td>
                                        </tr>
                                        </tbody>
                                    </table>

                                    <div class="alert alert-light text-center mb-0 {{ $latestSarSnapshot ? 'd-none' : '' }}" id="stock-market-sar-empty">لا يوجد Snapshot بالريال حتى الآن.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="alert alert-light text-center">سجل مزامنة السوق</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered text-center mb-0">
                                    <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>العملة</th>
                                        <th>الأونصة</th>
                                        <th>عيار 21</th>
                                        <th>التوقيت</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    @forelse($remoteHistory as $historyRow)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td>{{ $historyRow->currency }}</td>
                                            <td>{{ number_format($historyRow->ounce_price, 2) }}</td>
                                            <td>{{ number_format($historyRow->ounce_21_price, 2) }}</td>
                                            <td>{{ optional($historyRow->synced_at)->format('Y-m-d H:i:s') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5">لا يوجد سجل مزامنة خارجي بعد.</td>
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
@endsection

@section('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('gold-price:ticker-updated', function (event) {
                var market = (event.detail || {}).market_snapshots || {};

                function applySnapshot(prefix, snapshot) {
                    if (!snapshot || !snapshot.exists) {
                        return;
                    }

                    var bindings = {
                        [prefix + '-ounce']: snapshot.ounce_price_label,
                        [prefix + '-21']: snapshot.ounce_21_price_label,
                        [prefix + '-24']: snapshot.ounce_24_price_label,
                        [prefix + '-updated']: snapshot.synced_at_label
                    };

                    Object.keys(bindings).forEach(function (id) {
                        var node = document.getElementById(id);

                        if (node && bindings[id]) {
                            node.textContent = bindings[id];
                        }
                    });

                    var table = document.getElementById(prefix + '-table');
                    var empty = document.getElementById(prefix + '-empty');

                    if (table) {
                        table.classList.remove('d-none');
                    }

                    if (empty) {
                        empty.classList.add('d-none');
                    }
                }

                applySnapshot('stock-market-usd', market.USD || {});
                applySnapshot('stock-market-sar', market.SAR || {});
            });
        });
    </script>
@endsection
