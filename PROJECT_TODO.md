# خطة تنفيذ برنامج الذهب - Checklist تقنية قابلة للتحديث

Date: 2026-03-22
Status: Completed
Execution Mode: Active Implementation

هذا الملف هو مرجع التخطيط والتنفيذ الفعلي لنقاط برنامج الذهب. بعد صدور أمر البدء، أصبح الملف يستخدم لمتابعة ما نُفذ فعليًا وما يزال مفتوحًا.

## طريقة العمل

- كل نقطة تبدأ بحالة `[ ]`.
- لا تتحول النقطة إلى `[x]` إلا بعد اكتمال `DB + API/Service + UI/Print + Permission + Verification`.
- إذا تم جزء فقط من العمل تبقى النقطة `[ ]` ويضاف تحتها سطر `Progress (YYYY-MM-DD): ...`.
- عند الإنجاز يضاف تحت النقطة:
  - `Files:` الملفات المعدلة
  - `DB:` المايغريشن أو تعديل البيانات
  - `Verification:` كيف تم التحقق
- إذا كانت النقطة قابلة لإعادة البناء مباشرة أو جزئيًا داخل مشروع الذهب يكتب نوع الاستفادة تحت `Reuse`.
- ملاحظة إلزامية: كل تعديل فعلي داخل البرنامج يجب أن يصاحبه اختبار آلي، والأولوية لاختبارات `Feature` و`Integration` التي تحاكي المستخدم الحقيقي من تسجيل الدخول حتى حفظ البيانات واسترجاعها وعرضها.
- لا تعتبر أي شريحة مستقرة قبل التحقق من `data flow` الكامل: إدخال البيانات، حفظها، استرجاعها، ظهورها في الشاشة المناسبة، ومنع الوصول غير المصرح به عند الحاجة.

## معنى Reuse

- `Reuse: مباشر` يعني أن الفكرة واضحة ويمكن بناؤها داخل مشروع الذهب مباشرة من الوصف الموجود هنا.
- `Reuse: جزئي` يعني أن الفكرة واضحة جزئيًا لكنها تحتاج قرار تصميم أو تفصيل إضافي أثناء التنفيذ.
- `Reuse: جديد` يعني أن النقطة خاصة ببرنامج الذهب وتحتاج تصميمًا كاملاً قبل البدء في التنفيذ.

## مرجعية التخطيط

- هذه الخطة لا تعتمد على الوصول إلى أي برنامج محاسبي خارجي أو إلى ملفاته.
- أي منطق وارد هنا هو توصيف وظيفي عام فقط، وليس إحالة إلى كود خارجي يجب نسخه.
- المطلوب لاحقًا هو تنفيذ هذه النقاط داخل مشروع الذهب نفسه، بالاعتماد على الوصف الموجود في هذا الملف وعلى ما يتوفر داخل مشروع الذهب فقط.
- عند البدء الفعلي بالتنفيذ، تكون الأولوية لتوسيع الموجود داخل مشروع الذهب بدل افتراض وجود وحدات جاهزة خارجية.

## ملاحظات تصميم داخل مشروع الذهب

- لا يتم نقل أسماء الجداول أو الكلاسات حرفيًا إذا كان مشروع الذهب لديه أسماء Domain جاهزة مثل `Item`, `Invoice`, `GoldPrice`, `GoldCarat`.
- المطلوب تنفيذ السلوك والهيكل الفني داخل مشروع الذهب، لا بناء الخطة على كود خارجي غير متاح.
- إذا كان مشروع الذهب يملك جداول للعيارات والأسعار والمخزون الذهبي أصلًا، فالأولوية تكون لتوسيع الموجود بدل إنشاء جداول بديلة.

## نتائج مراجعة مرجعية

- مراجعة منطق backend المرجعي أكدت أن `عدة مستخدمين لفرع واحد` نمط سهل ومباشر، أما `مستخدم واحد لعدة فروع` فيحتاج تصميمًا مستقلًا مع مفهوم `default_branch` أو `current_branch` ولا يعامل كامتداد بسيط.
- مراجعة منطق الأصناف دعمت نمط `كتالوج أصناف مركزي + مخزون/سعر/توفر حسب الفرع أو المخزن` أكثر من نمط الأصناف المحلية الخالصة لكل فرع من أول إصدار.
- مراجعة منطق الدفع دعمت تنفيذ `multiple payment lines` من البداية، لكنها لم تثبت وجود إدارة قوية متعددة للحسابات البنكية على مستوى كل بنك/جهاز، لذلك هذا القرار يجب أن يكون صريحًا في خطة برنامج الذهب.

## الإدارة والفروع والمستخدمون

