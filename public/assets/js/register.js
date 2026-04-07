    document.addEventListener('DOMContentLoaded', function() {
        // Password visibility toggle
        function setupPasswordToggle(toggleId, inputId) {
            const toggle = document.getElementById(toggleId);
            const input = document.getElementById(inputId);
            
            if (toggle && input) {
                toggle.addEventListener('click', function() {
                    const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                    input.setAttribute('type', type);
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.toggle('ri-eye-line');
                        icon.classList.toggle('ri-eye-off-line');
                    }
                });
            }
        }

        setupPasswordToggle('togglePassword', 'password');
        setupPasswordToggle('toggleConfirmPassword', 'password_confirmation');

        // Password strength meter
        const passwordInput = document.getElementById('password');
        const strengthBar = document.getElementById('passwordStrengthBar');
        const strengthText = document.getElementById('passwordStrengthText');

        function checkPasswordStrength(password) {
            let strength = 0;
            let message = '';
            
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/\d/)) strength++;
            if (password.match(/[^a-zA-Z\d]/)) strength++;
            
            switch(strength) {
                case 0:
                case 1:
                    message = 'Weak password';
                    strengthBar.style.backgroundColor = '#dc3545';
                    strengthBar.style.width = '25%';
                    break;
                case 2:
                    message = 'Fair password';
                    strengthBar.style.backgroundColor = '#ffc107';
                    strengthBar.style.width = '50%';
                    break;
                case 3:
                    message = 'Good password';
                    strengthBar.style.backgroundColor = '#28a745';
                    strengthBar.style.width = '75%';
                    break;
                case 4:
                    message = 'Strong password';
                    strengthBar.style.backgroundColor = '#28a745';
                    strengthBar.style.width = '100%';
                    break;
            }
            
            strengthText.textContent = message;
            return strength >= 2;
        }

        if (passwordInput) {
            passwordInput.addEventListener('input', function() {
                checkPasswordStrength(this.value);
            });
        }

        // Form validation
        const registerForm = document.getElementById('registerForm');
        const registerBtn = document.getElementById('registerBtn');

        function validateField(field, validationFn, errorElement, errorMessage) {
            const value = field.value.trim();
            if (!validationFn(value)) {
                errorElement.style.display = 'flex';
                errorElement.querySelector('span').textContent = errorMessage;
                field.classList.add('error');
                return false;
            } else {
                errorElement.style.display = 'none';
                field.classList.remove('error');
                return true;
            }
        }

        function validateName(name) {
            return name.length >= 2 && /^[a-zA-Z\s]+$/.test(name);
        }

        function validateEmail(email) {
            return /^[^\s@]+@([^\s@]+\.)+[^\s@]+$/.test(email);
        }

        function validatePhone(phone) {
            return /^[\d\s\-+()]{10,}$/.test(phone);
        }

        function validatePassword(password) {
            return password.length >= 8;
        }

        function validateConfirmPassword(password, confirmPassword) {
            return password === confirmPassword && confirmPassword.length > 0;
        }

        function validateTerms(termsChecked) {
            return termsChecked === true;
        }

        if (registerForm) {
            const nameField = document.getElementById('name');
            const emailField = document.getElementById('email');
            const phoneField = document.getElementById('phone');
            const passwordField = document.getElementById('password');
            const confirmField = document.getElementById('password_confirmation');
            const termsCheckbox = document.getElementById('terms');

            const nameError = document.getElementById('nameError');
            const emailError = document.getElementById('emailError');
            const phoneError = document.getElementById('phoneError');
            const passwordError = document.getElementById('passwordError');
            const confirmError = document.getElementById('confirmPasswordError');
            const termsError = document.getElementById('termsError');

            if (nameField) {
                nameField.addEventListener('blur', function() {
                    validateField(this, validateName, nameError, 'Please enter a valid name (at least 2 characters, letters only)');
                });
            }

            if (emailField) {
                emailField.addEventListener('blur', function() {
                    validateField(this, validateEmail, emailError, 'Please enter a valid email address');
                });
            }

            if (phoneField) {
                phoneField.addEventListener('blur', function() {
                    validateField(this, validatePhone, phoneError, 'Please enter a valid phone number (at least 10 digits)');
                });
            }

            if (passwordField) {
                passwordField.addEventListener('blur', function() {
                    validateField(this, validatePassword, passwordError, 'Password must be at least 8 characters');
                });
            }

            if (confirmField && passwordField) {
                confirmField.addEventListener('blur', function() {
                    validateField(this, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                });
                
                passwordField.addEventListener('input', function() {
                    if (confirmField.value) {
                        validateField(confirmField, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                    }
                });
            }

            if (termsCheckbox) {
                termsCheckbox.addEventListener('change', function() {
                    validateField(this, (val) => validateTerms(this.checked), termsError, 'You must agree to the terms and conditions');
                });
            }

            registerForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                isValid &= validateField(nameField, validateName, nameError, 'Please enter a valid name (at least 2 characters, letters only)');
                isValid &= validateField(emailField, validateEmail, emailError, 'Please enter a valid email address');
                isValid &= validateField(phoneField, validatePhone, phoneError, 'Please enter a valid phone number (at least 10 digits)');
                isValid &= validateField(passwordField, validatePassword, passwordError, 'Password must be at least 8 characters');
                isValid &= validateField(confirmField, (val) => validateConfirmPassword(passwordField.value, val), confirmError, 'Passwords do not match');
                isValid &= validateField(termsCheckbox, (val) => validateTerms(termsCheckbox.checked), termsError, 'You must agree to the terms and conditions');
                
                if (!isValid) {
                    e.preventDefault();
                    registerBtn.classList.remove('loading');
                    // Scroll to first error
                    const firstError = document.querySelector('.field-error[style*="display: flex"]');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                } else {
                    registerBtn.classList.add('loading');
                    registerBtn.disabled = true;
                }
            });
        }

        // Real-time validation styling
        const inputs = document.querySelectorAll('.form-group input');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                if (this.value.trim() !== '') {
                    this.style.borderColor = '#28a745';
                } else {
                    this.style.borderColor = '#e5e7eb';
                }
            });
            
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = '#e5e7eb';
                }
            });
        });
    });