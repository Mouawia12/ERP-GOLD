{{-- shrink-to-fit: يضمن بقاء كل صفحة ضمن ارتفاع الورقة (مع المناطق الثابتة).
     يقلّص --invoice-print-scale فقط عند الفيض (بأرضية 0.75) ولا يكبّر أبداً، فالتكبير
     اليدوي (font_scale) يبقى آمناً ولا ينزل المحتوى لصفحة ثانية أو يتداخل مع المناطق. --}}
<script>
(function () {
    'use strict';
    var FLOOR = 0.75;
    var MM_TO_PX = 96 / 25.4;
    var root = document.documentElement;
    var baseScale = parseFloat(getComputedStyle(root).getPropertyValue('--invoice-print-scale')) || 1;

    function pageHeightPx() {
        var b = document.body;
        // A5 أفقي = ارتفاع 148مم، A4 طولي = ارتفاع 297مم
        var hMm = b.classList.contains('invoice-print-format-a4') ? 297 : 148;
        return hMm * MM_TO_PX;
    }

    function fit() {
        var pages = document.querySelectorAll('.page');
        if (!pages.length) return;
        /* القياس دائماً من المقياس الأساسي (إعداد المستخدم) لتجنّب التراكم */
        root.style.setProperty('--invoice-print-scale', baseScale);
        var target = pageHeightPx();
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
