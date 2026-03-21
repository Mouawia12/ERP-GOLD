@extends('admin.layouts.master')
@section('content')
@can('employee.items.show')
    @if (session('success'))
        <div class="alert alert-success  fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif
<style>
    table.display.w-100.text-nowrap.table-bordered.dataTable.dtr-inline {
        direction: rtl;
        text-align:center;
    }
    body{
        direction: rtl; 
    }
  
</style>   
    <!-- row opened -->
    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0"  id="head-right" >
                    <div class="col-lg-12 margin-tb">
                        <h4  class="alert alert-primary text-center">
                            [ {{__('مجموعات الاصناف')}} ]
                        </h4>
                    </div>
                    <div class="clearfix"></div>
                </div>  
                <div class="col-lg-12 margin-tb text-center">
                    @can('employee.items.add')
                    <a href="{{route('storeCategory')}}" type="button" class="btn btn-labeled btn-info " id="createButton">
                        <span class="btn-label" style="margin-right: 10px;">
                        <i class="fa fa-plus"></i></span>
                        {{__('main.add_new')}}
                    </a> 
                    @endcan 
                </div> 
                <div class="card-body px-0 pt-0 pb-2"> 
                    <div class="card shadow mb-4"> 
                        <div class="card-body">
                            <div class="table-responsive hoverable-table" style="direction: rtl;"> 
                            <table class="display w-100  text-nowrap table-bordered" id="example1" 
                                   style="text-align: center;direction: rtl;">
                                    <thead>
                                        <tr>
                                            <th>#</th> 
                                            <th>{{__('main.name_ar')}}</th>
                                            <th>{{__('main.description')}}</th>
                                            <th> {{__('اخر تعديل')}} </th>
                                            <th>{{__('main.actions')}}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    @foreach($categories as $category)
                                        <tr>
                                            <td class="text-center">{{$loop -> index + 1}}</td> 
                                            <td class="text-center">{{$category -> title}}</td>
                                            <td class="text-center">{{$category -> description}}</td>
                                            <td class="text-center">{{$category -> updated_at}}</td>
                                            <td class="text-center">
                                            @can('employee.items.edit')
                                                <button type="button" class="btn btn-labeled btn-info editBtn" value="{{$category -> id}}">
                                                    <i class="fa-regular fa-pen-to-square"></i>
                                                </button>
                                            @endcan 
                                            @can('employee.items.delete')
                                                <button type="button" class="btn btn-labeled btn-danger deleteBtn"  value="{{$category -> id}}">
                                                    <i class="fa fa-trash"></i>
                                                </button>
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
<!-- Logout Modal-->
<div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel"
     aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
                <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
                <a class="btn btn-primary" href="login.html">Logout</a>
            </div>
        </div>
    </div>
