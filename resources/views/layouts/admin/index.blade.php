<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
   <head>
      <meta charset="utf-8"/>
      <title>@yield('title', 'Dashboard | Admin &amp; Dashboard Template')</title>
      <meta content="width=device-width, initial-scale=1.0" name="viewport"/>
      <meta content="Premium Multipurpose Admin &amp; Dashboard Template" name="description">
      <meta content="admintem.com" name="author"/>
      <link href="{{ asset('assets/images/favicon.ico') }}" rel="shortcut icon"/>
      <link href="{{ asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css') }}" rel="stylesheet" type="text/css">
      <link href="{{ asset('assets/css/preloader.min.css') }}" rel="stylesheet" type="text/css"/>
      <link href="{{ asset('assets/css/bootstrap.min.css') }}" id="bootstrap-style" rel="stylesheet" type="text/css"/>
      <link href="{{ asset('assets/css/icons.min.css') }}" rel="stylesheet" type="text/css"/>
      <link href="{{ asset('assets/css/app.min.css') }}" id="app-style" rel="stylesheet" type="text/css"/>
      @stack('styles')
   </head>
   <body>
      <!-- <body data-layout="horizontal"> -->
      <div id="layout-wrapper">
         @include('layouts.admin.blocks.header')
         @include('layouts.admin.blocks.sidebar')
         <div class="main-content">
            <div class="page-content">
               @yield('content')
            </div>
            <!-- End Page-content -->
            @include('layouts.admin.blocks.footer')
         </div>
         <!-- end main content-->
      </div>
      <!-- END layout-wrapper -->
      <!-- Right Sidebar -->
      <div class="right-bar">
         <div class="h-100" data-simplebar="">
            <div class="rightbar-title d-flex align-items-center p-3">
               <h5 class="m-0 me-2">
                  Theme Customizer
               </h5>
               <a class="right-bar-toggle ms-auto" href="javascript:void(0);">
               <i class="mdi mdi-close noti-icon">
               </i>
               </a>
            </div>
            <!-- Settings -->
            <hr class="m-0"/>
            <div class="p-4">
               <h6 class="mb-3">
                  Layout
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-vertical" name="layout" type="radio" value="vertical"/>
                  <label class="form-check-label" for="layout-vertical">
                  Vertical
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-horizontal" name="layout" type="radio" value="horizontal"/>
                  <label class="form-check-label" for="layout-horizontal">
                  Horizontal
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2">
                  Layout Mode
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-mode-light" name="layout-mode" type="radio" value="light"/>
                  <label class="form-check-label" for="layout-mode-light">
                  Light
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-mode-dark" name="layout-mode" type="radio" value="dark"/>
                  <label class="form-check-label" for="layout-mode-dark">
                  Dark
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2">
                  Layout Width
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-width-fuild" name="layout-width" onchange="document.body.setAttribute('data-layout-size', 'fluid')" type="radio" value="fuild"/>
                  <label class="form-check-label" for="layout-width-fuild">
                  Fluid
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-width-boxed" name="layout-width" onchange="document.body.setAttribute('data-layout-size', 'boxed')" type="radio" value="boxed"/>
                  <label class="form-check-label" for="layout-width-boxed">
                  Boxed
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2">
                  Layout Position
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-position-fixed" name="layout-position" onchange="document.body.setAttribute('data-layout-scrollable', 'false')" type="radio" value="fixed"/>
                  <label class="form-check-label" for="layout-position-fixed">
                  Fixed
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-position-scrollable" name="layout-position" onchange="document.body.setAttribute('data-layout-scrollable', 'true')" type="radio" value="scrollable"/>
                  <label class="form-check-label" for="layout-position-scrollable">
                  Scrollable
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2">
                  Topbar Color
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="topbar-color-light" name="topbar-color" onchange="document.body.setAttribute('data-topbar', 'light')" type="radio" value="light"/>
                  <label class="form-check-label" for="topbar-color-light">
                  Light
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="topbar-color-dark" name="topbar-color" onchange="document.body.setAttribute('data-topbar', 'dark')" type="radio" value="dark"/>
                  <label class="form-check-label" for="topbar-color-dark">
                  Dark
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2 sidebar-setting">
                  Sidebar Size
               </h6>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-size-default" name="sidebar-size" onchange="document.body.setAttribute('data-sidebar-size', 'lg')" type="radio" value="default"/>
                  <label class="form-check-label" for="sidebar-size-default">
                  Default
                  </label>
               </div>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-size-compact" name="sidebar-size" onchange="document.body.setAttribute('data-sidebar-size', 'md')" type="radio" value="compact"/>
                  <label class="form-check-label" for="sidebar-size-compact">
                  Compact
                  </label>
               </div>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-size-small" name="sidebar-size" onchange="document.body.setAttribute('data-sidebar-size', 'sm')" type="radio" value="small"/>
                  <label class="form-check-label" for="sidebar-size-small">
                  Small (Icon View)
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2 sidebar-setting">
                  Sidebar Color
               </h6>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-color-light" name="sidebar-color" onchange="document.body.setAttribute('data-sidebar', 'light')" type="radio" value="light"/>
                  <label class="form-check-label" for="sidebar-color-light">
                  Light
                  </label>
               </div>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-color-dark" name="sidebar-color" onchange="document.body.setAttribute('data-sidebar', 'dark')" type="radio" value="dark"/>
                  <label class="form-check-label" for="sidebar-color-dark">
                  Dark
                  </label>
               </div>
               <div class="form-check sidebar-setting">
                  <input class="form-check-input" id="sidebar-color-brand" name="sidebar-color" onchange="document.body.setAttribute('data-sidebar', 'brand')" type="radio" value="brand"/>
                  <label class="form-check-label" for="sidebar-color-brand">
                  Brand
                  </label>
               </div>
               <h6 class="mt-4 mb-3 pt-2">
                  Direction
               </h6>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-direction-ltr" name="layout-direction" type="radio" value="ltr"/>
                  <label class="form-check-label" for="layout-direction-ltr">
                  LTR
                  </label>
               </div>
               <div class="form-check form-check-inline">
                  <input class="form-check-input" id="layout-direction-rtl" name="layout-direction" type="radio" value="rtl"/>
                  <label class="form-check-label" for="layout-direction-rtl">
                  RTL
                  </label>
               </div>
            </div>
         </div>
         <!-- end slimscroll-menu-->
      </div>
      <!-- /Right-bar -->
      <!-- Right bar overlay-->
      <div class="rightbar-overlay">
      </div>
      <!-- JAVASCRIPT -->
      <script src="{{ asset('assets/libs/jquery/jquery.min.js') }}"></script>
      <script src="{{ asset('assets/libs/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
      <script src="{{ asset('assets/libs/metismenu/metisMenu.min.js') }}"></script>
      <script src="{{ asset('assets/libs/simplebar/simplebar.min.js') }}"></script>
      <script src="{{ asset('assets/libs/node-waves/waves.min.js') }}"></script>
      <script src="{{ asset('assets/libs/feather-icons/feather.min.js') }}"></script>
      <!-- pace js -->
      <script src="{{ asset('assets/libs/pace-js/pace.min.js') }}"></script>
      <!-- apexcharts -->
      <script src="{{ asset('assets/libs/apexcharts/apexcharts.min.js') }}"></script>
      <!-- Plugins js-->
      <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.min.js') }}"></script>
      <script src="{{ asset('assets/libs/admin-resources/jquery.vectormap/maps/jquery-jvectormap-world-mill-en.js') }}"></script>
      <!-- dashboard init -->
      <script src="{{ asset('assets/js/pages/dashboard.init.js') }}"></script>
      <script src="{{ asset('assets/js/app.js') }}"></script>
      @stack('scripts')
   </body>
</html>
