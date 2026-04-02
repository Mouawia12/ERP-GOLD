@php
    $permissionInputName = $permissionInputName ?? 'permission[]';
    $permissionSearchId = $permissionSearchId ?? 'permission-search';
    $permissionCheckAllId = $permissionCheckAllId ?? 'check_all';
    $permissionUncheckAllId = $permissionUncheckAllId ?? 'uncheck_all';
    $permissionScope = $permissionScope ?? 'role-permissions';
    $permissionErrorKey = str_ends_with($permissionInputName, '[]')
        ? substr($permissionInputName, 0, -2)
        : $permissionInputName;
@endphp

<style>
    .permission-toolbar {
        gap: 10px;
    }

    .permission-search {
        max-width: 340px;
        margin: 0 auto 20px;
    }

    .permission-group-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        margin-bottom: 20px;
        overflow: hidden;
    }

    .permission-group-title {
        background: #eef6ff;
        border-bottom: 1px solid #d7e8ff;
        font-weight: 700;
        padding: 12px 16px;
    }

    .permission-module-row.hidden-by-search {
        display: none;
    }

    .permission-module-row td {
        vertical-align: middle;
    }

    .permission-module-name {
        font-weight: 600;
    }

    .permission-badge {
        display: inline-block;
        font-size: 12px;
        background: #f3f4f6;
        border-radius: 999px;
        padding: 4px 10px;
        margin-top: 4px;
    }

    .switch {
        position: relative;
        display: inline-block;
        width: 50px;
        height: 25px;
    }

    .switch input {
        opacity: 0;
        position: absolute;
        inset: 0;
        width: 100%;
        height: 100%;
        z-index: 2;
        cursor: pointer;
        margin: 0;
    }

    .slider {
        position: absolute;
        pointer-events: none;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #ccc;
        transition: .4s;
    }

    .slider:before {
        position: absolute;
        content: "";
        height: 18px;
        width: 18px;
        left: 0;
        bottom: 0;
        background-color: white;
        transition: .4s;
    }

    input:checked + .slider {
        background-color: #2196F3;
    }

    input:focus + .slider {
        box-shadow: 0 0 1px #2196F3;
    }

    input:checked + .slider:before {
        transform: translateX(26px);
    }

    .slider.round {
        border-radius: 34px;
    }

    .slider.round:before {
        border-radius: 50%;
    }
</style>

<div class="d-flex flex-wrap justify-content-center align-items-center permission-toolbar mb-3">
    <input
        type="text"
        id="{{ $permissionSearchId }}"
        class="form-control permission-search"
        placeholder="ابحث باسم الموديول أو الصلاحية"
    >
    <button type="button" id="{{ $permissionCheckAllId }}" class="btn btn-danger">
        <i class="fa fa-check"></i>
        تحديد الكل
    </button>
    <button type="button" id="{{ $permissionUncheckAllId }}" class="btn btn-secondary">
        <i class="fa fa-times"></i>
        الغاء تحديد الكل
    </button>
</div>

@error($permissionErrorKey)
    <div class="alert alert-danger mb-3">{{ $message }}</div>
@enderror

@foreach($permissionGroups as $group)
    <div class="permission-group-card">
        <div class="permission-group-title d-flex justify-content-between align-items-center">
            <span>{{ $group['label'] }}</span>
            <button
                type="button"
                class="btn btn-sm btn-outline-primary toggle-group"
                data-group="{{ $loop->index }}"
                data-permission-scope="{{ $permissionScope }}"
            >
                تحديد المجموعة
            </button>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered table-condensed table-hover text-center mb-0">
                <thead>
                <tr>
                    <th style="width: 8%;">#</th>
                    <th>اسم الصلاحية</th>
                    <th>اضافة</th>
                    <th>عرض</th>
                    <th>تعديل</th>
                    <th>حذف</th>
                </tr>
                </thead>
                <tbody>
                @foreach($group['modules'] as $module)
                    <tr
                        class="permission-module-row"
                        data-group-index="{{ $loop->parent->index }}"
                        data-permission-scope="{{ $permissionScope }}"
                        data-search-text="{{ mb_strtolower($group['label'].' '.$module['label'].' '.implode(' ', array_column($module['permissions'], 'label'))) }}"
                    >
                        <td>{{ $loop->iteration }}</td>
                        <td>
                            <div class="permission-module-name">{{ $module['label'] }}</div>
                            <div class="permission-badge">{{ $module['key'] }}</div>
                        </td>
                        @foreach($module['permissions'] as $permission)
                            <td>
                                <label class="switch">
                                    <input
                                        type="checkbox"
                                        class="permission-checkbox"
                                        data-permission-scope="{{ $permissionScope }}"
                                        name="{{ $permissionInputName }}"
                                        value="{{ $permission['name'] }}"
                                        @checked(in_array($permission['name'], $selectedPermissions, true))
                                    >
                                    <span class="slider round"></span>
                                </label>
                            </td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endforeach

<script>
    $(document).ready(function () {
        const permissionScope = @json($permissionScope);
        const permissionRows = $('.permission-module-row[data-permission-scope="' + permissionScope + '"]');
        const permissionCheckboxes = $('.permission-checkbox[data-permission-scope="' + permissionScope + '"]');

        $('#{{ $permissionCheckAllId }}').on('click', function () {
            permissionCheckboxes.prop('checked', true);
        });

        $('#{{ $permissionUncheckAllId }}').on('click', function () {
            permissionCheckboxes.prop('checked', false);
        });

        $('.toggle-group[data-permission-scope="' + permissionScope + '"]').on('click', function () {
            const groupIndex = $(this).data('group');
            const groupCheckboxes = permissionRows
                .filter('[data-group-index="' + groupIndex + '"]:visible')
                .find('.permission-checkbox');
            const shouldCheck = groupCheckboxes.filter(':checked').length !== groupCheckboxes.length;

            groupCheckboxes.prop('checked', shouldCheck);
        });

        $('#{{ $permissionSearchId }}').on('input', function () {
            const query = $(this).val().toString().trim().toLowerCase();

            permissionRows.each(function () {
                const haystack = ($(this).data('search-text') || '').toString().toLowerCase();
                $(this).toggleClass('hidden-by-search', query !== '' && !haystack.includes(query));
            });
        });
    });
</script>
