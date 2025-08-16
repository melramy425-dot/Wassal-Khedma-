<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'worker') {
    header('Location: login.php');
    exit();
}

$host = 'localhost';
$db   = 'wassal_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    $worker_id = $_SESSION['user_id'];

    // جلب طلبات العامل (آخر 10 طلبات)
    $stmt = $pdo->prepare("
        SELECT r.*, u.name AS client_name 
        FROM requests r
        JOIN users u ON r.client_id = u.id
        WHERE r.worker_id = ?
        ORDER BY r.request_date DESC
        LIMIT 10
    ");
    $stmt->execute([$worker_id]);
    $requests = $stmt->fetchAll();

    // جلب تقييمات العامل (آخر 5 تقييمات)
    $stmt = $pdo->prepare("
        SELECT rt.rating, rt.comment, u.name AS client_name, rt.created_at
        FROM ratings rt
        JOIN users u ON rt.client_id = u.id
        WHERE rt.worker_id = ?
        ORDER BY rt.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$worker_id]);
    $ratings = $stmt->fetchAll();

    // جلب الرسائل الأخيرة (5 رسائل لكل محادثة مميزة)
    // لأبسط الكود، نجيب آخر 10 رسائل فقط
    $stmt = $pdo->prepare("
        SELECT cm.*, u.name AS sender_name
        FROM chat_messages cm
        JOIN users u ON cm.sender_id = u.id
        WHERE cm.sender_id = ? OR cm.receiver_id = ?
        ORDER BY cm.sent_at DESC
        LIMIT 10
    ");
    $stmt->execute([$worker_id, $worker_id]);
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}

?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>لوحة تحكم العامل - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
    margin: 0; padding: 20px;
    color: #2c3e50;
  }
  h1, h2 {
    text-align: center;
    color: #16a085;
  }
  .container {
    max-width: 1100px;
    margin: auto;
  }
  section {
    background: white;
    padding: 20px;
    margin-bottom: 25px;
    border-radius: 12px;
    box-shadow: 0 0 15px rgba(0,0,0,0.1);
  }
  table {
    width: 100%;
    border-collapse: collapse;
  }
  th, td {
    padding: 12px 15px;
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
  .no-data {
    text-align: center;
    color: #888;
    padding: 15px 0;
  }
  a.button {
    display: inline-block;
    margin: 10px 0;
    padding: 10px 18px;
    background-color: #1abc9c;
    color: white;
    border-radius: 8px;
    text-decoration: none;
    font-weight: bold;
    transition: background-color 0.3s ease;
  }
  a.button:hover {
    background-color: #16a085;
  }
</style>
</head>
<body>

<div class="container" role="main" aria-label="لوحة تحكم العامل">

  <h1>مرحباً، <?= htmlspecialchars($_SESSION['user_name'] ?? 'العامل') ?></h1>

  <section aria-labelledby="requestsHeading">
    <h2 id="requestsHeading">طلبات الخدمة الخاصة بك</h2>
    <?php if (count($requests) === 0): ?>
      <p class="no-data">لا توجد طلبات حتى الآن.</p>
    <?php else: ?>
      <table aria-describedby="requestsHeading">
        <thead>
          <tr>
            <th>رقم الطلب</th>
            <th>اسم العميل</th>
            <th>الخدمة</th>
            <th>الموقع</th>
            <th>تاريخ الطلب</th>
            <th>الحالة</th>
            <th>تفاصيل</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($requests as $r): ?>
            <tr>
              <td><?= htmlspecialchars($r['id']) ?></td>
              <td><?= htmlspecialchars($r['client_name']) ?></td>
              <td><?= htmlspecialchars($r['service']) ?></td>
              <td><?= htmlspecialchars($r['location']) ?></td>
              <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['request_date']))) ?></td>
              <td><?= htmlspecialchars($r['status']) ?></td>
              <td><?= htmlspecialchars($r['details'] ?? '-') ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section aria-labelledby="ratingsHeading">
    <h2 id="ratingsHeading">تقييمات العملاء لك</h2>
    <?php if (count($ratings) === 0): ?>
      <p class="no-data">لا توجد تقييمات حتى الآن.</p>
    <?php else: ?>
      <table aria-describedby="ratingsHeading">
        <thead>
          <tr>
            <th>العميل</th>
            <th>التقييم</th>
            <th>التعليق</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ratings as $rt): ?>
            <tr>
              <td><?= htmlspecialchars($rt['client_name']) ?></td>
              <td><?= htmlspecialchars($rt['rating']) ?>/5</td>
              <td><?= htmlspecialchars($rt['comment'] ?: '-') ?></td>
              <td><?= htmlspecialchars(date('Y-m-d', strtotime($rt['created_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <section aria-labelledby="messagesHeading">
    <h2 id="messagesHeading">آخر الرسائل</h2>
    <?php if (count($messages) === 0): ?>
      <p class="no-data">لا توجد رسائل بعد.</p>
    <?php else: ?>
      <table aria-describedby="messagesHeading">
        <thead>
          <tr>
            <th>المرسل</th>
            <th>الرسالة</th>
            <th>التاريخ</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($messages as $msg): ?>
            <tr>
              <td><?= htmlspecialchars($msg['sender_name']) ?></td>
              <td><?= htmlspecialchars(mb_strimwidth($msg['message'], 0, 50, "...")) ?></td>
              <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($msg['sent_at']))) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </section>

  <a href="chat.php" class="button" aria-label="اذهب إلى صفحة الدردشة">الذهاب إلى الدردشة</a>
  <a href="edit_profile.php" class="button" aria-label="تعديل بيانات الحساب">تعديل بيانات الحساب</a>
  <a href="logout.php" class="button" aria-label="تسجيل الخروج">تسجيل الخروج</a>

</div>
<?php include 'footer.php'; ?>

</body>
</html>
