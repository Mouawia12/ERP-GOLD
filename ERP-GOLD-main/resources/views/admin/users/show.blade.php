@extends('admin.layouts.master')
<!-- Internal Data table css -->
<style>
    i.la {
        font-size: 15px !important;
    }
</style>
@section('content')
    <div class="row text-center">
        <div class="col-lg-12 mt-5">
            <p class="alert alert-info alert-md text-center"> عرض بيانات المستخدم </p>
        </div>
        <div class="table-responsive hoverable-table">
            <table class="table table-striped table-condensed table-bordered text-center">
                <thead>
                <tr>
                    <th class="border-bottom-0 text-center">اسم المستخدم</th>
                    <th class="border-bottom-0 text-center">البريد الالكترونى</th>
					<th class="border-bottom-0 text-center">الفرع</th>
					<th class="border-bottom-0 text-center">الصلاحية</th>
                    <th class="border-bottom-0 text-center">الصورة الشخصية</th>
                    

                </tr>
                </thead>
                <tbody>
                <tr>
                    <td>{{ $Admin ->name}}</td>
                    <td>{{ $Admin ->email }}</td>
					 <td>
                        @if(empty($Admin ->branch_id))
                            كل الفروع
                        @else
                            {{$Admin ->branch->branch_name}}
                        @endif
                    </td>
                    <td>
                        {{$Admin ->role_name}}
                    </td>
					 <td>
                        @if(empty($Admin ->profile_pic))
                            <img data-toggle="modal" href="#modaldemo9"
                                 src="{{asset('assets/img/guest.png')}}"
                                 style="width: 70px;cursor: pointer; height: 70px;border-radius: 100%; padding: 3px; border: 1px solid #aaa;">
                        @else
                            <img data-toggle="modal" href="#modaldemo9"
                                 src="{{asset($Admin ->profile_pic)}}"
                                 style="width: 70px;cursor: pointer; height: 70px;border-radius: 100%; padding: 3px; border: 1px solid #aaa;">
                        @endif
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
@endsection
