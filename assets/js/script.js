/**
 * EventEase - Interactive JavaScript
 * Form validation, live searching, confirmation modals, and demo helpers
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Auto dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) {
                bsAlert.close();
            }
        }, 6000);
    });

    // 2. Generic client-side live filter for tables and card grids
    const searchInputs = document.querySelectorAll('[data-filter-target]');
    searchInputs.forEach(function (input) {
        const targetSelector = input.getAttribute('data-filter-target');
        const itemSelector = input.getAttribute('data-filter-item') || 'tr';

        input.addEventListener('keyup', function () {
            const query = input.value.toLowerCase().trim();
            const container = document.querySelector(targetSelector);
            if (!container) return;

            const items = container.querySelectorAll(itemSelector);
            let matchCount = 0;

            items.forEach(function (item) {
                const text = item.innerText.toLowerCase();
                if (text.includes(query)) {
                    item.style.display = '';
                    matchCount++;
                } else {
                    item.style.display = 'none';
                }
            });

            // Empty state feedback if available
            const emptyNotice = document.querySelector(targetSelector + '-empty');
            if (emptyNotice) {
                emptyNotice.style.display = (matchCount === 0) ? 'block' : 'none';
            }
        });
    });

    // 3. Password match validation on registration form
    const regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            const password = document.getElementById('reg_password');
            const confirm = document.getElementById('reg_confirm');
            if (password && confirm && password.value !== confirm.value) {
                e.preventDefault();
                alert('Passwords do not match. Please re-enter matching passwords.');
                confirm.focus();
            }
        });
    }

    // 4. Future event date validation on event creation/editing
    const eventDateInput = document.getElementById('event_date');
    if (eventDateInput) {
        // Set minimum datetime to current time
        const now = new Date();
        const year = now.getFullYear();
        const month = String(now.getMonth() + 1).padStart(2, '0');
        const day = String(now.getDate()).padStart(2, '0');
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');
        const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
        eventDateInput.min = minDateTime;
    }
});

/**
 * Quick-fill demo credentials on login page (Ideal for MCA viva presentation!)
 */
function quickLogin(email, password) {
    const emailField = document.querySelector('input[name="email"]');
    const passwordField = document.querySelector('input[name="password"]');
    if (emailField && passwordField) {
        emailField.value = email;
        passwordField.value = password;
        // Visual indicator
        emailField.classList.add('is-valid');
        passwordField.classList.add('is-valid');
        setTimeout(() => {
            emailField.classList.remove('is-valid');
            passwordField.classList.remove('is-valid');
        }, 1200);
    }
}

/**
 * Confirm action dialog helper
 */
function confirmAction(message) {
    return confirm(message || 'Are you sure you want to proceed with this action?');
}

/**
 * Print registration pass
 */
function printPass(elementId) {
    const printContents = document.getElementById(elementId).innerHTML;
    const originalContents = document.body.innerHTML;
    document.body.innerHTML = printContents;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}
