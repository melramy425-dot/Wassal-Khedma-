<?php
session_start();

// تمديد الجلسة (30 دقيقة)
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// حماية الصفحة - يجب أن يكون المستخدم مسؤول (admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// تحميل بيانات المستخدمين من ملف JSON
$usersFile = __DIR__ . "/data/users.json";
$users = [];

if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?? [];
}

// تصفية المستخدمين بالدور client فقط
$clients = array_filter($users, fn($user) => ($user['role'] ?? '') === 'client');

// دعم خاصية البحث في الأسماء والبريد الإلكتروني
$searchTerm = trim($_GET['search'] ?? '');
if ($searchTerm !== '') {
    $clients = array_filter($clients, function($client) use ($searchTerm) {
        return (stripos($client['name'], $searchTerm) !== false) || (stripos($client['email'], $searchTerm) !== false);
    });
}

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>إدارة العملاء - Wassal Khedma</title>
  <style>
    /* Reset & Base */
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #f0f8ff, #d6eaf8);
      color: #2c3e50;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* Nav */
    nav {
      background-color: #2c3e50;
      color: white;
      padding: 15px 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      flex-wrap: wrap;
      position: sticky;
      top: 0;
      z-index: 1000;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    nav .nav-left,
    nav .nav-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    nav a {
      color: white;
      text-decoration: none;
      padding: 10px 18px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      transition: background-color 0.3s ease;
      white-space: nowrap;
    }

    nav a:hover, nav a.active {
      background-color: #1abc9c;
      box-shadow: 0 0 10px #16a085aa;
    }

    nav .welcome-text {
      font-weight: 600;
      font-size: 18px;
      user-select: none;
      color: #ecf0f1;
    }

    /* Main container */
    main {
      max-width: 1200px;
      margin: 25px auto 50px;
      background: white;
      border-radius: 20px;
      padding: 30px 25px 60px;
      box-shadow: 0 8px 30px rgb(50 50 93 / 0.1),
                  0 4px 15px rgb(0 0 0 / 0.07);
      flex-grow: 1;
    }

    main h1 {
      text-align: center;
      font-size: 2.8rem;
      margin-bottom: 40px;
      color: #34495e;
      user-select: none;
      letter-spacing: 1.5px;
    }

    /* Search bar */
    form.search-form {
      margin-bottom: 30px;
      display: flex;
      justify-content: center;
      gap: 12px;
    }

    form.search-form input[type="search"] {
      width: 320px;
      padding: 12px 15px;
      border-radius: 8px;
      border: 2px solid #1abc9c;
      font-size: 18px;
      transition: border-color 0.3s ease;
    }

    form.search-form input[type="search"]:focus {
      border-color: #16a085;
      outline: none;
    }

    form.search-form button {
      background-color: #1abc9c;
      border: none;
      color: white;
      font-weight: 700;
      font-size: 18px;
      padding: 12px 25px;
      border-radius: 8px;
      cursor: pointer;
      transition: background-color 0.3s ease;
    }

    form.search-form button:hover {
      background-color: #16a085;
    }

    /* Table */
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgb(0 0 0 / 0.05);
      background: #fff;
    }

    thead tr {
      background-color: #2980b9;
      color: white;
      font-weight: 700;
      user-select: none;
    }

    th, td {
      padding: 14px 18px;
      text-align: center;
      font-size: 17px;
      border-bottom: 1px solid #ddd;
    }

    tbody tr:hover {
      background-color: #f0f8ff;
      cursor: default;
    }

    /* Footer */
    footer.footer {
      background-color: #2c3e50;
      color: white;
      text-align: center;
      padding: 16px 10px;
      font-size: 15px;
      user-select: none;
      box-shadow: 0 -3px 8px rgb(0 0 0 / 0.1);
    }

    /* Responsive */
    @media (max-width: 720px) {
      nav {
        flex-direction: column;
        gap: 12px;
      }
      form.search-form {
        flex-direction: column;
        gap: 12px;
        align-items: center;
      }
      form.search-form input[type="search"] {
        width: 100%;
      }
      table, thead, tbody, th, td, tr {
        display: block;
      }
      thead tr {
        position: absolute;
        top: -9999px;
        left: -9999px;
      }
      tbody tr {
        margin-bottom: 20px;
        border-radius: 12px;
        box-shadow: 0 4px 10px rgb(0 0 0 / 0.1);
        padding: 15px;
        background: #fff;
      }
      tbody tr td {
        text-align: right;
        padding-left: 50%;
        position: relative;
        font-size: 16px;
        border-bottom: none;
        border-top: 1px solid #eee;
      }
      tbody tr td::before {
        content: attr(data-label);
        position: absolute;
        left: 15px;
        font-weight: 700;
        color: #2980b9;
        white-space: nowrap;
      }
    }
  </style>
</head>
<body>

  <nav aria-label="قائمة التنقل الرئيسية">
    <div class="nav-left">
      <a href="dashboard.php" title="لوحة التحكم الرئيسية">لوحة التحكم</a>
      <a href="workers.php" title="إدارة العمال">إدارة العمال</a>
      <a href="clients.php" class="active" aria-current="page" title="إدارة العملاء">إدارة العملاء</a>
      <a href="requests.php" title="إدارة الطلبات">الطلبات</a>
      <a href="payments.php" title="إدارة المدفوعات">المدفوعات</a>
      <a href="index.php" title="الصفحة الرئيسية">الصفحة الرئيسية</a>
    </div>

    <div class="nav-right">
      <span class="welcome-text" aria-label="مرحبًا بالمستخدم">
        مرحبًا، <?= htmlspecialchars($_SESSION['user_name'] ?? 'مسؤول') ?>
      </span>
      <a href="logout.php" id="logoutLink" title="تسجيل الخروج">تسجيل الخروج</a>
    </div>
  </nav>

  <main role="main" aria-label="صفحة إدارة العملاء">
    <h1>إدارة العملاء</h1>

    <form class="search-form" method="GET" action="clients.php" role="search" aria-label="بحث عن العملاء">
      <input
        type="search"
        name="search"
        placeholder="ابحث بالاسم أو البريد الإلكتروني"
        aria-label="بحث عن العملاء"
        value="<?= htmlspecialchars($searchTerm) ?>"
      />
      <button type="submit" aria-label="زر البحث">بحث</button>
    </form>

    <table role="table" aria-describedby="clientsTableDesc">
      <caption id="clientsTableDesc" style="padding: 15px 0; font-weight: bold; font-size: 18px;
