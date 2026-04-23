<?php
// Логіка пошуку в історії
$histResults = [];
$hParams = ['contract' => '', 'phone' => '', 'name' => ''];

if (isset($_GET['action']) && $_GET['action'] == 'history_search') {
    $hParams['contract'] = $_GET['s_contract'] ?? '';
    $hParams['phone'] = $_GET['s_phone'] ?? '';
    $hParams['name'] = $_GET['s_name'] ?? '';
    // Шукаємо по всій базі заявок
    $histResults = $sd->searchGlobalHistory($hParams['contract'], $hParams['phone'], $hParams['name']);
}
?>

<div class="p-3">
    <div class="border-bottom pb-3 mb-3">
        <form method="GET" action="index.php" class="d-flex align-items-center">
            <input type="hidden" name="page" value="history">
            <input type="hidden" name="action" value="history_search">
            
            <div class="d-flex align-items-center me-3">
                <span class="label-legacy">№ договору</span>
                <input type="text" name="s_contract" class="input-legacy" style="width: 100px;" value="<?= htmlspecialchars($hParams['contract']) ?>">
            </div>
            <div class="d-flex align-items-center me-3">
                <span class="label-legacy">Телефон</span>
                <input type="text" name="s_phone" class="input-legacy" style="width: 120px;" value="<?= htmlspecialchars($hParams['phone']) ?>">
            </div>
            <div class="d-flex align-items-center flex-grow-1 me-3">
                <span class="label-legacy">Назва абонента</span>
                <input type="text" name="s_name" class="input-legacy w-100" value="<?= htmlspecialchars($hParams['name']) ?>">
            </div>
            <button type="submit" class="btn btn-sm btn-secondary rounded-0 px-3 fw-bold">ПОШУК</button>
        </form>
    </div>

    <?php if (!empty($histResults)): ?>
        <h6 class="text-primary fst-italic">Знайдено записів: <?= count($histResults) ?></h6>
        <table class="legacy-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Дата</th>
                    <th>Договір</th>
                    <th>Клієнт</th>
                    <th>Проблема</th>
                    <th>Опис</th>
                    <th>Статус</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($histResults as $t): ?>
                <tr onclick="window.location.href='index.php?page=create&select_contract=<?= urlencode($t['contract_id']) ?>&view_ticket_id=<?= $t['id'] ?>'">
                    <td><?= $t['id'] ?></td>
                    <td><?= date('Y-m-d H:i', $t['time']) ?></td>
                    <td><?= $t['contract_id'] ?></td>
                    <td><?= htmlspecialchars($t['name']) ?></td>
                    <td style="color: navy;"><?= $t['issue_type'] ?></td>
                    <td><?= mb_strimwidth($t['desc'], 0, 50, "...") ?></td>
                    <td><?= $t['status'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif(isset($_GET['action'])): ?>
        <div class="alert alert-warning py-1">Нічого не знайдено.</div>
    <?php endif; ?>
</div>