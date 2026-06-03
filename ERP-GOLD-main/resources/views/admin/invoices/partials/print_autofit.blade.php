{{-- إجبار صفحة واحدة: يقيس ارتفاع محتوى الفاتورة مقابل الشريط الأوسط المتاح
     (ارتفاع الورقة − منطقة الترويسة − منطقة التذييل) ويُصغّر --invoice-print-scale
     تدريجياً حتى يتسع في صفحة واحدة. لا يكبّر أبداً. يخص A5 (خطوطه مبنية على المقياس). --}}
<script>
(function () {
    'use strict';
    var MM_TO_PX = 96 / 25.4;
    var root = document.documentElement;
    var base = parseFloat(getComputedStyle(root).getPropertyValue('--invoice-print-scale')) || 1;
    var FLOOR = 0.55;

    function bandPx() {
        var b = document.body;
        // الشريط الأوسط المتاح للمحتوى (مطروحاً منه منطقتا الترويسة والتذييل)
        var mm = b.classList.contains('invoice-print-format-a4') ? (297 - 40 - 25) : (148 - 36 - 20);
        return mm * MM_TO_PX;
    }

    function fit() {
        var contents = document.querySelectorAll('.page-content');
        if (!contents.length) return;
        var band = bandPx();
        var cur = base;
        root.style.setProperty('--invoice-print-scale', cur);

        // تكرار بسيط: قِس الأطول، صغّر بالنسبة، أعد القياس (يتقارب بسرعة)
        for (var i = 0; i < 4; i++) {
            var maxH = 0;
            contents.forEach(function (c) {
                if (c.scrollHeight > maxH) maxH = c.scrollHeight;
            });
            if (maxH <= band + 2) break;
            cur = Math.max(FLOOR, cur * (band / maxH) * 0.985);
            root.style.setProperty('--invoice-print-scale', cur);
            if (cur <= FLOOR) break;
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
