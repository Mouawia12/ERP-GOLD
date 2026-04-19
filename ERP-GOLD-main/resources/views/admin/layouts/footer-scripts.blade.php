<!-- Back-to-top -->
<a href="#top" id="back-to-top"><i class="las la-angle-double-up"></i></a>
<!-- JQuery min js -->
<script src="{{URL::asset('assets/plugins/jquery/jquery.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js')}}"></script>
<!-- Bootstrap Bundle js -->
<script src="{{URL::asset('assets/plugins/bootstrap/js/bootstrap.min.js')}}"></script>
<!-- Ionicons js -->
<script src="{{URL::asset('assets/plugins/ionicons/ionicons.js')}}"></script>
<!-- Moment js -->
<script src="{{URL::asset('assets/plugins/moment/moment.js')}}"></script>

<!-- Rating js-->
<script src="{{URL::asset('assets/plugins/rating/jquery.rating-stars.js')}}"></script>
<script src="{{URL::asset('assets/plugins/rating/jquery.barrating.js')}}"></script>
<script src="{{URL::asset('assets/js/eva-icons.min.js')}}"></script>
@yield('js')
<!-- Sticky js -->
<script src="{{URL::asset('assets/js/sticky.js')}}"></script>
<script src="{{URL::asset('assets/js/bootstrap-select.js')}}"></script>
<!-- custom js -->
<script src="{{URL::asset('assets/js/custom.js')}}"></script><!-- Left-menu js-->
<script src="{{URL::asset('assets/plugins/side-menu/sidemenu.js')}}"></script>
<!-- datatables js -->
<script src="{{URL::asset('assets/plugins/datatables/jquery.dataTables.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-bs4/js/dataTables.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-responsive/js/dataTables.responsive.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-responsive/js/responsive.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/dataTables.buttons.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.bootstrap4.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/jszip/jszip.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.html5.min.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.print.js')}}"></script>
<script src="{{URL::asset('assets/plugins/datatables-buttons/js/buttons.colVis.min.js')}}"></script> 
<script src="{{URL::asset('assets/plugins/select2/js/select2.full.min.js')}}"></script>
<style>
    .swal2-container,
    .sweet-overlay,
    .sweet-alert,
    #ui_notifIt,
    .notifit_confirm,
    .notifit_prompt,
    #erp-toast-stack,
    #erp-system-message-root {
        z-index: 200500 !important;
    }

    #erp-system-message-root {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
    }

    #erp-system-message-root.is-visible {
        display: flex;
    }

    .erp-system-message-backdrop {
        position: absolute;
        inset: 0;
        background: rgba(15, 23, 42, 0.62);
        backdrop-filter: blur(2px);
        -webkit-backdrop-filter: blur(2px);
    }

    .erp-system-message-dialog {
        position: relative;
        width: min(92vw, 460px);
        background: #ffffff;
        border: 1px solid #e6edf8;
        border-radius: 22px;
        box-shadow: 0 28px 70px rgba(15, 23, 42, 0.28);
        padding: 24px 24px 20px;
        text-align: right;
        direction: rtl;
    }

    .erp-system-message-dialog[data-type="error"] {
        border-top: 5px solid #e74c5b;
    }

    .erp-system-message-dialog[data-type="warning"] {
        border-top: 5px solid #f0ad4e;
    }

    .erp-system-message-dialog[data-type="success"] {
        border-top: 5px solid #2eb67d;
    }

    .erp-system-message-title {
        margin: 0 0 10px;
        font-size: 20px;
        font-weight: 700;
        color: #21314f;
    }

    .erp-system-message-body {
        margin: 0;
        white-space: pre-line;
        line-height: 1.8;
        font-size: 14px;
        color: #52637d;
    }

    .erp-system-message-actions {
        display: flex;
        justify-content: flex-start;
        margin-top: 18px;
    }

    .erp-system-message-btn {
        border: 0;
        border-radius: 12px;
        min-width: 110px;
        padding: 10px 18px;
        font-weight: 700;
        color: #fff;
        background: linear-gradient(135deg, #3b82f6 0%, #2356d3 100%);
        box-shadow: 0 14px 26px rgba(59, 130, 246, 0.28);
    }

    #erp-toast-stack {
        position: fixed;
        top: 20px;
        left: 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
        pointer-events: none;
    }

    .erp-toast {
        pointer-events: auto;
        width: min(360px, calc(100vw - 40px));
        border-radius: 18px;
        padding: 14px 16px;
        color: #fff;
        box-shadow: 0 20px 40px rgba(15, 23, 42, 0.22);
        direction: rtl;
    }

    .erp-toast[data-type="success"] {
        background: linear-gradient(135deg, #22c55e 0%, #1f9f57 100%);
    }

    .erp-toast[data-type="error"] {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .erp-toast[data-type="warning"] {
        background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
    }

    .erp-toast-title {
        margin: 0 0 4px;
        font-size: 15px;
        font-weight: 700;
    }

    .erp-toast-body {
        margin: 0;
        font-size: 13px;
        line-height: 1.6;
        white-space: pre-line;
        opacity: 0.96;
    }
</style>
<script>
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });
    $(function () {
        $('[data-toggle="tooltip"]').tooltip()
    });
    $('.js-example-basic-single').select2({
        placeholder: "اختر مما يلى"
    });
    $('.progress-pie-chart').each(function () {
        var $ppc = $(this),
            percent = parseInt($ppc.data('percent')),
            deg = 360 * percent / 100;
        if (percent > 50) {
            $ppc.addClass('gt-50');
        }
        if (percent <= 25) {
            $ppc.addClass('red');
        } else if (percent >= 25 && percent <= 90) {
            $ppc.addClass('orange');
        } else if (percent >= 90) {
            $ppc.addClass('green');
        }
        $ppc.find('.ppc-progress-fill').css('transform', 'rotate(' + deg + 'deg)');
        $ppc.find('.ppc-percents span').html('<cite>' + percent + '</cite>' + '%');
    });

    (function () {
        var nativeAlert = window.alert ? window.alert.bind(window) : function (message) {
            console.warn(message);
        };

        function escapeHtml(value) {
            return String(value ?? '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function sanitizeMessage(message) {
            if (message === null || typeof message === 'undefined') {
                return 'حدث خطأ غير متوقع.';
            }

            var wrapper = document.createElement('div');
            wrapper.innerHTML = String(message);

            return (wrapper.textContent || wrapper.innerText || '').trim() || 'حدث خطأ غير متوقع.';
        }

        function ensureDialogRoot() {
            var existing = document.getElementById('erp-system-message-root');

            if (existing) {
                return existing;
            }

            var root = document.createElement('div');
            root.id = 'erp-system-message-root';
            root.innerHTML = ''
                + '<div class="erp-system-message-backdrop" data-erp-close="1"></div>'
                + '<div class="erp-system-message-dialog" data-type="error" role="alertdialog" aria-modal="true" aria-labelledby="erp-system-message-title">'
                + '  <h3 class="erp-system-message-title" id="erp-system-message-title">تنبيه</h3>'
                + '  <p class="erp-system-message-body"></p>'
                + '  <div class="erp-system-message-actions">'
                + '      <button type="button" class="erp-system-message-btn" data-erp-close="1">موافق</button>'
                + '  </div>'
                + '</div>';

            root.addEventListener('click', function (event) {
                if (event.target && event.target.getAttribute('data-erp-close') === '1') {
                    hideSystemMessage();
                }
            });

            document.body.appendChild(root);

            return root;
        }

        function ensureToastStack() {
            var existing = document.getElementById('erp-toast-stack');

            if (existing) {
                return existing;
            }

            var stack = document.createElement('div');
            stack.id = 'erp-toast-stack';
            document.body.appendChild(stack);

            return stack;
        }

        function hideSystemMessage() {
            var root = document.getElementById('erp-system-message-root');

            if (!root) {
                return;
            }

            root.classList.remove('is-visible');
        }

        function showFallbackDialog(options) {
            var root = ensureDialogRoot();
            var dialog = root.querySelector('.erp-system-message-dialog');
            var title = root.querySelector('.erp-system-message-title');
            var body = root.querySelector('.erp-system-message-body');
            var button = root.querySelector('.erp-system-message-btn');

            dialog.setAttribute('data-type', options.type);
            title.textContent = options.title;
            body.textContent = options.message;
            button.textContent = options.confirmText;

            root.classList.add('is-visible');
        }

        function showToast(options) {
            var stack = ensureToastStack();
            var toast = document.createElement('div');
            toast.className = 'erp-toast';
            toast.setAttribute('data-type', options.type);
            toast.innerHTML = ''
                + '<h4 class="erp-toast-title">' + escapeHtml(options.title) + '</h4>'
                + '<p class="erp-toast-body">' + escapeHtml(options.message).replace(/\n/g, '<br>') + '</p>';

            stack.appendChild(toast);

            window.setTimeout(function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                toast.style.transition = 'opacity .2s ease, transform .2s ease';

                window.setTimeout(function () {
                    toast.remove();
                }, 220);
            }, options.duration);
        }

        function patchSweetAlert() {
            if (!window.Swal || window.Swal.__erpPatched) {
                return;
            }

            var originalFire = window.Swal.fire.bind(window.Swal);

            window.Swal.fire = function () {
                if (arguments.length === 1 && typeof arguments[0] === 'object' && arguments[0] !== null && !Array.isArray(arguments[0])) {
                    return originalFire(Object.assign({
                        zIndex: 200500,
                        confirmButtonText: 'موافق',
                        backdrop: 'rgba(15, 23, 42, 0.62)',
                    }, arguments[0]));
                }

                return originalFire.apply(window.Swal, arguments);
            };

            window.Swal.__erpPatched = true;
        }

        window.erpShowSystemMessage = function (options) {
            patchSweetAlert();

            var settings = Object.assign({
                type: 'error',
                title: 'تنبيه',
                message: 'حدث خطأ غير متوقع.',
                confirmText: 'موافق',
                mode: 'dialog',
                duration: 3200,
            }, options || {});

            settings.message = sanitizeMessage(settings.message);

            if (settings.mode === 'toast') {
                showToast(settings);
                return;
            }

            if (window.Swal && typeof window.Swal.fire === 'function') {
                return window.Swal.fire({
                    title: settings.title,
                    text: settings.message,
                    icon: settings.type === 'success' ? 'success' : (settings.type === 'warning' ? 'warning' : 'error'),
                    confirmButtonText: settings.confirmText,
                });
            }

            showFallbackDialog(settings);
        };

        window.erpShowError = function (message, title) {
            return window.erpShowSystemMessage({
                type: 'error',
                title: title || 'خطأ',
                message: message,
            });
        };

        window.erpShowWarning = function (message, title) {
            return window.erpShowSystemMessage({
                type: 'warning',
                title: title || 'تنبيه',
                message: message,
            });
        };

        window.erpShowSuccessToast = function (message, title) {
            return window.erpShowSystemMessage({
                type: 'success',
                title: title || 'تمت العملية',
                message: message,
                mode: 'toast',
            });
        };

        window.alert = function (message) {
            return window.erpShowWarning(message, 'تنبيه');
        };

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape') {
                hideSystemMessage();
            }
        });

        patchSweetAlert();
        window.__nativeAlert = nativeAlert;
    })();
