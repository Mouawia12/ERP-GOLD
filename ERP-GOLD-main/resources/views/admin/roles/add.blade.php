@extends('admin.layouts.master')
<style>
    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 25px;
    }

    .switch input {
        opacity: 0;
        width: 0;
        height: 0;
    }

    .slider {
        position: absolute;
        cursor: pointer;
        top: 5px;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        -webkit-transition: .4s;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 0px;
        bottom: 0px;
        background-color: white;
        -webkit-transition: .4s;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        -webkit-transform: translateX(26px);
        -ms-transform: translateX(26px);
        transform: translateX(26px);
    }

    /* Rounded sliders */
    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }

</style>
 
@section('content')
    <div class="content-wrapper">
        <!-- Content Header (Page header) -->
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>الصلاحيات</h1>
                    </div>
                   
                </div>
            </div><!-- /.container-fluid -->
        </section>
        <!-- Main content -->
       
        <section class="content">
            <div class="card card-default">
                <div class="card-header">
                    <h3 class="card-title">اضافة صلاحية جديدة</h3>

                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                        <button type="button" class="btn btn-tool" data-card-widget="remove">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                @if(Session::has('success'))
                    <div class="alert alert-success d-flex align-items-center" role="alert">
                        {{ Session::get('success') }}
                    </div>
                    <br>
                @endif
                <form method="POST" action="{{route('permission.create')}}">
                    @csrf
                    <!-- /.card-header -->
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label>name</label>
                                    <input type="text" class="form-control" id="name" name="name">
                                </div>
                            </div>
							 <div class="col-md-12">
                                <div class="form-group">
                                    <label>key</label>
                                    <input type="text" class="form-control" id="key" name="key">
                                </div>
                            </div>
							  <div class="col-md-12">
                                <div class="form-group">
                                    <label>guard_name</label>
                                    <input type="text" class="form-control" id="guard_name" name="guard_name" value="admin-web" readonly>
                                </div>
                            </div>
                          
                         

                            <!-- /.form-group -->
                        </div>
                        <!-- /.col -->

                    </div>
                    <!-- /.row -->
                     <div class="row">
                        <div class="col-xs-12 col-md-12 col-md-12 text-center">
						 <button type="submit" class="btn btn-info">تأكيد واضافة</button>
                        
                        </div>
                    </div>

                    <!-- /.card-body -->
                </form>

            </div>
            <!-- /.card -->
            <!-- /.container-fluid -->
        </section>
        <!-- /.content -->
    </div>
    <!-- /.content-wrapper -->
  <!-- main-content closed -->
    <script src="{{asset('assets/js/jquery.min.js')}}"></script>
    <script>
        $('#check_all').click(function () {
            $('input[type=checkbox]').prop('checked', true);
        });
    </script>
@endsection
 
