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
    }
</style>
<div class="main-header sticky side-header nav nav-item" id="main-header">
    <div class="container-fluid">
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
        
        <div id="gold_price_div"></div> 
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
<!-- /main-header -->
