@extends('admin.layouts.master')

@section('content')
@can('employee.system_settings.show')

@if (session('success'))
    <div class="alert alert-success fade show">
        <button class="close" data-dismiss="alert">×</button>
        {{ session('success') }}
    </div>
@endif
@if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<style>
/* ── layout ── */
.bg-settings-wrap {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 0;
    height: calc(100vh - 120px);
    min-height: 600px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
    background: #fff;
}
.bg-panel-left {
    border-left: 1px solid #dee2e6;
    overflow-y: auto;
    background: #f8f9fa;
    display: flex;
    flex-direction: column;
}
.bg-panel-right {
    background: #3f4550;
    position: relative;
    overflow: hidden;
}
.bg-preview-iframe {
    width: 100%;
    height: 100%;
    border: none;
    display: block;
}
.bg-no-template {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: #9ca3af;
    font-size: 15px;
    flex-direction: column;
    gap: 8px;
}

/* ── panel sections ── */
.bg-section {
    border-bottom: 1px solid #dee2e6;
    padding: 14px 16px;
}
.bg-section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .05em;
    color: #6b7280;
    margin-bottom: 10px;
}

/* ── control rows ── */
.ctrl {
    margin-bottom: 10px;
}
.ctrl label {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 3px;
}
.ctrl label span {
    font-weight: 400;
    color: #6b7280;
    font-family: monospace;
    font-size: 11px;
}
.ctrl input[type=range] {
    width: 100%;
    height: 4px;
    cursor: pointer;
}

/* ── save bar ── */
.bg-save-bar {
    padding: 12px 16px;
    background: #fff;
    border-top: 1px solid #dee2e6;
    margin-top: auto;
    display: flex;
    align-items: center;
    gap: 8px;
}

/* ── iframe loading overlay ── */
.iframe-loading {
    position: absolute;
    inset: 0;
    background: #3f4550;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    font-size: 13px;
    transition: opacity .3s;
    z-index: 10;
}
.iframe-loading.hidden { opacity: 0; pointer-events: none; }

/* ── paper size buttons ── */
.paper-btn-group { display: flex; gap: 6px; }
.paper-btn {
    flex: 1;
    padding: 5px 0;
    font-size: 12px;
    font-weight: 600;
    border-radius: 4px;
    border: 1px solid #d1d5db;
    background: #fff;
    cursor: pointer;
    transition: all .15s;
}
.paper-btn.active {
    background: #2563eb;
    color: #fff;
    border-color: #2563eb;
}

