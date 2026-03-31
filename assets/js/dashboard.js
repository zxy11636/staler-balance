document.addEventListener('DOMContentLoaded', function () {

    const modal = document.getElementById('transactionModal');
    const openModalBtn = document.getElementById('openTransactionModal');
    const closeModalBtn = document.querySelector('.modal-close');
    const typeButtons = document.querySelectorAll('.type-btn');
    const typeInput = document.getElementById('transactionType');
    const categorySelect = document.getElementById('category');
    const transactionForm = document.getElementById('transactionForm');
    const quickActionButtons = document.querySelectorAll('.action-card[data-type]');

    let categories = {
        expense: [],
        income: []
    };

    async function loadCategories() {
        try {
            console.log('Загрузка категорий...');
            const response = await fetch('api/get_categories.php');
            const data = await response.json();
            console.log('Получены данные категорий:', data);

            if (data.success) {
                categories = data.categories;
                console.log('Категории расходов:', categories.expense);
                console.log('Категории доходов:', categories.income);
                updateCategorySelect();
            } else {
                console.error('Ошибка загрузки категорий:', data.message);
            }
        } catch (error) {
            console.error('Ошибка загрузки категорий:', error);
        }
    }

    function updateCategorySelect() {
        const currentType = typeInput.value;
        const categoryList = categories[currentType] || [];

        categorySelect.innerHTML = '<option value="">Выберите категорию</option>';

        categoryList.forEach(category => {
            const option = document.createElement('option');
            option.value = category.id;
            option.textContent = category.name;
            categorySelect.appendChild(option);
        });
    }

    openModalBtn.addEventListener('click', function () {
        modal.classList.add('active');
        loadCategories();
    });

    closeModalBtn.addEventListener('click', function () {
        modal.classList.remove('active');
    });

    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.classList.remove('active');
        }
    });

    typeButtons.forEach(button => {
        button.addEventListener('click', function () {
            const type = this.getAttribute('data-type');

            typeButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');

            typeInput.value = type;

            updateCategorySelect();
        });
    });

    quickActionButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.preventDefault();
            const type = this.getAttribute('data-type');

            modal.classList.add('active');
            loadCategories();

            typeButtons.forEach(btn => btn.classList.remove('active'));
            const targetBtn = document.querySelector(`.type-btn[data-type="${type}"]`);
            if (targetBtn) {
                targetBtn.classList.add('active');
                typeInput.value = type;
                updateCategorySelect();
            }
        });
    });

    transactionForm.addEventListener('submit', async function (e) {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                alert('Операция успешно добавлена!');
                modal.classList.remove('active');
                transactionForm.reset();
                setTimeout(() => location.reload(), 500);
            } else {
                alert('Ошибка: ' + (data.message || 'Не удалось добавить операцию'));
            }
        } catch (error) {
            console.error('Ошибка:', error);
            alert('Ошибка при отправке формы');
        }
    });

    const dateInput = document.getElementById('date');
    const today = new Date().toISOString().split('T')[0];
    dateInput.max = today;

    const emptyStateBtn = document.querySelector('.empty-state .btn-add-transaction');
    if (emptyStateBtn) {
        emptyStateBtn.addEventListener('click', function () {
            modal.classList.add('active');
            loadCategories();
        });
    }
});