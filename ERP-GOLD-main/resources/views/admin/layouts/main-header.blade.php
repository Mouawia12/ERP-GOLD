<!-- main-header opened -->
<style>
    .responsive-logo a {
        display: inline-flex;
        align-items: center;
    }

    .responsive-logo .brand-header-logo {
        width: 112px;
        max-width: 100%;
        max-height: 48px;
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
    }

    .main-header__nav-row {
        display: flex;
        align-items: center;
        gap: 14px;
        flex-wrap: wrap;
    }

    .gold-price-ticker-dock {
        width: 100%;
        padding: 6px 0 10px;
        background: linear-gradient(180deg, rgba(255, 255, 255, 0.98) 0%, rgba(247, 249, 253, 0.94) 100%);
        border-top: 1px solid rgba(226, 232, 240, 0.75);
    }

    .gold-price-ticker-shell {
        width: 100%;
        display: flex;
        justify-content: center;
        margin-inline: 0;
    }

    .gold-price-ticker {
        width: 100%;
        min-height: 52px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        padding: 10px 16px;
        border-radius: 18px;
        border: 1px solid rgba(191, 162, 83, 0.34);
        background: linear-gradient(135deg, rgba(255, 249, 234, 0.98) 0%, rgba(248, 238, 204, 0.98) 100%);
        box-shadow: 0 18px 40px rgba(146, 111, 27, 0.12);
        color: #5a430f;
    }

    .gold-price-ticker--loading {
        opacity: 0.82;
    }

    .gold-price-ticker__status {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 12px;
        font-weight: 700;
        white-space: nowrap;
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
        padding: 6px 10px;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.65);
        border: 1px solid rgba(157, 126, 46, 0.16);
        font-size: 12px;
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
        text-align: left;
        font-size: 11px;
        font-weight: 700;
        line-height: 1.6;
        color: #7b6127;
        white-space: nowrap;
    }

    .gold-price-ticker__meta strong {
        color: #4b350a;
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

    @media (min-width: 992px) {
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

        .gold-price-ticker {
            border-radius: 16px;
            padding: 10px 12px;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .gold-price-ticker__status,
        .gold-price-ticker__meta {
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

    <div class="gold-price-ticker-dock">
        <div class="container-fluid">
            <div
                id="gold_price_div"
                class="gold-price-ticker-shell"
                data-gold-live-endpoint="{{ route('gold.prices.live') }}"
                data-refresh-interval-ms="{{ \App\Services\Pricing\GoldPriceSyncService::AUTO_REFRESH_INTERVAL_MINUTES * 60 * 1000 }}"
            >
                <div class="gold-price-ticker gold-price-ticker--loading" data-state="loading" data-gold-ticker-root>
                    <div class="gold-price-ticker__status">
                        <span class="gold-price-ticker__pulse"></span>
                        <span data-gold-ticker-status>جار تحديث أسعار الذهب...</span>
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
                            <span class="gold-price-ticker__item-label">عيار 24</span>
                            <strong class="gold-price-ticker__item-value" data-gold-ticker-price="24">--</strong>
                        </span>
                    </div>
                    <div class="gold-price-ticker__meta">
                        <div><strong data-gold-ticker-currency>--</strong></div>
                        <div data-gold-ticker-updated>لا يوجد تحديث</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
    (function () {
        var shell = document.getElementById('gold_price_div');

        if (!shell) {
            return;
        }

        var root = shell.querySelector('[data-gold-ticker-root]');
        var endpoint = shell.getAttribute('data-gold-live-endpoint');
        var refreshIntervalMs = parseInt(shell.getAttribute('data-refresh-interval-ms') || '900000', 10);
        var inFlight = false;
        var lastRefreshAttemptAt = 0;

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

            var current = payload.current || {};
            var statusNode = root.querySelector('[data-gold-ticker-status]');
            var updatedNode = root.querySelector('[data-gold-ticker-updated]');
            var currencyNode = root.querySelector('[data-gold-ticker-currency]');

            if (statusNode) {
                statusNode.textContent = payload.message || 'تم تحميل أسعار الذهب.';
            }

            if (currencyNode) {
                currencyNode.textContent = current.currency || 'بدون عملة';
            }

            if (updatedNode) {
                updatedNode.textContent = current.last_update_label || 'لا يوجد تحديث';
            }

            ['18', '21', '24'].forEach(function (carat) {
                var node = root.querySelector('[data-gold-ticker-price="' + carat + '"]');

                if (!node) {
                    return;
                }

                node.textContent = current['ounce_' + carat + '_price_label'] || '--';
            });

            setTickerState(payload.success === false ? 'warning' : 'ready');
        }

        function emitUpdate(payload) {
            window.dispatchEvent(new CustomEvent('gold-price:ticker-updated', {
                detail: payload
            }));
        }

        function refreshTicker(shouldRequestRefresh) {
            if (!endpoint || inFlight) {
                return;
            }

            inFlight = true;
            lastRefreshAttemptAt = Date.now();
            setTickerState('loading');

            var url = new URL(endpoint, window.location.origin);

            if (shouldRequestRefresh) {
                url.searchParams.set('refresh', '1');
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
                })
                .catch(function () {
                    updateTicker({
                        success: false,
                        message: 'تعذر مزامنة أسعار الذهب الآن. يتم عرض آخر Snapshot محفوظ.',
                        current: {}
                    });
                })
                .finally(function () {
                    inFlight = false;
                });
        }

        document.addEventListener('visibilitychange', function () {
            if (document.visibilityState !== 'visible') {
                return;
            }

            if ((Date.now() - lastRefreshAttemptAt) >= refreshIntervalMs) {
                refreshTicker(true);
            }
        });

        window.addEventListener('load', function () {
            refreshTicker(true);
            window.setInterval(function () {
                refreshTicker(true);
            }, refreshIntervalMs);
        });
    })();
</script>
<!-- /main-header -->
