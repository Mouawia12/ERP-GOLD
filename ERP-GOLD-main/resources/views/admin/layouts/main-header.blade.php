<!-- main-header opened -->
<style>
    .main-header {
        display: block !important;
        height: auto !important;
        min-height: var(--erp-main-header-row-height, 64px) !important;
        margin-bottom: 0 !important;
        transition: padding-right .28s ease, height .18s ease;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
    }

    .main-header > .container-fluid.main-header__nav-row {
        height: auto !important;
        min-height: var(--erp-main-header-row-height, 64px);
        align-items: center;
        padding: 10px 18px;
        background: rgba(255, 255, 255, 0.98);
        border: 1px solid rgba(221, 230, 243, 0.95);
        border-radius: 22px;
        box-shadow: 0 16px 34px rgba(28, 46, 78, 0.1);
    }

    .responsive-logo a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 46px;
        padding: 4px 0;
        border: 0;
        background: transparent;
        box-shadow: none;
    }

    .responsive-logo .brand-header-logo {
        width: 156px;
        max-width: 100%;
        max-height: 64px;
        object-fit: contain;
    }

    .branch-switcher-form {
        min-width: 260px;
        margin-left: 12px;
    }

    .branch-switcher-form .form-control {
        height: 38px;
    }

    .main-header-left {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
    }

    .main-header__nav-row {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .main-header__ticker-slot {
        flex: 1 1 520px;
        min-width: 320px;
    }

    .gold-price-ticker-shell {
        width: 100%;
        display: flex;
        align-items: center;
        justify-content: flex-start;
        margin-inline: 0;
    }

    .gold-price-ticker {
        width: 100%;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
        padding: 8px 14px;
        border-radius: 16px;
        border: 1px solid rgba(191, 162, 83, 0.34);
        background: linear-gradient(135deg, rgba(255, 249, 234, 0.98) 0%, rgba(248, 238, 204, 0.98) 100%);
        box-shadow: 0 12px 28px rgba(146, 111, 27, 0.1);
        color: #5a430f;
    }

    .gold-price-ticker--loading {
        opacity: 0.82;
    }

    .gold-price-ticker__status {
        display: inline-flex;
        align-items: center;
        gap: 0;
        flex: 0 0 auto;
        line-height: 0;
    }

    .gold-price-ticker__pulse {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        background: #2eb67d;
        box-shadow: 0 0 0 0 rgba(46, 182, 125, 0.35);
        animation: goldTickerPulse 1.8s infinite;
    }

    .gold-price-ticker[data-state="warning"] .gold-price-ticker__pulse {
        background: #f59e0b;
        box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.35);
    }

    .gold-price-ticker__items {
        flex: 1 1 auto;
        min-width: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-wrap: wrap;
        gap: 10px;
    }

    .gold-price-ticker__item {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.65);
        border: 1px solid rgba(157, 126, 46, 0.16);
        font-size: 11.5px;
        font-weight: 700;
        color: #624814;
        white-space: nowrap;
    }

    .gold-price-ticker__item-label {
        color: #8a6b26;
    }

    .gold-price-ticker__item-value {
        color: #3e2d08;
    }

    .gold-price-ticker__meta {
        display: inline-flex;
        flex-direction: column;
        align-items: flex-start;
        text-align: left;
        font-size: 10.5px;
        font-weight: 700;
        line-height: 1.45;
        color: #7b6127;
        white-space: nowrap;
    }

    .gold-price-ticker__meta strong {
        color: #4b350a;
    }

    .gold-price-ticker__actions {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        flex: 0 0 auto;
    }

    .gold-price-ticker__btn {
        border: 0;
        border-radius: 12px;
        padding: 7px 11px;
        font-size: 11.5px;
        font-weight: 700;
        line-height: 1;
        transition: transform .15s ease, opacity .15s ease, box-shadow .15s ease;
    }

    .gold-price-ticker__btn:hover {
        transform: translateY(-1px);
    }

    .gold-price-ticker__btn:disabled {
        opacity: 0.65;
        cursor: not-allowed;
        transform: none;
    }

    .gold-price-ticker__btn--refresh {
        background: linear-gradient(135deg, #2f7ef7 0%, #1d52c8 100%);
        color: #fff;
        box-shadow: 0 12px 24px rgba(47, 126, 247, 0.24);
    }

    .gold-price-ticker__btn--manual {
        background: rgba(255, 255, 255, 0.78);
        color: #624814;
        border: 1px solid rgba(157, 126, 46, 0.2);
    }

    .gold-price-modal .modal-content {
        border-radius: 22px;
        overflow: hidden;
        border: 1px solid #e6edf8;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.2);
    }

    .gold-price-modal .modal-header {
        background: linear-gradient(135deg, #1f3c56 0%, #a87912 100%);
        color: #fff;
        border-bottom: 0;
    }

    .gold-price-modal .close {
        color: #fff;
        opacity: 0.9;
        text-shadow: none;
    }

    .gold-price-modal .modal-body {
        padding: 24px;
        background: #fbfdff;
    }

    .gold-price-modal .form-control {
        border-radius: 12px;
        border: 1px solid #dbe5f3;
        background: #fff !important;
    }

    .gold-price-modal .invalid-feedback {
        display: block;
        font-weight: 700;
    }

    @keyframes goldTickerPulse {
        0% {
            box-shadow: 0 0 0 0 rgba(46, 182, 125, 0.34);
        }

        70% {
            box-shadow: 0 0 0 9px rgba(46, 182, 125, 0);
        }

        100% {
            box-shadow: 0 0 0 0 rgba(46, 182, 125, 0);
        }
    }

    .app-sidebar__toggle {
        position: fixed;
        top: 10px;
        right: calc(var(--erp-sidebar-expanded, 300px) + 14px);
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0;
        z-index: 1030;
        transition: right .28s ease, transform .2s ease;
    }

    .app-sidebar__toggle a {
        position: relative;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 52px;
        height: 52px;
        border-radius: 18px;
        border: 1px solid rgba(161, 182, 219, 0.68);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.98) 0%, rgba(229, 238, 255, 0.98) 100%);
        color: #2f4c79 !important;
        box-shadow: 0 18px 36px rgba(20, 49, 92, 0.18);
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease, border-color .2s ease;
        text-decoration: none !important;
    }

    .app-sidebar__toggle a::after {
        content: "";
        position: absolute;
        inset: 4px;
        border-radius: 14px;
        border: 1px solid rgba(255, 255, 255, 0.82);
        pointer-events: none;
    }

    .app-sidebar__toggle a:hover {
        transform: translateY(-1px) scale(1.02);
        box-shadow: 0 20px 40px rgba(20, 49, 92, 0.22);
        border-color: rgba(122, 155, 214, 0.92);
    }

    body.modal-open .app-sidebar__toggle,
    body.modal-open .app-sidebar__overlay {
        opacity: 0 !important;
        visibility: hidden !important;
        pointer-events: none !important;
    }

    .app-sidebar__toggle .open-toggle {
        display: inline-flex;
    }

    .app-sidebar__toggle .close-toggle {
        display: none;
    }

    .main-header-right {
        display: flex;
        align-items: center;
        flex: 0 0 auto;
    }

    .main-header-right .navbar-nav-right {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 12px;
        flex-wrap: wrap;
        margin-left: 0 !important;
    }

    @media (min-width: 992px) {
        body.app .main-header {
            position: fixed !important;
            top: var(--erp-main-header-gap);
            left: var(--erp-layout-gutter);
            right: calc(var(--erp-sidebar-current-width, var(--erp-sidebar-expanded, 300px)) + var(--erp-layout-gutter));
            width: auto !important;
            padding-right: 0 !important;
            z-index: 1015 !important;
        }

        body.app .main-header__nav-row {
            flex-wrap: nowrap;
        }

        body.app .gold-price-ticker {
            gap: 8px;
        }

        body.app .gold-price-ticker__items {
            justify-content: space-between;
            flex-wrap: nowrap;
            gap: 6px;
        }

        body.app .gold-price-ticker__item {
            flex: 0 1 auto;
            gap: 4px;
            padding: 5px 8px;
            font-size: 11px;
        }

        body.app .gold-price-ticker__meta {
            flex-direction: row;
            align-items: center;
            flex: 0 0 auto;
            gap: 6px;
        }

        body.app .gold-price-ticker__actions {
            gap: 6px;
        }

        body.app .gold-price-ticker__btn {
            padding: 7px 10px;
            font-size: 11px;
        }

        body.app .app-sidebar__toggle {
            display: none !important;
            pointer-events: none !important;
        }

        body.app:not(.sidenav-toggled) .app-sidebar__toggle .open-toggle,
        body.app.sidenav-toggled.sidenav-toggled-open .app-sidebar__toggle .open-toggle {
            display: none;
        }

        body.app:not(.sidenav-toggled) .app-sidebar__toggle .close-toggle,
        body.app.sidenav-toggled.sidenav-toggled-open .app-sidebar__toggle .close-toggle {
            display: inline-flex;
        }

        body.app.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__toggle .open-toggle {
            display: inline-flex;
        }

        body.app.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__toggle .close-toggle {
            display: none;
        }
    }

    @media (max-width: 991.98px) {
        body.app.sidenav-toggled .app-sidebar__toggle .open-toggle {
            display: none;
        }

        body.app.sidenav-toggled .app-sidebar__toggle .close-toggle {
            display: inline-flex;
        }
    }

    .app-sidebar__toggle .header-icon,
    .app-sidebar__toggle .header-icons {
        position: relative;
        z-index: 1;
        font-size: 22px;
        line-height: 1;
    }

    body.app:not(.sidenav-toggled) .app-sidebar__toggle,
    body.app.sidenav-toggled.sidenav-toggled-open .app-sidebar__toggle {
        right: calc(var(--erp-sidebar-expanded, 300px) + 14px);
    }

    body.app.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__toggle {
        right: calc(var(--erp-sidebar-collapsed, 88px) + 14px);
    }

    @media (max-width: 991.98px) {
        .branch-switcher-form {
            min-width: 220px;
        }

        .main-header__ticker-slot {
            order: 3;
            flex: 1 1 100%;
            min-width: 100%;
        }

        .gold-price-ticker {
            border-radius: 16px;
            padding: 10px 12px;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .gold-price-ticker__meta,
        .gold-price-ticker__actions {
            width: 100%;
            justify-content: center;
            text-align: center;
            white-space: normal;
        }

        .gold-price-ticker__items {
            width: 100%;
        }

        .app-sidebar__toggle {
            top: 8px;
            right: 12px !important;
        }

        .app-sidebar__toggle a {
            width: 48px;
            height: 48px;
            border-radius: 16px;
        }
    }

    @media (max-width: 575.98px) {
        .app-sidebar__toggle {
            top: 6px;
            right: 8px !important;
        }

        .gold-price-ticker__item {
            flex: 1 1 calc(50% - 8px);
            justify-content: center;
        }
    }
</style>
<div class="main-header sticky side-header nav nav-item" id="main-header">
    <div class="container-fluid main-header__nav-row">
        <div class="main-header-left ">
            <div class="responsive-logo">
                <a href="{{ url('/admin/' . $page='home') }}">
                    <img src="{{ $brandLogoUrl }}"
                         class="logo-1 brand-header-logo" alt="logo">
                </a>
                <a href="{{ url('/admin/' . $page='home') }}">
                    <img src="{{ $brandLogoUrl }}"
                         class="logo-2 brand-header-logo" alt="logo">
                </a>
            </div> 
            <div class="app-sidebar__toggle" data-toggle="sidebar">
                <a class="open-toggle" href="#"><i class="header-icon fe fe-align-left"></i></a>
                <a class="close-toggle" href="#"><i class="header-icons fe fe-x"></i></a>
            </div>
        </div>
        @can('employee.gold_prices.show')
            <div class="main-header__ticker-slot">
                <div
                    id="gold_price_div"
                    class="gold-price-ticker-shell"
                    data-gold-live-endpoint="{{ route('gold.prices.live') }}"
                    data-refresh-interval-ms="{{ \App\Services\Pricing\GoldPriceSyncService::AUTO_REFRESH_INTERVAL_MINUTES * 60 * 1000 }}"
                >
                    <div class="gold-price-ticker gold-price-ticker--loading" data-state="loading" data-gold-ticker-root>
                        <div class="gold-price-ticker__status" aria-hidden="true">
                            <span class="gold-price-ticker__pulse"></span>
                        </div>
                        <div class="gold-price-ticker__items">
                            <span class="gold-price-ticker__item">
                                <span class="gold-price-ticker__item-label">عيار 18</span>
                                <strong class="gold-price-ticker__item-value" data-gold-ticker-price="18">--</strong>
                            </span>
                            <span class="gold-price-ticker__item">
                                <span class="gold-price-ticker__item-label">عيار 21</span>
                                <strong class="gold-price-ticker__item-value" data-gold-ticker-price="21">--</strong>
                            </span>
                            <span class="gold-price-ticker__item">
                                <span class="gold-price-ticker__item-label">عيار 22</span>
                                <strong class="gold-price-ticker__item-value" data-gold-ticker-price="22">--</strong>
                            </span>
                            <span class="gold-price-ticker__item">
                                <span class="gold-price-ticker__item-label">عيار 24</span>
                                <strong class="gold-price-ticker__item-value" data-gold-ticker-price="24">--</strong>
                            </span>
                            <span class="gold-price-ticker__item">
                                <span class="gold-price-ticker__item-label">الأونصة</span>
                                <strong class="gold-price-ticker__item-value" data-gold-ticker-price="ounce">--</strong>
                            </span>
                        </div>
                        <div class="gold-price-ticker__meta">
                            <div><strong data-gold-ticker-currency>--</strong></div>
                            <div data-gold-ticker-updated>لا يوجد تحديث</div>
                        </div>
                        @can('employee.gold_prices.edit')
                            <div class="gold-price-ticker__actions">
                                <button type="button" class="gold-price-ticker__btn gold-price-ticker__btn--refresh" data-gold-refresh-button>
                                    تحديث الأسعار
                                </button>
                                <button type="button" class="gold-price-ticker__btn gold-price-ticker__btn--manual" data-gold-manual-button>
                                    تحديث يدوي
                                </button>
                            </div>
                        @endcan
                    </div>
                </div>
            </div>
        @endcan
        <div class="main-header-right">

            <div class="nav nav-item  navbar-nav-right ml-auto">
                @if(($availableAdminBranches ?? collect())->count() > 1)
                    <form action="{{ route('admin.current_branch.update') }}" method="POST" class="branch-switcher-form">
                        @csrf
                        <div class="input-group">
                            <select class="form-control" name="branch_id" onchange="this.form.submit()">
                                @foreach(($availableAdminBranches ?? collect()) as $availableBranch)
                                    <option
                                        value="{{ $availableBranch->id }}"
                                        @selected((int) ($currentAdminBranch?->id ?? Auth::user()->branch_id) === (int) $availableBranch->id)
                                    >
                                        {{ $availableBranch->branch_name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="input-group-append">
                                <span class="input-group-text">الفرع النشط</span>
                            </div>
                        </div>
                    </form>
                @endif

                <div class="dropdown main-profile-menu nav nav-item nav-link">
                    <a class="profile-user d-flex" href="#">
                        @if (isset(Auth::user()->profile_pic) && !empty(Auth::user()->profile_pic) )
                            <img src="{{asset(Auth::user()->profile_pic)}}" alt="avatar"><i></i>
                        @else
                            <img src="{{asset('assets/img/avatar.png')}}" alt="avatar"><i></i>
                        @endif
                    </a>
                    <div class="dropdown-menu">
                        <div class="main-header-profile bg-primary p-3">
                            <div class="d-flex wd-100p">
                                <div class="main-img-user">
                                    @if (isset(Auth::user()->profile_pic) && !empty(Auth::user()->profile_pic) )
                                        <img src="{{asset(Auth::user()->profile_pic)}}" alt="avatar"><i></i>
                                    @else
                                        <img src="{{asset('assets/img/avatar.png')}}" alt="avatar"><i></i>
                                    @endif
                                </div>
                                
                                <div class="mr-3 my-auto">
                                    <h6>{{Auth::user()->name}}</h6>
                                    <span>
                                        {{Auth::user()->role_name}}
                                    </span>
                                    <span>
                                    @if(!empty(Auth::user()->branch_id))
                                        {{Auth::user()->branch->branch_name}}
                                    @endif
                                    </span>
                                </div>
                            </div>
                        </div>
                        <a class="dropdown-item" href="{{route('admin.profile.edit',Auth::user()->id)}}"><i
                                class="bx bx-cog"></i> تعديل الملف الشخصى </a> 
                        <form action="{{ route('admin.logout') }}" method="POST" class="m-0">
                            @csrf
                            <button type="submit" class="dropdown-item border-0 bg-transparent text-right w-100">
                                <i class="fa fa-power-off"></i> تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@can('employee.gold_prices.edit')
    <div class="modal fade gold-price-modal" id="goldPriceManualModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modelTitle mb-0">تحديث أسعار الذهب يدويًا</label>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if(old('manual_gold_update'))
                        <div class="alert alert-danger">
                            <ul class="mb-0 pr-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('updatePricesManual') }}" id="gold-price-manual-form">
                        @csrf
                        <input type="hidden" name="return_to" value="{{ request()->getRequestUri() }}">
                        <input type="hidden" name="manual_gold_update" value="1">

                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>العملة</label>
                                    <input
                                        type="text"
                                        name="currency"
                                        id="gold-manual-currency"
                                        class="form-control {{ old('manual_gold_update') && $errors->has('currency') ? 'is-invalid' : '' }}"
                                        value="{{ old('currency', 'SAR') }}"
                                        required
                                    >
                                    @if(old('manual_gold_update') && $errors->has('currency'))
                                        <div class="invalid-feedback">{{ $errors->first('currency') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عيار 14</label>
                                    <input type="number" step="0.01" min="0" name="price14" id="gold-manual-14" class="form-control {{ old('manual_gold_update') && $errors->has('price14') ? 'is-invalid' : '' }}" value="{{ old('price14') }}" required>
                                    @if(old('manual_gold_update') && $errors->has('price14'))
                                        <div class="invalid-feedback">{{ $errors->first('price14') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عيار 18</label>
                                    <input type="number" step="0.01" min="0" name="price18" id="gold-manual-18" class="form-control {{ old('manual_gold_update') && $errors->has('price18') ? 'is-invalid' : '' }}" value="{{ old('price18') }}" required>
                                    @if(old('manual_gold_update') && $errors->has('price18'))
                                        <div class="invalid-feedback">{{ $errors->first('price18') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عيار 21</label>
                                    <input type="number" step="0.01" min="0" name="price21" id="gold-manual-21" class="form-control {{ old('manual_gold_update') && $errors->has('price21') ? 'is-invalid' : '' }}" value="{{ old('price21') }}" required>
                                    @if(old('manual_gold_update') && $errors->has('price21'))
                                        <div class="invalid-feedback">{{ $errors->first('price21') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عيار 22</label>
                                    <input type="number" step="0.01" min="0" name="price22" id="gold-manual-22" class="form-control {{ old('manual_gold_update') && $errors->has('price22') ? 'is-invalid' : '' }}" value="{{ old('price22') }}" required>
                                    @if(old('manual_gold_update') && $errors->has('price22'))
                                        <div class="invalid-feedback">{{ $errors->first('price22') }}</div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label>عيار 24</label>
                                    <input type="number" step="0.01" min="0" name="price24" id="gold-manual-24" class="form-control {{ old('manual_gold_update') && $errors->has('price24') ? 'is-invalid' : '' }}" value="{{ old('price24') }}" required>
                                    @if(old('manual_gold_update') && $errors->has('price24'))
                                        <div class="invalid-feedback">{{ $errors->first('price24') }}</div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-3">
                            <button type="submit" class="btn btn-primary px-5">حفظ الأسعار</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
<script>
    (function () {
        var header = document.getElementById('main-header');
        var spacer = document.querySelector('[data-main-header-spacer]');
        var sidebar = document.querySelector('.app-sidebar');
        var body = document.body;
        var documentElement = document.documentElement;
        var resizeObserver = null;
        var bodyClassObserver = null;
        var frameId = null;
        var sidebarSyncTimeout = null;

        if (!header || !spacer) {
            return;
        }

        function syncMainHeaderOffset() {
            frameId = null;

            var styles = window.getComputedStyle(header);
            var rootStyles = window.getComputedStyle(documentElement);
            var headerRect = header.getBoundingClientRect();
            var headerHeight = Math.ceil(headerRect.height);
            var headerBottomGap = parseFloat(rootStyles.getPropertyValue('--erp-main-header-bottom-gap')) || 0;
            var headerOffset = styles.position === 'fixed'
                ? Math.ceil(headerRect.bottom + headerBottomGap)
                : 0;

            document.documentElement.style.setProperty('--erp-main-header-height', headerHeight + 'px');
            document.documentElement.style.setProperty('--erp-main-header-offset', headerOffset + 'px');
            spacer.style.height = headerOffset + 'px';
        }

        function queueMainHeaderOffsetSync() {
            if (frameId !== null) {
                window.cancelAnimationFrame(frameId);
            }

            frameId = window.requestAnimationFrame(syncMainHeaderOffset);
        }

        function scheduleSidebarLayoutSync() {
            queueMainHeaderOffsetSync();

            if (sidebarSyncTimeout !== null) {
                window.clearTimeout(sidebarSyncTimeout);
            }

            sidebarSyncTimeout = window.setTimeout(queueMainHeaderOffsetSync, 320);
        }

        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(queueMainHeaderOffsetSync);
            resizeObserver.observe(header);
        }

        if (window.MutationObserver && body) {
            bodyClassObserver = new MutationObserver(function (mutations) {
                for (var i = 0; i < mutations.length; i += 1) {
                    if (mutations[i].attributeName === 'class') {
                        scheduleSidebarLayoutSync();
                        break;
                    }
                }
            });

            bodyClassObserver.observe(body, {
                attributes: true,
                attributeFilter: ['class']
            });
        }

        if (sidebar) {
            sidebar.addEventListener('transitionend', scheduleSidebarLayoutSync);
        }

        header.addEventListener('transitionend', function (event) {
            if (!event || event.propertyName === 'padding-right' || event.propertyName === 'height') {
                scheduleSidebarLayoutSync();
            }
        });

        window.addEventListener('load', queueMainHeaderOffsetSync);
        window.addEventListener('resize', queueMainHeaderOffsetSync);
        window.addEventListener('orientationchange', queueMainHeaderOffsetSync);
        window.addEventListener('gold-price:ticker-updated', queueMainHeaderOffsetSync);
        document.addEventListener('click', function (event) {
            if (event.target && event.target.closest('[data-toggle="sidebar"]')) {
                scheduleSidebarLayoutSync();
            }
        });

        window.syncMainHeaderOffset = queueMainHeaderOffsetSync;
        queueMainHeaderOffsetSync();
    })();
</script>
<script>
    (function () {
        var shell = document.getElementById('gold_price_div');

        if (!shell) {
            return;
        }

        var root = shell.querySelector('[data-gold-ticker-root]');
        var endpoint = shell.getAttribute('data-gold-live-endpoint');
        var inFlight = false;
        var latestPayload = null;
        var refreshButton = shell.querySelector('[data-gold-refresh-button]');
        var manualButton = shell.querySelector('[data-gold-manual-button]');

        function fillManualForm(current) {
            if (!current) {
                return;
            }

            var bindings = {
                'gold-manual-currency': current.currency,
                'gold-manual-14': current.ounce_14_price_label,
                'gold-manual-18': current.ounce_18_price_label,
                'gold-manual-21': current.ounce_21_price_label,
                'gold-manual-22': current.ounce_22_price_label,
                'gold-manual-24': current.ounce_24_price_label
            };

            Object.keys(bindings).forEach(function (id) {
                var input = document.getElementById(id);

                if (input && bindings[id] && !input.dataset.userEdited) {
                    input.value = bindings[id];
                }
            });
        }

        function setTickerState(state) {
            if (!root) {
                return;
            }

            root.setAttribute('data-state', state);
            root.classList.toggle('gold-price-ticker--loading', state === 'loading');
        }

        function updateTicker(payload) {
            if (!payload || !root) {
                return;
            }

            latestPayload = payload;

            var current = payload.current || {};
            var updatedNode = root.querySelector('[data-gold-ticker-updated]');
            var currencyNode = root.querySelector('[data-gold-ticker-currency]');

            if (currencyNode) {
                currencyNode.textContent = current.currency || 'بدون عملة';
            }

            if (updatedNode) {
                updatedNode.textContent = current.last_update_label || 'لا يوجد تحديث';
            }

            {
                var ounceNode = root.querySelector('[data-gold-ticker-price="ounce"]');

                if (ounceNode) {
                    ounceNode.textContent = current.ounce_price_label || '--';
                }
            }

            ['18', '21', '22', '24'].forEach(function (carat) {
                var node = root.querySelector('[data-gold-ticker-price="' + carat + '"]');

                if (!node) {
                    return;
                }

                node.textContent = current['ounce_' + carat + '_price_label'] || '--';
            });

            if (current.exists) {
                fillManualForm(current);
            }

            setTickerState(payload.success === false ? 'warning' : 'ready');
        }

        function emitUpdate(payload) {
            window.dispatchEvent(new CustomEvent('gold-price:ticker-updated', {
                detail: payload
            }));
        }

        function refreshTicker(options) {
            if (!endpoint || inFlight) {
                return;
            }

            options = options || {};
            inFlight = true;
            setTickerState('loading');

            if (refreshButton) {
                refreshButton.disabled = true;
            }

            var url = new URL(endpoint, window.location.origin);

            if (options.refresh) {
                url.searchParams.set('refresh', '1');
            }

            if (options.force) {
                url.searchParams.set('force', '1');
            }

            fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json'
                }
            })
                .then(function (response) {
                    if (!response.ok) {
                        throw new Error('تعذر جلب أسعار الذهب الحية.');
                    }

                    return response.json();
                })
                .then(function (payload) {
                    updateTicker(payload);
                    emitUpdate(payload);

                    if (options.refresh && payload.success !== false && window.erpShowSuccessToast) {
                        window.erpShowSuccessToast(payload.message || 'تم تحديث أسعار الذهب بنجاح.', 'أسعار الذهب');
                    }
                })
                .catch(function () {
                    updateTicker({
                        current: latestPayload && latestPayload.current ? latestPayload.current : {},
                        success: false,
                        message: 'تعذر مزامنة أسعار الذهب الآن. يتم عرض آخر Snapshot محفوظ.',
                    });

                    if (options.refresh && window.erpShowError) {
                        window.erpShowError('تعذر تحديث الأسعار من الخدمة الخارجية الآن.', 'أسعار الذهب');
                    }
                })
                .finally(function () {
                    inFlight = false;

                    if (refreshButton) {
                        refreshButton.disabled = false;
                    }

                    if (window.syncMainHeaderOffset) {
                        window.syncMainHeaderOffset();
                    }
                });
        }

        if (manualButton) {
            manualButton.addEventListener('click', function () {
                if (window.jQuery) {
                    window.jQuery('#goldPriceManualModal').modal('show');
                }
            });
        }

        if (refreshButton) {
            refreshButton.addEventListener('click', function () {
                refreshTicker({
                    refresh: true,
                    force: true
                });
            });
        }

        document.querySelectorAll('#gold-price-manual-form input').forEach(function (input) {
            input.addEventListener('input', function () {
                input.dataset.userEdited = '1';
            });
        });

        window.addEventListener('load', function () {
            refreshTicker();
            @if(old('manual_gold_update'))
                if (window.jQuery) {
                    window.jQuery('#goldPriceManualModal').modal('show');
                }
            @endif
        });
    })();
</script>
<!-- /main-header -->
