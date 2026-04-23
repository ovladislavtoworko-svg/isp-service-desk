<?php 
$searchResults = [];
$searchParams = ['contract' => '', 'phone' => '', 'name' => ''];

if (isset($_GET['action']) && $_GET['action'] == 'search') {
    $searchParams['contract'] = $_GET['s_contract'] ?? '';
    $searchParams['phone'] = $_GET['s_phone'] ?? '';
    $searchParams['name'] = $_GET['s_name'] ?? '';
    $searchResults = $sd->searchClientsAdvanced($searchParams['contract'], $searchParams['phone'], $searchParams['name']);
}

$selectedClient = null;
$recentHistory = [];
$formDesc = '';
$historyComments = [];
$formDeadline = '24';
$formIssueType = '';
$formDept = '';
$isViewingOldTicket = false;
$viewTicketId = '';

if (isset($_GET['select_contract'])) {
    $selectedClient = $sd->getClientByContract($_GET['select_contract']);
    if ($selectedClient) {
        $recentHistory = $sd->getRecentHistoryByContract($selectedClient['contract']);
    }
}

if (isset($_GET['view_ticket_id'])) {
    $oldTicket = $sd->getTicketById($_GET['view_ticket_id']);
    if ($oldTicket) {
        $isViewingOldTicket = true;
        $viewTicketId = $oldTicket['id'];
        $formDesc = $oldTicket['desc'];
        $formDeadline = $oldTicket['deadline'] ?? '24';
        $formIssueType = $oldTicket['issue_type'] ?? '';
        $formDept = $oldTicket['department'] ?? '';
        
        if (isset($oldTicket['comments']) && is_array($oldTicket['comments'])) {
            $historyComments = $oldTicket['comments'];
        } elseif (isset($oldTicket['comment']) && !empty($oldTicket['comment'])) {
            $historyComments[] = ['dept' => 'old', 'time' => $oldTicket['time'], 'text' => $oldTicket['comment']];
        }

        if (!$selectedClient) {
            $selectedClient = $sd->getClientByContract($oldTicket['contract_id']);
            if ($selectedClient) {
                $recentHistory = $sd->getRecentHistoryByContract($selectedClient['contract']);
            }
        }
    }
}
?>

