<script>
    (function () {
        var params = new URLSearchParams(window.location.search);
        if (params.get('auto_print') !== '1') {
            return;
        }

        window.addEventListener('load', function () {
            setTimeout(function () {
                window.focus();
                window.print();
            }, 300);
        });
    })();
</script>
