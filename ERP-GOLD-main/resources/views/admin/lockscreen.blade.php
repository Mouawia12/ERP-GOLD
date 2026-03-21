@extends('admin.layouts.master')
@section('css')
    <!-- Sidemenu-respoansive-tabs css -->
    <link href="{{asset('assets/plugins/sidemenu-responsive-tabs/css/sidemenu-responsive-tabs.css')}}"
          rel="stylesheet">
@endsection
@section('content')
    <div class="container-fluid">
        <div class="row no-gutter">
            <!-- The image half -->
            <div class="col-lg-6 col-sm-12 pull-right align-content-center justify-content-center text-center">
                <img src="{{asset('assets/img/logo.png')}}" style="width: 70%;height: 450px;">
                <h1 style="color: black !important;">
                    وزارة الخدمة المدنية والتامينات
                </h1>
            </div>
            <!-- The content half -->
            <div class="col-md-6 col-lg-6 col-xl-5 bg-white">
                <div class="login d-flex align-items-center py-2">
                    <!-- Demo content-->
                    <div class="container p-0">
                        <div class="row">
                            <div class="col-md-10 col-lg-10 col-xl-9 mx-auto">
                                <div class="mb-5 d-flex mx-auto">
                                    <a href="javascript:;"
                                       class="mx-auto d-flex">
                                        <h1 class="main-logo1 ml-1 mr-0 my-auto tx-28 text-dark ml-2">
                                            وزارة الخدمة المدنية والتامينات
                                        </h1>
                                    </a></div>
                                <div class="main-card-signin d-md-flex bg-white">
                                    <div class="p-4 wd-100p">
                                        <div class="main-signin-header">
                                            <div class="mx-auto text-center mb-2">
                                                <img
                                                    style="width:100px; height: 100px;padding: 5px ;border:1px solid #ddd;"
                                                    class="rounded-circle mt-2 mb-2"
                                                    src="{{asset('assets/img/logo.png')}}"></div>
                                            <div class="mx-auto text-center mt-4 mg-b-20"> 
                                                <p class="tx-13 text-muted">ادخل كلمة المرور الخاصة بك للدخول الى
                                                    حسابك</p>
                                            </div>
                                            <form method="post" action="{{route('admin.password.confirm')}}">
                                                @csrf
                                                @method('POST')
                                                <div class="form-group">
                                                    <input required name="password"
                                                           class="form-control @error('password') is-invalid @enderror"
                                                           placeholder="ادخل كلمة المرور"
                                                           type="password">
                                                    @error('password')
                                                    <span class="invalid-feedback" role="alert">
                                                            <strong>{{ $message }}</strong>
                                                        </span>
                                                    @enderror
                                                </div>
                                                <button class="btn btn-main-primary btn-block">الدخول الى لوحة التحكم
                                                </button>
 
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div><!-- End -->
                </div>
            </div><!-- End -->
        </div>
    </div>
@endsection
@section('js')
@endsection
