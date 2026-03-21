@extends('admin.layouts.master')
@section('content')
@can('employee.users.show') 
<style>
    tfoot input {
        width: 100%;
        padding: 3px;
        box-sizing: border-box;
    }

    .btn-md {
        height: 40px !important;
        min-width: 100px !important;
        padding: 10px !important;
        text-align: center !important;
    }

    input[type="checkbox"] {
        width: 15px !important;
        height: 15px !important;
    }
</style>
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                             مستخدمي النظام
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>
                @can('اضافة مستخدم') 
                <div class="card-body">
                    <form action="{{route('admin.admins.store','test')}}" method="post"
                          enctype="multipart/form-data">
                        {{csrf_field()}}
                        <input type="hidden"  name="remember_token"  value="" />
                                                      
                        <div class="row m-t-3 mb-3">
                            <div class="parsley-input col-md-4" id="fnWrapper">
                                <label> اسم المستخدم <span class="text-danger"> </span></label>
                                <input class="form-control mg-b-20"
                                       data-parsley-class-handler="#lnWrapper" name="name" required=""
                                       type="text">
                            </div>
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> البريد الالكترونى : <span class="text-danger"> </span></label>
                                <input class="form-control  mg-b-20 @error('email') is-invalid @enderror" style="text-align: left;direction:ltr;"
                                       data-parsley-class-handler="#lnWrapper" name="email" required=""
                                       type="email">

                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>

                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label class="form-label"> الصلاحية </label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الصلاحية"
                                        class="form-control selectpicker" required id="role_name" name="role_name">
                                    @foreach($roles as $role)
                                        <option value="{{$role}}">{{$role}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="row m-t-3 mb-3">
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> كلمة المرور : <span class="text-danger"> </span></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword" id="basic-addon1">
                                            <i class="fa fa-eye basic-addon1"></i>
                                        </span>
                                    </div>
                                    <input id="password" type="password"
                                           class="form-control @error('password') is-invalid @enderror text-left"
                                           dir="ltr" name="password" required
                                           aria-describedby="basic-addon1">
                                </div>
                            </div>
                            <div class="parsley-input col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label> تأكيد كلمة المرور : <span class="text-danger"> </span></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text showPassword2"
                                              id="basic-addon2">
                                            <i class="fa fa-eye basic-addon2"></i>
                                        </span>
                                    </div>
                                    <input id="confirm-password" type="password"
                                           class="form-control @error('password') is-invalid @enderror text-left"
                                           dir="ltr" name="confirm-password" required
                                           aria-describedby="basic-addon2">
                                </div>
                            </div>
                            <div class="parsley-input branch col-md-4 mg-t-20 mg-md-t-0" id="lnWrapper">
                                <label class="form-label"> الفرع </label>
                                <select data-live-search="true" data-style="btn-dark" title="اختر الفرع" 
                                        class="form-control selectpicker" name="branch_id" @if(!empty(Auth::user()->branch_id)) required @endif id="branch_id">
                                    @if(empty(Auth::user()->branch_id))
                                        <option value="">كل الفروع</option>
                                    @endif
                                    @foreach($branches as $branch)
                                        <option value="{{$branch->id}}">{{$branch->branch_name}}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div> 
                        <div class="col-xs-12 col-sm-12 col-md-12 text-center">
                            <button class="btn btn-info pd-x-20" type="submit">
                            <i class="fa fa-plus"></i>
                            اضافة مستخدم جديد  
                            </button> 
                        </div>
                    </form> 
                </div> 
                @endcan 
                <div class="card-body p-1 m-1">
                    <div class="table-responsive hoverable-table">
                        <div id="head-right" ></div> 
                        <table class="display w-100 table-bordered" id="example1"
                               style="text-align: center;"> 
                            <thead>
                            <tr> 
                                <th class="border-bottom-0 text-center">#</th>
                                <th class="border-bottom-0 text-center">اسم المستخدم</th>
                                <th class="border-bottom-0 text-center">البريد الالكترونى</th> 
								<th class="border-bottom-0 text-center">الصلاحية</th>
                                <th class="border-bottom-0 text-center">الفرع</th>
                                <th class="border-bottom-0 text-center">مفعل</th>
                                <th class="border-bottom-0 text-center">الصورة الشخصية</th>
                                <th >اعدادات</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach ($users as $key => $user)
                                <tr>
                                
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $user->name}}</td>
                                    <td>{{ $user->email }}</td> 
                                    @php
                                    $userRoles = implode(',', $user->roles()->pluck('name')->toArray());
                                    @endphp
                                    <td>{{$userRoles}}</td> 
                                    <td>
                                        @if($user->is_admin)
                                            كل الفروع
                                        @else
                                            {{$user->branch->name}}
                                        @endif
                                    </td>
                                    <td> 
                                        <input type="checkbox" name="status[]" 
                                            @if($user->status)
                                                checked 
                                            @endif 
                                            value="{{ $user->status}}">
                                    </td>
									<td>
                                        @if(empty($user->profile_pic))
                                            <img data-toggle="modal" href="#modaldemo9"
                                                 src="{{asset('assets/img/avatar.png')}}"
                                                 style="width: 70px;cursor: pointer; height: 70px;border-radius: 100%; padding: 3px; border: 1px solid #aaa;">
                                        @else
                                            <img data-toggle="modal" href="#modaldemo9"
                                                 src="{{asset($user->profile_pic)}}"
                                                 style="width: 70px;cursor: pointer; height: 70px;border-radius: 100%; padding: 3px; border: 1px solid #aaa;">
                                        @endif
                                    </td>
                                    <td>
                                        @can('employee.users.show')
                                            <a href="{{ route('admin.users.show', $user->id) }}"
                                                class="btn btn-info" role="button" data-bs-toggle="button">
                                                <i class="fa fa-eye"></i> 
                                            </a>
                                        @endcan
                                        @can('employee.users.edit')
                                            <a href="{{ route('admin.users.edit', $user->id) }}"
                                                class="btn btn-warning" role="button" data-bs-toggle="button">
                                                <i class="fa fa-edit"></i> 
                                            </a>
                                        @endcan
                                        @can('employee.users.delete')
                                            @if ($user->id != 1)
                                                <button  
                                                   user_id="{{ $user->id }}"
                                                   email="{{ $user->email }}" role="button" data-bs-toggle="modal" 
                                                   class="btn btn-danger delete_user">
                                                    <i class="fa fa-trash"></i> 
                                                </button>
                                            @endif
                                        @endcan
                                        
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <!--/div-->

        <!-- Modal effects -->
        <div class="modal" id="modaldemo8" tabindex="-1" role="dialog"  aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header text-center">
                        <h6 class="modal-title w-100" style="font-family: 'Almarai'; ">حذف مستخدم</h6>
                        <button aria-label="Close" class="close modal-close-btn close-delete"
                                data-dismiss="modal" type="button">
                                <span aria-hidden="true">&times;</span>
                        </button>
                    </div> 
                    <form action="{{ route('admin.users.destroy',':id') }}" method="post">
                        @csrf
                        @method("POST")
                        <div class="modal-body">
                            <p>هل انت متأكد انك تريد الحذف ؟</p><br>
                            <input type="hidden" name="user_id" id="user_id" value="">
                            <input class="form-control" name="email" id="email" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary cancel-modal">الغاء</button>
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- Modal effects -->
    <div class="modal" id="modaldemo9">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content modal-content-demo">
                <div class="modal-header text-center">
                    <h6 class="modal-title w-100"
                        style="font-family: 'Almarai'; ">عرض صورة المستخدم</h6>
                    <button aria-label="Close" class="close"
                            data-dismiss="modal" type="button"><span aria-hidden="true">&times;</span></button>
                </div>
                <div class="modal-body">
                    <img id="image_larger" alt="image" style="width: 100%;height: 400px!important;  "/>
                </div>
                <div class="modal-footer">
                    <button data-dismiss="modal" class="btn btn-md btn-danger"><i class="fa fa-colse"></i> اغلاق
                    </button>
                </div>
            </div>
        </div>
    </div>

