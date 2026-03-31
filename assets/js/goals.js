document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('goalModal');
    const openModalBtn = document.getElementById('openGoalModal');
    const openModalEmptyBtn = document.getElementById('openGoalModalEmpty');
    const closeModalBtn = document.querySelector('.modal-close');
    const goalForm = document.getElementById('goalForm');
    const viewButtons = document.querySelectorAll('.view-btn');
    const goalCards = document.querySelectorAll('.goal-card');
    const targetAmountInput = document.getElementById('targetAmount');
    const currentAmountInput = document.getElementById('currentAmount');
    const previewProgress = document.getElementById('previewProgress');
    const previewCurrent = document.getElementById('previewCurrent');
    const previewTarget = document.getElementById('previewTarget');
    const previewPercentage = document.getElementById('previewPercentage');
    const cancelBtn = document.getElementById('cancelGoal');
    const deadlineInput = document.getElementById('deadline');

    if (openModalBtn) {
        openModalBtn.addEventListener('click', () => {
            modal.classList.add('active');
            resetForm();
        });
    }

    if (openModalEmptyBtn) {
        openModalEmptyBtn.addEventListener('click', () => {
            modal.classList.add('active');
            resetForm();
        });
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', () => {
            modal.classList.remove('active');
        });
    }

    if (targetAmountInput && currentAmountInput) {
        targetAmountInput.addEventListener('input', updatePreview);
        currentAmountInput.addEventListener('input', updatePreview);
        updatePreview();
    }

    viewButtons.forEach(button => {
        button.addEventListener('click', function () {
            const filter = this.getAttribute('data-filter');
            viewButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            goalCards.forEach(card => {
                const status = card.getAttribute('data-status');
                if (filter === 'all') {
                    card.style.display = 'block';
                } else if (filter === 'active') {
                    card.style.display = status === 'active' ? 'block' : 'none';
                } else if (filter === 'completed') {
                    card.style.display = status === 'completed' ? 'block' : 'none';
                }
            });
        });
    });

    document.querySelectorAll('.update-progress-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);
            const currentAmount = parseFloat(formData.get('current_amount'));
            const maxAmount = parseFloat(this.querySelector('.progress-input').max);
            if (currentAmount < 0 || currentAmount > maxAmount) {
                alert(`Сумма должна быть от 0 до ${maxAmount} ₽`);
                return;
            }
            const submitBtn = this.querySelector('.btn-update');
            const originalHtml = submitBtn.innerHTML;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            submitBtn.disabled = true;
            this.submit();
        });
    });

    if (deadlineInput) {
        const today = new Date().toISOString().split('T')[0];
        deadlineInput.min = today;
    }

    function updatePreview() {
        const target = parseFloat(targetAmountInput.value) || 0;
        const current = parseFloat(currentAmountInput.value) || 0;
        const progress = target > 0 ? Math.min((current / target) * 100, 100) : 0;
        previewProgress.style.width = `${progress}%`;
        previewCurrent.textContent = formatCurrency(current);
        previewTarget.textContent = formatCurrency(target);
        previewPercentage.textContent = `${progress.toFixed(1)}%`;
        updateProgressColor(progress);
    }

    function updateProgressColor(progress) {
        const previewBar = document.getElementById('previewProgress');
        previewBar.style.background = '';
        if (progress >= 100) {
            previewBar.style.background = '#27ae60';
        } else if (progress >= 75) {
            previewBar.style.background = 'linear-gradient(90deg, #f39c12, #e67e22)';
        } else if (progress >= 50) {
            previewBar.style.background = 'linear-gradient(90deg, #3498db, #2980b9)';
        } else if (progress >= 25) {
            previewBar.style.background = 'linear-gradient(90deg, #9b59b6, #8e44ad)';
        } else {
            previewBar.style.background = 'linear-gradient(90deg, #6c5ce7, #00cec9)';
        }
    }

    function formatCurrency(value) {
        if (value >= 1000000) {
            return (value / 1000000).toFixed(1) + ' млн ₽';
        } else if (value >= 1000) {
            return (value / 1000).toFixed(1) + ' тыс ₽';
        } else {
            return Math.round(value).toLocaleString('ru-RU') + ' ₽';
        }
    }

    function resetForm() {
        const form = document.getElementById('goalForm');
        if (form) {
            form.reset();
        }
        updatePreview();
    }

    setTimeout(() => {
        goalCards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            setTimeout(() => {
                card.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    }, 300);
});