</div>

    <div class="modal fade" id="createModal" tabindex="-1" role="dialog" aria-labelledby="paymentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-md" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label class="modelTitle"> {{__('main.cats')}}</label>
                    <button type="button" class="close modal-close-btn close-create"  data-bs-dismiss="modal"  aria-label="Close" >
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="paymentBody">
                    <form   method="POST" action="{{ route('storeCategory') }}"
                            enctype="multipart/form-data" >
                        @csrf

                        <div class="row">
                            <div class="col-6">
                                <div class="form-group">
                                    <label>{{ __('main.name_ar') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                    <input type="text"  id="name_ar" name="name_ar"
                                           class="form-control"
                                           placeholder="{{ __('main.name_ar') }}"  />
                                    <input type="text"  id="id" name="id"
                                           class="form-control"
                                           placeholder="{{ __('main.code') }}"  hidden=""/>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="form-group">
                                    <label>{{ __('main.name_en') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                    <input type="text"  id="name_en" name="name_en"
                                           class="form-control"
                                           placeholder="{{__('main.name_en')}}"  />
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12 " >
                                <div class="form-group">
                                    <label>{{ __('main.description') }} <span style="color:red; font-size:20px; font-weight:bold;">*</span> </label>
                                    <textarea type="text"  id="description" name="description" class="form-control" placeholder="{{ __('main.description') }}"></textarea>
                                </div>
                            </div>

                        </div>
                        <div class="row">
                            <div class="col-6">
                                <div class="custom-file">
                                    <input type="file" class="form-control custom-file-input" id="image_url"   name="image_url"  accept="image/png, image/jpeg" >
                                    <label class="custom-file-label" for="img" id="path">{{__('main.img_choose')}}   <span style="color:red;">*</span></label>
                                </div>

                            </div>
                            <div class="col-6 text-right">
                                <img src="{{asset('assets/img/photo.png')}}" id="profile-img-tag" width="150px" height="150px" class="profile-img"/>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-6" style="display: block; margin: 20px auto; text-align: center;">
                                <button type="submit" class="btn btn-labeled btn-primary"  >
                                    {{__('main.save_btn')}}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>


<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="smallModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <label class="modelTitle"> {{__('main.deleteModal')}}</label>
                <button type="button" class="close"  data-bs-dismiss="modal"  aria-label="Close" style="color: red; font-size: 20px; font-weight: bold;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="smallBody">
                <img src="../assets/img/warning.png" class="alertImage">
                <label class="alertTitle">{{__('main.delete_alert')}}</label>
                <br> <label  class="alertSubTitle" id="modal_table_bill"></label>
                <div class="row">
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-primary" onclick="confirmDelete()">
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-check"></i></span>{{__('main.confirm_btn')}}</button>
                    </div>
                    <div class="col-6 text-center">
                        <button type="button" class="btn btn-labeled btn-secondary cancel-modal"  >
                            <span class="btn-label" style="margin-right: 10px;"><i class="fa fa-close"></i></span>{{__('main.cancel_btn')}}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endcan 
@endsection 
@section('js')
<script type="text/javascript">
    function readURL(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function (e) {
                $('#profile-img-tag').attr('src', e.target.result);

            }
            reader.readAsDataURL(input.files[0]);
        }
    }
    $("#image_url").change(function(){
        readURL(this);
    });
</script>

<script type="text/javascript">
    let id = 0 ;
    $(document).ready(function()
    {
        id = 0 ;
        $(document).on('click', '#createButton', function(event) {
            console.log('clicked');
            id = 0 ;
            event.preventDefault();
            let href = $(this).attr('data-attr');
            $.ajax({
                url: href,
                beforeSend: function() {
                    $('#loader').show();
                },
                // return the result
                success: function(result) {
                    $('#createModal').modal("show");
                    $(".modal-body #name_ar").val( "" );
                    $(".modal-body #name_en").val( "" );
                    $(".modal-body #description").val("");
                    $(".modal-body #id").val( 0 );
                    $(".modal-body #image_url").val("");
                    $(".modal-body #profile-img-tag").attr('src' , "{{asset('assets/img/photo.png')}}" );

                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    console.log(error);
                    alert("Page " + href + " cannot open. Error:" + error);
                    $('#loader').hide();
                },
                timeout: 8000
            })
        });



        $(document).on('click', '.editBtn', function(event) {

            id = event.currentTarget.value ;
            event.preventDefault();
            $.ajax({
                type:'get',
                url:'getCategory' + '/' + id,
                dataType: 'json',

                success:function(response){
                    console.log(response);
                    if(response){
                        let href = $(this).attr('data-attr');
                        $.ajax({
                            url: href,
                            beforeSend: function() {
                                $('#loader').show();
                            },
                            // return the result
                            success: function(result) {
                                $('#createModal').modal("show");
                                if(response.image_url){
                                    var img =  response.image_url ;

                                    $(".modal-body #profile-img-tag").attr('src' , img );
                                }

                                $(".modal-body #name_ar").val( response.name_ar );
                                $(".modal-body #name_en").val( response.name_en );
                                $(".modal-body #description").val(response.description);
                                $(".modal-body #id").val( response.id );

                            },
                            complete: function() {
                                $('#loader').hide();
                            },
                            error: function(jqXHR, testStatus, error) {
                                console.log(error);
                                alert("Page " + href + " cannot open. Error:" + error);
                                $('#loader').hide();
                            },
                            timeout: 8000
                        })
                    } else {

                    }
                }
            });

        });
        $(document).on('click', '.deleteBtn', function(event) {
            id = event.currentTarget.value ;
            event.preventDefault();
            let href = $(this).attr('data-attr');
            $.ajax({
                url: href,
                beforeSend: function() {
                    $('#loader').show();
                },
                // return the result
                success: function(result) {
                    $('#deleteModal').modal("show");
                },
                complete: function() {
                    $('#loader').hide();
                },
                error: function(jqXHR, testStatus, error) {
                    console.log(error);
                    alert("Page " + href + " cannot open. Error:" + error);
                    $('#loader').hide();
                },
                timeout: 8000
            })
        });

        $(document).on('click' , '.cancel-modal' , function (event) {
            $('#deleteModal').modal("hide");
            id = 0 ;
        });
        $(document).on('click' , '.close-create' , function (event) {
            $('#createModal').modal("hide");
            id = 0 ;
        });



    });
    function confirmDelete(){
        let url = "{{ route('deleteCategory', ':id') }}";
        url = url.replace(':id', id);
        document.location.href=url;
    }
    function EditModal(id){
        $.ajax({
            type:'get',
            url:'getCategory' + '/' + id,
            dataType: 'json',

            success:function(response){
                console.log(response);
                if(response){
                    let href = $(this).attr('data-attr');
                    $.ajax({
                        url: href,
                        beforeSend: function() {
                            $('#loader').show();
                        },
                        // return the result
                        success: function(result) {
                            $('#createModal').modal("show");
                            var img =  response.image_url ;
                            $(".modal-body #profile-img-tag").attr('src' , img );
                            $(".modal-body #name").val( response.name );
                            $(".modal-body #code").val( response.code );
                            $(".modal-body #slug").val(response.slug);
                            $(".modal-body #description").val(response.description);
                            $(".modal-body #parent_id").val(response.parent_id);
                            $(".modal-body #id").val( response.id );
                            $(".modal-body #isGold").prop('checked' , response.isGold);


                        },
                        complete: function() {
                            $('#loader').hide();
                        },
                        error: function(jqXHR, testStatus, error) {
                            console.log(error);
                            alert("Page " + href + " cannot open. Error:" + error);
                            $('#loader').hide();
                        },
                        timeout: 8000
                    })
                } else {

                }
            }
        });
    }
</script>
@endsection 

 











