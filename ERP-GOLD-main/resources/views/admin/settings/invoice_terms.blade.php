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
        <div class="col-xl-8 mx-auto">
            <div class="card">
                <div class="card-header pb-0">
                    <h4 class="alert alert-primary text-center">شروط الفاتورة الافتراضية</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.invoice-terms.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label for="invoice_terms" class="font-weight-bold">
                                نص الشروط الذي يظهر تلقائيًا عند إنشاء الفاتورة
                            </label>
                            <textarea
                                id="invoice_terms"
                                name="invoice_terms"
                                rows="8"
                                class="form-control"
                                placeholder="اكتب الشروط الافتراضية هنا"
                            >{{ old('invoice_terms', $invoiceTerms) }}</textarea>
                            <small class="text-muted d-block mt-2">
                                يمكن تعديل النص داخل كل فاتورة قبل الحفظ، لكن الفاتورة تحتفظ بنسختها الخاصة بعد الإنشاء.
                            </small>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center">
                                <button type="submit" class="btn btn-info btn-md">
                                    حفظ الشروط
                                </button>
                            </div>
                        @endcan
                    </form>
                </div>
            </div>
        </div>
    </div>
@endcan
@endsection
