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

// تأكد إن المستخدم مسؤول (admin)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// تحميل بيانات المستخدمين من JSON
$usersFile = __DIR__ . "/data/users.json";
$workers = [];

if (file_exists($usersFile)) {
    $allUsers = json_decode(file_get_contents($usersFile), true) ?? [];
    // فلترة العمال فقط
    $workers = array_filter($allUsers, fn($u) => isset($u['role']) && $u['role'] === 'worker');
}

// فلترة بحث حسب الاسم أو البريد لو تم الإرسال
$search = trim($_GET['search'] ?? '');
if ($search !== '') {
    $workers = array_filter($workers, function($worker) use ($search) {
        return (isset($worker['name']) && str_contains(mb_strtolower($worker['name']), mb_strtolower($search))) ||
               (isset($worker['email']) && str_contains(mb_strtolower($worker['email']), mb_strtolower($search)));
    });
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>إدارة العمال - Wassal Khedma</title>
  <style>
    /* نفس تصميم الداش بورد للتماشي */
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
    main {
      padding: 30px 25px 60px;
      max-width: 1200px;
      margin: 25px auto 50px;
      flex-grow: 1;
      background: white;
      border-radius: 20px;
      box-shadow: 0 8px 30px rgb(50 50 93 / 0.1),
                  0 4px 15px rgb(0 0 0 / 0.07);
    }
    h1 {
      text-align: center;
      font-size: 2.6rem;
      margin-bottom: 30px;
      color: #34495e;
      user-select: none;
      letter-spacing: 1.5px;
    }
    form.search-form {
      max-width: 400px;
      margin: 0 auto 30px;
      display: flex;
      gap: 10px;
    }
    form.search-form input[type="search"] {
      flex-grow: 1;
      padding: 12px 15px;
      border-radius: 10px;
      border: 2px solid #2980b9;
      font-size: 16px;
      transition: border-color 0.3s ease;
    }
    form.search-form input[type="search"]:focus {
      border-color: #1abc9c;
      outline: none;
    }
    form.search-form button {
      background-color: #1abc9c;
      border: none;
      border-radius: 10px;
      padding: 0 20px;
      cursor: pointer;
      font-size: 16px;
      font-weight: bold;
      color: white;
      transition: background-color 0.3s ease;
    }
    form.search-form button:hover {
      background-color: #16a085;
    }
    table {
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
      border-radius: 15px;
      overflow: hidden;
      box-shadow: 0 10px 25px rgb(0 0 0 / 0.05);
      background: #fff;
      table-layout: fixed;
      word-wrap: break-word;
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
      vertical-align: middle;
    }
    tbody tr:hover {
      background-color: #f0f8ff;
      cursor: default;
    }
    .no-data {
      text-align: center;
      color: #999;
      font-style: italic;
      padding: 25px 0;
      font-size: 18px;
      user-select: none;
    }
    /* زر إدارة (مثال تعديل/حذف) */
    .btn-action {
      background-color: #2980b9;
      color: white;
      border: none;
      padding: 8px 14px;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      transition: background-color 0.3s ease;
      margin: 0 4px;
      text-decoration: none;
      display: inline-block;
    }
    .btn-action:hover {
      background-color: #1abc9c;
    }
    /* Responsive */
    @media (max-width: 720px) {
      form.search-form {
        flex-direction: column;
      }
      form.search-form button {
        width: 100%;
      }
      th, td {
        font-size: 14px;
        padding: 10px 8px;
      }
    }
  </style>
</head>
<body>

  <nav aria-label="قائمة التنقل الرئيسية">
    <div class="nav-left">
      <a href="dashboard.php" title="لوحة التحكم الرئيسية">لوحة التحكم</a>
      <a href="workers.php" class="active" aria-current="page" title="إدارة العمال">إدارة العمال</a>
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

  <main role="main" aria-label="صفحة إدارة العمال">
    <h1>إدارة العمال</h1>

    <form method="GET" action="workers.php" class="search-form" role="search" aria-label="بحث عن عامل">
      <input type="search" name="search" placeholder="ابحث بالاسم أو البريد الإلكتروني" value="<?= htmlspecialchars($search) ?>" aria-label="حقل البحث" />
      <button type="submit" aria-label="زر البحث">بحث</button>
    </form>

    <?php if (count($workers) > 0): ?>
    <table role="table" aria-describedby="workersTableDesc">
      <caption id="workersTableDesc" style="text-align: left; padding: 15px 0; font-weight: bold; font-size: 18px;">
        قائمة العمال المسجلين في النظام
      </caption>
      <thead>
        <tr>
          <th scope="col">الاسم</th>
          <th scope="col">البريد الإلكتروني</th>
          <th scope="col">رقم الهاتف</th>
          <th scope="col">العنوان</th>
          <th scope="col">حالة الحساب</th>
          <th scope="col" style="min-width: 140px;">إجراءات</th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($workers as $worker): ?>
        <tr>
          <td><?= htmlspecialchars($worker['name'] ?? '-') ?></td>
          <td><?= htmlspecialchars($worker['email'] ?? '-') ?></td>
          <td><?= htmlspecialchars($worker['phone'] ?? '-') ?></td>
          <td><?= htmlspecialchars($worker['address'] ?? '-') ?></td>
          <td>
            <?php 
              $status = $worker['status'] ?? 'غير معروف';
              $statusText = match ($status) {
                'active' => 'نشط',
                'inactive' => 'غير نشط',
                default => htmlspecialchars($status),
              };
              echo $statusText;
            ?>
          </td>
          <td>
            <a href="edit_worker.php?id=<?= urlencode($worker['id'] ?? '') ?>" class="btn-action" title="تعديل بيانات العامل">تعديل</a>
            <a href="delete_worker.php?id=<?= urlencode($worker['id'] ?? '') ?>" class="btn-action" title="حذف العامل" onclick="return confirm('هل أنت متأكد من حذف هذا العامل؟');">حذف</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <?php else: ?>
      <p class="no-data">لا يوجد عمال مطابقين للبحث.</p>
    <?php endif; ?>
  </main>

  <footer class="footer" role="contentinfo" style="
    background-color: #2c3e50;
    color: white;
    text-align: center;
    padding: 16px 10px;
    font-size: 15px;
    user-select: none;
    box-shadow: 0 -3px 8px rgb(0 0 0 / 0.1);
  ">
    &copy; 2025 Wassal Khedma - جميع الحقوق محفوظة
  </footer>

</body>
</html>
