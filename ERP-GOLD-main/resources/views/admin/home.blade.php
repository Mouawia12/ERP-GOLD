@extends('admin.layouts.master')

@php
    $summaryCards = [
        [
            'label' => 'مبيعات اليوم',
            'value' => number_format($overview['today_sales_total'], 2),
            'meta' => 'صافي قبل خصم مرتجعات اليوم',
            'class' => 'dashboard-card--gold',
            'icon' => 'fa-coins',
        ],
        [
            'label' => 'صافي البيع اليومي',
            'value' => number_format($overview['today_net_sales_total'], 2),
            'meta' => 'المبيعات - مرتجع المبيعات',
            'class' => 'dashboard-card--emerald',
            'icon' => 'fa-chart-line',
        ],
        [
            'label' => 'مشتريات اليوم',
            'value' => number_format($overview['today_purchases_total'], 2),
            'meta' => 'إجمالي فواتير الشراء',
            'class' => 'dashboard-card--blue',
            'icon' => 'fa-cart-plus',
        ],
        [
            'label' => 'وزن البيع اليوم',
            'value' => number_format($overview['today_sold_weight'], 3) . ' جم',
            'meta' => 'وزن ذهبي محسوب بعامل التحويل',
            'class' => 'dashboard-card--rose',
            'icon' => 'fa-weight-scale',
        ],
        [
            'label' => 'وزن الشراء اليوم',
            'value' => number_format($overview['today_purchased_weight'], 3) . ' جم',
            'meta' => 'الوزن الداخل على فواتير الشراء',
            'class' => 'dashboard-card--violet',
            'icon' => 'fa-box-open',
        ],
        [
            'label' => 'عمليات اليوم',
            'value' => number_format($overview['today_invoice_count']),
            'meta' => 'بيع وشراء ومرتجعات اليوم',
            'class' => 'dashboard-card--slate',
            'icon' => 'fa-file-invoice-dollar',
        ],
    ];
@endphp

