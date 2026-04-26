<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Admin Panel - @yield('title')</title>

    <link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <link rel="stylesheet" href="{{ asset('/assets/css/admin.css') }}?v={{ filemtime(public_path('assets/css/admin.css')) }}">
    
    @stack('styles')

    <link rel="stylesheet" href="{{ asset('/assets/css/admin-overrides.css') }}?v={{ filemtime(public_path('assets/css/admin-overrides.css')) }}">
</head>
<body>
    <div class="admin-container">
        @include('partials.sidebar')
        <div class="admin-main">
            @include('partials.header')
            <main class="admin-content">
                @if(session('success'))
                    <div class="alert alert-success">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-error">{{ session('error') }}</div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>
    <div class="admin-confirm-modal" id="adminDeleteConfirm" aria-hidden="true">
        <div class="admin-confirm-dialog" role="dialog" aria-modal="true" aria-labelledby="adminDeleteConfirmTitle">
            <div class="admin-confirm-icon">
                <i class="ri-delete-bin-line"></i>
            </div>
            <h3 id="adminDeleteConfirmTitle">Delete item?</h3>
            <p id="adminDeleteConfirmMessage">This action will remove the selected item.</p>
            <div class="admin-confirm-actions">
                <button type="button" class="btn-secondary" data-delete-cancel>Cancel</button>
                <button type="button" class="btn-danger" data-delete-confirm>Delete</button>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('adminDeleteConfirm');
            if (!modal) return;

            const titleEl = document.getElementById('adminDeleteConfirmTitle');
            const messageEl = document.getElementById('adminDeleteConfirmMessage');
            const confirmBtn = modal.querySelector('[data-delete-confirm]');
            const cancelBtns = modal.querySelectorAll('[data-delete-cancel]');
            let pendingAction = null;
            let pendingForm = null;

            function openDeleteModal(options = {}) {
                pendingAction = typeof options.onConfirm === 'function' ? options.onConfirm : null;
                pendingForm = options.form || null;

                titleEl.textContent = options.title || 'Delete item?';
                messageEl.textContent = options.message || 'This action will remove the selected item.';
                confirmBtn.textContent = options.confirmText || 'Delete';
                confirmBtn.className = options.confirmClass || 'btn-danger';

                modal.classList.add('is-open');
                modal.setAttribute('aria-hidden', 'false');
                document.body.classList.add('admin-modal-open');
                confirmBtn.focus();
            }

            function closeDeleteModal() {
                modal.classList.remove('is-open');
                modal.setAttribute('aria-hidden', 'true');
                document.body.classList.remove('admin-modal-open');
                pendingAction = null;
                pendingForm = null;
            }

            window.adminConfirmDelete = openDeleteModal;

            document.addEventListener('submit', function (event) {
                const form = event.target.closest('form.js-delete-form');
                if (!form || form.dataset.confirmed === 'true') return;

                event.preventDefault();
                openDeleteModal({
                    form,
                    title: form.dataset.deleteTitle,
                    message: form.dataset.deleteMessage,
                    confirmText: form.dataset.deleteConfirm,
                    confirmClass: form.dataset.deleteConfirmClass || 'btn-danger'
                });
            });

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('[data-delete-target]');
                if (!trigger) return;

                const form = document.getElementById(trigger.dataset.deleteTarget);
                if (!form) return;

                event.preventDefault();
                openDeleteModal({
                    form,
                    title: trigger.dataset.deleteTitle || form.dataset.deleteTitle,
                    message: trigger.dataset.deleteMessage || form.dataset.deleteMessage,
                    confirmText: trigger.dataset.deleteConfirm || form.dataset.deleteConfirm,
                    confirmClass: trigger.dataset.deleteConfirmClass || form.dataset.deleteConfirmClass || 'btn-danger'
                });
            });

            confirmBtn.addEventListener('click', function () {
                if (pendingAction) {
                    const action = pendingAction;
                    closeDeleteModal();
                    action();
                    return;
                }

                if (pendingForm) {
                    pendingForm.dataset.confirmed = 'true';
                    pendingForm.submit();
                }
            });

            cancelBtns.forEach((button) => button.addEventListener('click', closeDeleteModal));
            modal.addEventListener('click', function (event) {
                if (event.target === modal) closeDeleteModal();
            });
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape' && modal.classList.contains('is-open')) {
                    closeDeleteModal();
                }
            });
        });
    </script>
    @stack('scripts')
</body>
</html>
