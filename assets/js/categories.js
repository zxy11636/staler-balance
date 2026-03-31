// categories.js

document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('categoryModal');
    const openModalBtn = document.getElementById('openCategoryModal');
    const closeModalBtn = document.querySelector('#categoryModal .modal-close');
    const categoryForm = document.getElementById('categoryForm');
    const cancelBtn = document.getElementById('cancelCategory');

    const categoryNameInput = document.getElementById('categoryName');
    const categoryTypeInputs = document.querySelectorAll('input[name="type"]');
    const categoryColorInput = document.getElementById('categoryColor');
    const colorPresetButtons = document.querySelectorAll('.color-preset');

    const previewColor = document.getElementById('previewColor');
    const previewName = document.getElementById('previewName');
    const previewType = document.getElementById('previewType');

    // Функции открытия/закрытия
    function openCategoryModal() {
        modal.classList.add('active');
        document.body.classList.add('modal-open');
        updatePreview();
    }

    function closeCategoryModal() {
        modal.classList.remove('active');
        document.body.classList.remove('modal-open');
    }

    // Обработчики
    if (openModalBtn) {
        openModalBtn.addEventListener('click', openCategoryModal);
    }

    if (closeModalBtn) {
        closeModalBtn.addEventListener('click', closeCategoryModal);
    }

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            closeCategoryModal();
        }
    });

    if (cancelBtn) {
        cancelBtn.addEventListener('click', closeCategoryModal);
    }

    // Обновление предпросмотра
    if (categoryNameInput) {
        categoryNameInput.addEventListener('input', updatePreview);
    }

    if (categoryTypeInputs) {
        categoryTypeInputs.forEach(input => {
            input.addEventListener('change', updatePreview);
        });
    }

    if (categoryColorInput) {
        categoryColorInput.addEventListener('input', updatePreview);
    }

    if (colorPresetButtons) {
        colorPresetButtons.forEach(button => {
            button.addEventListener('click', function () {
                const color = this.getAttribute('data-color');
                categoryColorInput.value = color;
                updatePreview();
            });
        });
    }

    function updatePreview() {
        const name = categoryNameInput ? (categoryNameInput.value || 'Новая категория') : 'Новая категория';
        const typeElement = document.querySelector('input[name="type"]:checked');
        const type = typeElement ? typeElement.value : 'expense';
        const color = categoryColorInput ? categoryColorInput.value : '#3498db';

        if (previewColor) previewColor.style.backgroundColor = color;
        if (previewName) previewName.textContent = name;
        if (previewType) {
            previewType.textContent = type === 'expense' ? 'Расход' : 'Доход';
            previewType.style.color = type === 'expense' ? '#e74c3c' : '#27ae60';
        }
    }

    // Обработка формы
    if (categoryForm) {
        categoryForm.addEventListener('submit', function (e) {
            if (!categoryNameInput) return;

            const name = categoryNameInput.value.trim();

            if (!name) {
                e.preventDefault();
                alert('Введите название категории');
                categoryNameInput.focus();
                return;
            }

            const submitBtn = this.querySelector('.btn-submit');
            if (submitBtn) {
                const originalHtml = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                submitBtn.disabled = true;

                setTimeout(() => {
                    submitBtn.innerHTML = originalHtml;
                    submitBtn.disabled = false;
                }, 2000);
            }
        });
    }

    // Кнопки удаления
    const deleteButtons = document.querySelectorAll('.btn-delete-category');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            if (!confirm('Удалить категорию? Все транзакции будут перенесены в стандартную категорию.')) {
                e.preventDefault();
            }
        });
    });

    // Управление прокруткой
    const formSections = document.querySelector('.form-sections-container');
    if (formSections) {
        formSections.addEventListener('wheel', function (e) {
            const isAtTop = this.scrollTop === 0;
            const isAtBottom = this.scrollTop + this.clientHeight >= this.scrollHeight - 1;

            if ((isAtTop && e.deltaY < 0) || (isAtBottom && e.deltaY > 0)) {
                return;
            }

            e.stopPropagation();
        });
    }

    // Для модального окна целей (если есть)
    const goalModal = document.getElementById('goalModal');
    if (goalModal) {
        const goalForm = goalModal.querySelector('.goal-form');
        if (goalForm) {
            goalForm.addEventListener('wheel', function (e) {
                e.stopPropagation();
            });
        }
    }

    // Инициализация
    updatePreview();
});