- [x] **GOLD-ADM-01 | دعم أكثر من فرع مع استقلالية البيانات والرقم الضريبي لكل فرع**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب وجود كيان فروع يحمل بيانات ضريبية وبيانات طباعة مستقلة، ويكون مرجعًا واضحًا لكل العمليات.
  - `Gold implementation:` جعل `branches` مرجعًا إلزاميًا لكل الفواتير والحركات والتقارير والقيود والمخزون. كل جدول تشغيلي في الذهب يجب أن يحمل `branch_id`. عند تبديل الفرع تتبدل الصلاحيات والبيانات والطباعة والتقارير تلقائيًا.
  - `Done when:` كل فرع يطبع رقمه الضريبي وبياناته الخاصة وتظهر بياناته فقط للمستخدم ضمن الفرع النشط.
  - `Files:` `app/Services/Branches/BranchAccessService.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `app/Http/Controllers/Admin/FinancialVoucherController.php`, `resources/views/admin/purchases/create.blade.php`, `resources/views/admin/financial_vouchers/index.blade.php`, و`tests/Feature/BranchDataIsolationFeatureTest.php`.
  - `DB:` لا توجد migration جديدة في هذه الشريحة؛ تم تثبيت العزل على البنية الحالية المعتمدة أصلًا على `branch_id` داخل الجداول التشغيلية، مع إبقاء `GOLD-ADM-10` مفتوحًا لتعدد الفروع لنفس المستخدم لاحقًا.
  - `Verification:` تم تنفيذ اختبارات `Feature` تحاكي مستخدم فرع فعليًا وتتحقق من أن قوائم المبيعات والمشتريات لا تعرض إلا بيانات فرعه، وأنه لا يستطيع فتح فواتير أو مرتجعات أو أسطر دفع أو سندات تخص فرعًا آخر عبر الرابط المباشر أو طلبات `AJAX`، ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-02 | إضافة أكثر من مستخدم مع التحكم بالتعطيل وتغيير كلمة المرور**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب إدارة مستخدمين بحالة نشط/موقوف، مع تغيير كلمة المرور وربط المستخدم بدور وصلاحيات.
  - `Gold implementation:` نقل نفس منطق الإنشاء والتعديل والتعطيل، مع إضافة Audit Log مختصر لعمليات التعطيل وإعادة تعيين كلمة المرور.
  - `Done when:` يمكن إنشاء المستخدم وتعطيله وإعادة تفعيلِه وتغيير كلمة مروره من شاشة الإدارة.
  - `Files:` `app/Http/Controllers/Admin/UsersController.php`, `app/Models/User.php`, `app/Models/UserAuditLog.php`, `database/migrations/2026_03_22_000012_create_user_audit_logs_table.php`, `resources/views/admin/users/index.blade.php`, `resources/views/admin/users/edit.blade.php`, `resources/views/admin/users/show.blade.php`, و`tests/Feature/AdminAccessTest.php`.
  - `DB:` تمت إضافة جدول `user_audit_logs` لحفظ `actor_user_id`, `target_user_id`, `event_key`, و`old_values/new_values` بصيغة JSON مع ربطها بالمستخدمين.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي إنشاء المستخدم، منعه بدون صلاحية، تعديل حالته وكلمة مروره، كتابة سجلات `Audit Log`، وظهورها في شاشة عرض المستخدم، ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-03 | تشغيل المستخدم على أكثر من جهاز أو منعه بجهاز واحد فقط**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب إعداد تحكم يحدد هل الدخول متعدد الأجهزة أو محصورًا بجهاز واحد مع إنهاء الجلسة السابقة.
  - `Gold implementation:` إضافة إعداد `login_mode = multi_device | single_device` وربطه بالـ session/token عند تسجيل الدخول.
  - `Done when:` عند تفعيل وضع الجهاز الواحد يتم إنهاء الجلسة/التوكن السابق تلقائيًا، وعند تعطيله يسمح بتسجيلات متزامنة.
  - `Files:` `app/Services/Auth/LoginModeService.php`, `app/Http/Middleware/EnsureValidAdminSession.php`, `app/Http/Controllers/Admin/Auth/LoginController.php`, `app/Http/Controllers/Admin/SystemSettingController.php`, `app/Http/Controllers/Api/AuthController.php`, `routes/api.php`, `resources/views/admin/settings/login_mode.blade.php`, `tests/Feature/AdminAccessTest.php`, و`tests/Feature/ApiLoginModeFeatureTest.php`.
  - `DB:` تم استخدام `system_settings` لتخزين المفتاح `login_mode`، مع الاعتماد على الحقل `users.active_session_id` لتتبع المرجع النشط سواء كان `web:session-id` أو `api:token-id`.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي الجلسات الإدارية على الويب في وضعي `single_device` و`multi_device`، ثم توسيع التغطية لمسار `API/Sanctum` للتأكد من حذف التوكن السابق عند الجهاز الواحد، والسماح بتعدد التوكنات عند الوضع المتعدد، ومنع المستخدم الموقوف من تسجيل الدخول عبر الـ API، ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-04 | تحسين شكل الشعار وجعله أكبر**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب أن يكون للشعار مصدر إعداد واحد مع استخدام موحد في التطبيق والطباعة.
  - `Gold implementation:` توحيد مصدر الشعار وحجمه في إعدادات واحدة بدل أحجام ثابتة مبعثرة في الشاشات. يفضل فصل حجم شعار التطبيق عن حجم شعار الطباعة.
  - `Done when:` يظهر الشعار بحجم مناسب في الهيدر وتسجيل الدخول والطباعة بدون تشويه أو قص.
  - `Files:` `app/Services/Branding/BrandLogoService.php`, `app/Providers/AppServiceProvider.php`, `app/Http/Controllers/Admin/SystemSettingController.php`, `resources/views/admin/settings/branding.blade.php`, `resources/views/admin/auth/login.blade.php`, `resources/views/admin/layouts/main-header.blade.php`, `resources/views/admin/layouts/main-sidebar.blade.php`, `resources/views/admin/layouts/head.blade.php`, `resources/views/admin/lockscreen.blade.php`, وقوالب الطباعة والتقارير التي تعرض الشعار.
  - `DB:` لا توجد migration جديدة؛ تم استخدام `system_settings` القائم لتخزين المفتاح `brand_logo_path` مع حفظ الملف فعليًا داخل `storage/app/public/branding`.
  - `Verification:` تم تنفيذ اختبارات `Feature` لرفع شعار فعلي عبر `Storage::fake('public')` والتحقق من ظهوره في شاشة الإعدادات وصفحة الدخول والداشبورد، بالإضافة إلى منع التحديث بدون صلاحية. كما تم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-05 | تسلسل فواتير مستقل لكل مستخدم**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب عداد مستندات مستقل قابل للفصل حسب المستخدم ونوع المستند والفرع وربما السنة المالية.
  - `Gold implementation:` مفتاح العداد يجب أن يكون على الأقل `user_id + branch_id + doc_type`، ويمكن إضافة `fiscal_year` إذا كان مطلوبًا محاسبيًا.
  - `Done when:` كل مستخدم يحصل على تسلسل خاص به لا يتعارض مع مستخدم آخر داخل نفس نوع المستند.
  - `Files:` `app/Services/Invoices/InvoiceNumberService.php`, `app/Models/Invoice.php`, `database/migrations/2026_03_22_000003_create_invoice_counters_table.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, و`tests/Feature/InvoiceNumberingTest.php`.
  - `DB:` تم إنشاء جدول `invoice_counters` مع مفاتيح فصل على `user_id + branch_id + type` لتتبع آخر رقم مستخدم لكل نوع مستند بشكل مستقل.
  - `Verification:` تم تنفيذ اختبارات `Feature` على مستويين: مستوى مباشر على الموديل للتحقق من الفصل بين المستخدمين والفروع والأنواع، ثم مستوى end-to-end على مسارات التشغيل الفعلية `sales.store`, `purchases.store`, و`sales_return.store` مع مستخدمين مختلفين داخل نفس الفرع للتحقق من أن العدادات لا تتقاطع وأن `bill_number` يظهر في طباعة البيع والشراء والمرتجع. بعد ذلك شُغّل السويت الكامل وكانت النتيجة نظيفة بالكامل.

- [x] **GOLD-ADM-06 | تحسين نظام الصلاحيات وترتيبها حسب المستخدم**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب نظام صلاحيات مرن مبني على موديولات وأدوار وصلاحيات قابلة للبحث والتخصيص.
  - `Gold implementation:` ترتيب الصلاحيات حسب الموديول: المبيعات، المشتريات، المخزون، الأسعار، الفروع، المستخدمون، المحاسبة، التقارير، الطباعة. مع دعم صلاحيات مباشرة للمستخدم فوق الدور إذا لزم.
  - `Done when:` تصبح شاشة الصلاحيات مرتبة وقابلة للبحث ويظهر تأثير كل صلاحية على الشاشات والعمليات بوضوح.
  - `Files:` `app/Services/Permissions/PermissionMatrixService.php`, `app/Http/Controllers/Admin/RolesController.php`, `app/Http/Controllers/Admin/UsersController.php`, `app/Models/UserAuditLog.php`, `resources/views/admin/roles/partials/permissions-matrix.blade.php`, `resources/views/admin/users/create.blade.php`, `resources/views/admin/users/edit.blade.php`, `resources/views/admin/users/show.blade.php`, `tests/Feature/RolePermissionUiTest.php`, و`tests/Feature/UserDirectPermissionsFeatureTest.php`.
  - `DB:` لا توجد migration جديدة في هذه الشريحة؛ تم الاعتماد على جداول الصلاحيات الحالية من `spatie/laravel-permission` مع توسيع الاستخدام ليشمل الصلاحيات المباشرة على المستخدم بالإضافة إلى صلاحيات الدور.
  - `Verification:` تم أولًا إغلاق الوصول غير المصرح به على إدارة المستخدمين عبر Middleware، ثم إعادة بناء شاشة إنشاء/تعديل الأدوار حول مجموعات صلاحيات مرتبة حسب الموديول مع بحث وتحديد شامل. بعد ذلك أضيفت صلاحيات مباشرة للمستخدم فوق الدور في شاشتي الإنشاء والتعديل، مع عرض منفصل للصلاحيات المباشرة وصلاحيات الدور والصلاحيات الفعلية في شاشة المستخدم، وتسجيل أي تغيير عليها في `UserAuditLog`. التغطية تشمل اختبارات `Feature` لشاشة الأدوار، وإنشاء مستخدم بصلاحيات مباشرة، وتعديل هذه الصلاحيات ومزامنتها وعرضها وتسجيلها في السجل، ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-07 | إضافة خانة الشروط في الفاتورة**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب دعم قوالب شروط مع شرط افتراضي وإمكانية التعديل على مستوى كل فاتورة.
  - `Gold implementation:` دعم قوالب شروط + شرط افتراضي + تعديل خاص بكل فاتورة مع حفظ Snapshot داخل الفاتورة نفسها حتى لا تتغير الطباعة التاريخية.
  - `Done when:` تظهر الشروط في المعاينة والطباعة ويمكن تغييرها افتراضيًا أو على مستوى الفاتورة.
  - `Files:` `app/Services/Invoices/InvoiceTermsService.php`, `app/Http/Controllers/Admin/SystemSettingController.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `resources/views/admin/settings/invoice_terms.blade.php`, `resources/views/admin/sales/create.blade.php`, `resources/views/admin/purchases/create.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `tests/Feature/AdminAccessTest.php`, و`tests/Feature/InvoiceTermsFeatureTest.php`.
  - `DB:` لا توجد migration جديدة في هذه الدفعة؛ تم استخدام `system_settings` لتخزين `default_invoice_terms`, `invoice_terms_templates`, و`default_invoice_terms_template_key`, مع استمرار حفظ Snapshot الفاتورة داخل `invoices.invoice_terms`.
  - `Verification:` تم تطوير شاشة الإعدادات من نص افتراضي واحد إلى مكتبة قوالب شروط مع تحديد قالب افتراضي، وربط هذه القوالب مباشرة بشاشتي البيع والشراء عبر selector يملأ حقل الشروط قبل الحفظ. كما بقيت الفاتورة تحتفظ بنسختها التاريخية داخل `invoice_terms` بحيث لا تتأثر بتغيير القالب لاحقًا. أضيفت اختبارات `Feature` لتحديث الإعدادات، وظهور القوالب في شاشات الإنشاء، وثبات النص في الطباعة بعد تغيير الإعدادات، ثم شُغّل السويت كاملًا بعد التنفيذ.

