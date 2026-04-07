document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('profile_picture');
    const passwordToggles = document.querySelectorAll('.password-toggle');

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

    passwordToggles.forEach(function (toggle) {
        toggle.addEventListener('click', function () {
            const targetId = this.getAttribute('data-target');
            const input = document.getElementById(targetId);
            const icon = this.querySelector('i');

            if (!input || !icon) return;

            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            icon.className = isPassword ? 'ri-eye-off-line' : 'ri-eye-line';
        });
    });
});
