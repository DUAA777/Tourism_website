document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('profile_picture');
    const resetModal = document.getElementById('profileResetConfirm');
    const resetForm = document.querySelector('.js-profile-reset-form');
    const resetTitle = document.getElementById('profileResetConfirmTitle');
    const resetMessage = document.getElementById('profileResetConfirmMessage');
    const resetConfirmButton = resetModal?.querySelector('[data-reset-confirm]');
    const resetCancelButtons = resetModal?.querySelectorAll('[data-reset-cancel]') || [];

    if (fileInput) {
        fileInput.addEventListener('change', function () {
            const [file] = fileInput.files;
            if (!file) return;

            const reader = new FileReader();
            reader.onload = function (event) {
                const currentPreview = document.getElementById('profileAvatarPreview');
                if (!currentPreview) return;

                if (currentPreview.tagName.toLowerCase() === 'img') {
                    currentPreview.setAttribute('src', event.target.result);
                } else {
                    const img = document.createElement('img');
                    img.id = 'profileAvatarPreview';
                    img.className = 'profile-head__avatar';
                    img.alt = 'Profile preview';
                    img.src = event.target.result;
                    currentPreview.replaceWith(img);
                }
            };
            reader.readAsDataURL(file);
        });
    }

    if (resetModal && resetForm && resetConfirmButton) {
        const openResetModal = function () {
            resetTitle.textContent = resetForm.dataset.resetTitle || 'Request password reset?';
            resetMessage.textContent = resetForm.dataset.resetMessage || 'We will send a secure password reset link to your email.';
            resetConfirmButton.textContent = resetForm.dataset.resetConfirm || 'Send Reset Link';
            resetModal.classList.add('is-open');
            resetModal.setAttribute('aria-hidden', 'false');
            document.body.classList.add('profile-modal-open');
            resetConfirmButton.focus();
        };

        const closeResetModal = function () {
            resetModal.classList.remove('is-open');
            resetModal.setAttribute('aria-hidden', 'true');
            document.body.classList.remove('profile-modal-open');
        };

        resetForm.addEventListener('submit', function (event) {
            if (resetForm.dataset.confirmed === 'true') {
                return;
            }

            event.preventDefault();
            openResetModal();
        });

        resetConfirmButton.addEventListener('click', function () {
            resetForm.dataset.confirmed = 'true';
            closeResetModal();
            resetForm.submit();
        });

        resetCancelButtons.forEach(function (button) {
            button.addEventListener('click', closeResetModal);
        });

        resetModal.addEventListener('click', function (event) {
            if (event.target === resetModal) {
                closeResetModal();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && resetModal.classList.contains('is-open')) {
                closeResetModal();
            }
        });
    }
});
