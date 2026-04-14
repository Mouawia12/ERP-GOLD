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
            --erp-sidebar-active-bg: linear-gradient(135deg, #eef5ff 0%, #ddeaff 100%);
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
            transition: margin-right .28s ease;
        }
        .main-sidemenu{
            margin-top:10px !important;
            height:98% !important;
        }
        .app-sidebar__user{
            padding:6px 10px 22px;
            transition: padding .28s ease;
        }
        .side-menu{
            padding: 0 10px 56px !important;
        }
        .side-menu .slide{
            margin-bottom:6px;
        }
        .side-menu__label{
            color:var(--erp-sidebar-text);
            font-size:13px;
            font-weight:700;
            padding-top:0;
            line-height:1.5;
            white-space:normal;
            flex:1 1 auto;
            transition: opacity .2s ease, width .2s ease;
        }
        .side-menu__item{
            display:flex !important;
            align-items:center;
            gap:12px;
            min-height:48px;
            padding:12px 16px !important;
            border-radius:14px;
            color: var(--erp-sidebar-text-strong) !important;
            transition: background-color .2s ease, color .2s ease, padding .2s ease;
        }
        .side-menu__item:hover,
        .side-menu .slide.active > .side-menu__item,
        .side-menu .slide.is-expanded > .side-menu__item{
            background: var(--erp-sidebar-active-bg);
        }
        .side-menu__item .angle{
            margin-right:auto;
            padding-right:4px;
            transition: opacity .2s ease;
        }
        .slide-menu{
            padding:8px 8px 10px 0 !important;
        }
        .slide-menu .slide-item{
            display:block;
            white-space:normal;
            line-height:1.45;
            padding:10px 18px 10px 18px !important;
            min-height:42px;
            border-radius:12px;
            margin-bottom:4px;
            color: var(--erp-sidebar-text) !important;
        }
        .slide-menu .slide-item:hover,
        .slide-menu li.active > .slide-item{
            background:#f5f9ff;
            color: var(--erp-sidebar-text-strong) !important;
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
            width: 140px !important;
            height:auto !important;
            max-width: 100%;
            object-fit: contain;
            filter: drop-shadow(0 6px 18px rgba(229, 184, 11, 0.18));
            transition: width .28s ease, transform .28s ease;
        }
        @media (min-width: 992px) {
            .main-content.app-content,
            .main-footer{
                margin-right: var(--erp-sidebar-expanded) !important;
            }
            .app.sidebar-mini.sidenav-toggled .app-sidebar{
                width: var(--erp-sidebar-collapsed);
                box-shadow: 0 16px 30px rgba(15, 34, 64, 0.06);
            }
            .app.sidebar-mini.sidenav-toggled .main-content.app-content,
            .app.sidebar-mini.sidenav-toggled .main-footer{
                margin-right: var(--erp-sidebar-collapsed) !important;
            }
            .app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .app-sidebar{
                width: var(--erp-sidebar-expanded);
                box-shadow: 0 20px 40px rgba(15, 34, 64, 0.1);
            }
            .app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .main-content.app-content,
            .app.sidebar-mini.sidenav-toggled.sidenav-toggled-open .main-footer{
                margin-right: var(--erp-sidebar-expanded) !important;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__user{
                padding-inline: 6px;
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__brand-logo{
                width: 42px !important;
                transform: translateY(4px);
            }
            .app.sidebar-mini.sidenav-toggled:not(.sidenav-toggled-open) .app-sidebar__user h5,
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
                padding:12px 0 !important;
                gap:0;
            }
        }
        @media (max-width: 991.98px) {
            .app-sidebar{
                width:320px;
            }
            .main-content.app-content,
            .main-footer{
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
    @endphp
    
    <div class="main-sidemenu" style="overflow: auto!important;" id="right">
        <div class="app-sidebar__user clearfix">
            <div class="dropdown user-pro-body">
                <a href="{{route('admin.home')}}">
                    <div class="">
                        <img alt="user-img" class="app-sidebar__brand-logo"
                             src="{{ $brandLogoUrl }}">  
                    </div> 
                    <br>
                    <h5 class="text-center">GOLD-MS</h5>
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                 <li class="slide">

                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
       
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
            @can('employee.customers.show')                  
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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

            @can(['عرض دفتر خروج النقدية','عرض دفتر دخول النقدية'])        
                <li class="slide">
                    <a class="side-menu__item" data-toggle="slide" href="#">
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
            <li class="slide">
                <a class="side-menu__item" data-toggle="slide" href="#">
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
</aside>
<!-- main-sidebar -->
