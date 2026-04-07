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

                        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4">
                            <div class="mb-2">
                                <h5 class="mb-1">مكتبة القوالب حسب صفحة الفاتورة</h5>
                                <small class="text-muted">
                                    عرّف الشروط مرة واحدة، وحدد الصفحة التابعة لها، ثم تصبح هي الشروط المطبقة تلقائيًا عند إنشاء الفاتورة.
                                </small>
                            </div>
                            @can('employee.system_settings.edit')
                                <button type="button" class="btn btn-outline-primary btn-sm" id="open-invoice-term-modal">
                                    إضافة شروط جديدة
                                </button>
                            @endcan
                        </div>

                        <div class="row mb-4" id="invoice-terms-context-summary"></div>

                        <div id="invoice-terms-templates-list"></div>
                        <div id="invoice-terms-hidden-inputs"></div>

                        @can('employee.system_settings.edit')
                            <div class="text-center mt-4">
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

    @can('employee.system_settings.edit')
        <div class="modal fade" id="invoice-terms-template-modal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="invoice-terms-template-modal-title">إضافة شروط جديدة</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">×</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-danger d-none" id="invoice-terms-template-modal-error"></div>

                        <div class="form-group">
                            <label for="invoice-terms-template-context">الصفحة التابعة لها</label>
                            <select id="invoice-terms-template-context" class="form-control">
                                @foreach ($invoiceTermContexts as $context)
                                    <option value="{{ $context['key'] }}">{{ $context['title'] }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="invoice-terms-template-title">اسم الشروط</label>
                            <input type="text" id="invoice-terms-template-title" class="form-control" placeholder="مثال: شروط بيع مبسط">
                        </div>

                        <div class="form-group mb-3">
                            <label for="invoice-terms-template-content">نص الشروط</label>
                            <textarea id="invoice-terms-template-content" rows="7" class="form-control" placeholder="اكتب الشروط التي تريد تطبيقها تلقائيًا"></textarea>
                        </div>

                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="invoice-terms-template-default">
                            <label class="custom-control-label" for="invoice-terms-template-default">
                                اجعل هذه الشروط هي الافتراضية لهذه الصفحة
                            </label>
                        </div>

                        <div class="custom-control custom-checkbox mt-2">
                            <input type="checkbox" class="custom-control-input" id="invoice-terms-template-show-on-invoice" checked>
                            <label class="custom-control-label" for="invoice-terms-template-show-on-invoice">
                                إظهار هذه الشروط عند طباعة الفاتورة
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">إلغاء</button>
                        <button type="button" class="btn btn-primary" id="save-invoice-terms-template">حفظ</button>
                    </div>
                </div>
            </div>
        </div>
    @endcan
@endcan
@endsection

@section('js')
<script>
    (function () {
        const form = document.getElementById('invoice-terms-settings-form');
        const summaryContainer = document.getElementById('invoice-terms-context-summary');
        const templatesContainer = document.getElementById('invoice-terms-templates-list');
        const hiddenInputsContainer = document.getElementById('invoice-terms-hidden-inputs');
        const openModalButton = document.getElementById('open-invoice-term-modal');
        const modalElement = document.getElementById('invoice-terms-template-modal');
        const modalTitle = document.getElementById('invoice-terms-template-modal-title');
        const modalError = document.getElementById('invoice-terms-template-modal-error');
        const modalContext = document.getElementById('invoice-terms-template-context');
        const modalTemplateTitle = document.getElementById('invoice-terms-template-title');
        const modalTemplateContent = document.getElementById('invoice-terms-template-content');
        const modalTemplateDefault = document.getElementById('invoice-terms-template-default');
        const modalTemplateShowOnInvoice = document.getElementById('invoice-terms-template-show-on-invoice');
        const saveTemplateButton = document.getElementById('save-invoice-terms-template');
        const contexts = @json($invoiceTermContexts);
        const canEdit = @json(auth('admin-web')->user()?->can('employee.system_settings.edit'));
        let templates = @json(array_values(old('templates', $invoiceTermTemplates)));
        let defaultTemplateKeys = @json(old('default_template_keys', $defaultInvoiceTermsTemplateKeys));
        let editingIndex = null;

        if (!form || !summaryContainer || !templatesContainer || !hiddenInputsContainer) {
            return;
        }

        templates = Array.isArray(templates) ? templates : [];
        defaultTemplateKeys = defaultTemplateKeys && typeof defaultTemplateKeys === 'object' ? defaultTemplateKeys : {};
        templates = templates.map(function (template) {
            const showOnInvoice = !(template.show_on_invoice === false
                || template.show_on_invoice === 0
                || template.show_on_invoice === '0');

            return Object.assign({
                show_on_invoice: true,
            }, template, {
                show_on_invoice: showOnInvoice,
            });
        });

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function nl2br(value) {
            return escapeHtml(value).replace(/\n/g, '<br>');
        }

        function escapeAttribute(value) {
            return escapeHtml(value).replace(/\n/g, '&#10;');
        }

        function normalizeKey(value) {
            return String(value || '')
                .toLowerCase()
                .trim()
                .replace(/[^a-z0-9\-_]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function findContextTitle(contextKey) {
            const context = contexts.find(function (item) {
                return item.key === contextKey;
            });

            return context ? context.title : contextKey;
        }

        function templatesForContext(contextKey) {
            return templates.filter(function (template) {
                return template.context === contextKey;
            });
        }

        function uniqueKey(title, contextKey, currentIndex) {
            const baseKey = normalizeKey(title) || (contextKey + '-terms');
            let candidate = baseKey;
            let suffix = 2;

            while (templates.some(function (template, index) {
                return index !== currentIndex
                    && template.context === contextKey
                    && template.key === candidate;
            })) {
                candidate = baseKey + '-' + suffix;
                suffix += 1;
            }

            return candidate;
        }

        function ensureDefaultKeys() {
            contexts.forEach(function (context) {
                const scopedTemplates = templatesForContext(context.key);
                const existingDefault = defaultTemplateKeys[context.key];

                if (scopedTemplates.length === 0) {
                    delete defaultTemplateKeys[context.key];
                    return;
                }

                const hasValidDefault = scopedTemplates.some(function (template) {
                    return template.key === existingDefault;
                });

                if (!hasValidDefault) {
                    defaultTemplateKeys[context.key] = scopedTemplates[0].key;
                }
            });
        }

        function renderSummary() {
            summaryContainer.innerHTML = contexts.map(function (context) {
                const defaultKey = defaultTemplateKeys[context.key];
                const defaultTemplate = templatesForContext(context.key).find(function (template) {
                    return template.key === defaultKey;
                });
                const printStatus = defaultTemplate && defaultTemplate.show_on_invoice
                    ? '<span class="badge badge-info ml-2">تظهر في الطباعة</span>'
                    : '<span class="badge badge-secondary ml-2">مخفية في الطباعة</span>';

                return `
                    <div class="col-lg-4 col-md-6 mb-3">
                        <div class="card border h-100 shadow-sm">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <h6 class="mb-0">${escapeHtml(context.title)}</h6>
                                    <div>
                                        <span class="badge badge-success">تلقائي</span>
                                        ${defaultTemplate ? printStatus : ''}
                                    </div>
                                </div>
                                <div class="text-muted small mb-2">
                                    ${defaultTemplate ? escapeHtml(defaultTemplate.title) : 'لا توجد شروط معرفة'}
                                </div>
                                <div class="small" style="white-space: pre-line;">
                                    ${defaultTemplate ? nl2br(defaultTemplate.content) : 'أضف شروطًا لهذه الصفحة لتصبح افتراضية تلقائيًا.'}
                                </div>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderTemplates() {
            if (templates.length === 0) {
                templatesContainer.innerHTML = `
                    <div class="alert alert-warning mb-0">
                        لا توجد قوالب معرفة حتى الآن.
                    </div>
                `;
                return;
            }

            templatesContainer.innerHTML = templates.map(function (template, index) {
                const isDefault = defaultTemplateKeys[template.context] === template.key;
                const actionButtons = canEdit ? `
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" data-action="edit" data-index="${index}">تعديل</button>
                        <button type="button" class="btn btn-outline-success" data-action="default" data-index="${index}">افتراضي</button>
                        <button type="button" class="btn btn-outline-danger" data-action="remove" data-index="${index}">حذف</button>
                    </div>
                ` : '';

                return `
                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start mb-3">
                                <div class="mb-2">
                                    <div class="d-flex flex-wrap align-items-center mb-2">
                                        <h5 class="mb-0 mr-2">${escapeHtml(template.title)}</h5>
                                        <span class="badge badge-light">${escapeHtml(findContextTitle(template.context))}</span>
                                        ${isDefault ? '<span class="badge badge-success ml-2">المعتمد تلقائيًا</span>' : ''}
                                        ${template.show_on_invoice ? '<span class="badge badge-info ml-2">يظهر في الطباعة</span>' : '<span class="badge badge-secondary ml-2">مخفي من الطباعة</span>'}
                                    </div>
                                    <small class="text-muted">المعرّف: ${escapeHtml(template.key)}</small>
                                </div>
                                ${actionButtons}
                            </div>
                            <div class="border rounded p-3 bg-light" style="white-space: pre-line;">
                                ${nl2br(template.content)}
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderHiddenInputs() {
            const hiddenInputs = [];

            templates.forEach(function (template, index) {
                hiddenInputs.push(`<input type="hidden" name="templates[${index}][key]" value="${escapeAttribute(template.key)}">`);
                hiddenInputs.push(`<input type="hidden" name="templates[${index}][title]" value="${escapeAttribute(template.title)}">`);
                hiddenInputs.push(`<input type="hidden" name="templates[${index}][content]" value="${escapeAttribute(template.content)}">`);
                hiddenInputs.push(`<input type="hidden" name="templates[${index}][context]" value="${escapeAttribute(template.context)}">`);
                hiddenInputs.push(`<input type="hidden" name="templates[${index}][show_on_invoice]" value="${template.show_on_invoice ? '1' : '0'}">`);
            });

            Object.keys(defaultTemplateKeys).forEach(function (contextKey) {
                hiddenInputs.push(`<input type="hidden" name="default_template_keys[${contextKey}]" value="${escapeAttribute(defaultTemplateKeys[contextKey])}">`);
            });

            hiddenInputsContainer.innerHTML = hiddenInputs.join('');
        }

        function renderAll() {
            ensureDefaultKeys();
            renderSummary();
            renderTemplates();
            renderHiddenInputs();
        }

        function resetModalError() {
            if (!modalError) {
                return;
            }

            modalError.textContent = '';
            modalError.classList.add('d-none');
        }

        function showModalError(message) {
            if (!modalError) {
                return;
            }

            modalError.textContent = message;
            modalError.classList.remove('d-none');
        }

        function openModal(index) {
            if (!modalElement || !canEdit) {
                return;
            }

            editingIndex = typeof index === 'number' ? index : null;
            resetModalError();

            if (editingIndex === null) {
                modalTitle.textContent = 'إضافة شروط جديدة';
                modalContext.value = contexts[0] ? contexts[0].key : '';
                modalTemplateTitle.value = '';
                modalTemplateContent.value = '';
                modalTemplateDefault.checked = true;
                if (modalTemplateShowOnInvoice) {
                    modalTemplateShowOnInvoice.checked = true;
                }
            } else {
                const template = templates[editingIndex];
                modalTitle.textContent = 'تعديل الشروط';
                modalContext.value = template.context;
                modalTemplateTitle.value = template.title;
                modalTemplateContent.value = template.content;
                modalTemplateDefault.checked = defaultTemplateKeys[template.context] === template.key;
                if (modalTemplateShowOnInvoice) {
                    modalTemplateShowOnInvoice.checked = template.show_on_invoice !== false;
                }
            }

            window.jQuery(modalElement).modal('show');
        }

        function saveTemplate() {
            const contextKey = modalContext ? modalContext.value : '';
            const title = modalTemplateTitle ? modalTemplateTitle.value.trim() : '';
            const content = modalTemplateContent ? modalTemplateContent.value.trim() : '';
            const makeDefault = modalTemplateDefault ? modalTemplateDefault.checked : false;
            const showOnInvoice = modalTemplateShowOnInvoice ? modalTemplateShowOnInvoice.checked : true;

            resetModalError();

            if (!contextKey) {
                showModalError('اختر الصفحة التي ستطبق عليها هذه الشروط.');
                return;
            }

            if (title === '') {
                showModalError('اسم الشروط مطلوب.');
                return;
            }

            if (content === '') {
                showModalError('نص الشروط مطلوب.');
                return;
            }

            if (editingIndex === null) {
                const newTemplate = {
                    key: uniqueKey(title, contextKey),
                    title: title,
                    content: content,
                    context: contextKey,
                    show_on_invoice: showOnInvoice,
                };

                templates.push(newTemplate);

                if (makeDefault || !defaultTemplateKeys[contextKey]) {
                    defaultTemplateKeys[contextKey] = newTemplate.key;
                }
            } else {
                const existingTemplate = templates[editingIndex];
                const previousContext = existingTemplate.context;
                const previousKey = existingTemplate.key;
                const nextKey = previousContext === contextKey
                    ? previousKey
                    : uniqueKey(title, contextKey, editingIndex);

                templates[editingIndex] = {
                    key: nextKey,
                    title: title,
                    content: content,
                    context: contextKey,
                    show_on_invoice: showOnInvoice,
                };

                if (defaultTemplateKeys[previousContext] === previousKey) {
                    delete defaultTemplateKeys[previousContext];
                }

                if (makeDefault || !defaultTemplateKeys[contextKey]) {
                    defaultTemplateKeys[contextKey] = nextKey;
                }
            }

            renderAll();
            window.jQuery(modalElement).modal('hide');
        }

        templatesContainer.addEventListener('click', function (event) {
            const button = event.target.closest('button[data-action]');

            if (!button || !canEdit) {
                return;
            }

            const index = Number(button.getAttribute('data-index'));
            const action = button.getAttribute('data-action');
            const template = templates[index];

            if (!template) {
                return;
            }

            if (action === 'edit') {
                openModal(index);
                return;
            }

            if (action === 'default') {
                defaultTemplateKeys[template.context] = template.key;
                renderAll();
                return;
            }

            if (action === 'remove') {
                if (!window.confirm('سيتم حذف هذه الشروط من المكتبة. هل تريد المتابعة؟')) {
                    return;
                }

                const wasDefault = defaultTemplateKeys[template.context] === template.key;
                templates.splice(index, 1);

                if (wasDefault) {
                    delete defaultTemplateKeys[template.context];
                }

                renderAll();
            }
        });

        openModalButton?.addEventListener('click', function () {
            openModal(null);
        });

        saveTemplateButton?.addEventListener('click', saveTemplate);

        modalElement?.addEventListener('hidden.bs.modal', function () {
            resetModalError();
        });

        renderAll();
    })();
</script>
@endsection
