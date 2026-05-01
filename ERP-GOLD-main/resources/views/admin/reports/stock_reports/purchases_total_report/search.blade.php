@extends('admin.layouts.master')

@section('content')
<div class="row row-sm">
    <div class="col-xl-12">
        <div class="card">
            <div class="card-header pb-0 text-center">
                <h4 class="alert alert-primary text-center">{{ $pageTitle ?? __('main.purchases_total_report') }}</h4>
            </div>
        </div>

        <div class="card-body px-0 pt-0 pb-2">
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="POST" action="{{ $formAction ?? (isset($presetClassification) ? ($presetClassification === 'collectible' ? route('reports.collectible.purchases_total_report.index') : route('reports.silver.purchases_total_report.index')) : route('reports.purchases_total_report.index')) }}" enctype="multipart/form-data">
                        @csrf
                        @if(isset($presetClassification))
                            <input type="hidden" name="classification" value="{{ $presetClassification }}">
                        @endif
                        @include('admin.reports.stock_reports.partials.common_filters', [
                            'branches' => $branches,
                            'users' => $users,
                            'defaultFilters' => $defaultFilters,
                            'showCarat' => false,
                            'showNetMoney' => true,
                        ])

                        @include('admin.reports.stock_reports.partials.print_actions')
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
