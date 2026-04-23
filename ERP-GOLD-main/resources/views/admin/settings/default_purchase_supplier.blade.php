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
                    <h4 class="alert alert-primary text-center">المورد الافتراضي للمشتريات</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border text-center">
                        عند اختيار مورد هنا سيتم تحديده تلقائيًا عند فتح صفحة إضافة فاتورة مشتريات.
                    </div>

                    <form method="POST" action="{{ route('admin.system-settings.default-purchase-supplier.update') }}">
                        @csrf
                        @method('PATCH')

                        <div class="form-group mb-4">
                            <label for="default_supplier_id" class="font-weight-bold">المورد الافتراضي</label>
                            <select
                                id="default_supplier_id"
                                name="default_supplier_id"
                                class="js-example-basic-single w-100 form-control"
                            >
                                <option value="">بدون مورد افتراضي</option>
                                @foreach($suppliers as $supplier)
                                    <option
                                        value="{{ $supplier->id }}"
                                        @selected((int) ($defaultSupplierId ?? 0) === (int) $supplier->id)
                                    >
                                        {{ $supplier->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-2">
                                يمكن تغيير المورد داخل الفاتورة يدويًا، وهذا الإعداد يحدد القيمة المبدئية فقط.
                            </small>
                        </div>

                        @can('employee.system_settings.edit')
                            <div class="text-center">
                                <button type="submit" class="btn btn-info btn-md">
                                    حفظ المورد الافتراضي
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
