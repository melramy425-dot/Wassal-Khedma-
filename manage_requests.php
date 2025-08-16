<?php
session_start();

// حماية الصفحة: تسجيل دخول مطلوب
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// إعدادات قاعدة البيانات
$host = 'localhost';
$db   = 'wassal_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

// بيانات الجلسة
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// تحديث حالة الطلب عند استلام POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['new_status'])) {
    $request_id = (int) $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $allowed_statuses = ['معلق', 'مقبول', 'مرفوض', 'مكتمل'];

    if (in_array($new_status, $allowed_statuses, true)) {
        if ($user_role === 'admin') {
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $request_id]);
        } elseif ($user_role === 'client') {
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ? AND client_id = ?");
            $stmt->execute([$new_status, $request_id, $user_id]);
        } elseif ($user_role === 'worker') {
            $stmt = $pdo->prepare("UPDATE requests SET status = ? WHERE id = ? AND worker_id = ?");
            $stmt->execute([$new_status, $request_id, $user_id]);
        }
    }
}

// جلب الطلبات حسب الدور
if ($user_role === 'admin') {
    $stmt = $pdo->query("
        SELECT r.*, 
               u.name AS client_name, 
               w.name AS worker_name 
        FROM requests r
        JOIN users u ON r.client_id = u.id
        JOIN workers w ON r.worker_id = w.id
        ORDER BY r.request_date DESC
    ");
    $requests = $stmt->fetchAll();
} elseif ($user_role === 'client') {
    $stmt = $pdo->prepare("
        SELECT r.*, w.name AS worker_name 
        FROM requests r
        JOIN workers w ON r.worker_id = w.id
        WHERE r.client_id = ?
        ORDER BY r.request_date DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
} else { // worker
    $stmt = $pdo->prepare("
        SELECT r.*, u.name AS client_name 
        FROM requests r
        JOIN users u ON r.client_id = u.id
        WHERE r.worker_id = ?
        ORDER BY r.request_date DESC
    ");
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>إدارة الطلبات - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f9;
    margin: 0; padding: 20px;
  }
  h1 {
    text-align: center;
    color: #2c3e50;
  }
  table {
    border-collapse: collapse;
    width: 100%;
    max-width: 1200px;
    margin: 20px auto;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    background: white;
    border-radius: 8px;
    overflow: hidden;
  }
  th, td {
    padding: 15px 12px;
    border-bottom: 1px solid #ddd;
    text-align: center;
  }
  th {
    background-color: #1abc9c;
    color: white;
  }
  tr:hover {
    background-color: #f1fef9;
  }
  form {
    margin: 0;
  }
  select {
    padding: 6px 10px;
    border-radius: 6px;
    border: 1.2px solid #ccc;
    font-size: 14px;
    cursor: pointer;
  }
  button {
    background-color: #1abc9c;
    border: none;
    color: white;
    padding: 6px 14px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #16a085;
  }
  .no-requests {
    text-align: center;
    margin-top: 50px;
    color: #888;
    font-size: 18px;
  }
  .back-btn {
    display: inline-block;
    margin: 20px auto;
    padding: 10px 20px;
    background-color: #34495e;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }
  .back-btn:hover {
    background-color: #2c3e50;
  }
</style>
</head>
<body>

<h1>إدارة طلبات الخدمات</h1>

<?php if (count($requests) === 0): ?>
  <p class="no-requests">لا توجد طلبات حالياً.</p>
<?php else: ?>
<table aria-label="جدول طلبات الخدمات">
  <thead>
    <tr>
      <th>رقم الطلب</th>
      <?php if ($user_role === 'admin'): ?>
        <th>اسم العميل</th>
        <th>اسم العامل</th>
      <?php elseif ($user_role === 'client'): ?>
        <th>اسم العامل</th>
      <?php else: ?>
        <th>اسم العميل</th>
      <?php endif; ?>
      <th>الخدمة</th>
      <th>الموقع</th>
      <th>تاريخ الطلب</th>
      <th>الحالة</th>
      <th>تحديث الحالة</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($requests as $r): ?>
    <tr>
      <td><?= htmlspecialchars($r['id']) ?></td>
      <?php if ($user_role === 'admin'): ?>
        <td><?= htmlspecialchars($r['client_name']) ?></td>
        <td><?= htmlspecialchars($r['worker_name']) ?></td>
      <?php elseif ($user_role === 'client'): ?>
        <td><?= htmlspecialchars($r['worker_name']) ?></td>
      <?php else: ?>
        <td><?= htmlspecialchars($r['client_name']) ?></td>
      <?php endif; ?>
      <td><?= htmlspecialchars($r['service']) ?></td>
      <td><?= htmlspecialchars($r['location']) ?></td>
      <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['request_date']))) ?></td>
      <td><?= htmlspecialchars($r['status']) ?></td>
      <td>
        <form method="POST" aria-label="تحديث حالة الطلب <?= htmlspecialchars($r['id']) ?>">
          <input type="hidden" name="request_id" value="<?= htmlspecialchars($r['id']) ?>">
          <select name="new_status" required>
            <?php
              $statuses = ['معلق', 'مقبول', 'مرفوض', 'مكتمل'];
              foreach ($statuses as $status) {
                  $selected = ($status === $r['status']) ? 'selected' : '';
                  echo "<option value=\"$status\" $selected>$status</option>";
              }
            ?>
          </select>
          <button type="submit">تحديث</button>
        </form>
      </td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<a href="dashboard.php" class="back-btn">العودة للوحة التحكم</a>
<?php include 'footer.php'; ?>

</body>
</html>
