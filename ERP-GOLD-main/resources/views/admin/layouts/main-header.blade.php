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
        display: flex;
        align-items: center;
        justify-content: center;
        margin-inline-start: 14px;
        z-index: 20;
    }

    .app-sidebar__toggle a {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 44px;
        height: 44px;
        border-radius: 14px;
        border: 1px solid #d9e5f6;
        background: linear-gradient(135deg, #eef5ff 0%, #dfeafe 100%);
        color: #36527d !important;
        box-shadow: 0 12px 24px rgba(20, 49, 92, 0.12);
        transition: transform .2s ease, box-shadow .2s ease, background-color .2s ease;
    }

    .app-sidebar__toggle a:hover {
        transform: translateY(-1px);
        box-shadow: 0 14px 28px rgba(20, 49, 92, 0.16);
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
        font-size: 20px;
        line-height: 1;
    }

    @media (max-width: 991.98px) {
        .branch-switcher-form {
            min-width: 220px;
        }

        .app-sidebar__toggle {
            margin-inline-start: 8px;
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
                        <a class="dropdown-item" href="{{ route('admin.logout') }}"
                           onclick="event.preventDefault();document.getElementById('logout-form').submit();">
                            <i class="fa fa-power-off"></i> تسجيل الخروج
                        </a>
                        <form id="logout-form" action="{{ route('admin.logout') }}" method="POST"
                              style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
<!-- /main-header -->
