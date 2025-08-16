<?php
session_start();

// تمديد الجلسة (30 دقيقة بدون نشاط يتم تسجيل الخروج تلقائياً)
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// حماية الصفحة - تأكد إن المستخدم مسؤول (admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// تحميل بيانات المستخدمين، الطلبات، والمدفوعات من ملفات JSON (يمكن تعديلها للربط بقاعدة بيانات)
$usersFile = __DIR__ . "/data/users.json";
$requestsFile = __DIR__ . "/data/requests.json";
$paymentsFile = __DIR__ . "/data/payments.json";

$users = [];
$requests = [];
$payments = [];

// قراءة الملفات فقط إذا وجدت، وتحويل المحتوى من JSON لمصفوفة
if (file_exists($usersFile)) {
    $users = json_decode(file_get_contents($usersFile), true) ?: [];
}

if (file_exists($requestsFile)) {
    $requests = json_decode(file_get_contents($requestsFile), true) ?: [];
}

if (file_exists($paymentsFile)) {
    $payments = json_decode(file_get_contents($paymentsFile), true) ?: [];
}

// إحصائيات
$workerCount = count(array_filter($users, fn($u) => isset($u['role']) && $u['role'] === 'worker'));
$clientCount = count(array_filter($users, fn($u) => isset($u['role']) && $u['role'] === 'client'));
$requestCount = count($requests);
$paymentCount = count($payments);
$adminCount = count(array_filter($users, fn($u) => isset($u['role']) && $u['role'] === 'admin'));

?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>لوحة التحكم - Wassal Khedma</title>
  <style>
    /* --- Reset & Base --- */
    * {
      box-sizing: border-box;
    }
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #dff9fb, #c7ecee);
      color: #2c3e50;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* --- Nav --- */
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

    /* --- Main Dashboard --- */
    main.dashboard {
      padding: 30px 25px 60px;
      max-width: 1200px;
      margin: 25px auto 50px;
      flex-grow: 1;
      background: white;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgb(50 50 93 / 0.1),
                  0 4px 15px rgb(0 0 0 / 0.07);
    }

    main.dashboard h1 {
      text-align: center;
      font-size: 2.8rem;
      margin-bottom: 40px;
      color: #34495e;
      user-select: none;
      letter-spacing: 1.5px;
    }

    /* --- Cards Section --- */
    .cards {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
      gap: 28px;
      margin-bottom: 60px;
    }

    .card {
      background: #fff;
      border-radius: 18px;
      box-shadow: 0 4px 20px rgb(0 0 0 / 0.1);
      padding: 35px 25px;
      text-align: center;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
      cursor: default;
      user-select: none;
    }

    .card:hover {
      transform: translateY(-8px);
      box-shadow: 0 12px 24px rgb(0 0 0 / 0.15);
    }

    .card h2 {
      font-size: 56px;
      margin: 0;
      color: #2980b9;
      letter-spacing: 3px;
      font-weight: 900;
    }

    .card p {
      margin-top: 14px;
      font-size: 21px;
      color: #555;
      font-weight: 600;
    }

    /* --- Table --- */
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

    /* --- Footer --- */
    footer.footer {
      background-color: #2c3e50;
      color: white;
      text-align: center;
      padding: 16px 10px;
      font-size: 15px;
      user-select: none;
      box-shadow: 0 -3px 8px rgb(0 0 0 / 0.1);
    }

    /* --- Responsive --- */
    @media (max-width: 720px) {
      nav {
        flex-direction: column;
        gap: 12px;
      }
      .cards {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body>

  <nav aria-label="قائمة التنقل الرئيسية">
    <div class="nav-left">
      <a href="dashboard.php" class="active" aria-current="page" title="لوحة التحكم الرئيسية">لوحة التحكم</a>
      <a href="workers.php" title="إدارة العمال">إدارة العمال</a>
      <a href="clients.php" title="إدارة العملاء">إدارة العملاء</a>
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

  <main class="dashboard" role="main" aria-label="لوحة تحكم الإدارة">

    <h1>لوحة تحكم الإدارة - Wassal Khedma</h1>

    <section class="cards" aria-label="مؤشرات النظام">
      <article class="card" aria-labelledby="workerCountLabel">
        <h2 id="workerCount"><?= $workerCount ?></h2>
        <p id="workerCountLabel">عدد العمال</p>
      </article>

      <article class="card" aria-labelledby="clientCountLabel">
        <h2 id="clientCount"><?= $clientCount ?></h2>
        <p id="clientCountLabel">عدد العملاء</p>
      </article>

      <article class="card" aria-labelledby="requestCountLabel">
        <h2 id="requestCount"><?= $requestCount ?></h2>
        <p id="requestCountLabel">عدد الطلبات</p>
      </article>

      <!-- التعديل: جعل كارد المدفوعات قابل للنقر -->
      <article class="card" aria-labelledby="paymentCountLabel" style="cursor: pointer;" onclick="window.location.href='payments.php'">
        <h2 id="paymentCount"><?= $paymentCount ?></h2>
        <p id="paymentCountLabel">عدد المدفوعات</p>
      </article>

      <article class="card" aria-labelledby="adminCountLabel">
        <h2 id="adminCount"><?= $adminCount ?></h2>
        <p id="adminCountLabel">عدد المسؤولين</p>
      </article>
    </section>

    <section aria-label="قائمة المستخدمين">
      <h2 style="margin-bottom: 20px; color: #2c3e50;">قائمة المستخدمين (عمال وعملاء ومسؤولين)</h2>
      <table aria-describedby="userTableDesc" role="table">
        <caption id="userTableDesc" style="text-align: left; padding: 15px 0; font-weight: bold; font-size: 18px;">
          قائمة تفصيلية للمستخدمين المسجلين في النظام
        </caption>
        <thead>
          <tr>
            <th scope="col">الاسم</th>
            <th scope="col">البريد الإلكتروني</th>
            <th scope="col">الدور</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($users as $user): ?>
          <tr>
            <td><?= htmlspecialchars($user['name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($user['email'] ?? '-') ?></td>
            <td>
              <?php
                $roleText = match ($user['role'] ?? '') {
                  'worker' => 'عامل',
                  'client' => 'عميل',
                  'admin'  => 'مسؤول',
                  default => htmlspecialchars($user['role'] ?? '-'),
                };
                echo $roleText;
              ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if(empty($users)): ?>
            <tr>
              <td colspan="3" style="color: #999; font-style: italic;">لا يوجد مستخدمين مسجلين</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </section>

  </main>

  <footer class="footer" role="contentinfo">
    &copy; 2025 Wassal Khedma - جميع الحقوق محفوظة
  </footer>
  <?php include 'footer.php'; ?>

</body>
</html>