/* ── switch ── */
.sw { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 600; color: #374151; }
.sw .custom-control-label { font-size: 12px; }
</style>

<div class="bg-settings-wrap">

    {{-- ══════════ LEFT PANEL ══════════ --}}
    <div class="bg-panel-left">

        {{-- محدد سياق الإعدادات --}}
        <div class="bg-section" style="background:#fff;">
            <div class="bg-section-title">السياق المحفوظ له</div>
            <small class="text-muted d-block mb-2">
                كل تركيبة (نوع فاتورة + مقاس) لها إعدادات منفصلة. التصميم نفسه يبقى موحّداً للفرع.
            </small>

            <div class="ctrl">
                <label>نوع الفاتورة</label>
                <select id="ctx-invoice-type" class="form-control form-control-sm">
                    @foreach($availableInvoiceTypes as $key => $label)
                        <option value="{{ $key }}" {{ $selectedInvoiceType === $key ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="ctrl">
                <label>المقاس</label>
                <div class="paper-btn-group">
                    <button type="button" class="paper-btn {{ $selectedFormat === 'a4' ? 'active' : '' }}"
                            id="ctx-a4">A4 &nbsp;<small>210×297</small></button>
                    <button type="button" class="paper-btn {{ $selectedFormat === 'a5' ? 'active' : '' }}"
                            id="ctx-a5">A5 &nbsp;<small>148×210</small></button>
                </div>
            </div>
        </div>

        {{-- رفع تصميم --}}
        <div class="bg-section">
            <div class="bg-section-title">ورق الشركة (التصميم الجاهز)</div>
            @can('employee.system_settings.edit')
            <form method="POST"
                  action="{{ route('admin.system-settings.invoice-background.upload') }}"
                  enctype="multipart/form-data">
                @csrf
                <div class="mb-2">
                    <input type="file" name="background_file" class="form-control form-control-sm"
                           accept="image/jpeg,image/jpg,image/png,image/webp,application/pdf" required>
                    <small class="text-muted">JPG · PNG · WebP · PDF &nbsp;|&nbsp; max 10MB</small>
                </div>
                <button type="submit" class="btn btn-info btn-sm btn-block">رفع التصميم</button>
            </form>
            @endcan

            @if($hasTemplate)
            <div class="d-flex gap-2 mt-2">
                @can('employee.system_settings.edit')
                <form method="POST" action="{{ route('admin.system-settings.invoice-background.toggle') }}" class="flex-1">
                    @csrf @method('PATCH')
                    <input type="hidden" name="enabled" value="{{ $isEnabled ? '0' : '1' }}">
                    <button type="submit" class="btn btn-sm btn-block {{ $isEnabled ? 'btn-warning' : 'btn-success' }}">
                        {{ $isEnabled ? 'إيقاف الخلفية' : 'تفعيل الخلفية' }}
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.system-settings.invoice-background.delete') }}"
                      onsubmit="return confirm('حذف التصميم؟')">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger">حذف</button>
                </form>
                @endcan
            </div>
            @if($isEnabled)
                <div class="mt-1"><span class="badge badge-success">مفعّلة</span></div>
            @else
                <div class="mt-1"><span class="badge badge-secondary">موقوفة</span></div>
            @endif
            @endif
        </div>

        @if($hasTemplate)
        @can('employee.system_settings.edit')

        {{-- حجم الورق --}}
        <div class="bg-section">
            <div class="bg-section-title">حجم ورق التصميم</div>
            @if(($imageInfo['width'] ?? 0) > 0 && ($imageInfo['height'] ?? 0) > 0)
                <small class="text-muted d-block mb-2">
                    الملف: {{ $imageInfo['width'] }}×{{ $imageInfo['height'] }}
                    · {{ $imageInfo['orientation'] === 'landscape' ? 'أفقي' : 'عمودي' }}
                    · {{ $imageInfo['mode'] === 'wide_strip' ? 'رأس/تذييل' : ($imageInfo['mode'] === 'partial' ? 'جزئي' : 'صفحة كاملة') }}
                </small>
            @endif
            <div class="paper-btn-group">
                <button class="paper-btn {{ $paperSize==='a4'?'active':'' }}" id="btn-a4" onclick="setPaperSize('a4')">A4 &nbsp;<small>210×297</small></button>
                <button class="paper-btn {{ $paperSize==='a5'?'active':'' }}" id="btn-a5" onclick="setPaperSize('a5')">A5 &nbsp;<small>148×210</small></button>
            </div>
            <div class="paper-btn-group mt-2">
                <button class="paper-btn {{ $paperOrientation==='portrait'?'active':'' }}" id="btn-portrait" onclick="setPaperOrientation('portrait')">عمودي</button>
                <button class="paper-btn {{ $paperOrientation==='landscape'?'active':'' }}" id="btn-landscape" onclick="setPaperOrientation('landscape')">أفقي</button>
            </div>
        </div>

        {{-- إعدادات الخلفية --}}
        <div class="bg-section">
            <div class="bg-section-title">الخلفية</div>

            <div class="ctrl">
                <label>حجم الخلفية <span id="v-scale">{{ round($scale*100) }}%</span></label>
                <input type="range" id="s-scale" min="0.3" max="2" step="0.05" value="{{ $scale }}">
            </div>

            <div class="ctrl">
                <label>موضع أفقي <span id="v-offset-x">{{ $offsetX > 0 ? '+' : '' }}{{ $offsetX }}%</span></label>
                <input type="range" id="s-offset-x" min="-50" max="50" step="1" value="{{ $offsetX }}">
            </div>
        </div>

        {{-- إعدادات المحتوى --}}
        <div class="bg-section">
            <div class="bg-section-title">منطقة الفاتورة</div>

            <div class="ctrl">
                <label>بداية المحتوى <span id="v-top">{{ $contentTop }}mm</span></label>
                <input type="range" id="s-top" min="0" max="200" step="1" value="{{ $contentTop }}">
            </div>

            <div class="ctrl">
                <label>نهاية المحتوى <span id="v-bottom">{{ $contentBottom }}mm</span></label>
                <input type="range" id="s-bottom" min="0" max="200" step="1" value="{{ $contentBottom }}">
            </div>

            <div class="ctrl">
                <label>عرض الفاتورة <span id="v-width">{{ $contentWidth }}%</span></label>
                <input type="range" id="s-width" min="50" max="100" step="1" value="{{ $contentWidth }}">
            </div>

            <div class="ctrl">
                <label>حجم الفاتورة <span id="v-content-scale">{{ round($contentScale * 100) }}%</span></label>
                <input type="range" id="s-content-scale" min="0.5" max="1.5" step="0.02" value="{{ $contentScale }}">
            </div>

            <div class="ctrl">
                <label>حجم خط الفاتورة <span id="v-font-scale">{{ round($fontScale * 100) }}%</span></label>
                <input type="range" id="s-font-scale" min="0.7" max="1.4" step="0.02" value="{{ $fontScale }}">
            </div>

            <div class="ctrl mt-2">
                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="sw-hide-header"
                           {{ $hideHeader ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sw-hide-header">
                        إخفاء ترويسة الفاتورة
                    </label>
                </div>
                <div class="custom-control custom-switch mt-2">
                    <input type="checkbox" class="custom-control-input" id="sw-hide-footer"
                           {{ $hideFooter ? 'checked' : '' }}>
                    <label class="custom-control-label" for="sw-hide-footer">
                        إخفاء تذييل الفاتورة
                    </label>
                </div>
                <small class="text-muted d-block mt-1">عند الطباعة على ورق شركة جاهز يفضل إخفاء الاثنين.</small>
            </div>
        </div>

        {{-- save --}}
        <div class="bg-save-bar">
            <button type="button" id="save-btn" class="btn btn-primary btn-sm flex-1">حفظ الإعدادات</button>
            <button type="button" id="reset-btn" class="btn btn-outline-secondary btn-sm" title="إعادة ضبط افتراضي">↺ ضبط</button>
            <span id="save-msg" class="text-success d-none font-weight-bold" style="font-size:12px;">تم ✓</span>
        </div>

        @endcan
        @endif

    </div>{{-- /left --}}

    {{-- ══════════ RIGHT PANEL (iframe) ══════════ --}}
    <div class="bg-panel-right">
        @if($hasTemplate && $sampleInvoice)
            <div class="iframe-loading" id="iframe-loading">
                <div class="spinner-border spinner-border-sm text-light mb-2" role="status"></div>
                <span>جاري تحميل المعاينة...</span>
            </div>
            <iframe
                id="preview-iframe"
                class="bg-preview-iframe"
                src="{{ $previewUrl }}"
                onload="document.getElementById('iframe-loading').classList.add('hidden')"
            ></iframe>
        @elseif($hasTemplate && !$sampleInvoice)
            <div class="bg-no-template">
                <i class="fa fa-file-invoice fa-2x mb-2"></i>
                <span>لا توجد فاتورة للمعاينة — أنشئ فاتورة أولاً</span>
            </div>
        @else
            <div class="bg-no-template">
                <i class="fa fa-image fa-2x mb-2"></i>
                <span>ارفع تصميم الشركة لرؤية المعاينة</span>
            </div>
        @endif
    </div>{{-- /right --}}

</div>{{-- /wrap --}}

@endcan

@if($hasTemplate && $sampleInvoice)
<script>
(function () {
    /* ── state ── */
    var S = {
        scale:      {{ $scale }},
        offsetX:    {{ $offsetX }},
        top:        {{ $contentTop }},
        bottom:     {{ $contentBottom }},
        width:      {{ $contentWidth }},
        contentScale: {{ $contentScale }},
        fontScale:  {{ $fontScale }},
        paperSize:  '{{ $paperSize }}',
        paperOrientation: '{{ $paperOrientation }}',
        hideHeader: {{ $hideHeader ? 'true' : 'false' }},
        hideFooter: {{ $hideFooter ? 'true' : 'false' }},
    };

    var BASE_URL = '{{ $previewUrl }}';
    var SAVE_URL = '{{ route("admin.system-settings.invoice-background.scale") }}';
    var CSRF     = '{{ csrf_token() }}';
    var CTX = {
        invoiceType: '{{ $selectedInvoiceType }}',
        format: '{{ $selectedFormat }}',
    };

    /* ── build iframe URL from state ── */
    function iframeUrl() {
        var u = new URL(BASE_URL, window.location.origin);
        u.searchParams.set('bg_scale',          S.scale);
        u.searchParams.set('bg_content_top',    S.top);
        u.searchParams.set('bg_content_bottom', S.bottom);
        u.searchParams.set('bg_content_width',  S.width);
        u.searchParams.set('bg_content_scale',  S.contentScale);
        u.searchParams.set('bg_font_scale',     S.fontScale);
        u.searchParams.set('bg_offset_x',       S.offsetX);
        u.searchParams.set('bg_paper_size',     S.paperSize);
        u.searchParams.set('bg_paper_orientation', S.paperOrientation);
        u.searchParams.set('paper',             S.paperSize);
        u.searchParams.set('orientation',       S.paperOrientation);
        u.searchParams.set('bg_hide_header',    S.hideHeader ? '1' : '0');
        u.searchParams.set('bg_hide_footer',    S.hideFooter ? '1' : '0');
        return u.toString();
    }

    var reloadTimer = null;
    function scheduleReload(delay) {
        clearTimeout(reloadTimer);
        reloadTimer = setTimeout(function () {
            var iframe = document.getElementById('preview-iframe');
            var loader = document.getElementById('iframe-loading');
            if (!iframe) return;
            if (loader) loader.classList.remove('hidden');
            iframe.src = iframeUrl();
        }, delay || 800);
    }

    /* ── sliders ── */
    function wire(id, key, fmt, delay) {
        var el = document.getElementById(id);
        var vEl = document.getElementById('v-' + id.replace('s-', ''));
        if (!el) return;
        el.addEventListener('input', function () {
            S[key] = parseFloat(this.value);
            if (vEl) vEl.textContent = fmt(S[key]);
            scheduleReload(delay || 800);
        });
    }

    wire('s-scale',    'scale',   function(v){ return Math.round(v*100)+'%'; });
    wire('s-offset-x', 'offsetX', function(v){ return (v>0?'+':'')+v+'%'; });
    wire('s-top',      'top',     function(v){ return v+'mm'; });
    wire('s-bottom',   'bottom',  function(v){ return v+'mm'; });
    wire('s-width',    'width',   function(v){ return v+'%'; });
    wire('s-content-scale', 'contentScale', function(v){ return Math.round(v*100)+'%'; }, 500);
    wire('s-font-scale', 'fontScale', function(v){ return Math.round(v*100)+'%'; }, 500);

    var swHide = document.getElementById('sw-hide-header');
    if (swHide) {
        swHide.addEventListener('change', function () {
            S.hideHeader = this.checked;
            scheduleReload(400);
        });
    }
    var swHideFooter = document.getElementById('sw-hide-footer');
    if (swHideFooter) {
        swHideFooter.addEventListener('change', function () {
            S.hideFooter = this.checked;
            scheduleReload(400);
        });
    }

    /* ── paper size ── */
    window.setPaperSize = function (size) {
        S.paperSize = size;
        document.getElementById('btn-a4').classList.toggle('active', size === 'a4');
        document.getElementById('btn-a5').classList.toggle('active', size === 'a5');
        scheduleReload(400);
    };

    window.setPaperOrientation = function (orientation) {
        S.paperOrientation = orientation;
        document.getElementById('btn-portrait').classList.toggle('active', orientation === 'portrait');
        document.getElementById('btn-landscape').classList.toggle('active', orientation === 'landscape');
        scheduleReload(400);
    };

    /* ── save ── */
    var saveBtn = document.getElementById('save-btn');
    var saveMsg = document.getElementById('save-msg');
    if (saveBtn) {
        saveBtn.addEventListener('click', function () {
            saveBtn.disabled = true;
            saveBtn.textContent = 'جاري الحفظ...';
            fetch(SAVE_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
                body: JSON.stringify({
                    scale:          S.scale,
                    paper_size:     S.paperSize,
                    paper_orientation: S.paperOrientation,
                    content_top:    S.top,
                    content_bottom: S.bottom,
                    content_width:  S.width,
                    content_scale:  S.contentScale,
                    font_scale:     S.fontScale,
                    offset_x:       S.offsetX,
                    offset_y:       0,
                    hide_header:    S.hideHeader ? 1 : 0,
                    hide_footer:    S.hideFooter ? 1 : 0,
                    invoice_type:   CTX.invoiceType,
                    format:         CTX.format,
                }),
            }).then(function(r){ return r.json(); })
              .then(function(){
                  saveBtn.textContent = 'حفظ الإعدادات';
                  saveBtn.disabled = false;
                  if (saveMsg) {
                      saveMsg.classList.remove('d-none');
                      setTimeout(function(){ saveMsg.classList.add('d-none'); }, 3000);
                  }
              }).catch(function(){
                  saveBtn.textContent = 'حفظ الإعدادات';
                  saveBtn.disabled = false;
                  alert('فشل الحفظ، حاول مرة أخرى.');
              });
        });
    }

    /* ── context selector (invoice type + format) ── */
    function reloadWithContext(invoiceType, format) {
        var u = new URL(window.location.href);
        u.searchParams.set('invoice_type', invoiceType);
        u.searchParams.set('format', format);
        window.location.href = u.toString();
    }

    var ctxTypeSel = document.getElementById('ctx-invoice-type');
    if (ctxTypeSel) {
        ctxTypeSel.addEventListener('change', function () {
            reloadWithContext(this.value, CTX.format);
        });
    }
    var ctxA4 = document.getElementById('ctx-a4');
    var ctxA5 = document.getElementById('ctx-a5');
    if (ctxA4) ctxA4.addEventListener('click', function () {
        if (CTX.format !== 'a4') reloadWithContext(CTX.invoiceType, 'a4');
    });
    if (ctxA5) ctxA5.addEventListener('click', function () {
        if (CTX.format !== 'a5') reloadWithContext(CTX.invoiceType, 'a5');
    });

    /* ── reset ── */
    var resetBtn = document.getElementById('reset-btn');
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            if (!confirm('إعادة ضبط القيم الافتراضية؟')) return;
            S.scale = 1; S.offsetX = 0; S.top = 50; S.bottom = 20; S.width = 100; S.contentScale = 1; S.fontScale = 1; S.hideHeader = true; S.hideFooter = true;
            ['s-scale','s-offset-x','s-top','s-bottom','s-width','s-content-scale','s-font-scale'].forEach(function(id) {
                var el = document.getElementById(id);
                if (!el) return;
                var key = id.replace('s-','').replace('-','');
                var map = {'scale':'scale','offsetx':'offsetX','top':'top','bottom':'bottom','width':'width','contentscale':'contentScale','fontscale':'fontScale'};
                if (el) el.value = S[map[key]] !== undefined ? S[map[key]] : el.value;
            });
            document.getElementById('s-scale').value    = S.scale;
            document.getElementById('s-offset-x').value = S.offsetX;
            document.getElementById('s-top').value      = S.top;
            document.getElementById('s-bottom').value   = S.bottom;
            document.getElementById('s-width').value    = S.width;
            document.getElementById('s-content-scale').value = S.contentScale;
            document.getElementById('s-font-scale').value = S.fontScale;
            if (swHide) swHide.checked = S.hideHeader;
            if (swHideFooter) swHideFooter.checked = S.hideFooter;
            document.getElementById('v-scale').textContent   = '100%';
            document.getElementById('v-offset-x').textContent= '0%';
            document.getElementById('v-top').textContent     = '50mm';
            document.getElementById('v-bottom').textContent  = '20mm';
            document.getElementById('v-width').textContent   = '100%';
            document.getElementById('v-content-scale').textContent = '100%';
            document.getElementById('v-font-scale').textContent = '100%';
            scheduleReload(200);
        });
    }
})();
</script>
@endif

@endsection
