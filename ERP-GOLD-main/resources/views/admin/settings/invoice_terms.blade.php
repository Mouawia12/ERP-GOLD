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
                    <h4 class="alert alert-primary text-center">شروط الفاتورة الافتراضية</h4>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.system-settings.invoice-terms.update') }}" id="invoice-terms-settings-form">
                        @csrf
                        @method('PATCH')

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">مكتبة قوالب الشروط</h5>
                                <small class="text-muted">اختر قالبًا افتراضيًا، أو عدل النصوص، أو أضف قالبًا جديدًا حسب نوع الفاتورة.</small>
                            </div>
                            @can('employee.system_settings.edit')
                                <button type="button" class="btn btn-outline-primary btn-sm" id="add-invoice-template-row">
                                    إضافة قالب
                                </button>
                            @endcan
                        </div>

                        <div id="invoice-terms-templates">
                            @foreach (old('templates', $invoiceTermTemplates) as $index => $template)
                                <div class="card border mb-3 invoice-terms-template-row">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-lg-3">
                                                <div class="form-group">
                                                    <label>معرّف القالب</label>
                                                    <input
                                                        type="text"
                                                        name="templates[{{ $index }}][key]"
                                                        class="form-control invoice-template-key"
                                                        value="{{ $template['key'] ?? '' }}"
                                                        placeholder="retail-exchange"
                                                    >
                                                </div>
                                            </div>
                                            <div class="col-lg-4">
                                                <div class="form-group">
                                                    <label>اسم القالب</label>
                                                    <input
                                                        type="text"
                                                        name="templates[{{ $index }}][title]"
                                                        class="form-control invoice-template-title"
                                                        value="{{ $template['title'] ?? '' }}"
                                                        placeholder="استبدال وبيع تجزئة"
                                                    >
                                                </div>
                                            </div>
                                            <div class="col-lg-3">
                                                <div class="form-group">
                                                    <label class="d-block">القالب الافتراضي</label>
                                                    <div class="custom-control custom-radio mt-2">
                                                        <input
                                                            type="radio"
                                                            id="default_template_key_{{ $index }}"
                                                            name="default_template_key"
                                                            value="{{ $template['key'] ?? '' }}"
                                                            class="custom-control-input invoice-template-default"
                                                            {{ old('default_template_key', $defaultInvoiceTermsTemplateKey) === ($template['key'] ?? '') ? 'checked' : '' }}
                                                        >
                                                        <label class="custom-control-label" for="default_template_key_{{ $index }}">
                                                            استخدامه كافتراضي
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-lg-2">
                                                <div class="form-group">
                                                    <label class="d-block">&nbsp;</label>
                                                    @can('employee.system_settings.edit')
                                                        <button type="button" class="btn btn-outline-danger btn-block remove-invoice-template-row">
                                                            حذف
                                                        </button>
                                                    @endcan
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="form-group mb-0">
                                                    <label>نص القالب</label>
                                                    <textarea
                                                        name="templates[{{ $index }}][content]"
                                                        rows="4"
                                                        class="form-control invoice-template-content"
                                                        placeholder="اكتب نص هذا القالب"
                                                    >{{ $template['content'] ?? '' }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div class="form-group mb-4">
                            <label for="invoice_terms" class="font-weight-bold">
                                النص الافتراضي النهائي للفواتير الجديدة
                            </label>
                            <textarea
                                id="invoice_terms"
                                name="invoice_terms"
                                rows="8"
                                class="form-control"
                                placeholder="اكتب الشروط الافتراضية هنا"
                            >{{ old('invoice_terms', $invoiceTerms) }}</textarea>
                            <small class="text-muted d-block mt-2">
                                يتم مزامنة هذا النص مع القالب المحدد كافتراضي، ويمكن تعديله داخل كل فاتورة قبل الحفظ.
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

    <template id="invoice-terms-template-prototype">
        <div class="card border mb-3 invoice-terms-template-row">
            <div class="card-body">
                <div class="row">
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label>معرّف القالب</label>
                            <input type="text" data-name="key" class="form-control invoice-template-key" placeholder="template-key">
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="form-group">
                            <label>اسم القالب</label>
                            <input type="text" data-name="title" class="form-control invoice-template-title" placeholder="اسم القالب">
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <div class="form-group">
                            <label class="d-block">القالب الافتراضي</label>
                            <div class="custom-control custom-radio mt-2">
                                <input type="radio" data-name="default" class="custom-control-input invoice-template-default">
                                <label class="custom-control-label">استخدامه كافتراضي</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label class="d-block">&nbsp;</label>
                            <button type="button" class="btn btn-outline-danger btn-block remove-invoice-template-row">
                                حذف
                            </button>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="form-group mb-0">
                            <label>نص القالب</label>
                            <textarea data-name="content" rows="4" class="form-control invoice-template-content" placeholder="اكتب نص هذا القالب"></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </template>
@endcan
@endsection

@section('js')
<script>
    (function () {
        const container = document.getElementById('invoice-terms-templates');
        const addButton = document.getElementById('add-invoice-template-row');
        const previewTextarea = document.getElementById('invoice_terms');
        const prototype = document.getElementById('invoice-terms-template-prototype');

        if (!container || !previewTextarea) {
            return;
        }

        function normalizeKey(value) {
            return (value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\-_]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function rows() {
            return Array.from(container.querySelectorAll('.invoice-terms-template-row'));
        }

        function syncRowNames() {
            rows().forEach((row, index) => {
                const keyInput = row.querySelector('.invoice-template-key');
                const titleInput = row.querySelector('.invoice-template-title');
                const contentInput = row.querySelector('.invoice-template-content');
                const defaultInput = row.querySelector('.invoice-template-default');
                const defaultLabel = row.querySelector('.custom-control-label');
                const generatedKey = normalizeKey(keyInput.value) || normalizeKey(titleInput.value) || `template-${index + 1}`;

                keyInput.name = `templates[${index}][key]`;
                titleInput.name = `templates[${index}][title]`;
                contentInput.name = `templates[${index}][content]`;

                const radioId = `default_template_key_${index}`;
                defaultInput.name = 'default_template_key';
                defaultInput.id = radioId;
                defaultInput.value = generatedKey;
                defaultLabel.setAttribute('for', radioId);

                if (!normalizeKey(keyInput.value)) {
                    keyInput.value = generatedKey;
                }
            });
        }

        function syncPreviewFromDefault() {
            const selectedRow = rows().find((row) => row.querySelector('.invoice-template-default')?.checked);
            if (!selectedRow) {
                return;
            }

            previewTextarea.value = selectedRow.querySelector('.invoice-template-content').value;
        }

        function syncDefaultRowFromPreview() {
            const selectedRow = rows().find((row) => row.querySelector('.invoice-template-default')?.checked);
            if (!selectedRow) {
                return;
            }

            selectedRow.querySelector('.invoice-template-content').value = previewTextarea.value;
        }

        function bindRow(row) {
            row.querySelector('.invoice-template-key')?.addEventListener('input', syncRowNames);
            row.querySelector('.invoice-template-title')?.addEventListener('input', function () {
                const keyInput = row.querySelector('.invoice-template-key');
                if (!keyInput.value) {
                    keyInput.value = normalizeKey(this.value);
                }
                syncRowNames();
            });
            row.querySelector('.invoice-template-default')?.addEventListener('change', function () {
                if (this.checked) {
                    syncPreviewFromDefault();
                }
            });
            row.querySelector('.remove-invoice-template-row')?.addEventListener('click', function () {
                if (rows().length === 1) {
                    return;
                }

                const wasDefault = row.querySelector('.invoice-template-default')?.checked;
                row.remove();
                syncRowNames();

                if (wasDefault && rows()[0]) {
                    rows()[0].querySelector('.invoice-template-default').checked = true;
                    syncPreviewFromDefault();
                }
            });
        }

        rows().forEach(bindRow);
        syncRowNames();
        previewTextarea.addEventListener('input', syncDefaultRowFromPreview);

        addButton?.addEventListener('click', function () {
            const fragment = prototype.content.cloneNode(true);
            const row = fragment.querySelector('.invoice-terms-template-row');
            container.appendChild(row);
            bindRow(row);
            syncRowNames();
        });
    })();
</script>
@endsection
