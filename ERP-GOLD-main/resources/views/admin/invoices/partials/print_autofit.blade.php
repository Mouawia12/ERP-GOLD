{{-- shrink-to-fit لفواتير A5: يضمن بقاء كل صفحة ضمن حدود A5. يقلّص
     --invoice-print-scale فقط عند الفيض (بأرضية 0.85) ولا يكبّر أبداً، فالتكبير
     اليدوي (font_scale) يبقى آمناً. يُترك مسار letterhead لضبطه الخاص فلا يعمل معه. --}}
@if(empty($bgImageUrl))
<script>
(function () {
    'use strict';
    var FLOOR = 0.85;
    var MM_TO_PX = 96 / 25.4;
    var root = document.documentElement;
    var baseScale = parseFloat(getComputedStyle(root).getPropertyValue('--invoice-print-scale')) || 1;

    function targetHeightPx() {
        var b = document.body;
        var ready = b.classList.contains('invoice-paper-ready');
        var landscape = ready || b.classList.contains('invoice-orientation-landscape');
        var hMm = landscape ? 148 : 210;
        var marginMm = ready ? 0 : 5; /* @page margin أعلى+أسفل */
        return (hMm - 2 * marginMm) * MM_TO_PX;
    }

    function fit() {
        var pages = document.querySelectorAll('.page');
        if (!pages.length) return;
        /* القياس دائماً من المقياس الأساسي (إعداد المستخدم) لتجنّب التراكم */
        root.style.setProperty('--invoice-print-scale', baseScale);
        var target = targetHeightPx();
        var worst = 1;
        pages.forEach(function (p) {
            var h = p.scrollHeight;
            if (h > target + 2) {
                var f = target / h;
                if (f < worst) worst = f;
            }
        });
        if (worst < 1) {
            root.style.setProperty('--invoice-print-scale', Math.max(FLOOR, baseScale * worst));
        }
    }

    function fireFit() {
        fit();
        setTimeout(fit, 120);
        setTimeout(fit, 400);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', fireFit);
    } else {
        fireFit();
    }
    window.addEventListener('load', fireFit);
    window.addEventListener('beforeprint', fit);
    var rT = null;
    window.addEventListener('resize', function () {
        clearTimeout(rT);
        rT = setTimeout(fit, 150);
    });
})();
</script>
@endif
