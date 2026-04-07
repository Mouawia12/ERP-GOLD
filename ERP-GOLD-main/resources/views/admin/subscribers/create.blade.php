@extends('admin.layouts.master')

@section('content')
    <div class="row">
        <div class="col-lg-12">
            @include('admin.partials.validation-alert', [
                'title' => 'تعذر حفظ المشترك. يرجى مراجعة الأخطاء التالية:',
            ])

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">إضافة مشترك جديد</h4>
                    <p class="text-muted mb-0 mt-2">سيتم إنشاء أول حساب دخول لهذا المشترك مع فرعه الرئيسي تلقائيًا.</p>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.subscribers.store') }}">
                        @csrf

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>اسم الشركة / المشترك</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                                @error('name')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>اسم مدير الحساب الأول</label>
                                <input type="text" name="admin_name" class="form-control @error('admin_name') is-invalid @enderror" value="{{ old('admin_name') }}" placeholder="اختياري">
                                @error('admin_name')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد الدخول</label>
                                <input type="email" name="login_email" class="form-control @error('login_email') is-invalid @enderror" value="{{ old('login_email') }}" required>
                                @error('login_email')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>كلمة المرور</label>
                                <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" required>
                                @error('password')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>تأكيد كلمة المرور</label>
                                <input type="password" name="password_confirmation" class="form-control @error('password_confirmation') is-invalid @enderror" required>
                                @error('password_confirmation')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد التواصل</label>
                                <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email') }}">
                                @error('contact_email')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>هاتف التواصل</label>
                                <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone') }}">
                                @error('contact_phone')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد المستخدمين المسموح</label>
                                <input type="number" min="1" name="max_users" class="form-control @error('max_users') is-invalid @enderror" value="{{ old('max_users', 3) }}">
                                @error('max_users')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد الفروع المسموح</label>
                                <input type="number" min="1" name="max_branches" class="form-control @error('max_branches') is-invalid @enderror" value="{{ old('max_branches', 1) }}">
                                @error('max_branches')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بداية الاشتراك</label>
                                <input type="date" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at') }}">
                                @error('starts_at')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>نهاية الاشتراك</label>
                                <input type="date" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at') }}">
                                @error('ends_at')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>اسم الفرع الافتراضي</label>
                                <input type="text" name="default_branch_name" class="form-control @error('default_branch_name') is-invalid @enderror" value="{{ old('default_branch_name', 'الفرع الرئيسي') }}">
                                @error('default_branch_name')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>الرقم الضريبي الافتراضي</label>
                                <input type="text" name="default_tax_number" class="form-control @error('default_tax_number') is-invalid @enderror" value="{{ old('default_tax_number') }}">
                                @error('default_tax_number')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-6 form-group">
                                <label>العنوان الافتراضي</label>
                                <input type="text" name="default_address" class="form-control @error('default_address') is-invalid @enderror" value="{{ old('default_address') }}">
                                @error('default_address')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-3 form-group d-flex align-items-end">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="subscriber_status" name="status" value="1" @checked(old('status', true))>
                                    <label class="custom-control-label" for="subscriber_status">الحساب نشط</label>
                                </div>
                            </div>
                            <div class="col-md-3 form-group d-flex align-items-end">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="subscriber_trial" name="is_trial" value="1" @checked(old('is_trial'))>
                                    <label class="custom-control-label" for="subscriber_trial">نسخة تجريبية</label>
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>ملاحظات</label>
                                <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes') }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.subscribers.index') }}" class="btn btn-secondary">إلغاء</a>
                            <button type="submit" class="btn btn-primary">حفظ المشترك</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
