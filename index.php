<?php
require_once 'ServiceDesk.php';
$sd = new ServiceDesk(); 

// --- ГОЛОВНИЙ КОНТРОЛЕР ОБРОБКИ ДАНИХ (POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {     
    
    // 1. Створення НОВОЇ заявки
    if (isset($_POST['save_ticket'])) {
        $sd->enqueue($_POST);
        // ЗМІНА 1: Додано параметр &success=1 для виклику спливаючого вікна SweetAlert
        header("Location: index.php?page=tickets&success=1");
        exit;
    }

    // 2. Додавання КОМЕНТАРЯ до існуючої
    if (isset($_POST['add_comment_only'])) {
        $tId = $_POST['view_ticket_id_hidden']; 
        $text = $_POST['comment'];
        $dept = $_POST['comment_author_dept'];         
        
        if (!empty($text) && !empty($tId)) {
            $sd->addCommentToTicket($tId, $text, $dept);
        }
        
        $redirect = "index.php?page=create&select_contract=" . urlencode($_POST['contract_id']) . "&view_ticket_id=" . $tId;
        header("Location: " . $redirect);
        exit;
    }

    // 3. Зміна СТАТУСУ
    if (isset($_POST['action']) && $_POST['action'] === 'update_status') {
        $ticketId = $_POST['ticket_id'];
        $newStatus = $_POST['new_status'];
        $sd->updateTicketStatus($ticketId, $newStatus);
        header("Location: index.php?page=tickets");
        exit;
    }
}

// Визначаємо сторінку (за замовчуванням tickets)
$page = $_GET['page'] ?? 'tickets'; 
?>
<!DOCTYPE html>
<html lang="uk">
<head>
    <meta charset="UTF-8">
    <title>ISP Service Desk</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background-color: #f0f8ff; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 13px; margin: 0; }
        
        /* МЕНЮ ВКЛАДОК */
        .top-menu-bar {
            background: linear-gradient(to bottom, #0099cc 0%, #006699 100%);
            height: 40px; 
            display: flex; 
            align-items: center; 
            padding-left: 10px; 
            border-bottom: 2px solid #004466;
        }

        .logo-img {
            height: 30px; 
            object-fit: contain;
            margin-right: 15px; /* Відступ від логотипу до кнопок меню */
        }
        
        /* --- ВИКОНАННЯ ЗАВДАННЯ 4 (Спискове меню) --- */
        .menu-items-container {
            display: flex; /* Розміщує елементи горизонтально */
            list-style-type: none; /* Прибирає маркери (крапки) списку */
            margin: 0; 
            padding: 0;
            height: 100%;
        }

        .menu-items-container li {
            height: 100%;
        }

        .menu-item {
            color: white; font-family: 'Georgia', serif; font-style: italic; font-weight: bold; font-size: 16px;
            text-decoration: none; padding: 0 20px; height: 100%; display: flex; align-items: center;
            border-right: 1px solid rgba(255,255,255,0.4); 
            transition: background 0.2s, color 0.2s;
        }

        /* Реакція на наведення миші */
        .menu-item:hover { 
            background-color: rgba(255,255,255,0.2); 
            color: #fff; 
        }

        /* Реакція на натискання */
        .menu-item:active { 
            background-color: #002233; 
        }

        /* Активна вкладка */
        .menu-item.active { 
            background-color: #004466; 
            box-shadow: inset 0 0 5px rgba(0,0,0,0.5); 
        }

        /* СПІЛЬНІ СТИЛІ */
        .legacy-table { width: 100%; border-collapse: collapse; background: white; font-size: 12px; border: 1px solid #999; }
        .legacy-table th { background: #e1eafc; color: #000080; font-weight: bold; font-style: italic; border: 1px solid #a0a0a0; padding: 4px; text-align: left; }
        .legacy-table td { border: 1px solid #ccc; padding: 2px 5px; color: #000; font-weight: bold; }
        
        .input-legacy { border: 1px solid #7a7a7a; padding: 2px 5px; font-size: 13px; background-color: #fff; }
        .label-legacy { font-weight: bold; color: #333; margin-right: 5px; font-size: 12px; }
        .list-box-legacy { border: 1px solid #7a7a7a; height: 180px; font-size: 12px; padding: 2px; background: white; width: 100%; overflow-y: scroll; }
        .list-box-legacy option:checked { background-color: #3399ff; color: white; }
    </style>
</head>
<body>

<div class="top-menu-bar">
    <div>
        <img src="logo.png" alt="ISP Logo" class="logo-img">
    </div>

    <ul class="menu-items-container">
        <li><a href="index.php?page=tickets" class="menu-item <?= $page == 'tickets' ? 'active' : '' ?>">Заявки</a></li>
        <li><a href="index.php?page=create" class="menu-item <?= $page == 'create' ? 'active' : '' ?>">Створити заявку</a></li>
        <li><a href="index.php?page=history" class="menu-item <?= $page == 'history' ? 'active' : '' ?>">Історія заявок</a></li>
        <li><a href="index.php?page=admin" class="menu-item <?= $page == 'admin' ? 'active' : '' ?>">Адмін-панель</a></li>
    </ul>
</div>

<div class="content-area mt-3 px-3">
    <?php
    if ($page == 'tickets') {
        include 'tab_tickets.php';
    } elseif ($page == 'create') {
        include 'tab_create.php';
    } elseif ($page == 'history') {
        include 'tab_history.php';
    } elseif ($page == 'admin') {
        include 'tab_admin.php';
    }
    ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="app.js"></script>

</body>
</html>