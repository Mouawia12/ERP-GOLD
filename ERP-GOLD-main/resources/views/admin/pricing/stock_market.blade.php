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
                                <button type="submit" class="btn btn-sm btn-outline-primary shadow-sm" style="border-radius: 10px;">
                                    تحديث Snapshot الدولار
                                </button>
                            </form>
                            <form method="POST" action="{{ route('updatePrices') }}" class="mb-0">
                                @csrf
                                <input type="hidden" name="currency" value="SAR">
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

                                    @if($latestUsdSnapshot)
                                        <table class="table table-bordered text-center mb-0">
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
                                                <td>{{ number_format($latestUsdSnapshot->ounce_price, 2) }}</td>
                                                <td>{{ number_format($latestUsdSnapshot->ounce_21_price, 2) }}</td>
                                                <td>{{ number_format($latestUsdSnapshot->ounce_24_price, 2) }}</td>
                                                <td>{{ optional($latestUsdSnapshot->synced_at)->format('Y-m-d H:i:s') }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="alert alert-light text-center mb-0">لا يوجد Snapshot بالدولار حتى الآن.</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card shadow-sm mb-4">
                                <div class="card-body">
                                    <h5 class="alert alert-info text-center">آخر Snapshot بالريال السعودي</h5>

                                    @if($latestSarSnapshot)
                                        <table class="table table-bordered text-center mb-0">
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
                                                <td>{{ number_format($latestSarSnapshot->ounce_price, 2) }}</td>
                                                <td>{{ number_format($latestSarSnapshot->ounce_21_price, 2) }}</td>
                                                <td>{{ number_format($latestSarSnapshot->ounce_24_price, 2) }}</td>
                                                <td>{{ optional($latestSarSnapshot->synced_at)->format('Y-m-d H:i:s') }}</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    @else
                                        <div class="alert alert-light text-center mb-0">لا يوجد Snapshot بالريال حتى الآن.</div>
                                    @endif
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
