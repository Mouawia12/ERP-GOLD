@extends('admin.layouts.master')

@section('content')
    <style>
        .subscriber-password-toggle {
            min-width: 46px;
            border-right: 0;
        }

        .subscriber-password-input {
            border-left: 0;
        }
    </style>

    <div class="row">
        <div class="col-lg-12">
            @include('admin.partials.validation-alert', [
                'title' => 'تعذر حفظ التعديلات. يرجى مراجعة الأخطاء التالية:',
            ])

            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">تعديل بيانات المشترك</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.subscribers.update', $subscriber) }}">
                        @csrf
                        @method('PUT')

                        <div class="row">
                            <div class="col-md-4 form-group">
                                <label>اسم الشركة / المشترك</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $subscriber->name) }}" required>
                                @error('name')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد الدخول</label>
                                <input type="email" name="login_email" class="form-control @error('login_email') is-invalid @enderror" value="{{ old('login_email', $subscriber->login_email) }}" required>
                                @error('login_email')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد التواصل</label>
                                <input type="email" name="contact_email" class="form-control @error('contact_email') is-invalid @enderror" value="{{ old('contact_email', $subscriber->contact_email) }}">
                                @error('contact_email')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>هاتف التواصل</label>
                                <input type="text" name="contact_phone" class="form-control @error('contact_phone') is-invalid @enderror" value="{{ old('contact_phone', $subscriber->contact_phone) }}">
                                @error('contact_phone')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>كلمة سر جديدة</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button
                                            type="button"
                                            class="btn btn-light subscriber-password-toggle"
                                            data-target="subscriber_new_password"
                                            aria-label="إظهار أو إخفاء كلمة السر"
                                        >
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    <input
                                        type="password"
                                        id="subscriber_new_password"
                                        name="new_password"
                                        class="form-control subscriber-password-input @error('new_password') is-invalid @enderror"
                                        autocomplete="new-password"
                                        placeholder="اتركه فارغًا للإبقاء على الكلمة الحالية"
                                    >
                                </div>
                                <small class="text-muted d-block mt-2">لن تتغير كلمة المرور الحالية إلا إذا كتبت كلمة سر جديدة هنا.</small>
                                @error('new_password')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>تأكيد كلمة السر الجديدة</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <button
                                            type="button"
                                            class="btn btn-light subscriber-password-toggle"
                                            data-target="subscriber_new_password_confirmation"
                                            aria-label="إظهار أو إخفاء كلمة السر"
                                        >
                                            <i class="fa fa-eye"></i>
                                        </button>
                                    </div>
                                    <input
                                        type="password"
                                        id="subscriber_new_password_confirmation"
                                        name="new_password_confirmation"
                                        class="form-control subscriber-password-input @error('new_password_confirmation') is-invalid @enderror"
                                        autocomplete="new-password"
                                        placeholder="أعد كتابة كلمة السر الجديدة"
                                    >
                                </div>
                                @error('new_password_confirmation')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد المستخدمين المسموح</label>
                                <input type="number" min="1" name="max_users" class="form-control @error('max_users') is-invalid @enderror" value="{{ old('max_users', $subscriber->max_users) }}">
                                @error('max_users')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد الفروع المسموح</label>
                                <input type="number" min="1" name="max_branches" class="form-control @error('max_branches') is-invalid @enderror" value="{{ old('max_branches', $subscriber->max_branches) }}">
                                @error('max_branches')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بداية الاشتراك</label>
                                <input type="date" name="starts_at" class="form-control @error('starts_at') is-invalid @enderror" value="{{ old('starts_at', optional($subscriber->starts_at)->format('Y-m-d')) }}">
                                @error('starts_at')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-4 form-group">
                                <label>نهاية الاشتراك</label>
                                <input type="date" name="ends_at" class="form-control @error('ends_at') is-invalid @enderror" value="{{ old('ends_at', optional($subscriber->ends_at)->format('Y-m-d')) }}">
                                @error('ends_at')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                            <div class="col-md-2 form-group d-flex align-items-end">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="subscriber_edit_status" name="status" value="1" @checked(old('status', $subscriber->status))>
                                    <label class="custom-control-label" for="subscriber_edit_status">نشط</label>
                                </div>
                            </div>
                            <div class="col-md-2 form-group d-flex align-items-end">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="subscriber_edit_trial" name="is_trial" value="1" @checked(old('is_trial', $subscriber->is_trial))>
                                    <label class="custom-control-label" for="subscriber_edit_trial">تجريبي</label>
                                </div>
                            </div>
                            <div class="col-md-12 form-group">
                                <label>ملاحظات</label>
                                <textarea name="notes" rows="4" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $subscriber->notes) }}</textarea>
                                @error('notes')
                                    <span class="invalid-feedback d-block" role="alert"><strong>{{ $message }}</strong></span>
                                @enderror
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('admin.subscribers.show', $subscriber) }}" class="btn btn-secondary">رجوع</a>
                            <button type="submit" class="btn btn-primary">حفظ التعديلات</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        (function () {
            document.querySelectorAll('.subscriber-password-toggle').forEach(function (button) {
                button.addEventListener('click', function () {
                    var target = document.getElementById(this.dataset.target);
                    var icon = this.querySelector('i');

                    if (!target) {
                        return;
                    }

                    var isHidden = target.getAttribute('type') === 'password';
                    target.setAttribute('type', isHidden ? 'text' : 'password');

                    if (icon) {
                        icon.classList.toggle('fa-eye', !isHidden);
                        icon.classList.toggle('fa-eye-slash', isHidden);
                    }
                });
            });
        })();
    </script>
@endsection
