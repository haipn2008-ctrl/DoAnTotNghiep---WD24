<header id="page-topbar">
    <div class="navbar-header">
        <div class="d-flex">
            <div class="navbar-brand-box">
                <a class="logo logo-dark" href="{{ route('admin.home') }}">
                    <span class="logo-sm">
                        <img alt="Logo" height="24" src="{{ asset('assets/images/logo-sm.svg') }}">
                    </span>
                    <span class="logo-lg">
                        <img alt="Logo" height="24" src="{{ asset('assets/images/logo-sm.svg') }}">
                        <span class="logo-txt">Quản lý Phòng Trọ</span>
                    </span>
                </a>

                <a class="logo logo-light" href="{{ route('admin.home') }}">
                    <span class="logo-sm">
                        <img alt="Logo" height="24" src="{{ asset('assets/images/logo-sm.svg') }}">
                    </span>
                    <span class="logo-lg">
                        <img alt="Logo" height="24" src="{{ asset('assets/images/logo-sm.svg') }}">
                        <span class="logo-txt">Quản lý Phòng Trọ</span>
                    </span>
                </a>
            </div>

            <button class="btn btn-sm px-3 font-size-16 header-item" id="vertical-menu-btn" type="button">
                <i class="fa fa-fw fa-bars"></i>
            </button>
        </div>

        <div class="d-flex">
            <div class="dropdown d-inline-block">
                <button aria-expanded="false"
                        aria-haspopup="true"
                        class="btn header-item bg-light-subtle border-start border-end"
                        data-bs-toggle="dropdown"
                        id="page-header-user-dropdown"
                        type="button">
                    <img alt="Avatar" class="rounded-circle header-profile-user" src="{{ asset('assets/images/users/avatar-1.jpg') }}">
                    <span class="d-none d-xl-inline-block ms-1 fw-medium">
                        {{ Auth::user()->name ?? 'Admin' }}
                    </span>
                    <i class="mdi mdi-chevron-down d-none d-xl-inline-block"></i>
                </button>

                <div class="dropdown-menu dropdown-menu-end">
                    <a class="dropdown-item" href="#">
                        <i class="mdi mdi-account-circle font-size-16 align-middle me-1"></i>
                        Tài khoản
                    </a>
                    <div class="dropdown-divider"></div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="mdi mdi-logout font-size-16 align-middle me-1"></i>
                            Đăng xuất
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</header>
