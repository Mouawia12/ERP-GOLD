@extends('admin.layouts.master')

@section('content')
@can('employee.system_settings.show')
    @if (session('success'))
        <div class="alert alert-success fade show">
            <button class="close" data-dismiss="alert" aria-label="Close">×</button>
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="row row-sm">
        <div class="col-xl-10 mx-auto">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center mb-0">إعدادات الشركة وشعار الفواتير</h4>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        تظهر هذه البيانات والشعار في الفواتير المطبوعة الخاصة بشركتك.
                        إذا لم يتم رفع شعار خاص فستستخدم الفواتير الشعار الرئيسي للتطبيق.
                        البيانات التفصيلية للفرع (الرقم الضريبي، السجل التجاري، العنوان) تُدار من صفحة
                        <a href="{{ route('admin.branches.index') }}">الفروع</a>.
                    </p>

                    <hr>

                    <div class="row">
                        <div class="col-lg-5">
                            <div class="form-group text-center">
                                <label class="font-weight-bold d-block mb-2">الشعار الحالي على الفواتير</label>
                                <div class="border rounded p-3 mb-3" style="background-color: #fafafa;">
                                    <img
                                        src="{{ $companyLogoUrl ?: $fallbackLogoUrl }}"
                                        alt="شعار الشركة"
                                        style="max-width: 100%; max-height: 180px; object-fit: contain;"
                                    >
                                </div>
                                @if ($companyLogoUrl)
                                    <small class="text-success d-block mb-2">
                                        <i class="fa fa-check-circle"></i> هذا شعار خاص بشركتك.
                                    </small>
                                    <form method="POST"
                                          action="{{ route('admin.system-settings.company.logo.delete') }}"
                                          onsubmit="return confirm('هل أنت متأكد من حذف شعار الشركة؟ سيتم استخدام الشعار الرئيسي للتطبيق على الفواتير.');">
                                        @csrf
                                        @method('DELETE')
                                        @can('employee.system_settings.edit')
                                            <button type="submit" class="btn btn-outline-danger btn-sm">
                                                <i class="fa fa-trash"></i> حذف الشعار الخاص بالشركة
                                            </button>
                                        @endcan
                                    </form>
                                @else
                                    <small class="text-muted d-block">
                                        لا يوجد شعار مخصص — يتم استخدام الشعار الرئيسي للتطبيق حالياً.
                                    </small>
                                @endif
                            </div>
                        </div>

                        <div class="col-lg-7">
                            <form method="POST"
                                  action="{{ route('admin.system-settings.company.update') }}"
                                  enctype="multipart/form-data">
                                @csrf
                                @method('PATCH')

                                <div class="form-group">
                                    <label for="company_name" class="font-weight-bold">اسم الشركة (يظهر على الفاتورة)</label>
                                    <input
                                        type="text"
                                        id="company_name"
                                        name="name"
                                        class="form-control"
                                        value="{{ old('name', $subscriber->name) }}"
                                        required
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="contact_email" class="font-weight-bold">البريد الإلكتروني للتواصل</label>
                                    <input
                                        type="email"
                                        id="contact_email"
                                        name="contact_email"
                                        class="form-control"
                                        value="{{ old('contact_email', $subscriber->contact_email) }}"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="contact_phone" class="font-weight-bold">رقم الهاتف للتواصل</label>
                                    <input
                                        type="text"
                                        id="contact_phone"
                                        name="contact_phone"
                                        class="form-control"
                                        value="{{ old('contact_phone', $subscriber->contact_phone) }}"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="invoice_logo" class="font-weight-bold">رفع شعار جديد للفواتير</label>
                                    <input
                                        type="file"
                                        id="invoice_logo"
                                        name="invoice_logo"
                                        class="form-control"
                                        accept="image/*"
                                    >
                                    <small class="text-muted d-block mt-2">
                                        صور بصيغة PNG / JPG / WEBP. الحجم الأقصى 2 ميجابايت.
                                        يفضل أن يكون الشعار بخلفية شفافة بأبعاد قريبة من <strong>400×200</strong> بكسل.
                                    </small>
                                </div>

                                @can('employee.system_settings.edit')
                                    <div class="text-center mt-4">
                                        <button type="submit" class="btn btn-info btn-md">
                                            <i class="fa fa-save"></i> حفظ التغييرات
                                        </button>
                                    </div>
                                @endcan
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