</script>
<script>
 
 
$(document).ready( function () {
 
            $("#example1").DataTable({
                "responsive": true, "lengthChange": true, "autoWidth": false, 
                buttons: [
                    {
                        extend: 'copy',
                        text: '<i title="copy" class="fa fa-copy"></i>',
                    }, 
                    {
                        extend: 'excel',
                        text: '<i title="export to excel" class="fa fa-file-excel"></i>',
                    }, 
                    {
                        extend: 'print',
                        text: '<i title="print" class="fa fa-print"></i>',
                    },
                    {
                        extend: 'colvis',
                        text: '<i title="column visibility" class="fa fa-eye"></i>',
                    },  
                ],
	 
            }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');

            $('#example2').DataTable({
                "paging": false,
                "lengthChange": false,
                "searching": false,
                "ordering": true,
                "info": false,
                "autoWidth": false,
                "responsive": true, 
            }); 
        });

        // Before printing: show ALL rows in DataTable (override pagination)
        window.addEventListener('beforeprint', function () {
            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#example1')) {
                var dt = $('#example1').DataTable();
                window._dtPrintPageLen = dt.page.len();
                dt.page.len(-1).draw(false);
            }
        });

        // After printing: restore original page length
        window.addEventListener('afterprint', function () {
            if (typeof $.fn.DataTable !== 'undefined' && $.fn.DataTable.isDataTable('#example1') && typeof window._dtPrintPageLen !== 'undefined') {
                $('#example1').DataTable().page.len(window._dtPrintPageLen).draw(false);
                window._dtPrintPageLen = undefined;
            }
        });
</script>
