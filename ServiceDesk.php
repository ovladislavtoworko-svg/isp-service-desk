<?php

class ServiceDesk {
    private $ticketsFile = 'db/tickets.json';
    private $clientsFile = 'db/clients.json';
    private $tickets = [];
    private $clients = [];


// --- ВЛАСНА РЕАЛІЗАЦІЯ QUICKSORT (Замість usort) ---
private function manualQuickSort($array) {
    // 1. Базовий випадок: якщо масив порожній або має 1 елемент, сортувати не треба
    if (count($array) < 2) {
        return $array;
    }

    // 2. Вибираємо опорний елемент (Pivot) - беремо перший елемент
    $pivot = $array[0];
    
    // Отримуємо дані про VIP для опорного елемента один раз
    $clientPivot = $this->getClientByContract($pivot['contract_id']);
    $isVipPivot = $clientPivot['is_vip'] ?? false;

    $left = [];  // Сюди підуть "важливіші" заявки
    $right = []; // Сюди підуть "менш важливі" заявки

    // 3. Проходимо по всіх елементах, крім першого (бо він Pivot)
    for ($i = 1; $i < count($array); $i++) {
        $current = $array[$i];
        
        // Отримуємо дані клієнта для поточного елемента
        $clientCurrent = $this->getClientByContract($current['contract_id']);
        $isVipCurrent = $clientCurrent['is_vip'] ?? false;

        // --- ЛОГІКА ПОРІВНЯННЯ (Та сама, що була в usort) ---
        $isCurrentMoreImportant = false;

        if ($isVipCurrent && !$isVipPivot) {
            // Поточний VIP, а Півот ні -> Поточний важливіший
            $isCurrentMoreImportant = true;
        } elseif (!$isVipCurrent && $isVipPivot) {
            // Поточний не VIP, а Півот VIP -> Поточний менш важливий
            $isCurrentMoreImportant = false;
        } else {
            // Статуси рівні -> порівнюємо час (хто новіший, той важливіший)
            // Якщо час поточного БІЛЬШИЙ, значить він новіший
            if ($current['time'] > $pivot['time']) {
                $isCurrentMoreImportant = true;
            } else {
                $isCurrentMoreImportant = false;
            }
        }

        // 4. Розподіл по масивах
        if ($isCurrentMoreImportant) {
            $left[] = $current;
        } else {
            $right[] = $current;
        }
    }

    // 5. Рекурсія і склеювання: [Важливі] + [Pivot] + [Менш важливі]
    return array_merge(
        $this->manualQuickSort($left),
        [$pivot],
        $this->manualQuickSort($right)
    );
}

public function getActiveTicketsSorted() {
    $activeTickets = [];
    
    // 1. Відбираємо активні заявки
    foreach ($this->tickets as $ticket) {
        if ($ticket['status'] !== 'closed' && $ticket['status'] !== 'resolved') {
            $activeTickets[] = $ticket;
        }
    }

    // 2. Викликаємо НАШ алгоритм QuickSort
    // Більше ніяких usort!
    return $this->manualQuickSort($activeTickets);
}
    // 2. Змінити статус заявки
    public function updateTicketStatus($id, $newStatus) {
        foreach ($this->tickets as &$ticket) { // & означає посилання на оригінал
            if ($ticket['id'] == $id) {
                $ticket['status'] = $newStatus;
                $this->saveData(); // Зберігаємо у файл
                return true;
            }
        }
        return false;
    }

    public function __construct() {
        $this->loadData();
    }

    private function loadData() {
        if (!file_exists($this->ticketsFile)) file_put_contents($this->ticketsFile, json_encode([]));
        if (!file_exists($this->clientsFile)) file_put_contents($this->clientsFile, json_encode([]));
        
        $this->tickets = json_decode(file_get_contents($this->ticketsFile), true) ?? [];
        $this->clients = json_decode(file_get_contents($this->clientsFile), true) ?? [];
    }
    