@endcan 
@endsection 
@section('js')
<script>
    $(document).ready(function () {
        $('#check_all').click(function () {
            if (this.checked) {
                $('input.check').prop('checked', true);
            } else {
                $('input.check').prop('checked', false);
            }
        });
        $('.delete_admin').on('click', function () {
            var admin_id = $(this).attr('admin_id');
            var email = $(this).attr('email'); 
            $('.modal-body #admin_id').val(admin_id);
            $('.modal-body #email').val(email);
            $('#modaldemo8').show();
        });
        $('img').on('click', function () {
            var image_larger = $('#image_larger');
            var path = $(this).attr('src');
            $(image_larger).prop('src', path);
        });
 
        $(document).on('click', '.cancel-modal', function (event) {
            $('#modaldemo8').hide(); 
        });
        $(document).on('click', '.close-delete', function (event) {
            $('#modaldemo8').hide();
        });
 

        $('#example-table tfoot tr th:nth-child(2)').html('<input class="form-control" type="text" placeholder="اسم المستخدم" />');
        $('#example-table tfoot tr th:nth-child(3)').html('<input class="form-control" type="text" placeholder="البريد الالكترونى" />');
        $('#example-table tfoot tr th:nth-child(4)').html('<select id="roles" class="form-control">@foreach($roles as $role)<option value="{{$role}}">{{$role}}</option>@endforeach</select>');
        $('#example-table tfoot tr th:nth-child(5)').html('<input class="form-control" type="text" placeholder="الفرع" />');
        
        $('#example-table').DataTable({ 
            "responsive": true, "lengthChange": true, "autoWidth": false, 
            "buttons": ["copy", "excel", "print", "colvis",
				], 
            "order": [[1, "desc"]],
            initComplete: function () {
                this.api().columns().every(function () {
                    var that = this;
                    $('input[type="text"]', this.footer()).on('keyup change clear', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                    $('select', this.footer()).on('change', function () {
                        if (that.search() !== this.value) {
                            that.search(this.value).draw();
                        }
                    });
                });
            }
        });
    });
</script>
@endsection 
