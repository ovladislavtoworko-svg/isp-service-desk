document.addEventListener('DOMContentLoaded', function() {
    
    // ==========================================
    // 1. Модуль: Візуалізація часу життя заявки (SLA Timer)
    // ==========================================
    function updateSLATimers() {
        const timeCells = document.querySelectorAll('.ticket-time');
        const now = Math.floor(Date.now() / 1000); // Поточний час в секундах

        timeCells.forEach(cell => {
            const createdTime = parseInt(cell.getAttribute('data-timestamp'));
            if (!createdTime) return; // Пропускаємо, якщо атрибута немає

            const diffSeconds = now - createdTime; 
            const hours = Math.floor(diffSeconds / 3600);
            const minutes = Math.floor((diffSeconds % 3600) / 60);

            cell.textContent = `${hours} год ${minutes} хв`;

            const tableRow = cell.closest('tr');
            if (hours >= 24) {
                tableRow.classList.add('table-danger');
            } else if (hours >= 12) {
                tableRow.classList.add('table-warning');
            }
        });
    }

    // Запускаємо таймер
    updateSLATimers();
    setInterval(updateSLATimers, 60000);

    // ==========================================
    // 2. Модуль: Спливаючі сповіщення (SweetAlert2)
    // ==========================================
    const urlParams = new URLSearchParams(window.location.search);

    if (urlParams.has('success')) {
        // Перевіряємо, чи підключена бібліотека Swal
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Успішно!',
                text: 'Нову технічну заявку зареєстровано.',
                icon: 'success',
                confirmButtonColor: '#198754',
                confirmButtonText: 'Ок',
                timer: 3000,
                timerProgressBar: true
            });
        }
        // Очищаємо URL
        window.history.replaceState(null, null, window.location.pathname);
    }
    // ==========================================
// 3. Автозбереження чернетки (LocalStorage) та Розумна валідація
// ==========================================
const descriptionInput = document.getElementById('ticketDescription');

if (descriptionInput) {
    // 3.1 Відновлюємо текст, якщо оператор випадково оновив сторінку
    const savedDraft = localStorage.getItem('ticket_draft');
    if (savedDraft) {
        descriptionInput.value = savedDraft;
    }

    // 3.2 Зберігаємо текст у пам'ять браузера при кожному натисканні клавіші
    descriptionInput.addEventListener('input', function() {
        localStorage.setItem('ticket_draft', this.value);
    });

    // 3.3 Валідація перед відправкою форми
    const form = descriptionInput.closest('form'); // Знаходимо форму заявки
    if (form) {
        form.addEventListener('submit', function(event) {
            // Рахуємо кількість символів без урахування зайвих пробілів
            const textLength = descriptionInput.value.trim().length;
            
            if (textLength < 10) {
                // Блокуємо відправку форми на сервер!
                event.preventDefault(); 
                
                // Виводимо красиву помилку
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Закороткий опис',
                        text: 'Будь ласка, опишіть проблему детальніше (мінімум 10 символів).',
                        confirmButtonColor: '#d33'
                    });
                } else {
                    alert('Помилка: Опис проблеми надто короткий (мінімум 10 символів).');
                }
            } else {
                // Якщо все добре — дозволяємо відправку і очищаємо чернетку,
                // щоб при створенні наступної заявки поле було пустим
                localStorage.removeItem('ticket_draft');
            }
        });
    }
}
});