    // --- НАДІЙНЕ ЗБЕРЕЖЕННЯ З БЛОКУВАННЯМ ---
    public function saveData() {
        // 1. Відкриваємо файл для запису ('w' - очищає файл, 'c' - краще для блокування)
        // Використовуємо 'c', щоб не затерти файл до того, як отримаємо лок
        $fp = fopen($this->ticketsFile, 'c');

        if ($fp) {
            // 2. Спроба отримати ексклюзивне блокування (LOCK_EX)
            // Якщо файл вже зайнятий іншим процесом, скрипт тут "зависне" і почекає
            if (flock($fp, LOCK_EX)) {
                
                // 3. Очищаємо файл (бо ми могли відкрити старий вміст)
                ftruncate($fp, 0);
                
                // 4. Пишемо JSON (JSON_PRETTY_PRINT для краси, UNESCAPED_UNICODE для кирилиці)
                $jsonData = json_encode($this->tickets, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                fwrite($fp, $jsonData);
                
                // 5. Примусовий запис з буфера на диск (щоб точно збереглося)
                fflush($fp);
                
                // 6. Знімаємо замок
                flock($fp, LOCK_UN);
            } else {
                // (Опціонально) Обробка помилки, якщо не вдалося заблокувати
                error_log("Не вдалося отримати блокування файлу заявок!");
            }
            
            // 7. Закриваємо файл
            fclose($fp);
        }
    }

    // --- МЕТОДИ ПОШУКУ (з попередніх версій) ---
    public function searchClientsAdvanced($sContract, $sPhone, $sName) {
        $results = [];
        $sContract = mb_strtolower(trim($sContract));
        $sPhone = mb_strtolower(trim($sPhone));
        $sName = mb_strtolower(trim($sName));
        if (empty($sContract) && empty($sPhone) && empty($sName)) return [];

        foreach ($this->clients as $client) {
            $match = false;
            if ($sContract && strpos(mb_strtolower($client['contract']), $sContract) !== false) $match = true;
            if ($sPhone && strpos(mb_strtolower($client['phone']), $sPhone) !== false) $match = true;
            if ($sName && strpos(mb_strtolower($client['name']), $sName) !== false) $match = true;
            if ($match) $results[] = $client;
        }
        return $results;
    }

    public function getClientByContract($contract) {
        foreach ($this->clients as $client) {
            if ($client['contract'] === $contract) return $client;
        }
        return null;
    }

    public function getRecentHistoryByContract($contractId) {
        $history = [];
        $oneMonthAgo = strtotime("-1 month"); 
        foreach ($this->tickets as $ticket) {
            if (isset($ticket['contract_id']) && $ticket['contract_id'] === $contractId && $ticket['time'] > $oneMonthAgo) {
                $history[] = $ticket;
            }
        }
        usort($history, function($a, $b) { return $b['time'] - $a['time']; });
        return $history;
    }
    
// --- РЕАЛІЗАЦІЯ BINARY SEARCH (Пошук заявки за ID) ---
public function getTicketById($id) {
    // Для бінарного пошуку потрібен числовий індекс (0, 1, 2...),
    // тому переконаємося, що масив індексований правильно
    $tickets = array_values($this->tickets);
    
    $low = 0;                        // Початок масиву
    $high = count($tickets) - 1;     // Кінець масиву

    while ($low <= $high) {
        // Знаходимо індекс середини
        $mid = floor(($low + $high) / 2);
        
        // Дивимось, яка заявка лежить посередині
        $midVal = $tickets[$mid]['id'];

        if ($midVal == $id) {
            return $tickets[$mid]; // ЗНАЙШЛИ! Повертаємо заявку
        }

        if ($midVal < $id) {
            // Якщо середня заявка менша за шукану (напр. 50 < 100),
            // значить шукана десь справа. Відкидаємо ліву частину.
            $low = $mid + 1;
        } else {
            // Якщо середня більша (напр. 150 > 100),
            // значить шукана зліва. Відкидаємо праву частину.
            $high = $mid - 1;
        }
    }

    return null; // Не знайшли
}

    public function enqueue($data) {
        $lastId = count($this->tickets) > 0 ? end($this->tickets)['id'] : 50000;
        
        // Формуємо структуру першого коментаря (якщо він є)
        $commentsList = [];
        if (!empty($data['comment'])) {
            $commentsList[] = [
                'dept' => $data['department'] ?? 'system', // Відділ автора
                'time' => time(),
                'text' => $data['comment']
            ];
        }

        $ticket = [
            'id' => $lastId + 1,
            'contract_id' => $data['contract_id'],
            'name' => $data['client_name'],
            'contact' => $data['contact_info'],
            'issue_type' => $data['issue_type'],
            'desc' => $data['description'],
            'department' => $data['department'],
            'deadline' => $data['deadline'],
            'comments' => $commentsList, // <-- ТУТ ТЕПЕР МАСИВ
            'status' => 'new',
            'time' => time()
        ];
        $this->tickets[] = $ticket; 
        $this->saveData();
    }

    // --- НОВИЙ МЕТОД: Отримати ВСІ заявки для таблиці (Сортування: Нові зверху) ---
    public function getAllTicketsSorted() {
        $all = $this->tickets;
        // Сортуємо: час створення DESC (спадання)
        usort($all, function($a, $b) {
            return $b['time'] - $a['time'];
        });
        return $all;
    }
    
    // --- НОВИЙ МЕТОД: Глобальний пошук по історії (по клієнту) ---
    public function searchGlobalHistory($sContract, $sPhone, $sName) {
        // 1. Спочатку знаходимо клієнтів
        $foundClients = $this->searchClientsAdvanced($sContract, $sPhone, $sName);
        $foundContracts = array_column($foundClients, 'contract');
        
        // 2. Тепер шукаємо всі заявки по цих контрактах
        $results = [];
        foreach ($this->tickets as $ticket) {
            if (in_array($ticket['contract_id'], $foundContracts)) {
                $results[] = $ticket;
            }
        }
        // Сортуємо
        usort($results, function($a, $b) { return $b['time'] - $a['time']; });
        return $results;
    }
    // Додавання коментаря в існуючу заявку
    // Додавання коментаря в існуючу заявку (З ВИКОРИСТАННЯМ БІНАРНОГО ПОШУКУ)
    public function addCommentToTicket($ticketId, $text, $authorDept) {
        // Замість foreach робимо Binary Search для пошуку індексу
        $low = 0;
        $high = count($this->tickets) - 1;
        $foundIndex = -1;

        while ($low <= $high) {
            $mid = floor(($low + $high) / 2);
            
            // Перевіряємо, чи існує такий елемент (на всяк випадок)
            if (!isset($this->tickets[$mid])) {
                break; 
            }

            $midVal = $this->tickets[$mid]['id'];

            if ($midVal == $ticketId) {
                $foundIndex = $mid; // Знайшли потрібний індекс!
                break;
            }

            if ($midVal < $ticketId) {
                $low = $mid + 1;
            } else {
                $high = $mid - 1;
            }
        }

        // Якщо знайшли ($foundIndex не -1), то редагуємо
        if ($foundIndex !== -1) {
            $ticket = &$this->tickets[$foundIndex]; // Беремо за посиланням

            // (Далі код той самий, що й був)
            if (!isset($ticket['comments']) || !is_array($ticket['comments'])) {
                $ticket['comments'] = [];
                if (!empty($ticket['comment'])) {
                    $ticket['comments'][] = [
                        'dept' => 'old',
                        'time' => $ticket['time'],
                        'text' => $ticket['comment']
                    ];
                }
            }
            
            $ticket['comments'][] = [
                'dept' => $authorDept,
                'time' => time(),
                'text' => $text
            ];
            
            $ticket['comment'] = $text;
            $this->saveData();
            return true;
        }
        
        return false;
    }
}


?>