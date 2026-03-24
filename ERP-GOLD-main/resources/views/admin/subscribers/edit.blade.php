@extends('admin.layouts.master')

@section('content')
    <div class="row">
        <div class="col-lg-12">
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
                                <input type="text" name="name" class="form-control" value="{{ old('name', $subscriber->name) }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد الدخول</label>
                                <input type="email" name="login_email" class="form-control" value="{{ old('login_email', $subscriber->login_email) }}" required>
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بريد التواصل</label>
                                <input type="email" name="contact_email" class="form-control" value="{{ old('contact_email', $subscriber->contact_email) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>هاتف التواصل</label>
                                <input type="text" name="contact_phone" class="form-control" value="{{ old('contact_phone', $subscriber->contact_phone) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد المستخدمين المسموح</label>
                                <input type="number" min="1" name="max_users" class="form-control" value="{{ old('max_users', $subscriber->max_users) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>عدد الفروع المسموح</label>
                                <input type="number" min="1" name="max_branches" class="form-control" value="{{ old('max_branches', $subscriber->max_branches) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>بداية الاشتراك</label>
                                <input type="date" name="starts_at" class="form-control" value="{{ old('starts_at', optional($subscriber->starts_at)->format('Y-m-d')) }}">
                            </div>
                            <div class="col-md-4 form-group">
                                <label>نهاية الاشتراك</label>
                                <input type="date" name="ends_at" class="form-control" value="{{ old('ends_at', optional($subscriber->ends_at)->format('Y-m-d')) }}">
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
                                <textarea name="notes" rows="4" class="form-control">{{ old('notes', $subscriber->notes) }}</textarea>
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
