
document.addEventListener('DOMContentLoaded', function () {
    initCharts();

    initTabs();

    initDateInputs();
});

function initCharts() {

    if (chartData.expense.length > 0) {
        const expenseCtx = document.getElementById('expenseChart').getContext('2d');
        new Chart(expenseCtx, {
            type: 'pie',
            data: {
                labels: chartData.expense.map(item => item.name),
                datasets: [{
                    data: chartData.expense.map(item => item.value),
                    backgroundColor: chartData.expense.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const item = chartData.expense[context.dataIndex];
                                return [
                                    item.name,
                                    `Сумма: ${formatCurrency(item.value)}`,
                                    `Доля: ${item.percentage}%`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    if (chartData.income.length > 0) {
        const incomeCtx = document.getElementById('incomeChart').getContext('2d');
        new Chart(incomeCtx, {
            type: 'pie',
            data: {
                labels: chartData.income.map(item => item.name),
                datasets: [{
                    data: chartData.income.map(item => item.value),
                    backgroundColor: chartData.income.map(item => item.color),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                const item = chartData.income[context.dataIndex];
                                return [
                                    item.name,
                                    `Сумма: +${formatCurrency(item.value)}`,
                                    `Доля: ${item.percentage}%`
                                ];
                            }
                        }
                    }
                }
            }
        });
    }

    if (chartData.monthly.labels.length > 0) {
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: chartData.monthly.labels,
                datasets: [
                    {
                        label: 'Доходы',
                        data: chartData.monthly.income,
                        borderColor: '#27ae60',
                        backgroundColor: 'rgba(39, 174, 96, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    },
                    {
                        label: 'Расходы',
                        data: chartData.monthly.expense,
                        borderColor: '#e74c3c',
                        backgroundColor: 'rgba(231, 76, 60, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: function (context) {
                                return `${context.dataset.label}: ${formatCurrency(context.raw)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function (value) {
                                return formatCurrency(value);
                            }
                        }
                    }
                }
            }
        });
    }
}

function initTabs() {
    const tabButtons = document.querySelectorAll('.stats-tabs .tab-btn');
    const tabContents = document.querySelectorAll('.stats-table');

    tabButtons.forEach(button => {
        button.addEventListener('click', () => {
            const tabId = button.getAttribute('data-tab');

            tabButtons.forEach(btn => btn.classList.remove('active'));
            tabContents.forEach(content => content.classList.remove('active'));

            button.classList.add('active');
            document.getElementById(`${tabId}-tab`).classList.add('active');
        });
    });
}

function initDateInputs() {
    const dateFromInput = document.getElementById('date_from');
    const dateToInput = document.getElementById('date_to');

    if (dateFromInput && dateToInput) {

        const today = new Date().toISOString().split('T')[0];

        dateFromInput.max = today;
        dateToInput.max = today;

        dateFromInput.addEventListener('change', function () {
            dateToInput.min = this.value;
            if (dateToInput.value && dateToInput.value < this.value) {
                dateToInput.value = this.value;
            }
        });

        dateToInput.addEventListener('change', function () {
            dateFromInput.max = this.value;
            if (dateFromInput.value && dateFromInput.value > this.value) {
                dateFromInput.value = this.value;
            }
        });
    }
}

function formatCurrency(value) {
    return new Intl.NumberFormat('ru-RU', {
        minimumFractionDigits: 0,
        maximumFractionDigits: 0
    }).format(value) + ' ₽';
}

function exportStatistics(format) {
    alert(`Экспорт в ${format} будет реализован позже`);
}