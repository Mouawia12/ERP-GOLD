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
