@extends('admin.layouts.master')

@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0 text-center">
                <h4 class="alert alert-primary text-center">التقرير اليومي للمبيعات والمشتريات حسب العيار</h4>
            </div>
        </div>

        <div class="card-body px-0 pt-0 pb-2">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('reports.daily_carat_report.index') }}">
                        @csrf
                        @include('admin.reports.stock_reports.partials.common_filters', [
                            'branches' => $branches,
                            'users' => $users,
                            'carats' => $carats,
                            'defaultFilters' => $defaultFilters,
                            'showCarat' => true,
                            'showNetMoney' => false,
                        ])

                        <div class="row mt-4">
                            <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                                <button type="submit" class="btn btn-labeled btn-primary">{{ __('main.search_btn') }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
