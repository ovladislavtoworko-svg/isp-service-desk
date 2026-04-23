<?php
// Отримуємо ТІЛЬКИ АКТИВНІ заявки
$activeTickets = $sd->getActiveTicketsSorted();

$statuses = [
    'new' => 'Створена',
    'in_progress' => 'В обробці',
    'postponed' => 'Відкладена',
    'resolved' => 'Виконана',
    'closed' => 'Закрита'
];

$issue_types_list = [
    'inet_down'      => 'ІНТЕРНЕТ не працює (взагалі)',
    'inet_slow'      => 'ІНТЕРНЕТ швидкість менше норми',
    'inet_flapping'  => 'ІНТЕРНЕТ постійно відключається',
    'inet_mail'      => 'ІНТЕРНЕТ (пошта, сайти)',
    'iptv_down'      => 'IPTV не працює взагалі',
    'iptv_bad'       => 'IPTV працює незадовільно',
    'inet_iptv_down' => 'Інтернет та IPTV не працює',
    'phone_down'     => 'ТЕЛЕФОН не працює',
    'link'           => 'КАНАЛ зв\'язку',
    'other'          => 'ІНШЕ'
];
?>

<style>
    .ticket-row { background-color: #fff; border-bottom: 1px solid #ccc; }
    
    /* Рядок VIP: світло-червоний фон */
    .ticket-row-vip { 
        background-color: #fff0f0 !important; 
        border-left: 3px solid red;
    }
    
    /* Значок VIP */
    .vip-label {
        color: white;
        background-color: #cc0000;
        font-size: 10px;
        font-weight: bold;
        padding: 1px 4px;
        border-radius: 3px;
        margin-left: 5px;
        box-shadow: 1px 1px 1px rgba(0,0,0,0.2);
        vertical-align: middle;
    }
</style>

<div class="d-flex align-items-center p-2 bg-light border-bottom">
    <select class="input-legacy me-2" style="width: 250px;">
        <option>Всі відділи</option>
        <option>Відділ технічної підтримки</option>
        <option>Відділ експлуатації (інтернет)</option>
        <option>Відділ експлуатації (IPTV)</option>
        <option>Бухгалтерія</option>
    </select>
    
    <button class="btn btn-sm btn-light border me-1 fw-bold text-success" onclick="window.location.reload()">Refresh</button>
    <div class="ms-auto fw-bold text-muted small">Активних заявок: <?= count($activeTickets) ?></div>
</div>

<div class="p-2">
    <table class="legacy-table">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th style="width: 100px;">Статус (Змінити)</th>
                <th style="width: 50px;">ID</th>
                <th style="width: 40px;">KOD</th>
                <th>Найменування заявки</th>
                <th>Назва абонента</th>
                <th style="width: 120px;">Створена</th>
                <th style="width: 120px;">Кінцевий термін</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($activeTickets as $t): ?>
            <?php 
                // Отримуємо дані клієнта з бази клієнтів
                $clientData = $sd->getClientByContract($t['contract_id']);
                
                // Перевіряємо VIP
                $isVip = !empty($clientData['is_vip']);
                
                // Визначаємо ім'я: беремо з бази клієнтів, якщо там нема - то з заявки, інакше "Невідомий"
                $clientName = $clientData['name'] ?? $t['name'] ?? 'Невідомий абонент';

                $rowClass = $isVip ? 'ticket-row-vip' : 'ticket-row';
            ?>
            
            <tr class="<?= $rowClass ?>" onclick="if(!event.target.closest('select')) window.location.href='index.php?page=create&select_contract=<?= urlencode($t['contract_id']) ?>&view_ticket_id=<?= $t['id'] ?>'">
                
            <td class="text-center ticket-time" data-timestamp="<?= $t['time'] ?>">
    <?= date('Y-m-d', $t['time']) ?><br>
    <small><?= date('H:i:s', $t['time']) ?></small>
</td>
                
                <td onclick="event.stopPropagation()">
                    <form method="POST" action="index.php" style="margin:0;">
                        <input type="hidden" name="action" value="update_status">
                        <input type="hidden" name="ticket_id" value="<?= $t['id'] ?>">
                        <select name="new_status" class="status-select" onchange="this.form.submit()">
                            <?php foreach ($statuses as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($t['status'] == $key) ? 'selected' : '' ?>>
                                    <?= $label ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </form>
                </td>

                <td><?= $t['id'] ?></td>
                <td>ТП</td>
                
                <td style="color: #000080; font-weight: bold;">
                    <?= htmlspecialchars($issue_types_list[$t['issue_type']] ?? $t['issue_type']) ?>
                </td>
                
                <td>
                    <?= htmlspecialchars($clientName) ?>
                    <?php if ($isVip): ?>
                        <span class="vip-label">★ VIP</span>
                    <?php endif; ?>
                </td>

                <td class="text-center">
                    <?= date('Y-m-d', $t['time']) ?><br>
                    <small><?= date('H:i:s', $t['time']) ?></small>
                </td>
                <td class="text-center">
                    <?= date('Y-m-d', $t['time'] + ($t['deadline_hours'] ?? 24)*3600) ?><br>
                    <small><?= date('H:i:s', $t['time'] + ($t['deadline_hours'] ?? 24)*3600) ?></small>
                </td>
            </tr>
            <?php endforeach; ?>
            
            <?php if(empty($activeTickets)): ?>
                <tr><td colspan="8" class="text-center p-3">Немає активних заявок. Черга пуста!</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>