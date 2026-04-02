@extends('admin.layouts.master')

@section('content')
@can('employee.users.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    <style>
        .btn-md {
            height: 40px !important;
            min-width: 100px !important;
            padding: 10px !important;
            text-align: center !important;
        }

        .user-avatar {
            width: 70px;
            height: 70px;
            cursor: pointer;
            border-radius: 100%;
            padding: 3px;
            border: 1px solid #aaa;
            object-fit: cover;
        }
    </style>

    <div class="row row-sm">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header pb-0">
                    <div class="col-lg-12 margin-tb">
                        <h4 class="alert alert-primary text-center">مستخدمو النظام</h4>
                    </div>
                    <div class="row mt-1 mb-1 text-center justify-content-center align-content-center">
                        @can('employee.users.add')
                            <a href="{{ route('admin.users.create') }}" role="button" class="btn btn-md btn-info m-1">
                                <i class="fa fa-plus"></i>
                                اضافة مستخدم
                            </a>
                        @endcan
                    </div>
                </div>
                <div class="card-body p-1 m-1">
                    <div class="table-responsive hoverable-table">
                        <table class="table table-bordered table-hover display w-100" id="users-table"
                               style="text-align: center;">
                            <thead>
                            <tr>
                                <th class="border-bottom-0 text-center">#</th>
                                <th class="border-bottom-0 text-center">اسم المستخدم</th>
                                <th class="border-bottom-0 text-center">البريد الالكتروني</th>
                                <th class="border-bottom-0 text-center">الصلاحية</th>
                                <th class="border-bottom-0 text-center">الفرع</th>
                                <th class="border-bottom-0 text-center">الحالة</th>
                                <th class="border-bottom-0 text-center">الصورة الشخصية</th>
                                <th class="border-bottom-0 text-center">الاعدادات</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse ($users as $user)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $user->name }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>{{ $user->roles->pluck('name')->filter()->implode('، ') ?: '-' }}</td>
                                    <td>{{ $user->branch?->branch_name ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $user->status ? 'success' : 'danger' }}">
                                            {{ $user->status ? 'مفعل' : 'موقوف' }}
                                        </span>
                                    </td>
                                    <td>
                                        @php
                                            $avatar = empty($user->profile_pic)
                                                ? asset('assets/img/avatar.png')
                                                : asset($user->profile_pic);
                                        @endphp
                                        <img
                                            class="user-avatar preview-user-image"
                                            src="{{ $avatar }}"
                                            alt="{{ $user->name }}"
                                        >
                                    </td>
                                    <td>
                                        @can('employee.users.show')
                                            <a href="{{ route('admin.users.show', $user->id) }}"
                                               class="btn btn-info" role="button">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                        @endcan
                                        @can('employee.users.edit')
                                            <a href="{{ route('admin.users.edit', $user->id) }}"
                                               class="btn btn-warning" role="button">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <a href="{{ route('admin.users.permissions.edit', $user->id) }}"
                                               class="btn btn-primary" role="button" title="إسناد الصلاحيات">
                                                <i class="fa fa-key"></i>
                                            </a>
                                        @endcan
                                        @can('employee.users.delete')
                                            @if ($user->id !== 1)
                                                <button
                                                    type="button"
                                                    class="btn btn-danger delete-user"
                                                    data-user-id="{{ $user->id }}"
                                                    data-user-email="{{ $user->email }}"
                                                >
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            @endif
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8">لا يوجد مستخدمون لعرضهم.</td>
                                </tr>
                            @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="modal" id="modaldemo8" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header text-center">
                        <h6 class="modal-title w-100" style="font-family: 'Almarai';">حذف مستخدم</h6>
                        <button aria-label="Close" class="close" data-dismiss="modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form id="delete-user-form"
                          action="{{ route('admin.users.destroy', ['user' => '__USER__']) }}"
                          method="post">
                        @csrf
                        @method('DELETE')
                        <div class="modal-body">
                            <p>هل انت متأكد انك تريد الحذف؟</p>
                            <input class="form-control" id="delete-user-email" type="text" readonly>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-dismiss="modal">الغاء</button>
                            <button type="submit" class="btn btn-danger">حذف</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal" id="modaldemo9">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content modal-content-demo">
                    <div class="modal-header text-center">
                        <h6 class="modal-title w-100" style="font-family: 'Almarai';">عرض صورة المستخدم</h6>
                        <button aria-label="Close" class="close" data-dismiss="modal" type="button">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <img id="image_larger" alt="image" style="width: 100%; height: 400px !important; object-fit: contain;">
                    </div>
                    <div class="modal-footer">
                        <button data-dismiss="modal" class="btn btn-md btn-danger" type="button">اغلاق</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection

@section('js')
    <script>
        $(document).ready(function () {
            const deleteForm = $('#delete-user-form');
            const deleteActionTemplate = deleteForm.attr('action');

            $('.delete-user').on('click', function () {
                const userId = $(this).data('user-id');
                const userEmail = $(this).data('user-email');

                deleteForm.attr('action', deleteActionTemplate.replace('__USER__', userId));
                $('#delete-user-email').val(userEmail);
                $('#modaldemo8').modal('show');
            });

            $('.preview-user-image').on('click', function () {
                $('#image_larger').prop('src', $(this).attr('src'));
                $('#modaldemo9').modal('show');
            });

            $('#users-table').DataTable({
                responsive: true,
                lengthChange: true,
                autoWidth: false,
                order: [[0, 'asc']],
            });
        });
    </script>
@endsection
