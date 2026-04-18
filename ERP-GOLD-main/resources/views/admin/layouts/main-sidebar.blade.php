 <!-- main-sidebar -->
<div class="app-sidebar__overlay" data-toggle="sidebar"></div>
<aside class="app-sidebar sidebar-scroll">
    <style type="text/css">
        :root{
            --erp-sidebar-expanded: 300px;
            --erp-sidebar-collapsed: 88px;
            --erp-sidebar-surface: #ffffff;
            --erp-sidebar-border: #e5edf7;
            --erp-sidebar-text: #5a6780;
            --erp-sidebar-text-strong: #2d4266;
            --erp-sidebar-accent: #4f7cff;
            --erp-sidebar-icon: #97a6bb;
        }
        ::-webkit-scrollbar {width: 7px !important;}
        ::-webkit-scrollbar-track {background: #eee !important;}
        ::-webkit-scrollbar-thumb {background: #949eb7 !important;}
        .app-sidebar{
            width: var(--erp-sidebar-expanded);
            background: var(--erp-sidebar-surface);
            border-left: 1px solid var(--erp-sidebar-border);
            box-shadow: 0 20px 40px rgba(15, 34, 64, 0.08);
            transition: width .28s ease, right .28s ease, box-shadow .28s ease;
        }
        .main-content.app-content,
        .main-footer{
            transition: margin-right .28s ease, width .28s ease, max-width .28s ease;
        }
        .main-sidemenu{
            margin-top:10px !important;
            height:100% !important;
        }
        .app-sidebar__user{
            padding:14px 14px 22px;
            transition: padding .28s ease;
        }
        .app-sidebar__chrome{
            display:none;
        }
        .app-sidebar__toggle-btn{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            width:40px;
            height:40px;
            border-radius:14px;
            border:1px solid rgba(205, 218, 238, 0.92);
            background:#fff;
            color:#4668a9 !important;
            box-shadow: 0 10px 26px rgba(31, 51, 88, 0.1);
            text-decoration:none !important;
            transition: transform .2s ease, box-shadow .2s ease, border-color .2s ease, color .2s ease;
        }
        .app-sidebar__toggle-btn:hover{
            transform: translateY(-1px);
            color:#3158a8 !important;
            border-color: rgba(129, 157, 213, 0.92);
            box-shadow: 0 14px 28px rgba(31, 51, 88, 0.14);
        }
        .app-sidebar__toggle-icon{
            font-size:20px;
            line-height:1;
        }
        .app-sidebar__toggle-icon--open{
            display:none;
        }
        .app-sidebar__brand-link{
            display:block;
            color:inherit;
            text-decoration:none !important;
        }
        .app-sidebar__brand-shell{
            display:flex;
            align-items:center;
            justify-content:center;
            min-height:78px;
            padding:6px 4px 10px;
            border:none;
            background: transparent;
            box-shadow:none;
            overflow:hidden;
            transition: min-height .28s ease, padding .28s ease;
        }
        .side-menu{
            padding: 2px 12px 56px !important;
        }
        .side-menu .slide{
            margin-bottom:2px;
        }
        .side-menu__label{
            color:var(--erp-sidebar-text);
            font-size:12.5px;
            font-weight:700;
            padding-top:0;
            line-height:1.35;
            white-space:normal;
            flex:1 1 auto;
            transition: color .2s ease, opacity .2s ease, width .2s ease;
        }
        .side-menu__icon{
            width:16px;
            min-width:16px;
            font-size:14px;
            text-align:center;
            color: var(--erp-sidebar-icon);
            transition: color .2s ease;
        }
        .side-menu__item{
            display:flex !important;
            align-items:center;
            gap:10px;
            min-height:40px;
            padding:8px 12px !important;
            border-radius:10px;
            background: transparent !important;
            color: var(--erp-sidebar-text-strong) !important;
            transition: color .2s ease, padding .2s ease;
        }
        .side-menu__item:hover,
        .side-menu__item.active,
        .side-menu .slide.active > .side-menu__item,
        .side-menu .slide.is-expanded > .side-menu__item{
            background: transparent !important;
            box-shadow: none;
        }
        .side-menu__item:hover .side-menu__label,
        .side-menu__item:hover .side-menu__icon,
        .side-menu__item:hover .angle,
        .side-menu__item.active .side-menu__label,
        .side-menu__item.active .side-menu__icon,
        .side-menu__item.active .angle,
        .side-menu .slide.active > .side-menu__item .side-menu__label,
        .side-menu .slide.active > .side-menu__item .side-menu__icon,
        .side-menu .slide.active > .side-menu__item .angle,
        .side-menu .slide.is-expanded > .side-menu__item .side-menu__label,
        .side-menu .slide.is-expanded > .side-menu__item .side-menu__icon,
        .side-menu .slide.is-expanded > .side-menu__item .angle{
            color: var(--erp-sidebar-accent) !important;
        }
        .side-menu__item .angle{
            margin-right:auto;
            padding-right:2px;
            font-size:12px;
            color: #b5c2d4;
            transition: color .2s ease, opacity .2s ease;
        }
        .slide-menu{
            padding:2px 4px 6px 0 !important;
        }
        .slide-menu .slide-item{
            display:block;
            white-space:normal;
            line-height:1.35;
            padding:7px 18px 7px 30px !important;
            min-height:32px;
            border-radius:8px;
            margin-bottom:1px;
            font-size:12px;
            font-weight:600;
            background: transparent !important;
            color: var(--erp-sidebar-text) !important;
        }
        .slide-menu .slide-item:hover,
        .slide-menu .slide-item.active,
        .slide-menu li.active > .slide-item{
            background: transparent !important;
            color: var(--erp-sidebar-accent) !important;
        }
        .side-menu .slide.is-expanded > .slide-menu{
            display:block;
        }
        .main-header {
            min-height: var(--erp-main-header-row-height, 64px) !important;
            height: auto !important;
        }
        .main-profile-menu.show .dropdown-menu {top: calc(var(--erp-main-header-row-height, 64px) - 6px) !important;}
        .app-sidebar__brand-logo{
            width: min(100%, 210px) !important;
            height:auto !important;
            max-height: 92px;
            max-width: 100%;
            object-fit: contain;
            filter: none;
            transition: width .28s ease, max-height .28s ease, transform .28s ease;
        }
        @media (min-width: 992px) {
            body.app{
                --erp-sidebar-current-width: var(--erp-sidebar-expanded);
            }
            body.app.sidebar-mini.sidenav-toggled{
                --erp-sidebar-current-width: var(--erp-sidebar-collapsed);
            }
            body.app.sidebar-mini.sidenav-toggled.sidenav-toggled-open,
            body.app:not(.sidenav-toggled){
                --erp-sidebar-current-width: var(--erp-sidebar-expanded);
            }
            .app-sidebar{
                top: var(--erp-main-header-offset, 0px);
                height: calc(100vh - var(--erp-main-header-offset, 0px));
                max-height: calc(100vh - var(--erp-main-header-offset, 0px));
            }
            .app-sidebar__chrome{
                display:flex;
                justify-content:flex-start;
                margin-bottom:8px;
            }
            .main-content.app-content,
            .main-footer{
                width: calc(100% - var(--erp-sidebar-current-width, var(--erp-sidebar-expanded)));
                max-width: calc(100% - var(--erp-sidebar-current-width, var(--erp-sidebar-expanded)));
                margin-right: var(--erp-sidebar-current-width, var(--erp-sidebar-expanded)) !important;
            }
            .app.sidebar-mini.sidenav-toggled .app-sidebar{
                width: var(--erp-sidebar-collapsed);
                box-shadow: 0 16px 30px rgba(15, 34, 64, 0.06);
            }
            .app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .app-sidebar{
                width: var(--erp-sidebar-expanded);
                box-shadow: 0 20px 40px rgba(15, 34, 64, 0.1);
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__user{
                padding-inline: 8px;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__chrome{
                justify-content:center;
                margin-bottom:6px;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__brand-shell{
                min-height:54px;
                padding:8px 4px;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__brand-logo{
                width: 44px !important;
                max-height: 44px;
                transform: none;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .side-menu__label,
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .slide-menu,
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .side-menu__item .angle{
                opacity:0;
                width:0;
                overflow:hidden;
                pointer-events:none;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .side-menu{
                padding-inline: 8px !important;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .side-menu__item{
                justify-content:center;
                padding:10px 0 !important;
                gap:0;
            }
            body.app:not(.sidenav-toggled) .app-sidebar__toggle-icon--close,
            body.app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .app-sidebar__toggle-icon--close{
                display:inline-flex;
            }
            body.app:not(.sidenav-toggled) .app-sidebar__toggle-icon--open,
            body.app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .app-sidebar__toggle-icon--open{
                display:none;
            }
            body.app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__toggle-icon--open{
                display:inline-flex;
            }
            body.app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__toggle-icon--close{
                display:none;
            }
        }
        @media (max-width: 991.98px) {
            .app-sidebar{
                width:320px;
            }
            .main-content.app-content,
            .main-footer{
                width: 100% !important;
                max-width: 100% !important;
                margin-right:0 !important;
            }
        }
        @media (max-width: 767.98px) {
            .app .app-sidebar{
                right:-320px;
            }
            .app.sidenav-toggled .app-sidebar{
                right:0;
                width:320px;
            }
        }
    </style>
    @php
        $currentSidebarUser = Auth::guard('admin-web')->user();
        $sidebarMode = $adminSidebarMode ?? ($currentSidebarUser?->isOwner() ? 'owner' : 'operational');
        $isOwnerSidebar = $sidebarMode === 'owner';
        $currentRoute = request()->route();
        $currentRouteName = $currentRoute?->getName();
        $currentRouteParameters = $currentRoute?->parameters() ?? [];
        $currentInvoiceSaleType = null;
        $currentCustomerType = null;

        if (in_array($currentRouteName, ['sales.show', 'sales_return.show'], true) && isset($currentRouteParameters['id'])) {
            $currentInvoiceSaleType = \App\Models\Invoice::query()
                ->whereKey((int) $currentRouteParameters['id'])
                ->value('sale_type');
        }

        if (in_array($currentRouteName, ['customers.report', 'customers.report.cash'], true) && isset($currentRouteParameters['id'])) {
            $currentCustomerType = \App\Models\Customer::query()
                ->whereKey((int) $currentRouteParameters['id'])
                ->value('type');
        }

        $currentSidebarSection = match (true) {
            (
                request()->routeIs('sales.index', 'sales.create', 'sales.store')
                && request()->route('type') === 'simplified'
            ) || (
                request()->routeIs('sales_return.index', 'sales_return.create', 'sales_return.store')
                && request()->route('type') === 'simplified'
            ) || (
                in_array($currentRouteName, ['sales.show', 'sales_return.show'], true)
                && $currentInvoiceSaleType === 'simplified'
            ) => 'simplified-sales',

            (
                request()->routeIs('sales.index', 'sales.create', 'sales.store')
                && request()->route('type') === 'standard'
            ) || (
                request()->routeIs('sales_return.index', 'sales_return.create', 'sales_return.store')
                && request()->route('type') === 'standard'
            ) || (
                in_array($currentRouteName, ['sales.show', 'sales_return.show'], true)
                && $currentInvoiceSaleType === 'standard'
            ) => 'standard-sales',

            request()->routeIs('pos.collectible', 'pos.collectible.create', 'return.sales.Collectible') => 'collectible-sales',
            request()->routeIs('purchases.*', 'purchase_return.*') => 'purchases',
            request()->routeIs('items.*', 'initial_quantities.*')
                || in_array($currentRouteName, ['categories', 'storeCategory', 'getCategory', 'deleteCategory'], true) => 'items',
            request()->routeIs('manufacturing_orders.*', 'manufacturing_receipts.*', 'manufacturing_returns.*', 'manufacturing_loss_settlements.*') => 'manufacturing',
            request()->routeIs('reports.gold_stock.*') => 'gold-stock',
            request()->routeIs('prices', 'gold.stock.market.prices', 'gold.prices.live', 'updatePrices', 'updatePricesManual') => 'gold-prices',
            request()->routeIs('admin.simplified_debit.*', 'admin.standard_debit.*') => 'invoice-debits',
            (
                request()->routeIs('customers', 'customers.cash')
                && request()->route('type') === 'supplier'
            ) => 'suppliers',
            (
                request()->routeIs('customers.reports.index', 'customers.reports.cash')
                && request()->route('type') === 'supplier'
            ) || (
                in_array($currentRouteName, ['customers.report', 'customers.report.cash'], true)
                && $currentCustomerType === 'supplier'
            ) => 'supplier-reports',
            (
                request()->routeIs('customers', 'customers.cash')
                && request()->route('type') === 'customer'
            ) => 'customers',
            (
                request()->routeIs('customers.reports.index', 'customers.reports.cash')
                && request()->route('type') === 'customer'
            ) || (
                in_array($currentRouteName, ['customers.report', 'customers.report.cash'], true)
                && $currentCustomerType === 'customer'
            ) => 'customer-reports',
            request()->routeIs('money_exit_list', 'money_entry_list') => 'cash-books',
            request()->routeIs('financial_vouchers', 'financial_vouchers.store', 'admin.shifts.*') => 'financial-vouchers',
            default => null,
        };
    @endphp
    
    <div class="main-sidemenu" style="overflow: auto!important;" id="right">
        <div class="app-sidebar__user clearfix">
            <div class="dropdown user-pro-body">
                <div class="app-sidebar__chrome">
                    <a href="#" class="app-sidebar__toggle-btn" data-toggle="sidebar" aria-label="تبديل القائمة الجانبية">
                        <i class="app-sidebar__toggle-icon app-sidebar__toggle-icon--open fe fe-align-left"></i>
                        <i class="app-sidebar__toggle-icon app-sidebar__toggle-icon--close fe fe-x"></i>
                    </a>
                </div>
                <a href="{{ route('admin.home') }}" class="app-sidebar__brand-link">
                    <div class="app-sidebar__brand-shell">
                        <img alt="brand-logo" class="app-sidebar__brand-logo"
                             src="{{ $brandLogoUrl }}">
                    </div>
                </a> 
            </div>
        </div> 
        <ul class="side-menu" style="padding-bottom: 50px !important;" id="main-menu-navigation"
            data-menu="menu-navigation">
            <li class="slide {{ Request::is('home*') ? 'active' : '' }}">
                <a class="side-menu__item" href="{{ url('/admin/' . $page='home') }}"> 
                    <i class="fa fa-home side-menu__icon"></i>
                    <span class="side-menu__label"> الرئيسية </span>
                </a>
            </li>       
            @if($isOwnerSidebar)
                @include('admin.layouts.sidebar.menu-sections', [
                    'sections' => $ownerSidebarSections ?? [],
                ])
            @else
            @can('employee.simplified_tax_invoices.show')                
                <li class="slide {{ $currentSidebarSection === 'simplified-sales' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'simplified-sales' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-newspaper side-menu__icon"></i>
                        <span class="side-menu__label">
                            {{__('المبيعات الضريبية المبسطة')}}
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                    @can('employee.simplified_tax_invoices.add')    
                        <li>
                            <a class="slide-item" href="{{route('sales.create','simplified')}}">
                            {{__('فاتورة جديدة')}}
                            </a>
                        </li> 
                    @endcan  
                    @can('employee.simplified_tax_invoices.show') 
                        <li>
                            <a class="slide-item" href="{{route('sales.index','simplified')}}">
                            {{__(' قائمة المبيعات')}}
                            </a>
                        </li> 
                    @endcan   

                    @can(['employee.sales_returns.add','employee.sales_returns.show'])                           
                        <li>
                            <a class="slide-item" href="{{route('sales_return.index','simplified')}}">
                            {{__('main.return_sales')}}
                            </a>
                        </li> 
                    @endcan                                                     
                    </ul>
                </li> 
            @endcan  
            @can('employee.tax_invoices.show')                
                <li class="slide {{ $currentSidebarSection === 'standard-sales' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'standard-sales' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-newspaper side-menu__icon"></i>
                        <span class="side-menu__label">
                       المبيعات الضريبية الشركات
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                    @can('employee.tax_invoices.add')    
                        <li>
                            <a class="slide-item" href="{{route('sales.create','standard')}}">
                               اضافة فاتورة    
                            </a>
                        </li> 
                    @endcan  
                    @can('employee.tax_invoices.show')  
                        <li>
                            <a class="slide-item" href="{{route('sales.index','standard')}}">
                                المبيعات الضريبية للشركات
                            </a>
                        </li> 
                    @endcan  
                    @canany(['employee.sales_returns.add','employee.sales_returns.show'])                           
                        <li>
                            <a class="slide-item" href="{{route('sales_return.index','standard')}}">
                              مردود مبيعات شركات 
                            </a>
                        </li> 
                    @endcan                                                     
                    </ul>
                </li> 
            @endcan    
            @can('عرض فاتورة ضريبية')                
                <li class="slide {{ $currentSidebarSection === 'collectible-sales' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'collectible-sales' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-newspaper side-menu__icon"></i>
                        <span class="side-menu__label">
                      مبيعات - المقتنيات الثمينة
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                    @can('اضافة فاتورة ضريبية')    
                        <li>
                            <a class="slide-item" href="{{route('pos.collectible.create')}}">
                               اضافة فاتورة    
                            </a>
                        </li> 
                    @endcan  
                    @can('عرض فاتورة ضريبية')  
                        <li>
                            <a class="slide-item" href="{{route('pos.collectible')}}">
                                مبيعات المقتنيات الثمينة
                            </a>
                        </li> 
                    @endcan  
                    @can(['اضافة مرتجع فاتورة مبيعات','عرض مرتجع فاتورة مبيعات'])                           
                        <li>
                            <a class="slide-item" href="{{route('return.sales.Collectible')}}">
                              مردود مبيعات مقتنيات ثمينة
                            </a>
                        </li> 
                    @endcan                                                     
                    </ul>
                </li> 
            @endcan    
           
           
            @can('employee.purchase_invoices.show')           
                <li class="slide {{ $currentSidebarSection === 'purchases' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'purchases' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa-fw fa-folder-open side-menu__icon"></i>
                        <span class="side-menu__label">
                            {{__('main.purchases')}}
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('purchases.index')}}">
                             {{__('main.purchases')}}
                            </a>
                        </li>  
                        <li>
                            <a class="slide-item" href="{{route('purchase_return.index')}}">
                             {{__('main.purchases_return')}}
                            </a>
                        </li>  
                    </ul>
                </li> 
            @endcan    
            @can('employee.items.show')
                 <!-- Nav Item - Pages Collapse Menu -->
                 <li class="slide {{ $currentSidebarSection === 'items' ? 'is-expanded active' : '' }}">

                    <a class="side-menu__item {{ $currentSidebarSection === 'items' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa fa-barcode side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('main.items')}}
                        </span>
                        <i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        @can('employee.items.add')
                        <li>
                            <a class="slide-item" href="{{route('items.create')}}">
                            {{__('اضافة صنف جديد')}}
                            </a>
                        </li> 
                        @endcan
                        <li>
                            <a class="slide-item" href="{{route('items.index')}}">
                            {{__('main.item_list')}}
                            </a>
                        </li> 
                        <li>
                            <a class="slide-item" href="{{route('categories')}}">
                            {{__('مجموعات الاصناف')}}
                            </a>
                        </li> 
                        <li>
                            <a class="slide-item" href="{{route('items.lost_barcodes')}}">
                            {{__('الباركود المفقود')}}
                            </a>
                        </li>
                        @can('employee.initial_quantities.show')
                        <li>
                            <a class="slide-item" href="{{route('initial_quantities.index')}}">
                            {{__('main.initial_quantities')}}
                            </a>
                        </li>  
                        @endcan
                    </ul>
                </li> 
            @endcan  
            @canany(['employee.manufacturing_orders.show', 'employee.manufacturing_orders.add'])
                <li class="slide {{ $currentSidebarSection === 'manufacturing' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'manufacturing' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa-tools side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{ __('main.manufacturing_orders') }}
                        </span>
                        <i class="angle fe fe-chevron-down"></i>
                    </a>
                    <ul class="slide-menu">
                        @can('employee.manufacturing_orders.add')
                        <li>
                            <a class="slide-item" href="{{ route('manufacturing_orders.create') }}">
                            {{ __('main.manufacturing_orders_add') }}
                            </a>
                        </li>
                        @endcan
                        @can('employee.manufacturing_orders.show')
                        <li>
                            <a class="slide-item" href="{{ route('manufacturing_orders.index') }}">
                            {{ __('main.manufacturing_orders') }}
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
            @endcanany
            @can('employee.stock.show')
                <li class="slide {{ $currentSidebarSection === 'gold-stock' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'gold-stock' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-pie-chart side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('رصيد الذهب')}}
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('reports.gold_stock.index')}}">
                            {{__('ميزان ارصدة الذهب')}}
                            </a>
                        </li>  
                    </ul>
                </li> 
            @endcan  
            @can(['employee.gold_prices.show'])
                <li class="slide {{ $currentSidebarSection === 'gold-prices' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'gold-prices' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-gem side-menu__icon"></i>  
                        <span class="side-menu__label">
                        {{__('اسعار الذهب')}}
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu"> 
                        <li>
                            <a class="slide-item" href="{{route('prices')}}">
                            {{__('main.prices')}}
                            </a>
                        </li> 
                        <li>
                            <a class="slide-item" href="{{route('gold.stock.market.prices')}}">
                            {{__('اسعار بورصة الذهب')}}
                            </a>
                        </li> 
                    </ul> 
                </li> 
            @endcan   
            @can(['عرض اشعار مدين مبسطة','عرض اشعار مدين ضريبية'])  
       
                <li class="slide {{ $currentSidebarSection === 'invoice-debits' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'invoice-debits' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-credit-card side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('اشعارات الفواتير')}}
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li> 
                            <a class="slide-item" href="{{ route('admin.simplified_debit.show',0) }}">
                            {{__('اشعار مدين لفاتورة مبسطة')}}
                            </a>
                        </li>  
                        <li> 
                            <a class="slide-item" href="{{ route('admin.standard_debit.show',0) }}">
                            {{__(' اشعار مدين لفاتورة ضريبية')}}
                            </a>
                        </li>   
                                               
                    </ul>
                </li>  
          
            @endcan    
            @can('employee.suppliers.show') 
                <li class="slide {{ $currentSidebarSection === 'suppliers' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'suppliers' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa-user-plus side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('الموردين')}}
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('customers' , 'supplier')}}">
                            {{__('main.suppliers')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{ route('customers.cash', 'supplier') }}">
                            {{__('الموردون النقديون')}}
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan
            @can('employee.suppliers.show')
                <li class="slide {{ $currentSidebarSection === 'supplier-reports' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'supplier-reports' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-line-chart side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{ __('تقارير الموردين') }}
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a>
                    <ul class="slide-menu">
                        <li>
                            <a class="slide-item" href="{{ route('customers.reports.index', 'supplier') }}">
                            {{ __('تقارير الموردين التفصيلية') }}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{ route('customers.reports.cash', 'supplier') }}">
                            {{ __('تقارير الموردين النقدية') }}
                            </a>
                        </li>
                    </ul>
                </li>
            @endcan
            @can('employee.customers.show')                  
                <li class="slide {{ $currentSidebarSection === 'customers' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'customers' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa-user side-menu__icon"></i>
                        <span class="side-menu__label">
                            {{__('main.customers')}} 
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('customers' , 'customer')}}">
                            {{__('main.customers')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{ route('customers.cash', 'customer') }}">
                            {{__('العملاء النقديون')}}
                            </a>
                        </li>
                    </ul>
                </li>  
            @endcan
            @can('employee.customers.show')                  
                <li class="slide {{ $currentSidebarSection === 'customer-reports' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'customer-reports' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-area-chart side-menu__icon"></i>
                        <span class="side-menu__label">
                            {{ __('تقارير العملاء') }}
                        </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{ route('customers.reports.index', 'customer') }}">
                            {{ __('تقارير العملاء التفصيلية') }}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{ route('customers.reports.cash', 'customer') }}">
                            {{ __('تقارير العملاء النقدية') }}
                            </a>
                        </li>
                    </ul>
                </li>  
            @endcan  

            @can(['عرض دفتر خروج النقدية','عرض دفتر دخول النقدية'])        
                <li class="slide {{ $currentSidebarSection === 'cash-books' ? 'is-expanded active' : '' }}">
                    <a class="side-menu__item {{ $currentSidebarSection === 'cash-books' ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fas fa-money-bill side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('النقدية')}}
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('money_exit_list')}}">
                            {{__('main.money_exit_list')}}
                            </a>
                        </li> 
                        <li>
                            <a class="slide-item" href="{{route('money_entry_list')}}">
                            {{__('main.money_entry_list')}}
                            </a>
                        </li>                             
                    </ul>
                </li>  
            @endcan             
            <li class="slide {{ $currentSidebarSection === 'financial-vouchers' ? 'is-expanded active' : '' }}">
                <a class="side-menu__item {{ $currentSidebarSection === 'financial-vouchers' ? 'active' : '' }}" data-toggle="slide" href="#">
                    <i class="fa fa-credit-card side-menu__icon"></i>
                    <span class="side-menu__label">
                    {{__('main.financial_vouchers')}}
                </span><i class="angle fe fe-chevron-down"></i>
                </a> 
                <ul class="slide-menu">  
                    <li>
                        <a class="slide-item" href="{{route('financial_vouchers' , 'receipt')}}">
                        {{__('main.receipts')}}
                        </a>
                    </li>                             
                    <li>
                        <a class="slide-item" href="{{route('financial_vouchers' , 'payment')}}">
                        {{__('main.payments')}}
                        </a>
                    </li>                             
                    <li>
                        <a class="slide-item" href="{{ route('admin.shifts.index') }}">
                        {{ __('الشفتات') }}
                        </a>
                    </li>
                </ul>
            </li>  
            @canany(['employee.accounts.add','employee.accounts.show','employee.accounts.edit','employee.accounts.delete'])                 
                @php
                    $accountingSectionActive = request()->routeIs(
                        'accounts.*',
                        'accounts.settings.*'
                    );
                @endphp
                <li class="slide {{ $accountingSectionActive ? 'is-expanded' : '' }}">
                    <a class="side-menu__item {{ $accountingSectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-usd side-menu__icon"></i>
                        <span class="side-menu__label">
                        {{__('main.accounting')}}
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">  
                        <li>
                            <a class="slide-item" href="{{route('accounts.index')}}">
                             {{__('main.accounts')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('accounts.opening')}}">
                             {{__('main.accounts_opening')}}
                            </a>
                        </li>
             
                        <li>
                            <a class="slide-item" href="{{route('accounts.settings.index')}}">
                            {{__('main.account_settings')}}
                            </a>
                        </li>  
                        <li>
                            <a class="slide-item" href="{{route('accounts.journals.index', 'transactions')}}">
                            {{__('main.journals')}}
                            </a>
                        </li>   
                        <li>
                            <a class="slide-item" href="{{route('accounts.journals.index', 'manual')}}">
                            {{__('main.manual_journals')}}
                            </a>
                        </li>  
                        <li>
                            <a class="slide-item" href="{{route('accounts.journals.create')}}">
                            {{__('main.manual_journal_add')}}
                            </a>
                        </li>                                               
                    </ul>
                </li> 
            @endcan   
            @can('employee.inventory_reports.show')
                @php
                    $inventoryReportsSectionActive = request()->routeIs(
                        'reports.items.list',
                        'reports.items.list.search',
                        'reports.sold_items_report.*',
                        'reports.sales_report.*',
                        'reports.sales_total_report.*',
                        'reports.sales_return_total_report.*',
                        'reports.purchases_report.*',
                        'reports.purchases_total_report.*',
                        'reports.daily_carat_report.*',
                        'reports.gold_stock.*',
                        'stock_settlements.*'
                    );
                @endphp
                <li class="slide {{ $inventoryReportsSectionActive ? 'is-expanded' : '' }}">
                    <a class="side-menu__item {{ $inventoryReportsSectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-copy side-menu__icon"></i>
                        <span class="side-menu__label">
                         تقارير المخزون
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a>
                    <ul class="slide-menu">
                        <li>
                            <a class="slide-item" href="{{route('reports.items.list')}}">
                            {{__('main.item_list_report')}}
                            </a>
                        </li>

                        <li>
                            <a class="slide-item" href="{{route('reports.sold_items_report.index')}}">
                            {{__('main.sold_items_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.sales_report.search')}}">
                            {{__('main.sales_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.sales_total_report.search')}}">
                            {{__('main.sales_total_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.sales_return_total_report.search')}}">
                            {{__('main.sales_return_total_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.purchases_report.search')}}">
                            {{__('main.purchase_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.purchases_total_report.search')}}">
                            {{__('main.purchase_total_report')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.daily_carat_report.search')}}">
                            التقرير اليومي حسب العيار
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('reports.gold_stock.search')}}">
                            {{__('main.gold_stock_report')}}
                            </a>
                        </li>
                        @can('employee.stock_settlements.show')
                        <li>
                            <a class="slide-item" href="{{route('stock_settlements.index')}}">
                            {{__('main.stock_settlements')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('stock_settlements.create_by_default')}}">
                            {{__('main.stock_settlements_by_default')}}
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>

                {{-- تقارير المقتنيات --}}
                @php
                    $collectibleReportsSectionActive = request()->routeIs('reports.collectible.*');
                @endphp
                <li class="slide {{ $collectibleReportsSectionActive ? 'is-expanded' : '' }}">
                    <a class="side-menu__item {{ $collectibleReportsSectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-gem side-menu__icon"></i>
                        <span class="side-menu__label">تقارير المقتنيات</span>
                        <i class="angle fe fe-chevron-down"></i>
                    </a>
                    <ul class="slide-menu">
                        <li><a class="slide-item" href="{{ route('reports.collectible.sales_report.search') }}">تقرير المبيعات التفصيلي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.sales_total_report.search') }}">تقرير المبيعات الإجمالي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.sales_return_report.search') }}">تقرير مرتجعات المبيعات</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.purchases_report.search') }}">تقرير المشتريات التفصيلي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.purchases_total_report.search') }}">تقرير المشتريات الإجمالي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.purchases_return_report.search') }}">تقرير مرتجعات المشتريات</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.weight_report.search') }}">تقرير حركة الوزن</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.sold_items_report.index') }}">الأصناف المباعة</a></li>
                        <li><a class="slide-item" href="{{ route('reports.collectible.items.list') }}">قائمة الأصناف</a></li>
                    </ul>
                </li>

                {{-- تقارير الفضة --}}
                @php
                    $silverReportsSectionActive = request()->routeIs('reports.silver.*');
                @endphp
                <li class="slide {{ $silverReportsSectionActive ? 'is-expanded' : '' }}">
                    <a class="side-menu__item {{ $silverReportsSectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-circle side-menu__icon"></i>
                        <span class="side-menu__label">تقارير الفضة</span>
                        <i class="angle fe fe-chevron-down"></i>
                    </a>
                    <ul class="slide-menu">
                        <li><a class="slide-item" href="{{ route('reports.silver.sales_report.search') }}">تقرير المبيعات التفصيلي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.sales_total_report.search') }}">تقرير المبيعات الإجمالي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.sales_return_report.search') }}">تقرير مرتجعات المبيعات</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.purchases_report.search') }}">تقرير المشتريات التفصيلي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.purchases_total_report.search') }}">تقرير المشتريات الإجمالي</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.purchases_return_report.search') }}">تقرير مرتجعات المشتريات</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.weight_report.search') }}">تقرير حركة الوزن</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.sold_items_report.index') }}">الأصناف المباعة</a></li>
                        <li><a class="slide-item" href="{{ route('reports.silver.items.list') }}">قائمة الأصناف</a></li>
                    </ul>
                </li>
            @endcan  
            @can('employee.accounting_reports.show')                  
                @php
                    $accountingReportsSectionActive = request()->routeIs(
                        'trail_balance.*',
                        'income_statement.*',
                        'balance_sheet.*',
                        'account_statement.*',
                        'tax.declaration.*'
                    );
                @endphp
                <li class="slide {{ $accountingReportsSectionActive ? 'is-expanded' : '' }}">
                    <a class="side-menu__item {{ $accountingReportsSectionActive ? 'active' : '' }}" data-toggle="slide" href="#">
                        <i class="fa fa-copy side-menu__icon"></i>
                        <span class="side-menu__label">
                        التقارير المحاسبية
                    </span><i class="angle fe fe-chevron-down"></i>
                    </a> 
                    <ul class="slide-menu">   
                        <li>
                            <a class="slide-item" href="{{route('trail_balance.index')}}">
                            {{__('main.balance_report')}}
                            </a>
                        </li> 
              
                        <li>
                            <a class="slide-item" href="{{route('income_statement.index')}}">
                            {{__('main.incoming_list')}}
                            </a>
                        </li>
                        <li>
                            <a class="slide-item" href="{{route('balance_sheet.index')}}">
                            {{__('main.balance_sheet')}}
                            </a>
                        </li> 
                        <li>
                            <a class="slide-item" href="{{route('account_statement.index')}}">
                            {{__('main.account_movement_report')}}
                            </a>
                        </li>     
                        <li>
                            <a class="slide-item" href="{{route('tax.declaration.index')}}">
                                الاقرار الضريبي
                            </a>
                        </li>                       
                    </ul>
                </li>     
            @endcan   
            @include('admin.layouts.sidebar.menu-sections', [
                'sections' => $operationalAdminSections ?? [],
            ])
            @endif
        </ul>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const normalizePath = (value) => {
                if (!value) {
                    return '/';
                }

                const normalized = value.replace(/\/+$/, '');
                return normalized === '' ? '/' : normalized;
            };

            const currentPath = normalizePath(window.location.pathname);

            document.querySelectorAll('.app-sidebar .slide').forEach((slide) => {
                const childLinks = Array.from(slide.querySelectorAll('.slide-menu .slide-item[href]'));

                if (childLinks.length === 0) {
                    return;
                }

                let bestMatch = null;

                childLinks.forEach((link) => {
                    const href = link.getAttribute('href');

                    if (!href || href === '#') {
                        return;
                    }

                    let linkPath = '';

                    try {
                        linkPath = normalizePath(new URL(href, window.location.origin).pathname);
                    } catch (error) {
                        return;
                    }

                    const isExact = currentPath === linkPath;
                    const isNested = linkPath !== '/' && currentPath.startsWith(linkPath + '/');

                    if (!isExact && !isNested) {
                        return;
                    }

                    const score = linkPath.length;

                    if (!bestMatch || score > bestMatch.score) {
                        bestMatch = { link, score };
                    }
                });

                if (!bestMatch) {
                    return;
                }

                const activeLink = bestMatch.link;
                activeLink.classList.add('active');
                activeLink.parentElement?.classList.add('active');
                slide.classList.add('is-expanded', 'active');
                slide.querySelector(':scope > .side-menu__item')?.classList.add('active');
            });
        });
    </script>
</aside>
<!-- main-sidebar -->
