@extends('admin.layouts.master')

@section('content')
<style>
    .shifts-page {
        direction: rtl;
    }
    .shifts-page .summary-card {
        border: 1px solid #ececec;
        border-radius: 14px;
        padding: 18px;
        background: #fff;
        box-shadow: 0 8px 28px rgba(0, 0, 0, 0.05);
    }
</style>

<div class="container-fluid shifts-page">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="mb-0">إدارة الشفتات</h3>
        @if($activeShift)
            <a href="{{ route('admin.shifts.show', $activeShift) }}" class="btn btn-primary">عرض الشفت النشط</a>
        @endif
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row mb-4">
        <div class="col-lg-5 mb-3">
            <div class="summary-card h-100">
                @if($activeShift)
                    <h4 class="mb-3">الشفت النشط</h4>
                    <p class="mb-1"><strong>الفرع:</strong> {{ $activeShift->branch->name }}</p>
                    <p class="mb-1"><strong>المستخدم:</strong> {{ $activeShift->user->name }}</p>
                    <p class="mb-1"><strong>وقت الفتح:</strong> {{ $activeShift->opened_at?->format('Y-m-d H:i') }}</p>
                    <p class="mb-3"><strong>عهدة البداية:</strong> {{ number_format((float) $activeShift->opening_cash, 2) }}</p>

                    <form method="POST" action="{{ route('admin.shifts.close', $activeShift) }}">
                        @csrf
                        @method('PATCH')
                        <div class="form-group">
                            <label>النقدية الفعلية عند الإغلاق</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                name="closing_cash"
                                value="{{ old('closing_cash') }}"
                                required
                            >
                        </div>
                        <div class="form-group">
                            <label>ملاحظات الإغلاق</label>
                            <textarea class="form-control" name="closing_notes" rows="3">{{ old('closing_notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-danger">إغلاق الشفت</button>
                    </form>
                @else
                    <h4 class="mb-3">فتح شفت جديد</h4>
                    <form method="POST" action="{{ route('admin.shifts.store') }}">
                        @csrf
                        <div class="form-group">
                            <label>الفرع</label>
                            @if($canManageShiftDirectory)
                                <select class="js-example-basic-single w-100" name="branch_id" required>
                                    @foreach($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected(old('branch_id', Auth::user()->branch_id) == $branch->id)>
                                            {{ $branch->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input class="form-control" type="text" readonly value="{{ Auth::user()->branch->name }}">
                                <input type="hidden" name="branch_id" value="{{ Auth::user()->branch_id }}">
                            @endif
                        </div>
                        <div class="form-group">
                            <label>عهدة البداية</label>
                            <input
                                type="number"
                                step="0.01"
                                min="0"
                                class="form-control"
                                name="opening_cash"
                                value="{{ old('opening_cash', 0) }}"
                            >
                        </div>
                        <div class="form-group">
                            <label>ملاحظات الافتتاح</label>
                            <textarea class="form-control" name="opening_notes" rows="3">{{ old('opening_notes') }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-success">فتح الشفت</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="col-lg-7 mb-3">
            <div class="card shadow-sm h-100">
                <div class="card-header">
                    <h4 class="mb-0">فلاتر البحث</h4>
                </div>
                <div class="card-body">
                    <form method="GET" action="{{ route('admin.shifts.index') }}">
                        <div class="row">
                            <div class="col-md-3">
                                <label>الحالة</label>
                                <select class="form-control" name="status">
                                    <option value="">الكل</option>
                                    <option value="open" @selected(($filters['status'] ?? null) === 'open')>مفتوح</option>
                                    <option value="closed" @selected(($filters['status'] ?? null) === 'closed')>مغلق</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label>من تاريخ</label>
                                <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
                            </div>
                            <div class="col-md-3">
                                <label>إلى تاريخ</label>
                                <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
                            </div>
                            @if($canManageShiftDirectory)
                                <div class="col-md-3">
                                    <label>الفرع</label>
                                    <select class="form-control" name="branch_id">
                                        <option value="">الكل</option>
                                        @foreach($branches as $branch)
                                            <option value="{{ $branch->id }}" @selected(($filters['branch_id'] ?? null) == $branch->id)>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label>المستخدم</label>
                                    <select class="form-control" name="user_id">
                                        <option value="">الكل</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" @selected(($filters['user_id'] ?? null) == $user->id)>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                        </div>
                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">تطبيق الفلاتر</button>
                            <a href="{{ route('admin.shifts.index') }}" class="btn btn-outline-secondary">إعادة التعيين</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <h4 class="mb-0">سجل الشفتات</h4>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>الحالة</th>
                        <th>الفرع</th>
                        <th>المستخدم</th>
                        <th>وقت الفتح</th>
                        <th>وقت الإغلاق</th>
                        <th>عهدة البداية</th>
                        <th>النقدية الفعلية</th>
                        <th>الفرق</th>
                        <th>الإجراء</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($shifts as $shift)
                        <tr>
                            <td>{{ $shift->id }}</td>
                            <td>
                                @if($shift->status === 'open')
                                    <span class="badge badge-success">مفتوح</span>
                                @else
                                    <span class="badge badge-secondary">مغلق</span>
                                @endif
                            </td>
                            <td>{{ $shift->branch->name }}</td>
                            <td>{{ $shift->user->name }}</td>
                            <td>{{ $shift->opened_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ $shift->closed_at?->format('Y-m-d H:i') ?? '-' }}</td>
                            <td>{{ number_format((float) $shift->opening_cash, 2) }}</td>
                            <td>{{ $shift->closing_cash !== null ? number_format((float) $shift->closing_cash, 2) : '-' }}</td>
                            <td>{{ $shift->cash_difference !== null ? number_format((float) $shift->cash_difference, 2) : '-' }}</td>
                            <td>
                                <a href="{{ route('admin.shifts.show', $shift) }}" class="btn btn-sm btn-primary">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center text-muted">لا توجد شفتات مسجلة بعد.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
