(function (window, document) {
    'use strict';

    function normalizeUrl(url, params) {
        var target = new URL(url, window.location.origin);
        if (params) {
            params.forEach(function (value, key) {
                target.searchParams.append(key, value);
            });
        }
        return target.toString();
    }

    function open(url, params, options) {
        options = options || {};
        var popup = window.open('', options.target || '_blank');
        if (!popup) {
            alert(options.blockedMessage || 'الرجاء السماح بالنوافذ المنبثقة لهذا الموقع');
            return null;
        }

        popup.location.href = normalizeUrl(url, params);
        return popup;
    }

    function formParams(form, options) {
        options = options || {};
        var params = new URLSearchParams(new FormData(form));
        params.delete('_token');
        params.delete('_method');

        if (options.autoPrint) {
            params.set('auto_print', '1');
        } else {
            params.delete('auto_print');
        }

        if (options.format) {
            params.set('format', options.format);
        }

        return params;
    }

    function openFromForm(form, url, options) {
        return open(url, formParams(form, options), options);
    }

    function navigate(url, params) {
        window.location.href = normalizeUrl(url, params);
    }

    function navigateFromForm(form, url, options) {
        navigate(url, formParams(form, options));
    }

    function formCanSubmit(form) {
        return !form.reportValidity || form.reportValidity();
    }

    function submitNative(form) {
        HTMLFormElement.prototype.submit.call(form);
    }

    function withTemporaryTarget(form, target, callback) {
        var previousTarget = form.getAttribute('target');

        form.setAttribute('target', target);
        callback();

        setTimeout(function () {
            if (previousTarget === null) {
                form.removeAttribute('target');
            } else {
                form.setAttribute('target', previousTarget);
            }
        }, 0);
    }

    function submitFormToTarget(form, target, options) {
        options = options || {};
        target = target || '_self';

        if (!formCanSubmit(form)) {
            return null;
        }

        if (target === '_blank') {
            var popupName = 'erp-report-preview-' + Date.now();
            var popup = window.open('', popupName);

            if (!popup) {
                alert(options.blockedMessage || 'الرجاء السماح بالنوافذ المنبثقة لهذا الموقع');
                return null;
            }

            withTemporaryTarget(form, popupName, function () {
                submitNative(form);
            });

            return popup;
        }

        withTemporaryTarget(form, target, function () {
            submitNative(form);
        });

        return null;
    }

    var currentPagePrintPending = false;

    function printCurrentPage(options) {
        options = options || {};

        if (currentPagePrintPending) {
            return false;
        }

        currentPagePrintPending = true;

        var unlock = function () {
            setTimeout(function () {
                currentPagePrintPending = false;
            }, options.cooldown || 800);
        };

        window.addEventListener('afterprint', unlock, { once: true });

        setTimeout(function () {
            try {
                window.print();
            } finally {
                setTimeout(function () {
                    currentPagePrintPending = false;
                }, options.fallbackCooldown || 2000);
            }
        }, options.delay || 0);

        return true;
    }

    function printFormInHiddenFrame(form, options) {
        options = options || {};

        if (!formCanSubmit(form)) {
            return null;
        }

        var previousFrame = document.getElementById('erp-print-post-frame');
        if (previousFrame) {
            previousFrame.remove();
        }

        var frameName = 'erp-print-post-frame-' + Date.now();
        var frame = document.createElement('iframe');
        var submitted = false;
        var printed = false;

        frame.id = 'erp-print-post-frame';
        frame.name = frameName;
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';
        frame.setAttribute('aria-hidden', 'true');

        frame.addEventListener('load', function () {
            if (!submitted || printed) {
                return;
            }

            try {
                if (frame.contentWindow && frame.contentWindow.location.href === 'about:blank') {
                    return;
                }
            } catch (error) {
                return;
            }

            printed = true;

            setTimeout(function () {
                try {
                    if (frame.contentWindow) {
                        frame.contentWindow.addEventListener('afterprint', function () {
                            setTimeout(function () {
                                if (frame.parentNode) {
                                    frame.parentNode.removeChild(frame);
                                }
                            }, 300);
                        }, { once: true });

                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    }
                } catch (error) {
                    alert(options.printErrorMessage || 'تعذر فتح نافذة الطباعة.');
                }
            }, options.delay || 300);
        });

        document.body.appendChild(frame);

        submitted = true;
        withTemporaryTarget(form, frameName, function () {
            submitNative(form);
        });

        return frame;
    }

    function printInHiddenFrame(url, params, options) {
        options = options || {};

        var previousFrame = document.getElementById('erp-print-frame');
        if (previousFrame) {
            previousFrame.remove();
        }

        var frame = document.createElement('iframe');
        var printed = false;
        frame.id = 'erp-print-frame';
        frame.name = 'erp-print-frame';
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';
        frame.setAttribute('aria-hidden', 'true');

        frame.addEventListener('load', function () {
            if (printed) {
                return;
            }

            try {
                if (frame.contentWindow && frame.contentWindow.location.href === 'about:blank') {
                    return;
                }
            } catch (error) {
                return;
            }

            printed = true;

            setTimeout(function () {
                try {
                    if (frame.contentWindow) {
                        frame.contentWindow.addEventListener('afterprint', function () {
                            setTimeout(function () {
                                if (frame.parentNode) {
                                    frame.parentNode.removeChild(frame);
                                }
                            }, 300);
                        }, { once: true });
                    }
                    frame.contentWindow.focus();
                    frame.contentWindow.print();
                } catch (error) {
                    alert(options.printErrorMessage || 'تعذر فتح نافذة الطباعة.');
                }
            }, 300);
        });

        document.body.appendChild(frame);
        var target = new URL(url, window.location.origin);
        target.searchParams.delete('auto_print');
        if (params) {
            params.delete('auto_print');
        }
        frame.src = normalizeUrl(target.toString(), params);

        return frame;
    }

    function printFrameFromForm(form, url, options) {
        var frameOptions = Object.assign({}, options, { autoPrint: false });

        return printInHiddenFrame(url, formParams(form, frameOptions), options);
    }

    function printHtml(html, options) {
        options = options || {};

        var previousFrame = document.getElementById('erp-print-html-frame');
        if (previousFrame) {
            previousFrame.remove();
        }

        var frame = document.createElement('iframe');
        frame.id = 'erp-print-html-frame';
        frame.style.position = 'fixed';
        frame.style.right = '0';
        frame.style.bottom = '0';
        frame.style.width = '0';
        frame.style.height = '0';
        frame.style.border = '0';
        frame.style.opacity = '0';
        frame.style.pointerEvents = 'none';
        frame.setAttribute('aria-hidden', 'true');

        document.body.appendChild(frame);

        var frameDocument = frame.contentWindow.document;
        frameDocument.open();
        frameDocument.write(html);
        frameDocument.close();

        setTimeout(function () {
            try {
                frame.contentWindow.addEventListener('afterprint', function () {
                    setTimeout(function () {
                        if (frame.parentNode) {
                            frame.parentNode.removeChild(frame);
                        }
                    }, 300);
                }, { once: true });
                frame.contentWindow.focus();
                frame.contentWindow.print();
            } catch (error) {
                alert(options.printErrorMessage || 'تعذر فتح نافذة الطباعة.');
            }
        }, options.delay || 250);

        return frame;
    }

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-print-open]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        var formSelector = trigger.getAttribute('data-print-form');
        var form = formSelector ? document.querySelector(formSelector) : trigger.closest('form');
        var url = trigger.getAttribute('data-print-url') || trigger.getAttribute('href');

        if (!url) {
            return;
        }

        var options = {
            autoPrint: trigger.getAttribute('data-auto-print') === '1',
            format: trigger.getAttribute('data-print-format') || '',
            blockedMessage: trigger.getAttribute('data-blocked-message') || undefined,
            target: trigger.getAttribute('data-print-target') || '_blank',
        };

        if (!form) {
            if (options.target === '_self') {
                navigate(url, null);
                return;
            }

            if (options.target === '_iframe') {
                printInHiddenFrame(url, null, options);
                return;
            }

            open(url, null, options);
            return;
        }

        if (options.target === '_self') {
            navigateFromForm(form, url, options);
            return;
        }

        if (options.target === '_iframe') {
            printFrameFromForm(form, url, options);
            return;
        }

        openFromForm(form, url, options);
    });

    document.addEventListener('click', function (event) {
        var trigger = event.target.closest('[data-print-submit]');
        if (!trigger) {
            return;
        }

        event.preventDefault();

        var formSelector = trigger.getAttribute('data-print-form');
        var form = formSelector ? document.querySelector(formSelector) : trigger.closest('form');

        if (!form) {
            return;
        }

        var options = {
            blockedMessage: trigger.getAttribute('data-blocked-message') || undefined,
            target: trigger.getAttribute('data-print-target') || '_self',
        };

        if (options.target === '_iframe') {
            printFormInHiddenFrame(form, options);
            return;
        }

        submitFormToTarget(form, options.target, options);
    });

    window.ErpPrint = {
        open: open,
        navigate: navigate,
        printCurrentPage: printCurrentPage,
        submitFormToTarget: submitFormToTarget,
        printFormInHiddenFrame: printFormInHiddenFrame,
        printInHiddenFrame: printInHiddenFrame,
        printHtml: printHtml,
        formParams: formParams,
        openFromForm: openFromForm,
        navigateFromForm: navigateFromForm,
        printFrameFromForm: printFrameFromForm,
    };
})(window, document);
