<script>
    (function () {
        var forms = document.querySelectorAll('.branch-validation-form');

        if (!forms.length) {
            return;
        }

        var emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

        forms.forEach(function (form) {
            var fields = form.querySelectorAll('[data-branch-validate="1"]');

            function feedbackFor(input) {
                return form.querySelector('[data-feedback-for="' + input.name + '"]');
            }

            function clearFieldError(input) {
                var feedback = feedbackFor(input);

                input.classList.remove('is-invalid');

                if (feedback) {
                    feedback.textContent = '';
                    feedback.style.display = 'none';
                }
            }

            function setFieldError(input, message) {
                var feedback = feedbackFor(input);

                input.classList.add('is-invalid');

                if (feedback) {
                    feedback.textContent = message;
                    feedback.style.display = 'block';
                }
            }

            function normalizedValue(input) {
                return (input.value || '').trim();
            }

            function validateField(input) {
                var value = normalizedValue(input);
                var requiredMessage = input.dataset.requiredMessage || ((input.dataset.label || 'هذا الحقل') + ' مطلوب.');
                var digitsMessage = input.dataset.digitsMessage || ((input.dataset.label || 'هذا الحقل') + ' غير صالح.');
                var emailMessage = input.dataset.emailMessage || 'القيمة المدخلة غير صحيحة.';

                if (input.dataset.digits) {
                    input.value = input.value.replace(/\D+/g, '');
                    value = normalizedValue(input);
                }

                if (input.dataset.required === '1' && value === '') {
                    setFieldError(input, requiredMessage);
                    return false;
                }

                if (value === '') {
                    clearFieldError(input);
                    return true;
                }

                if (input.dataset.email === '1' && !emailPattern.test(value)) {
                    setFieldError(input, emailMessage);
                    return false;
                }

                if (input.dataset.digits) {
                    var digits = parseInt(input.dataset.digits, 10);

                    if (!/^\d+$/.test(value) || value.length !== digits) {
                        setFieldError(input, digitsMessage);
                        return false;
                    }
                }

                clearFieldError(input);
                return true;
            }

            fields.forEach(function (input) {
                ['input', 'change', 'blur'].forEach(function (eventName) {
                    input.addEventListener(eventName, function () {
                        validateField(input);
                    });
                });
            });

            form.addEventListener('submit', function (event) {
                var firstInvalid = null;

                fields.forEach(function (input) {
                    if (!validateField(input) && !firstInvalid) {
                        firstInvalid = input;
                    }
                });

                if (firstInvalid) {
                    event.preventDefault();
                    firstInvalid.focus();
                    firstInvalid.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                }
            });
        });
    })();
</script>
