@extends('admin.layouts.master')

@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0 text-center">
                <h4 class="alert alert-primary text-center">{{ __('main.sales_total_report') }}</h4>
            </div>
        </div>

        <div class="card-body px-0 pt-0 pb-2">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ route('reports.sales_total_report.index') }}" enctype="multipart/form-data">
                        @csrf
                        @include('admin.reports.stock_reports.partials.common_filters', [
                            'branches' => $branches,
                            'users' => $users,
                            'defaultFilters' => $defaultFilters,
                            'showCarat' => false,
                            'showNetMoney' => true,
                        ])

                        <div class="row">
                            <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                                <button type="submit" class="btn btn-labeled btn-primary">
                                    {{ __('main.search_btn') }}
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