<style>
    .owner-dashboard {
        padding-bottom: 24px;
    }

    .owner-dashboard__hero {
        background: linear-gradient(135deg, #1f3c56 0%, #a87912 100%);
        border-radius: 22px;
        color: #fff;
        padding: 28px 24px;
        box-shadow: 0 24px 48px rgba(31, 60, 86, 0.18);
        margin-bottom: 22px;
    }

    .owner-dashboard__eyebrow {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: rgba(255, 255, 255, 0.14);
        border-radius: 999px;
        padding: 6px 14px;
        font-size: 13px;
        margin-bottom: 14px;
    }

    .owner-dashboard__title {
        font-size: 28px;
        font-weight: 700;
        margin-bottom: 8px;
    }

    .owner-dashboard__subtitle {
        color: rgba(255, 255, 255, 0.86);
        font-size: 15px;
        margin-bottom: 0;
    }

    .owner-dashboard__hero-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 14px;
        margin-top: 18px;
    }

    .owner-dashboard__hero-box {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(255, 255, 255, 0.14);
        border-radius: 18px;
        padding: 14px 16px;
    }

    .owner-dashboard__hero-box-label {
        display: block;
        font-size: 12px;
        color: rgba(255, 255, 255, 0.74);
        margin-bottom: 8px;
    }

    .owner-dashboard__hero-box-value {
        font-size: 20px;
        font-weight: 700;
        display: block;
    }

    .dashboard-card {
        border: 0;
        border-radius: 20px;
        overflow: hidden;
        min-height: 165px;
        box-shadow: 0 14px 32px rgba(19, 28, 45, 0.08);
    }

    .dashboard-card__body {
        padding: 18px 18px 16px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .dashboard-card__icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(255, 255, 255, 0.18);
        color: #fff;
        font-size: 18px;
        margin-bottom: 16px;
    }

    .dashboard-card__label,
    .dashboard-card__meta,
    .dashboard-card__value {
        color: #fff;
    }

    .dashboard-card__label {
        display: block;
        font-size: 14px;
        opacity: 0.88;
    }

    .dashboard-card__value {
        font-size: 28px;
        font-weight: 700;
        margin: 12px 0 4px;
        display: block;
    }

    .dashboard-card__meta {
        font-size: 12px;
        opacity: 0.8;
    }

    .dashboard-card--gold { background: linear-gradient(135deg, #9c6b11 0%, #d9a328 100%); }
    .dashboard-card--emerald { background: linear-gradient(135deg, #0f6d5d 0%, #2ea37d 100%); }
    .dashboard-card--blue { background: linear-gradient(135deg, #1b4f8f 0%, #3b82d1 100%); }
    .dashboard-card--rose { background: linear-gradient(135deg, #7c3053 0%, #c85f89 100%); }
    .dashboard-card--violet { background: linear-gradient(135deg, #5f3f8c 0%, #8a68c0 100%); }
    .dashboard-card--slate { background: linear-gradient(135deg, #2d3d4f 0%, #536779 100%); }

    .dashboard-panel {
        border: 0;
        border-radius: 20px;
        box-shadow: 0 12px 30px rgba(19, 28, 45, 0.07);
        margin-bottom: 22px;
    }

    .dashboard-panel__header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        margin-bottom: 18px;
    }

    .dashboard-panel__title {
        font-size: 18px;
        font-weight: 700;
        color: #18283c;
        margin-bottom: 4px;
    }

    .dashboard-panel__subtitle {
        font-size: 13px;
        color: #7a8699;
        margin-bottom: 0;
    }

    .dashboard-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #f4f7fb;
        color: #38506a;
        border-radius: 999px;
        padding: 8px 14px;
        font-size: 13px;
        font-weight: 600;
    }

    .dashboard-mini-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
        gap: 12px;
    }

    .dashboard-mini-list__item {
        background: #f8fafc;
        border: 1px solid #e7edf5;
        border-radius: 16px;
        padding: 14px 16px;
    }

    .dashboard-mini-list__label {
        display: block;
        font-size: 12px;
        color: #6a7b91;
        margin-bottom: 8px;
    }

    .dashboard-mini-list__value {
        display: block;
        font-size: 22px;
        color: #203247;
        font-weight: 700;
    }

    .dashboard-table thead th {
        border-top: 0;
        background: #f4f7fb;
        color: #53667d;
        font-size: 12px;
        white-space: nowrap;
    }

    .dashboard-table tbody td {
        vertical-align: middle;
    }

    .dashboard-empty {
        border: 1px dashed #d8e0eb;
        border-radius: 16px;
        padding: 20px;
        color: #7a8699;
        text-align: center;
        background: #fafcfe;
    }
</style>

@section('page-header')
    <div class="breadcrumb-header justify-content-between">
        <div class="left-content">
            <div>
                <h2 class="main-content-title tx-24 mg-b-1 mg-b-lg-1">لوحة المالك</h2>
                <p class="mb-0 text-muted">ملخص يومي موحد للمال والحركة الوزنية والمستخدمين والفروع.</p>
            </div>
        </div>
    </div>

    <div class="owner-dashboard">
        <div class="owner-dashboard__hero">
            <span class="owner-dashboard__eyebrow">
                <i class="fa fa-layer-group"></i>
                {{ $user->is_admin ? 'عرض المالك على مستوى جميع الفروع' : 'عرض الفرع النشط فقط' }}
            </span>
            <div class="row align-items-end">
                <div class="col-xl-7">
                    <h1 class="owner-dashboard__title">تشغيل {{ $scopeLabel }}</h1>
                    <p class="owner-dashboard__subtitle">
                        تاريخ العمل: {{ $today->format('Y-m-d') }}
                        @if($latestGoldPrice)
                            | آخر تحديث سعر: {{ optional($latestGoldPrice->last_update)->format('Y-m-d H:i') }}
                        @endif
                    </p>
                </div>
                <div class="col-xl-5">
                    <div class="owner-dashboard__hero-grid">
                        <div class="owner-dashboard__hero-box">
                            <span class="owner-dashboard__hero-box-label">الفروع النشطة اليوم</span>
                            <span class="owner-dashboard__hero-box-value">{{ number_format($overview['today_active_branches_count']) }}</span>
                        </div>
                        <div class="owner-dashboard__hero-box">
                            <span class="owner-dashboard__hero-box-label">العملاء</span>
                            <span class="owner-dashboard__hero-box-value">{{ number_format($directoryCounts['customers']) }}</span>
                        </div>
                        <div class="owner-dashboard__hero-box">
                            <span class="owner-dashboard__hero-box-label">الموردون</span>
                            <span class="owner-dashboard__hero-box-value">{{ number_format($directoryCounts['suppliers']) }}</span>
                        </div>
                        <div class="owner-dashboard__hero-box">
                            <span class="owner-dashboard__hero-box-label">الأصناف</span>
                            <span class="owner-dashboard__hero-box-value">{{ number_format($directoryCounts['items']) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            @foreach($summaryCards as $card)
                <div class="col-xl-4 col-lg-6 col-md-6">
                    <div class="card dashboard-card {{ $card['class'] }}">
                        <div class="dashboard-card__body">
                            <div>
                                <span class="dashboard-card__icon">
                                    <i class="fa {{ $card['icon'] }}"></i>
                                </span>
                                <span class="dashboard-card__label">{{ $card['label'] }}</span>
                                <span class="dashboard-card__value">{{ $card['value'] }}</span>
                            </div>
                            <span class="dashboard-card__meta">{{ $card['meta'] }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row mt-2">
            <div class="col-xl-5">
                <div class="card dashboard-panel">
                    <div class="card-body">
                        <div class="dashboard-panel__header">
                            <div>
                                <h3 class="dashboard-panel__title">آخر تحديث لأسعار الذهب</h3>
                                <p class="dashboard-panel__subtitle">Snapshot واحد واضح لآخر سعر تم اعتماده داخل النظام.</p>
                            </div>
                            <span class="dashboard-chip">
                                <i class="fa fa-clock"></i>
                                {{ $latestGoldPrice ? optional($latestGoldPrice->last_update)->format('Y-m-d H:i') : 'لا يوجد تحديث' }}
                            </span>
                        </div>

                        @if($latestGoldPrice)
                            <div class="table-responsive">
                                <table class="table dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>الأونصة</th>
                                            <th>عيار 18</th>
                                            <th>عيار 21</th>
                                            <th>عيار 24</th>
                                            <th>العملة</th>
                                            <th>المصدر</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>{{ number_format($latestGoldPrice->ounce_price, 2) }}</td>
                                            <td>{{ number_format($latestGoldPrice->ounce_18_price, 2) }}</td>
                                            <td>{{ number_format($latestGoldPrice->ounce_21_price, 2) }}</td>
                                            <td>{{ number_format($latestGoldPrice->ounce_24_price, 2) }}</td>
                                            <td>{{ $latestGoldPrice->currency }}</td>
                                            <td>{{ $latestGoldPrice->source_label }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="dashboard-empty">لا يوجد Snapshot أسعار محفوظ حتى الآن.</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-xl-7">
                <div class="card dashboard-panel">
                    <div class="card-body">
                        <div class="dashboard-panel__header">
                            <div>
                                <h3 class="dashboard-panel__title">دليل الكيانات</h3>
                                <p class="dashboard-panel__subtitle">أرقام مرجعية سريعة تساعد المالك على قراءة حجم التشغيل الحالي.</p>
                            </div>
                            <span class="dashboard-chip">
                                <i class="fa fa-building"></i>
                                {{ $scopeLabel }}
                            </span>
                        </div>

                        <div class="dashboard-mini-list">
                            <div class="dashboard-mini-list__item">
                                <span class="dashboard-mini-list__label">عدد الفروع</span>
                                <span class="dashboard-mini-list__value">{{ number_format($directoryCounts['branches']) }}</span>
                            </div>
                            <div class="dashboard-mini-list__item">
                                <span class="dashboard-mini-list__label">عدد المستخدمين</span>
                                <span class="dashboard-mini-list__value">{{ number_format($directoryCounts['users']) }}</span>
                            </div>
                            <div class="dashboard-mini-list__item">
                                <span class="dashboard-mini-list__label">مرتجع المبيعات</span>
                                <span class="dashboard-mini-list__value">{{ number_format($overview['today_sales_return_total'], 2) }}</span>
                            </div>
                            <div class="dashboard-mini-list__item">
                                <span class="dashboard-mini-list__label">مرتجع المشتريات</span>
                                <span class="dashboard-mini-list__value">{{ number_format($overview['today_purchase_return_total'], 2) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-4">
                <div class="card dashboard-panel">
                    <div class="card-body">
                        <div class="dashboard-panel__header">
                            <div>
                                <h3 class="dashboard-panel__title">المشتريات حسب العيار</h3>
                                <p class="dashboard-panel__subtitle">تجميع يومي للوزن والقيمة بحسب العيار المشتَرى.</p>
                            </div>
                            <span class="dashboard-chip">
                                <i class="fa fa-weight-hanging"></i>
                                {{ number_format($overview['today_purchased_weight'], 3) }} جم
                            </span>
                        </div>

                        @forelse($purchaseBreakdown as $row)
                            <div class="d-flex justify-content-between align-items-center border rounded-lg px-3 py-2 mb-2">
                                <div>
                                    <strong>{{ $row->carat_title }}</strong>
                                    <div class="text-muted small">{{ number_format($row->invoice_count) }} فاتورة شراء</div>
                                </div>
                                <div class="text-left">
                                    <div class="font-weight-bold">{{ number_format($row->total_in_weight, 3) }} جم</div>
                                    <div class="text-muted small">{{ number_format($row->total_net_total, 2) }}</div>
                                </div>
                            </div>
                        @empty
                            <div class="dashboard-empty">لا توجد مشتريات مسجلة لهذا اليوم.</div>
                        @endforelse
                    </div>
                </div>
            </div>

            <div class="col-xl-8">
                <div class="card dashboard-panel">
                    <div class="card-body">
                        <div class="dashboard-panel__header">
                            <div>
                                <h3 class="dashboard-panel__title">أعلى المستخدمين نشاطًا اليوم</h3>
                                <p class="dashboard-panel__subtitle">الترتيب يعتمد على عدد المستندات ثم إجمالي المبالغ التي عالجها كل مستخدم.</p>
                            </div>
                        </div>

                        @if($topUsers->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>المستخدم</th>
                                            <th>الفرع</th>
                                            <th>عدد المستندات</th>
                                            <th>مبيعات</th>
                                            <th>مشتريات</th>
                                            <th>إجمالي معالج</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topUsers as $row)
                                            <tr>
                                                <td>{{ $row->user_name }}</td>
                                                <td>{{ $row->branch_name }}</td>
                                                <td>{{ number_format($row->invoice_count) }}</td>
                                                <td>{{ number_format($row->sales_total, 2) }}</td>
                                                <td>{{ number_format($row->purchases_total, 2) }}</td>
                                                <td>{{ number_format($row->processed_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="dashboard-empty">لا توجد حركة مستخدمين لهذا اليوم.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-xl-12">
                <div class="card dashboard-panel">
                    <div class="card-body">
                        <div class="dashboard-panel__header">
                            <div>
                                <h3 class="dashboard-panel__title">أعلى الفروع أداءً اليوم</h3>
                                <p class="dashboard-panel__subtitle">ترتيب الفروع مبني على صافي المبيعات اليومية ثم حجم المستندات.</p>
                            </div>
                        </div>

                        @if($topBranches->isNotEmpty())
                            <div class="table-responsive">
                                <table class="table dashboard-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>الفرع</th>
                                            <th>عدد المستندات</th>
                                            <th>مبيعات</th>
                                            <th>مرتجع مبيعات</th>
                                            <th>صافي البيع</th>
                                            <th>مشتريات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($topBranches as $row)
                                            <tr>
                                                <td>{{ $row->branch_name }}</td>
                                                <td>{{ number_format($row->invoice_count) }}</td>
                                                <td>{{ number_format($row->sales_total, 2) }}</td>
                                                <td>{{ number_format($row->sales_return_total, 2) }}</td>
                                                <td>{{ number_format($row->net_sales_total, 2) }}</td>
                                                <td>{{ number_format($row->purchases_total, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="dashboard-empty">لا توجد فروع ذات حركة اليوم.</div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