- [x] **GOLD-ADM-08 | دعم أكثر من حجم فاتورة A4 و A5 مع الرأس والتذييل أو بدونهما**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب محرك طباعة يدعم أكثر من مقاس مع تشغيل أو إخفاء الرأس والتذييل.
  - `Gold implementation:` جعل محرك الطباعة موحدًا بمدخلات `format`, `show_header`, `show_footer`, `template`, `branch_id`.
  - `Done when:` نفس الفاتورة تطبع A4 وA5 مع أو بدون رأس/تذييل دون كسر التنسيق أو فقد البيانات.
  - `Files:` `app/Services/Invoices/InvoicePrintSettingsService.php`, `app/Http/Controllers/Admin/SystemSettingController.php`, `resources/views/admin/settings/invoice_print.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `tests/Feature/AdminAccessTest.php`, و`tests/Feature/InvoicePrintSettingsFeatureTest.php`.
  - `DB:` لا توجد migration جديدة؛ تم استخدام `system_settings` لتخزين `invoice_print_format`, `invoice_print_show_header`, `invoice_print_show_footer`, و`invoice_print_template`.
  - `Verification:` تم الإبقاء على دعم `A4/A5` والرأس/التذييل، مع إضافة قوالب طباعة متعددة (`classic`, `compact`, `modern`) داخل الإعدادات وربطها مباشرة بصفحات طباعة البيع والشراء. النتيجة الآن أن نفس الفاتورة يمكن أن تطبع بمقاس مختلف، وبقالب بصري مختلف، ومع أو بدون رأس/تذييل من دون تغيير البيانات نفسها. تم تغطية ذلك باختبارات `Feature` تتحقق من تخزين الإعدادات، وظهور `data-print-format` و`data-print-template` ووسوم الرأس/التذييل حسب الإعداد، ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-ADM-09 | ربط عدة مستخدمين بفرع معين**
  - `Reuse: مباشر`
  - `Planning basis:` هذا هو النمط الأبسط والأقرب للتنفيذ من أول إصدار: كل مستخدم يملك `branch_id` واحدًا، ويمكن لأي عدد من المستخدمين الانتماء إلى نفس الفرع.
  - `Gold implementation:` في المرحلة الأولى يكفي دعم علاقة `one branch -> many users` عبر ربط المستخدم بفرع عمل واحد، مع إظهار مستخدمي الفرع من شاشة الفرع والإدارة.
  - `Done when:` شاشة الفرع تعرض جميع مستخدميه وتسمح بإدارة الربط منهم وإليهم.
  - `Files:` `app/Models/Branch.php`, `app/Http/Controllers/Admin/BranchController.php`, `app/Http/Controllers/Admin/UsersController.php`, `resources/views/admin/branches/index.blade.php`, `resources/views/admin/branches/show.blade.php`, `resources/views/admin/users/create.blade.php`, `resources/views/admin/users/edit.blade.php`, و`tests/Feature/AdminAccessTest.php`.
  - `DB:` لا توجد migration جديدة؛ تم الاعتماد على العلاقة الحالية `users.branch_id -> branches.id` وتوسيع واجهات الإدارة حولها.
  - `Verification:` تم تفعيل علاقة `Branch -> users` وإظهار عدد المستخدمين في قائمة الفروع وجدولهم داخل شاشة الفرع، ثم إضافة إدارة فعلية من نفس الشاشة عبر زر `إضافة مستخدم لهذا الفرع` وروابط `عرض/تعديل` للمستخدمين المرتبطين مع رجوع تلقائي إلى شاشة الفرع بعد الحفظ أو التعديل. تم تغطية هذا السلوك باختبارات `Feature` تتحقق من ظهور الروابط، وفتح نموذج الإنشاء بفرع محدد مسبقًا، والرجوع إلى شاشة الفرع بعد `store/update`, ثم تشغيل اختبارات الشريحة بنجاح قبل دمجها في السويت الكامل.

- [x] **GOLD-ADM-10 | ربط مستخدم واحد بعدة فروع**
  - `Reuse: جديد`
  - `Planning basis:` هذا ليس امتدادًا بسيطًا للبند السابق، بل إعادة تصميم جزئية لمنطق الفرع النشط والصلاحيات والتصفية والتقارير.
  - `Gold implementation:` عند اعتماد هذه الميزة يتم إنشاء Pivot مثل `branch_user` مع حقول `user_id`, `branch_id`, `is_default`, `is_active`، مع إضافة مفهوم `current_branch_id` في الجلسة أو التوكن أو على المستخدم. يبقى `users.branch_id` كفرع افتراضي مرحليًا فقط إذا احتجنا تقليل كسر المنطق القائم.
  - `Done when:` يستطيع المستخدم التبديل بين فروعه المسموح بها بعد تسجيل الدخول دون إنشاء حسابات مكررة، وتصبح جميع الفلاتر والتقارير والعمليات مبنية على `current_branch` لا على فرع ثابت واحد.
  - `Files:` `database/migrations/2026_03_23_000020_create_branch_user_table.php`, `app/Services/Branches/BranchContextService.php`, `app/Http/Middleware/ApplyCurrentAdminBranch.php`, `app/Http/Controllers/Admin/CurrentBranchController.php`, `app/Http/Controllers/Admin/UsersController.php`, `app/Http/Controllers/Admin/Auth/LoginController.php`, `app/Http/Controllers/Admin/BranchController.php`, `app/Models/User.php`, `app/Models/Branch.php`, `app/Models/UserAuditLog.php`, `resources/views/admin/users/create.blade.php`, `resources/views/admin/users/edit.blade.php`, `resources/views/admin/users/show.blade.php`, `resources/views/admin/layouts/main-header.blade.php`, `resources/views/admin/branches/show.blade.php`, `app/Http/Kernel.php`, `routes/web.php`, و`tests/Feature/UserMultiBranchFeatureTest.php`.
  - `DB:` تم إنشاء Pivot باسم `branch_user` مع `user_id`, `branch_id`, `is_default`, `is_active` وBackfill للمستخدمين الحاليين. استمر `users.branch_id` كفرع افتراضي متوافق مع البنية السابقة، بينما صار الفرع النشط أثناء الطلب يُدار من الجلسة عبر `current_branch_id`.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي إنشاء مستخدم متعدد الفروع، تحديث الفروع المسموح بها والفرع الافتراضي، ظهور الفروع في شاشة المستخدم وسجل التعديلات، ثم اختبار عملي لتبديل `current_branch` من الواجهة والتأكد من أن شاشات وقوائم المبيعات تتبع الفرع النشط الجديد فورًا، مع منع التبديل إلى فرع غير مصرح به. بعد ذلك تم تشغيل السويت الكامل بنجاح.

## الأصناف والمنتجات

- [x] **GOLD-INV-01 | إضافة قسم للمقتنيات الثمينة وقسم للفضة**
  - `Reuse: جديد`
  - `Planning basis:` المطلوب تصنيف مخزني واضح يميز الذهب والمقتنيات والفضة.
  - `Gold implementation:` إضافة `inventory_classification` موحد بقيم مثل `gold`, `collectible`, `silver` بدل إنشاء جداول متوازية بلا داع.
  - `Done when:` كل صنف يحدد نوعه ويظهر في القوائم والتقارير والفلاتر حسب التصنيف.
  - `Files:` `database/migrations/2026_03_22_000014_add_inventory_classification_to_items_table.php`, `app/Models/Item.php`, `app/Http/Controllers/Admin/ItemController.php`, `app/Http/Controllers/Admin/ItemsReportsController.php`, `resources/views/admin/items/form.blade.php`, `resources/views/admin/items/index.blade.php`, `resources/views/admin/reports/items/search.blade.php`, `resources/views/admin/reports/items/index.blade.php`, `resources/views/admin/reports/sold_items/search.blade.php`, `resources/views/admin/reports/sold_items/index.blade.php`, `tests/Feature/ItemClassificationFeatureTest.php`, `tests/Feature/ItemListReportFeatureTest.php`, و`tests/Feature/SoldItemsReportFiltersFeatureTest.php`.
  - `DB:` تمت إضافة الحقل `items.inventory_classification` بقيم تشغيلية موحدة `gold / collectible / silver` مع قيمة افتراضية `gold` لضمان ترحيل السجلات الحالية دون كسر.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي ظهور التصنيف في شاشة إنشاء الصنف، حفظ صنف غير ذهبي بدون عيار، منع الصنف الذهبي بدون عيار، وفلترة تقريري `قائمة الأصناف` و`الأصناف المباعة` بالتصنيف الجديد، ثم تشغيل السويت الكامل وكانت النتيجة نظيفة.

- [x] **GOLD-INV-02 | إضافة الأصناف لكل فرع بشكل مستقل مع خيار توحيدها من الأدمن**
  - `Reuse: جزئي`
  - `Planning basis:` الاتجاه الموصى به من أول إصدار هو `كتالوج أصناف مركزي` مع إدارة `التوفر/السعر/المخزون/الباركود` على مستوى الفرع أو المخزن، بدل البدء بأصناف محلية خالصة لكل فرع.
  - `Gold implementation:` يعتمد التصميم الأساسي على جدول أصناف رئيسي، وجدول ربط مثل `branch_items` أو `branch_products` يحمل `branch_id`, `item_id`, `status`, `price`, `barcode`, `visibility`. الأدمن ينشئ الصنف مرة واحدة ثم ينشره أو يفعله على فروع محددة. إذا طُلب لاحقًا دعم أصناف محلية خالصة لفرع معيّن، تضاف كمرحلة ثانية.
  - `Done when:` يمكن إنشاء صنف مركزي ثم نشره على فرع واحد أو عدة فروع مع بقاء المخزون والسعر والتوفر محليًا على مستوى الفرع، دون الحاجة في المرحلة الأولى إلى أصناف محلية مستقلة بالكامل.
  - `Files:` `database/migrations/2026_03_22_000017_create_branch_items_table.php`, `app/Models/BranchItem.php`, `app/Models/Item.php`, `app/Models/Branch.php`, `app/Http/Controllers/Admin/ItemController.php`, `app/Http/Controllers/Admin/ItemsReportsController.php`, `resources/views/admin/items/form.blade.php`, `resources/views/admin/items/index.blade.php`, `resources/views/admin/reports/items/index.blade.php`, `tests/Feature/ItemBranchPublicationFeatureTest.php`, و`tests/Feature/ItemListReportFeatureTest.php`.
  - `DB:` تمت إضافة جدول `branch_items` كطبقة نشر فوق جدول `items` الحالي، مع Backfill تلقائي لكل الأصناف الموجودة إلى فرعها المالك الحالي، وحقول `is_active`, `is_visible`, `sale_price_per_gram`, و`published_by_user_id`. بقي `items.branch_id` كفرع مالك/مرجعي لتقليل كسر المنطق القديم، بينما أصبحت الإتاحة التشغيلية تعتمد على `branch_items`.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي إنشاء الصنف مرة واحدة ونشره على عدة فروع، حفظ سعر بيع محلي لفرع محدد، ظهور الصنف في بحث البيع فقط عند الفروع المنشور عليها، اختفاؤه عن الفروع غير المنشور عليها، وتوحيد تقرير `قائمة الأصناف` مع نفس منطق `branch_items`. بعد ذلك شُغّل السويت الكامل وكانت النتيجة نظيفة بالكامل.

- [x] **GOLD-INV-03 | إضافة تصنيف للصنف: ذهب / مقتنيات / فضة**
  - `Reuse: جديد`
  - `Planning basis:` المطلوب أن يتحكم تصنيف الصنف في الحقول الإلزامية والقواعد التشغيلية لكل نوع.
  - `Gold implementation:` إضافة حقل تصنيف إلزامي على الصنف مع قواعد تحقق إضافية، مثل إلزام `karat_id` للأصناف الذهبية فقط.
  - `Done when:` التصنيف يتحكم في شاشة الإدخال والحسابات والفلاتر والتقارير.
  - `Files:` `database/migrations/2026_03_22_000014_add_inventory_classification_to_items_table.php`, `app/Models/Item.php`, `app/Models/InvoiceDetail.php`, `app/Http/Controllers/Admin/ItemController.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `resources/views/admin/items/form.blade.php`, `resources/views/admin/items/index.blade.php`, `resources/views/admin/purchases/create.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `tests/Feature/ItemClassificationFeatureTest.php`, `tests/Feature/SoldItemsReportFiltersFeatureTest.php`, `tests/Feature/ItemListReportFeatureTest.php`, و`tests/Feature/NonGoldOperationsFeatureTest.php`.
  - `DB:` تم استخدام `items.inventory_classification` كمرجع تشغيلي فعلي مع الإبقاء على `gold_carat_id/gold_carat_type_id` فارغين للأصناف غير الذهبية. لا توجد migration إضافية في هذه الدفعة؛ التوسعة اعتمدت على نفس الحقول الحالية داخل `invoice_details` مع السماح بحفظ `gold_carat_id` و`gold_carat_type_id` كقيم `null` عند بيع أو شراء `silver/collectible`.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي التحقق من شاشة إنشاء الصنف، فلترة التقارير بالتصنيف، ثم تدفقات تشغيل فعلية للأصناف غير الذهبية: `sales.store`, `purchases.store`, `sales_return.store`, `items.purchases.search`, وطباعة البيع والشراء والمرتجع مع ظهور `فضة/مقتنيات` بدل افتراض العيار. كما شُغّل السويت الكامل بعد التنفيذ وكانت النتيجة نظيفة بالكامل.

- [x] **GOLD-INV-04 | إضافة الباركود للأصناف وتحسين طباعة الباركود حسب الورق**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب دعم توليد باركود، والبحث به، وطباعة ملصقات بمقاسات ورق مختلفة.
  - `Gold implementation:` الإبقاء على الباركود، لكن يضاف مفهوم `paper_profile` للطباعة مثل `A4-3x8`, `A5-2x5`, `Label-50x25`. يفضل ألا تعتمد النسخة النهائية على خدمة خارجية لتوليد الباركود إذا كان البرنامج يحتاج عملًا داخليًا أو أوفلاين.
  - `Done when:` يمكن اختيار مقاس ورق الطباعة وتظهر الملصقات بمحاذاة صحيحة وبدون انزياح.
  - `Files:` `app/Services/Items/BarcodePrintProfileService.php`, `app/Http/Controllers/Admin/ItemController.php`, `resources/views/admin/items/barcodes_table.blade.php`, `resources/views/admin/items/print_barcode.blade.php`, `public/css/barcode.css`, و`tests/Feature/ItemBarcodePrintFeatureTest.php`.
  - `DB:` لا توجد migrations جديدة في هذه النقطة؛ تم البناء على بنية `items/item_units` الحالية وإضافة مفهوم `paper_profile` على مستوى الطباعة فقط.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي ظهور خيارات المقاسات في نافذة الباركود، واحترام الطباعة الجماعية للمقاس المختار مثل `A5 - 2 x 5`، وطباعة ملصق مفرد لصنف غير ذهبي مع إظهار تصنيفه عند استخدام `Label - 50 x 25`. كما شُغّل السويت الكامل بعد التنفيذ وكانت النتيجة نظيفة بالكامل.

- [x] **GOLD-INV-05 | إضافة زر تحديث أسعار الذهب داخل البرنامج**
  - `Reuse: جديد`
  - `Planning basis:` المطلوب إدارة أسعار الذهب كسجل زمني مستقل مع تحديث يدوي أو خارجي وحفظ Snapshot داخل المستندات.
  - `Gold implementation:` إنشاء جدول مثل `gold_prices` يحفظ السعر حسب العيار والمصدر ووقت التحديث، مع خدمة `GoldPriceSyncService` وزر يدوي للتحديث وسجل تاريخي للأسعار. يجب حفظ السعر المستخدم داخل الفاتورة Snapshot وقت البيع/الشراء.
  - `Done when:` يمكن تحديث السعر يدويًا أو من مصدر خارجي، وتُستخدم الأسعار الجديدة دون تغيير فواتير الماضي.
  - `Files:` `app/Models/GoldPrice.php`, `app/Models/GoldPriceHistory.php`, `app/Services/Pricing/GoldPriceSyncService.php`, `app/Http/Controllers/Admin/PricingController.php`, `app/Http/Controllers/Admin/HomeController.php`, `app/Http/Controllers/Admin/StockSettlementController.php`, `resources/views/admin/pricing/index.blade.php`, `resources/views/admin/pricing/stock_market.blade.php`, `routes/web.php`, `config/services.php`, `tests/Feature/GoldPriceManagementFeatureTest.php`, و`tests/Feature/InvoicePaymentLinesFeatureTest.php`.
  - `DB:` تم توسيع `gold_prices` بحقول المصدر والعملة الوصفية و`meta`، مع إضافة جدول `gold_price_histories` للاحتفاظ بكل Snapshot تاريخي مرتبط بالمستخدم ووقت التحديث. كما تم التحقق من أن Snapshot السعر الفعلي للفواتير يبقى محفوظًا داخل `invoice_details.unit_price/line_total/net_total` ولا يتأثر بتغير أسعار الذهب اللاحقة.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي التحديث اليدوي، والمزامنة من خدمة خارجية، وسجل التاريخ، والصلاحيات، وعرض آخر Snapshot بحسب العملة، ثم إضافة اختبار end-to-end يحاكي بيعًا فعليًا وبعده تغييرًا لاحقًا في سعر الذهب المركزي مع التأكد من ثبات `unit_price` و`line_total` و`net_total` داخل الفاتورة والطباعة. بعد ذلك شُغّل السويت الكامل وكانت النتيجة نظيفة بالكامل.

## التقارير والفلاتر والمحاسبة

- [x] **GOLD-REP-01 | إضافة فلاتر موحدة في جميع التقارير**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب طبقة فلاتر موحدة يعاد استخدامها عبر جميع التقارير بدل بناء كل تقرير بمعزل.
  - `Gold implementation:` إنشاء Filter DTO أو Service موحد للحقول `from_date`, `to_date`, `from_time`, `to_time`, `user_id`, `branch_id`, `item_id`, `karat_id`, `company_id`, `invoice_no`.
  - `Done when:` كل تقرير يدعم نفس الفلاتر الأساسية إن كانت منطقية لطبيعته، وتظهر الفلاتر من `meta()` لا من قيم hardcoded.
  - `Files:` `app/Http/Controllers/Admin/StockReportsController.php`, `app/Http/Controllers/Admin/CustomerController.php`, `app/Http/Controllers/Admin/AccountingReportsController.php`, `app/Http/Controllers/Admin/ItemsReportsController.php`, `app/Http/Controllers/Admin/WarehouseController.php`, شاشات البحث والنتائج داخل `resources/views/admin/reports/**`, وملفات الاختبارات `tests/Feature/StockReportFiltersFeatureTest.php`, `tests/Feature/CustomerStatementReportFeatureTest.php`, `tests/Feature/TaxDeclarationReportFeatureTest.php`, `tests/Feature/SoldItemsReportFiltersFeatureTest.php`, `tests/Feature/AccountStatementReportFeatureTest.php`, `tests/Feature/ItemListReportFeatureTest.php`, `tests/Feature/AccountingSummaryReportsFeatureTest.php`, و`tests/Feature/GoldStockReportFeatureTest.php`.
  - `DB:` لا توجد migrations جديدة خاصة بهذه النقطة؛ تم البناء على الجداول الحالية مع تغيير منطق الاستعلام والتجميع فقط.
  - `Verification:` تم تغطية كل تقارير الويب الحالية تحت مسار `reports` باختبارات `Feature` تثبت ظهور الفلاتر المناسبة لكل تقرير وتأثيرها الفعلي على النتائج، بما في ذلك تقارير المبيعات والمشتريات، الضرائب، كشف الحساب، كشف الطرف، الأصناف المباعة، قائمة الأصناف، ميزان المراجعة، قائمة الدخل، الميزانية، ومخزون الذهب. كما شُغّل السويت الكامل بعد كل دفعة وآخر نتيجة كانت نظيفة بالكامل.

- [x] **GOLD-REP-02 | إضافة مستويات للشجرة المحاسبية وقسم المحاسبة**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب شجرة حسابات متعددة المستويات مع علاقة `parent/child` واضحة وتقارير مبنية عليها.
  - `Gold implementation:` نقل نفس بنية الشجرة للمحاسبة في الذهب مع ربط تلقائي بحسابات العملاء والموردين والبنوك والفروع.
  - `Done when:` تظهر شجرة حسابات متعددة المستويات مع حركات وكشوف وتقارير ميزان مراجعة وقيود مرتبطة بعمليات الذهب.
  - `Files:` `app/Models/Account.php`, `app/Http/Controllers/Admin/AccountsController.php`, `resources/views/admin/accounts/index.blade.php`, `resources/views/admin/accounts/partials/tree-node.blade.php`, و`tests/Feature/AccountsTreeFeatureTest.php`.
  - `DB:` لا توجد migrations جديدة لهذه النقطة؛ تم البناء على بنية `accounts`, `opening_balances`, و`journal_entries/journal_entry_documents` الحالية.
  - `Verification:` تم تحويل صفحة `دليل الحسابات` إلى شاشة محاسبية فعلية تعرض مؤشرات القسم، اختصارات تشغيلية، وجدول حسابات مع `level` وحالة الحساب، إضافة إلى شجرة recursive محمّلة من الكنترولر بدون استعلامات داخل Blade. كما تم إكمال مسار حذف الحساب الموجود أصلًا مع حمايات تمنع حذف الحسابات المرتبطة بحسابات فرعية أو أرصدة افتتاحية أو قيود يومية. أضيفت اختبارات `Feature` تغطي تحميل الشجرة والملخصات والمنع/السماح بالحذف، ثم شُغّل `php artisan test` كاملًا وكانت النتيجة النهائية `120 passed`.

- [x] **GOLD-REP-03 | إضافة قائمة الجرد**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب دورة جرد كاملة تشمل إنشاء الجرد، إدخال الفروقات، والاعتماد النهائي.
  - `Gold implementation:` نقل نفس الفكرة، مع توسيعها إن لزم لتسجيل وزن إجمالي ووزن صافي وفرق وزن للأصناف الذهبية.
  - `Done when:` يوجد إنشاء جرد واعتماد جرد وقائمة محفوظة مع فروقات واضحة.
  - `Files:` `app/Http/Controllers/Admin/StockSettlementController.php`, `app/Models/Invoice.php`, `app/Models/InvoiceDetail.php`, `resources/views/admin/stock_settlements/index.blade.php`, `resources/views/admin/stock_settlements/create.blade.php`, `resources/views/admin/stock_settlements/create_by_default.blade.php`, `resources/views/admin/stock_settlements/show.blade.php`, `routes/web.php`, `database/migrations/2026_03_22_000019_add_stock_snapshot_fields_to_invoice_details_table.php`, و`tests/Feature/StockSettlementFeatureTest.php`.
  - `DB:` تمت إضافة `stock_actual_weight`, `stock_counted_weight`, و`stock_diff_weight` إلى `invoice_details` لحفظ Snapshot تاريخي واضح لفروقات الجرد بدل الاعتماد على حساب لحظي أثناء الإدخال فقط.
  - `Verification:` تم الإبقاء على مساري الجرد العادي والجرد الافتراضي، ثم توسيعهما ليخزّنا Snapshot الفروقات داخل `invoice_details` ويظهرا في شاشة `تفاصيل جرد المخزون` الجديدة مع إجماليات الوزن النظامي والوزن المعدود وصافي الفرق. كما تم تحسين قائمة الجرد لتعرض الفرع والمستخدم وفرق الوزن وإضافة رابط معاينة لكل جرد محفوظ. أضيفت اختبارات `Feature` end-to-end تغطي `stock_settlements.store` و`stock_settlements.store_by_default` والتحقق من الحفظ في قاعدة البيانات ثم استرجاعه من شاشة العرض، قبل تشغيل السويت الكامل.

## العملاء والموردون

- [x] **GOLD-CRM-01 | إدخال اسم العميل/المورد ورقم الهاتف عند البيع والشراء**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب التقاط بيانات سريعة للطرف داخل الفاتورة مع إمكانية حفظها لاحقًا كطرف دائم.
  - `Gold implementation:` عند الفاتورة يمكن اختيار جهة موجودة أو إدخال بيانات سريعة Snapshot داخل المستند ثم حفظها لاحقًا كطرف دائم.
  - `Done when:` الاسم والهاتف يظهران في الفاتورة والتقارير ويمكن إعادة استخدام البيانات بسهولة.
  - `Files:` `app/Services/Invoices/InvoicePartySnapshotService.php`, `app/Models/Invoice.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `app/Http/Controllers/Admin/CustomerController.php`, `resources/views/admin/sales/create.blade.php`, `resources/views/admin/purchases/create.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `resources/views/admin/customers/report.blade.php`, و`tests/Feature/InvoicePartySnapshotFeatureTest.php`.
  - `DB:` تم البناء على حقول Snapshot الحالية داخل `invoices` وهي `bill_client_name` و`bill_client_phone`، بدون migration إضافية في هذه الدفعة.
  - `Verification:` تم تغطية الحقول في واجهات البيع والشراء، والتعبئة التلقائية من الطرف المختار، وثبات الاسم والهاتف في طباعة البيع والشراء بعد تعديل بطاقة العميل/المورد لاحقًا، ثم توسيع التغطية لتشمل `كشف الطرف` نفسه بحيث يعرض اسم/هاتف الطرف كما حُفظا على الفاتورة لا كما أصبحا لاحقًا في البطاقة الرئيسية. بعد ذلك تم تشغيل اختبارات الشريحة بنجاح ثم إدخالها ضمن السويت الكامل.

- [x] **GOLD-CRM-02 | إضافة رقم الهوية**
  - `Reuse: جديد`
  - `Planning basis:` المطلوب حقل هوية مستقل قابل للبحث مع مراعاة الصلاحيات إذا كانت الحساسية عالية.
  - `Gold implementation:` إضافة حقل مثل `identity_number` أو `national_id` على جدول العملاء/الموردين، مع صلاحية مشاهدة/تعديل مستقلة إذا كانت الخصوصية مطلوبة.
  - `Done when:` يمكن حفظ رقم الهوية والبحث به وإظهاره في شاشة الطرف والتقرير عند الحاجة.
  - `Files:` `database/migrations/2026_03_22_000005_add_identity_number_to_customers_table.php`, `database/migrations/2026_03_22_000013_add_bill_client_identity_number_to_invoices_table.php`, `app/Http/Controllers/Admin/CustomerController.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `app/Models/Invoice.php`, `app/Services/Invoices/InvoicePartySnapshotService.php`, `resources/views/admin/customers/index.blade.php`, `resources/views/admin/customers/report.blade.php`, `resources/views/admin/sales/create.blade.php`, `resources/views/admin/purchases/create.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `tests/Feature/CustomerIdentityFeatureTest.php`, `tests/Feature/InvoicePartySnapshotFeatureTest.php`, و`tests/Feature/InvoicePaymentLinesFeatureTest.php`.
  - `DB:` تمت إضافة `identity_number` على جدول `customers` ثم إضافة `bill_client_identity_number` على جدول `invoices` لحفظ Snapshot تاريخي مستقل عن بطاقة العميل/المورد.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي حفظ رقم الهوية واسترجاعه من endpoint التعديل، البحث به داخل قائمة العملاء، ظهوره في واجهات البيع والشراء والطباعة، وثباته في Snapshot الفاتورة حتى بعد تعديل بطاقة الطرف الأساسية، بالإضافة إلى التحقق من مسارات `sales.store`, `purchases.store`, و`sales_return.store` فعليًا ثم تشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-CRM-03 | حفظ البيانات لاستخدامها لاحقًا بسرعة**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب خدمة حفظ مركزية لبيانات العميل/المورد من أكثر من شاشة تشغيلية.
  - `Gold implementation:` عند البيع/الشراء النقدي تظهر للمستخدم إمكانية حفظ الاسم والهاتف والهوية كعميل/مورد دائم أو كطرف نقدي سريع.
  - `Done when:` إدخال بيانات جديدة مرة واحدة يجعلها متاحة في البحث السريع بالفواتير القادمة.
  - `Files:` `app/Http/Controllers/Admin/CustomerController.php`, `resources/views/admin/sales/create.blade.php`, `resources/views/admin/purchases/create.blade.php`, `routes/web.php`, `tests/Feature/QuickPartyDirectoryFeatureTest.php`, `tests/Feature/InvoicePartySnapshotFeatureTest.php`, و`tests/Feature/InvoicePaymentLinesFeatureTest.php`.
  - `DB:` لا توجد migration جديدة خاصة بهذه النقطة؛ تم البناء على `customers.identity_number`, `customers.is_cash_party`, وSnapshot الفاتورة في `invoices`.
  - `Verification:` تم تنفيذ اختبارات `Feature` تغطي إنشاء طرف جديد من الحفظ السريع، إعادة استخدام طرف موجود حسب `phone` أو `identity_number`, تعبئة البيانات الناقصة عند إعادة الاستخدام، دعم `is_cash_party` من نفس تدفق الحفظ السريع، ظهور الحقول والمفاتيح في واجهات البيع والشراء، ثم التحقق من استخدام البيانات نفسها داخل الحفظ والطباعة عبر اختبارات store/print وتشغيل السويت الكامل بعد التنفيذ.

- [x] **GOLD-CRM-04 | إنشاء قائمة للعملاء/الموردين النقديين**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب تمييز الأطراف النقدية عن الأطراف العادية بفلاج أو نوع واضح.
  - `Gold implementation:` إضافة Flag مثل `is_cash_party` أو نوع حساب فرعي `cash_customer` / `cash_vendor` مع قوائم مستقلة واختيارات سريعة من شاشة البيع والشراء.
  - `Done when:` يوجد تبويب أو فلتر منفصل للأطراف النقدية ويمكن اختيارهم بسرعة من الفاتورة.
  - `Files:` `app/Http/Controllers/Admin/CustomerController.php`, `routes/web.php`, `resources/views/admin/customers/index.blade.php`, `resources/views/admin/layouts/main-sidebar.blade.php`, و`tests/Feature/CashPartyFeatureTest.php`.
  - `DB:` لا توجد migration جديدة في هذه الدفعة؛ تم البناء على الحقل `customers.is_cash_party` المضاف سابقًا وتوسيع استخدامه فقط على مستوى الويب والتصفية.
  - `Verification:` تم الإبقاء على Toggle التصفية داخل البيع والشراء، مع إضافة صفحة مستقلة فعلية لكل من `العملاء النقديون` و`الموردين النقديين` عبر route مخصص وروابط مباشرة من القائمة الجانبية. كما تم تنفيذ اختبارات `Feature` تغطي حفظ الفلاج، استرجاعه في endpoint التعديل، تصفية القائمة التقليدية، فتح دليل العملاء النقديين، فتح دليل الموردين النقديين مع البحث برقم الهوية، ثم تشغيل اختبارات البند بنجاح قبل إدخاله ضمن السويت الكامل.

- [x] **GOLD-CRM-05 | تقرير تفصيلي لكل عميل/مورد يشمل العمليات النقدية والذهب حسب العيار**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب كشف طرف موحد يجمع النقدي والوزني والحركة حسب العيار ونوع العملية.
  - `Gold implementation:` توسيع التقرير ليعرض المال + الوزن + التجميع حسب `karat_id` ونوع العملية `بيع/شراء/مرتجع/قبض/صرف`.
  - `Done when:` يظهر كشف طرف واحد يجمع النقدي والذهبي حسب العيارات ضمن فترة محددة.
  - `Files:` `app/Http/Controllers/Admin/CustomerController.php`, `resources/views/admin/customers/report.blade.php`, `resources/views/admin/customers/index.blade.php`, `tests/Feature/CustomerStatementReportFeatureTest.php`, و`tests/Feature/InvoicePartySnapshotFeatureTest.php`.
  - `DB:` لا توجد migrations جديدة خاصة بهذه النقطة؛ تم البناء على `invoices`, `invoice_details`, `financial_vouchers`, وحقول Snapshot الحالية للطرف.
  - `Verification:` تم إنشاء كشف طرف موحد للطرف الواحد من واقع الفواتير ومرتجعاتها وسندات `قبض/صرف` المرتبطة بحسابه، مع فلاتر `from_date`, `to_date`, `from_time`, `to_time`, `branch_id`, `user_id`, `carat_id`, `operation_type`, و`invoice_number`. كما يعرض التقرير ملخصًا ماليًا ووزنيًا حسب نوع العملية والعيار لكل من العميل والمورد، ويظهر داخل السندات أيضًا القناة البنكية ومرجع العملية عند كونها غير نقدية. التغطية الحالية تشمل التجميع المالي والوزني، احترام الفلاتر، عمل الصفحة نفسها مع المورد، ظهور السندات المالية مع فلاتر المستخدم والوقت ونوع العملية، وإظهار Snapshot الطرف المحفوظ على الفاتورة داخل التقرير. بعد ذلك شُغّل السويت الكامل وكانت النتيجة نظيفة بالكامل.

## التقارير والعمليات اليومية

- [x] **GOLD-DAY-01 | تقرير يومي للمبيعات والمشتريات حسب عيارات الذهب**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب تقرير يومي مبني على تفاصيل الفواتير مع تجميع حسب العيار والفرع واليوم.
  - `Gold implementation:` يفضل بناء التقرير النهائي مباشرة من تفاصيل فواتير الذهب بدل الاعتماد على بنية Legacy غير ثابتة، مع تجميع حسب اليوم والفرع والعيار.
  - `Done when:` يعرض التقرير كمية وقيمة المبيعات والمشتريات والمرتجعات حسب كل عيار.
  - `Files:` `app/Http/Controllers/Admin/StockReportsController.php`, `resources/views/admin/reports/stock_reports/daily_carat_report/search.blade.php`, `resources/views/admin/reports/stock_reports/daily_carat_report/index.blade.php`, `resources/views/admin/reports/stock_reports/partials/common_filters.blade.php`, `tests/Feature/DailyCaratReportFeatureTest.php`, و`tests/Feature/StockReportFiltersFeatureTest.php`.
  - `DB:` لا توجد migrations جديدة؛ تم البناء مباشرة على `invoices + invoice_details + gold_carats`.
  - `Verification:` تم تنفيذ التقرير مباشرة من تفاصيل الفواتير مع دعم `sale`, `purchase`, `sale_return`, و`purchase_return`، وتجميع يومي حسب العملية والعيار، مع فلاتر `date_from`, `date_to`, `branch_id`, `user_id`, `carat_id`, `from_time`, `to_time`, و`invoice_number`. كما تم توسيع الواجهة لإظهار نطاق الوقت المختار داخل التقرير نفسه، وتغطية ذلك باختبارات `Feature` مستقلة للتجميع اليومي، واحترام الفرع والعيار، واحترام فلترة الوقت، إضافة إلى اختبارات الفلاتر الموحدة التي تغطي التقرير نفسه ضمن مسار التقارير. بعد ذلك شُغّل السويت الكامل وكانت النتيجة نظيفة.

- [x] **GOLD-DAY-02 | نظام شفتات لكل مستخدم مع وقت بداية ونهاية وتقارير**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب دورة شفت كاملة: فتح، إغلاق، وربط العمليات المالية والتشغيلية بالشفت.
  - `Gold implementation:` ربط الفواتير والقبوض والمدفوعات بـ `shift_id`، مع تقارير بداية/نهاية الشفت والفروقات النقدية والعمليات المنفذة.
  - `Done when:` لا يمكن احتساب تقرير الشفت إلا من البيانات المرتبطة به فعليًا، مع سجل واضح لوقت الفتح والإغلاق.
  - `Files:` `database/migrations/2026_03_22_000007_create_shifts_table.php`, `database/migrations/2026_03_22_000008_add_shift_id_to_invoices_table.php`, `database/migrations/2026_03_22_000009_add_shift_id_to_financial_vouchers_table.php`, `app/Models/Shift.php`, `app/Services/Shifts/ShiftService.php`, `app/Http/Controllers/Admin/ShiftController.php`, `app/Http/Controllers/Admin/FinancialVoucherController.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `resources/views/admin/shifts/index.blade.php`, `resources/views/admin/shifts/show.blade.php`, و`tests/Feature/ShiftWorkflowFeatureTest.php`.
  - `DB:` إنشاء جدول `shifts`، وإضافة `shift_id` إلى `invoices` و`financial_vouchers` وربط العلاقات اللازمة بالشفت والفرع والمستخدم.
  - `Verification:` تم تغطية الدورة كاملة باختبارات `Feature` تشمل فتح الشفت، منع فتح شفت ثانٍ، منع السندات بدون شفت، ربط سندات القبض/الصرف بالشفت، إغلاق الشفت واحتساب الفروقات، تقييد الوصول، فلترة الإدارة، ثم التحقق end-to-end من أن `sales.store` و`purchases.store` يحفظان الفواتير الفعلية على `shift_id` الصحيح ويظهرانها داخل تقرير الشفت.

- [x] **GOLD-DAY-03 | Dashboard للمالك**
  - `Reuse: مباشر`
  - `Planning basis:` المطلوب لوحة مؤشرات عليا تجمع المال والوزن والفروع والمستخدمين والأسعار.
  - `Gold implementation:` إضافة مؤشرات ذهبية: إجمالي الوزن المباع، المشتريات حسب العيار، آخر تحديث لسعر الذهب، أعلى المستخدمين نشاطًا، وأعلى الفروع أداءً.
  - `Done when:` يفتح المالك لوحة واحدة تلخص المال والحركة الوزنية والتشغيل اليومي والفروع.
  - `Files:` `app/Services/Dashboard/OwnerDashboardService.php`, `app/Http/Controllers/Admin/HomeController.php`, `resources/views/admin/home.blade.php`, `tests/Feature/OwnerDashboardFeatureTest.php`, و`tests/Feature/AdminAccessTest.php`.
  - `DB:` لا توجد migrations جديدة؛ تم البناء على جداول `invoices`, `invoice_details`, `gold_prices`, `gold_carats`, `branches`, و`users` الحالية فقط.
  - `Verification:` تم استبدال الداشبورد القديم بلوحة مالك فعلية مبنية على `invoice.date` التشغيلي لا على `created_at`، مع مؤشرات يومية تشمل المبيعات، صافي البيع، المشتريات، الوزن المباع والمشتَرى بعامل التحويل، وعدد العمليات، إضافة إلى Snapshot آخر سعر ذهب، وتجميع المشتريات حسب العيار، وجدولي أعلى المستخدمين نشاطًا وأعلى الفروع أداءً. كما تم تغطية ذلك باختبار `Feature` مستقل يثبت صحة المؤشرات على مستوى المالك عبر جميع الفروع، ويثبت أيضًا أن مستخدم الفرع غير الإداري يرى بيانات فرعه فقط دون تسرب بيانات الفروع الأخرى. بعد نجاح اختبار الداشبورد تم تشغيل السويت الكامل للتأكد من عدم كسر الصفحة الرئيسية أو الاختبارات السابقة.

## الدفع والبنك

- [x] **GOLD-PAY-01 | ربط حسابات بنكية حسب الطلب**
  - `Reuse: جزئي`
  - `Planning basis:` القرار المعتمد هو دعم `حسابات بنكية متعددة فعلية لكل فرع` من أول إصدار، مع ربط كل عملية دفع بالحساب البنكي المحدد فعليًا.
  - `Gold implementation:` إنشاء جدول `bank_accounts` من البداية وربطه بالفرع والحساب المحاسبي، مع حقول مثل `branch_id`, `bank_name`, `account_name`, `iban`, `ledger_account_id`, `status`, وحقول اختيارية مثل `terminal_name` أو `device_code` إذا احتجنا تمييز أجهزة الشبكة داخل نفس الفرع.
  - `Done when:` يمكن لكل فرع امتلاك أكثر من حساب بنكي فعلي، ويمكن اختيار الحساب البنكي الصحيح داخل كل عملية دفع، ويظهر أثره بشكل صحيح في القيود والتقارير.
  - `Files:` `database/migrations/2026_03_22_000010_create_bank_accounts_table.php`, `database/migrations/2026_03_22_000018_add_payment_channel_fields_to_financial_vouchers_table.php`, `app/Models/BankAccount.php`, `app/Models/FinancialVoucher.php`, `app/Http/Controllers/Admin/BankAccountController.php`, `app/Http/Controllers/Admin/FinancialVoucherController.php`, `app/Services/Shifts/ShiftService.php`, `app/Http/Controllers/Admin/CustomerController.php`, `resources/views/admin/settings/bank_accounts/*.blade.php`, `resources/views/admin/financial_vouchers/index.blade.php`, `resources/views/admin/shifts/show.blade.php`, `tests/Feature/BankAccountsFeatureTest.php`, `tests/Feature/ShiftWorkflowFeatureTest.php`, و`tests/Feature/CustomerStatementReportFeatureTest.php`.
  - `DB:` تم إنشاء `bank_accounts` كـ master table متعدد لكل فرع وربطه بـ `ledger_account_id`، ثم توسيع `financial_vouchers` بحقول `payment_method`, `bank_account_id`, و`reference_no` لالتقاط القناة البنكية الفعلية للسندات غير النقدية.
  - `Verification:` تم تغطية CRUD وإعداد الحسابات البنكية من الواجهة، وربطها الفعلي بفواتير البيع والمشتريات ومرتجع البيع سابقًا، ثم في هذه الدفعة تم ربط السندات المالية غير النقدية بحساب بنكي فعلي من نفس الفرع مع منع أي حساب لا يطابق أحد طرفي السند، وتعديل احتساب الشفت بحيث لا تدخل السندات البنكية ضمن النقد المتوقع، وإظهار القناة البنكية ومرجع العملية في تقرير الشفت وكشف الطرف. تم إثبات ذلك باختبارات `Feature` تغطي السند البنكي الحقيقي، صحة القيود، ومنع الربط الخاطئ، ثم تشغيل السويت الكامل بنجاح.

- [x] **GOLD-PAY-02 | دعم أكثر من طريقة دفع بنكي**
  - `Reuse: جزئي`
  - `Planning basis:` هذه النقطة يجب اعتمادها من أول إصدار؛ الدفع المتعدد لا يبنى على حقل نصي واحد مثل `paid_by` بل على أسطر دفع مستقلة.
  - `Gold implementation:` إنشاء `invoice_payment_lines` أو ما يعادله، بحيث يحمل كل سطر `method_type`, `amount`, `reference_no`, `terminal_name`, و`bank_account_id` للحركات البنكية أو الشبكية. بذلك يمكن تقسيم نفس الفاتورة بين نقدي، تحويل، شبكة، أو أكثر من بنك داخل نفس الفرع.
  - `Done when:` يمكن جمع أكثر من وسيلة دفع داخل نفس الفاتورة مع توزيع صحيح في التقارير والقيود، ويكون كل سطر دفع بنكي مرتبطًا بحساب بنكي فعلي لا بنص حر فقط.
  - `Files:` `database/migrations/2026_03_22_000011_create_invoice_payment_lines_table.php`, `app/Models/InvoicePaymentLine.php`, `app/Services/Payments/InvoicePaymentService.php`, `app/Models/Invoice.php`, `app/Http/Controllers/Admin/SalesController.php`, `app/Http/Controllers/Admin/PurchasesController.php`, `app/Services/Shifts/ShiftService.php`, `resources/views/admin/sales/payment.blade.php`, `resources/views/admin/purchases/payment.blade.php`, `resources/views/admin/sales_return/create.blade.php`, `resources/views/admin/purchase_return/index.blade.php`, `resources/views/admin/purchase_return/create.blade.php`, `resources/views/admin/sales_and_sales_return/print.blade.php`, `resources/views/admin/purchases_and_purchases_return/print.blade.php`, `resources/views/admin/layouts/main-sidebar.blade.php`, `routes/web.php`, و`tests/Feature/InvoicePaymentLinesFeatureTest.php`.
  - `DB:` تم إنشاء جدول `invoice_payment_lines` وربطه بالفواتير والحسابات البنكية الفعلية. يعتمد كل سطر على `invoice_id`, `branch_id`, `method_type`, `bank_account_id`, `reference_no`, `terminal_name`, `notes`, و`amount`.
  - `Verification:` تم توسيع الدفع المتعدد ليغطي الآن مسارات `sales.store`, `purchases.store`, `sales_return.store`, و`purchase_return.store`, مع دعم `cash + credit_card + bank_transfer` داخل نفس المستند، وربط كل سطر غير نقدي بحساب بنكي فعلي من نفس الفرع. تم أيضًا تحديث القيود المحاسبية، والطباعة، وملخص الشفت بحيث تعتمد على تجميع أسطر الدفع الحقيقية، مع احتساب `purchase_return_cash_total` و`purchase_return_non_cash_total` ضمن التدفق النقدي. اختبارات `Feature` end-to-end تثبت حفظ الفواتير والمردودات المختلطة، وإنشاء أسطر الدفع، وربط الحساب البنكي الصحيح، وتوليد القيود، وظهور البيانات في الطباعة والشفت، ثم شُغّل السويت الكامل بنجاح بعد التنفيذ.

## التصنيع

- [x] **GOLD-MFG-01 | تحديد نطاق التصنيع في برنامج الذهب**
  - `Reuse: جزئي`
  - `Planning basis:` المطلوب تثبيت سيناريوهات التصنيع الذهبية قبل تصميم الجداول والقيود.
  - `Gold implementation:` قبل أي تطوير يجب تحديد هل المقصود تصنيع ذهب، تجميع أطقم، صهر/فصل، أو تحويل خام إلى مشغولات. بعد تثبيت القواعد، يتم تحديد الجداول والقيود والوزن المفقود والهالك والأجرة.
  - `Done when:` يتم اعتماد Document Business Rules واضح للتصنيع ثم تحويله إلى Checklist فرعية مستقلة.
  - `Progress (2026-03-23):` تم تثبيت النطاق التنفيذي النهائي للإصدار الأول على أنه `مصنع خارجي فقط` مع 4 مستندات أساسية: `أمر إرسال للتصنيع`, `استلام من التصنيع`, `إرجاع للمصنع/المورد`, و`تسوية فاقد/هالك`. كما تم اعتماد أن الأجرة في `V1` تدعم `لكل جرام` أو `مبلغ ثابت`, وأن الفاقد لا يشطب داخل الاستلام بل عبر مستند تسوية مستقل.
  - `Files:` `MANUFACTURING_SCOPE.md`, `MANUFACTURING_CHECKLIST.md`.
  - `DB:` لا توجد تعديلات قاعدة بيانات في هذه المرحلة؛ تم اعتماد تصميم الكيانات المقترحة فقط مثل `manufacturing_orders`, `manufacturing_receipts`, `manufacturing_returns`, و`manufacturing_loss_settlements` تمهيدًا للتنفيذ البرمجي لاحقًا.
  - `Verification:` تم تحويل البند من متطلب عام إلى وثيقتين واضحتين على مستوى الجذر: الأولى لقواعد العمل المعتمدة ونطاق `V1`, والثانية Checklist تنفيذية مستقلة تغطي `DB + Business Rules + Accounting + UI + Permissions + Tests`. بذلك أصبح نطاق التصنيع معتمدًا وجاهزًا للبرمجة اللاحقة بدون غموض.

## ملاحظات تنفيذية مهمة لبرنامج الذهب

- النقاط `GOLD-ADM-10` و`GOLD-ADM-09` و`GOLD-INV-02` مرتبطة ببعضها. إذا تقرر دعم المستخدم متعدد الفروع والأصناف الخاصة بكل فرع، فيجب تصميمها معًا من البداية.
- القرار المبدئي بعد المراجعة المرجعية: `GOLD-ADM-09` مناسب للمرحلة الأولى، أما `GOLD-ADM-10` فيعد مرحلة أعمق لأنه يحتاج مفهوم `current_branch` وتأثيره على كامل النظام.
- القرار المبدئي بعد المراجعة المرجعية: `GOLD-INV-02` يبدأ كـ `كتالوج مركزي + تفعيل/مخزون/سعر حسب الفرع`، وليس كأصناف محلية خالصة لكل فرع من أول إصدار.
- النقاط `GOLD-INV-01`, `GOLD-INV-03`, `GOLD-DAY-01`, `GOLD-CRM-05`, `GOLD-INV-05` مرتبطة بموديل العيارات والأسعار. لا تنفذ كل نقطة بمعزل عن الأخرى.
- النقاط `GOLD-PAY-01` و`GOLD-PAY-02` يجب ربطها من البداية بقسم المحاسبة حتى لا تتكرر إعادة تصميم القيود لاحقًا.
- القرار المعتمد: `GOLD-PAY-01` و`GOLD-PAY-02` ينفذان من أول إصدار على أساس `master bank_accounts` متعدد لكل فرع، مع ربط كل سطر دفع بنكي بالحساب البنكي الفعلي.
- النقاط الخاصة بالطباعة يجب أن تحفظ بيانات الفاتورة بصيغة Snapshot داخل المستند نفسه، لأن الشروط والشعار والأسعار والرقم الضريبي قد تتغير لاحقًا.
