s
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('transactionModal');
    const openButtons = document.querySelectorAll('[id^="openTransactionModal"]');
    const closeButton = document.querySelector('.modal-close');
    const cancelButton = document.getElementById('cancelTransaction');
    const transactionForm = document.getElementById('transactionForm');
    const typeRadios = document.querySelectorAll('input[name="type"]');
    

    const modalTitle = document.getElementById('modalTitle');
    const modalSubtitle = document.querySelector('.modal-subtitle');
    const submitText = document.getElementById('submitText');
    const transactionIdInput = document.getElementById('transactionId');
    

    const expenseCategories = document.getElementById('expenseCategories');
    const incomeCategories = document.getElementById('incomeCategories');
    const categorySelect = document.getElementById('category_id');

    openButtons.forEach(button => {
        button.addEventListener('click', function() {
            resetForm();
            modal.classList.add('active');
            document.body.style.overflow = 'hidden'; 
        });
    });

    function closeModal() {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto'; 
    }
    
    closeButton.addEventListener('click', closeModal);
    cancelButton.addEventListener('click', closeModal);

    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeModal();
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    typeRadios.forEach(radio => {
        radio.addEventListener('change', updateCategories);
    });
    function updateCategories() {
        const selectedType = document.querySelector('input[name="type"]:checked').value;

        if (selectedType === 'expense') {
            expenseCategories.style.display = 'block';
            incomeCategories.style.display = 'none';
        } else {
            expenseCategories.style.display = 'none';
            incomeCategories.style.display = 'block';
        }

        categorySelect.value = '';
    }

    function resetForm() {
        transactionForm.reset();
        transactionIdInput.value = '';
        modalTitle.textContent = 'Новая транзакция';
        modalSubtitle.textContent = 'Добавление операции';
        submitText.textContent = 'Добавить операцию';
        document.querySelector('input[name="type"][value="expense"]').checked = true;

        document.getElementById('date').value = new Date().toISOString().split('T')[0];
        
        updateCategories();
    }

    transactionForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const isEdit = transactionIdInput.value !== '';

        const submitBtn = this.querySelector('.btn-submit');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Обработка...';
        submitBtn.disabled = true;

        fetch(this.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {

                showMessage(isEdit ? 'Транзакция обновлена!' : 'Транзакция добавлена!', 'success');

                closeModal();

                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {

                showMessage(data.message || 'Произошла ошибка', 'error');
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showMessage('Ошибка соединения с сервером', 'error');
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    });
    

    window.editTransaction = function(transactionId) {

        fetch(`../api/get_transaction.php?id=${transactionId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {

                    transactionIdInput.value = data.transaction.id;
                    modalTitle.textContent = 'Редактирование транзакции';
                    modalSubtitle.textContent = 'Изменение операции';
                    submitText.textContent = 'Сохранить изменения';

                    document.querySelector(`input[name="type"][value="${data.transaction.type}"]`).checked = true;

                    updateCategories();
                    

                    setTimeout(() => {
                        document.getElementById('amount').value = data.transaction.amount;
                        document.getElementById('date').value = data.transaction.date;
                        document.getElementById('category_id').value = data.transaction.category_id;
                        document.getElementById('comment').value = data.transaction.comment || '';
                    }, 100);

                    modal.classList.add('active');
                    document.body.style.overflow = 'hidden';
                } else {
                    showMessage('Не удалось загрузить данные транзакции', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('Ошибка загрузки данных', 'error');
            });
    };

    window.exportTransactions = function() {
        const params = new URLSearchParams(window.location.search);
        params.set('export', 'csv');
        

        window.open(`../api/export_transactions.php?${params.toString()}`, '_blank');
    };
    
    function showMessage(text, type) {
        const oldMessage = document.querySelector('.custom-message');
        if (oldMessage) oldMessage.remove();
        
        const messageDiv = document.createElement('div');
        messageDiv.className = `custom-message ${type === 'success' ? 'success-message' : 'error-message'}`;
        messageDiv.innerHTML = `
            <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
            <span>${text}</span>
        `;
        
        const mainContent = document.querySelector('.main-content');
        mainContent.insertBefore(messageDiv, mainContent.firstChild);
        

        setTimeout(() => {
            if (messageDiv.parentNode) {
                messageDiv.style.opacity = '0';
                setTimeout(() => {
                    if (messageDiv.parentNode) messageDiv.remove();
                }, 300);
            }
        }, 5000);
    }
    
    updateCategories();
    
    const style = document.createElement('style');
    style.textContent = `
        .custom-message {
            transition: opacity 0.3s ease;
        }
    `;
    document.head.appendChild(style);
});