<style>
    .vip-badge {
        background: linear-gradient(to bottom, #ff3333 0%, #990000 100%);
        color: white;
        font-weight: bold;
        padding: 2px 8px;
        border-radius: 4px;
        border: 1px solid #660000;
        font-family: Arial, sans-serif;
        text-shadow: 1px 1px 1px black;
        box-shadow: 2px 2px 3px rgba(0,0,0,0.3);
        font-size: 11px;
        margin-left: 10px;
        display: inline-block;
        transform: skew(-10deg); /* Нахил для стилю */
    }
</style>

<div class="p-3 bg-light">
    <div class="search-bar">
        <form method="GET" action="index.php" class="d-flex align-items-center">
            <input type="hidden" name="page" value="create">
            <input type="hidden" name="action" value="search">
            
            <div class="d-flex align-items-center me-3">
                <span class="label-legacy">№ договору</span>
                <input type="text" name="s_contract" class="input-legacy" style="width: 100px;" value="<?= htmlspecialchars($searchParams['contract']) ?>">
            </div>
            <div class="d-flex align-items-center me-3">
                <span class="label-legacy">Телефон</span>
                <input type="text" name="s_phone" class="input-legacy" style="width: 120px;" value="<?= htmlspecialchars($searchParams['phone']) ?>">
            </div>
            <div class="d-flex align-items-center flex-grow-1 me-3">
                <span class="label-legacy">Назва абонента</span>
                <input type="text" name="s_name" class="input-legacy w-100" value="<?= htmlspecialchars($searchParams['name']) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-secondary rounded-0 px-3 fw-bold">ПОШУК</button>
        </form>

        <?php if (!empty($searchResults)): ?>
        <table class="legacy-table mt-2">
            <thead><tr><th>№ договору</th><th>Телефон</th><th>Назва абонента</th><th>Адреса</th></tr></thead>
            <tbody>
                <?php foreach ($searchResults as $row): ?>
                <tr onclick="window.location.href='index.php?page=create&select_contract=<?= urlencode($row['contract']) ?>'">
                    <td>
                        <?= $row['contract'] ?>
                        <?php if(!empty($row['is_vip'])): ?>
                            <span style="color:red; font-weight:bold; font-size:10px;">[VIP]</span>
                        <?php endif; ?>
                    </td>
                    <td><?= $row['phone'] ?></td>
                    <td><?= $row['name'] ?></td>
                    <td><?= $row['contact_field'] ?? '' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>

    <form method="POST" action="index.php?page=create">
        <div class="d-flex mb-1 align-items-center">
            <div style="width: 100px;" class="label-legacy text-end">№ договору:</div>
            <input type="text" name="contract_id" class="input-legacy ms-2 me-2" style="width: 80px;" value="<?= $selectedClient['contract'] ?? '' ?>" readonly>
            <div style="width: 100px;" class="label-legacy text-end">ФІО клієнта *:</div>
            <input type="text" name="client_name" class="input-legacy ms-2 flex-grow-1" value="<?= $selectedClient['name'] ?? '' ?>">
            <?php if($isViewingOldTicket): ?>
                <span class="badge bg-warning text-dark ms-2 border border-dark rounded-0">ПЕРЕГЛЯД №<?= $viewTicketId ?></span>
            <?php endif; ?>
        </div>
        
        <div class="d-flex mb-3">
            <div class="d-flex align-items-center flex-grow-1">
                <div style="width: 100px;" class="label-legacy text-end">Контакт *:</div>
                <input type="text" name="contact_info" class="input-legacy ms-2 w-100" value="<?= $selectedClient['contact_field'] ?? '' ?>">
            </div>
            <div class="ms-2 d-flex flex-column" style="width: 200px;">
                <div class="mb-1">
                    <select class="input-legacy w-100" onchange="document.getElementById('cb_phone').value = this.value;">
                        <option value="">контактний номер</option>
                        <?php if ($selectedClient && !empty($selectedClient['phone'])) {
                            $phones = explode(',', $selectedClient['phone']);
                            foreach($phones as $ph) {
                                $ph = trim($ph);
                                echo "<option value='$ph'>$ph</option>";
                            }
                        } ?>
                    </select>
                </div>
                <div class="d-flex">
                    <input type="text" name="callback_phone" id="cb_phone" class="input-legacy flex-grow-1" placeholder="тел.">
                    <select class="input-legacy ms-1" style="width: 60px;"><option>тел.</option><option>моб.</option></select>
                </div>
            </div>
        </div>

        <?php if ($selectedClient): ?>
        <div class="mb-2">
            <?php if (!empty($recentHistory)): ?>
                <div style="color: red; font-weight: bold; font-style: italic; font-size: 12px;">Увага! Нещодавно вже були заявки (останні 30 днів):</div>
                <?php foreach ($recentHistory as $h): ?>
                    <a href="index.php?page=create&select_contract=<?= urlencode($selectedClient['contract']) ?>&view_ticket_id=<?= $h['id'] ?>"
                        style="display:block; color:blue; text-decoration: underline; font-size: 11px;">
                        <?= date('Y-m-d H:i', $h['time']) ?> № <?= $h['id'] ?> (<?= htmlspecialchars($h['issue_type']) ?>) [<?= $h['status'] ?>]
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <div class="mt-1 d-flex align-items-center">
                <span class="small fw-bold">Перелік послуг:</span>
                <?php if(!empty($selectedClient['is_vip'])): ?>
                    <div class="vip-badge">★ VIP КЛІЄНТ ★</div>
                <?php endif; ?>
            </div>
            
            <div style="font-family: monospace; font-size: 11px; margin-top:2px;">
                <?php if (isset($selectedClient['services'])): foreach ($selectedClient['services'] as $svc): ?>
                    <div><span class="fw-bold" style="width:80px; display:inline-block"><?= $svc['type'] ?></span> <span style="color:blue"><?= $svc['address'] ?></span> <span class="text-dark ms-2"><?= $svc['info'] ?></span></div>
                <?php endforeach; endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <hr class="my-2">
        <div class="row g-2">
            <div class="col-md-4">
                <div class="label-legacy">Найменування заявки:</div>
                <select name="issue_type" class="list-box-legacy" multiple size="10">
                    <?php
                    $opts = [
                        'inet_down'      => 'ІНТЕРНЕТ не працює (взагалі)',
                        'inet_slow'      => 'ІНТЕРНЕТ швидкість менше норми',
                        'inet_flapping'  => 'ІНТЕРНЕТ постійно відключається',
                        'inet_mail'      => 'ІНТЕРНЕТ (пошта, сайти)',
                        'iptv_down'      => 'IPTV не працює взагалі',
                        'iptv_bad'       => 'IPTV працює незадовільно',
                        'inet_iptv_down' => 'Інтернет та IPTV не працює',
                        'other'          => 'ІНШЕ'
                    ];
                    foreach($opts as $val => $lbl) {
                        $sel = ($val == $formIssueType) ? 'selected' : '';
                        echo "<option value='$val' $sel>$lbl</option>";
                    }
                    ?>
                </select>
                <?php if ($isViewingOldTicket): ?>
                    <div class="label-legacy mt-1">Історія коментарів:</div>
                    <div style="border: 1px solid #999; background: #eee; height: 100px; overflow-y: auto; font-size: 11px; padding: 4px;">
                        <?php if (empty($historyComments)): ?>
                            <span class="text-muted">Коментарів немає.</span>
                        <?php else: ?>
                            <?php foreach ($historyComments as $c): ?>
                                <div class="mb-1 border-bottom pb-1">
                                    <b>[<?= date('d.m.y H:i', $c['time']) ?>]</b> 
                                    <span style="color:#a00; font-weight:bold;">[<?= htmlspecialchars($c['dept']) ?>]:</span> 
                                    <?= htmlspecialchars($c['text']) ?>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="col-md-4">
                <div class="label-legacy text-danger">Детальний опис*:</div>
                <textarea name="description" id="ticketDescription" class="input-legacy w-100" style="height: 250px;" required><?= htmlspecialchars($formDesc) ?></textarea>
                <div class="mt-1 text-end">
                    <span class="label-legacy">Термін:</span> <input type="number" name="deadline" value="<?= $formDeadline ?>" class="input-legacy" style="width: 50px;"> год
                </div>
            </div>
            <div class="col-md-4">
                <div class="label-legacy">Направлено до відділу:</div>
                <select name="department" class="list-box-legacy" style="height: 250px;" multiple size="10">
                    <?php 
                    $depts = [
                        'support'   => 'Відділ технічної підтримки',
                        'expl_inet' => 'Відділ експлуатації (інтернет)',
                        'expl_iptv' => 'Відділ експлуатації (IPTV)',
                        'buh'       => 'Бухгалтерія',
                        'noc'       => 'Відділ експлуатації (NOC)'
                    ];
                    foreach($depts as $val => $lbl) {
                        $sel = ($val == $formDept) ? 'selected' : '';
                        echo "<option value='$val' $sel>$lbl</option>";
                    }
                    ?>
                </select>
            </div>
        </div>

<input type="hidden" name="view_ticket_id_hidden" value="<?= $viewTicketId ?>">

        <div class="mt-2 p-2 border bg-light">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <div class="label-legacy">
                    <?php if($isViewingOldTicket): ?>
                        Додати коментар до цієї заявки:
                    <?php else: ?>
                        Коментар до нової заявки:
                    <?php endif; ?>
                </div>
                
                <div class="d-flex align-items-center">
                    <span class="small me-1 fw-bold">Хто пише:</span>
                    <select name="comment_author_dept" class="input-legacy" style="width: 200px;">
                        <option value="support">Технічна підтримка</option>
                        <option value="noc">NOC (Інженери)</option>
                        <option value="expl_inet">Монтажники (Інтернет)</option>
                        <option value="expl_iptv">Монтажники (IPTV)</option>
                        <option value="buh">Бухгалтерія</option>
                    </select>
                </div>
            </div>
            
            <textarea name="comment" class="input-legacy w-100" rows="2" placeholder="Введіть текст..."></textarea>
            
            <?php if($isViewingOldTicket): ?>
                <div class="text-end mt-1">
                    <button type="submit" name="add_comment_only" class="btn btn-sm btn-primary rounded-0 px-3 fw-bold">💾 Додати тільки коментар</button>
                </div>
            <?php endif; ?>
        </div>

        <div class="mt-3">
            <?php if(!$isViewingOldTicket): ?>
                <button type="submit" name="save_ticket" class="btn btn-sm btn-secondary rounded-0 px-4 fw-bold">ЗБЕРЕГТИ НОВУ ЗАЯВКУ</button>
            <?php else: ?>
                <a href="index.php?page=create&select_contract=<?= urlencode($selectedClient['contract']) ?>" class="btn btn-sm btn-warning rounded-0 px-4 fw-bold">Створити НОВУ (Вийти з перегляду)</a>
            <?php endif; ?>
            
            <a href="index.php?page=tickets" class="btn btn-sm btn-light border rounded-0 px-4">ПОВЕРНУТИСЬ</a>
            <button type="button" class="btn btn-sm btn-light border rounded-0 px-4 ms-auto">Відновити</button>
        </div>
    </form>
</div>