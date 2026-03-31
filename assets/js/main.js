document.addEventListener('DOMContentLoaded', function () {
    const tabButtons = document.querySelectorAll('.tab-btn');
    const authForms = document.querySelectorAll('.auth-form');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            authForms.forEach(form => form.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(`${tabId}-form`).classList.add('active');
        });
    });

    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();

            const targetId = this.getAttribute('href');
            if (targetId === '#') return;

            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                window.scrollTo({
                    top: targetElement.offsetTop - 80,
                    behavior: 'smooth'
                });
            }
        });
    });

    const registerForm = document.getElementById('register-form');
    if (registerForm) {
        registerForm.addEventListener('submit', function (e) {
            const password = document.getElementById('reg-password').value;
            const confirmPassword = document.getElementById('reg-password-confirm').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Пароли не совпадают!');
                return false;
            }

            if (password.length < 6) {
                e.preventDefault();
                alert('Пароль должен содержать не менее 6 символов!');
                return false;
            }
        });
    }
});

document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.error-close').forEach(button => {
        button.addEventListener('click', function () {
            const notification = this.closest('.error-notification');
            if (notification) {
                notification.classList.add('hiding');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        });
    });

    setTimeout(() => {
        document.querySelectorAll('.error-notification').forEach(notification => {
            if (!notification.classList.contains('hiding')) {
                notification.classList.add('hiding');
                setTimeout(() => {
                    notification.remove();
                }, 300);
            }
        });
    }, 8000);

    document.querySelectorAll('.error-notification').forEach(notification => {
        notification.addEventListener('mouseenter', function () {
            this.style.transform = 'translateY(-2px)';
            this.style.boxShadow = '0 6px 16px rgba(0, 0, 0, 0.12)';
        });

        notification.addEventListener('mouseleave', function () {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 4px 12px rgba(0, 0, 0, 0.08)';
        });
    });
});