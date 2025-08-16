<?php
session_start();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
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
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$error = '';
$success = '';
$order = null;

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // جلب طلب الخدمة بناءً على ID موجود في GET
    if (!isset($_GET['request_id']) || !is_numeric($_GET['request_id'])) {
        throw new Exception('طلب غير صالح.');
    }

    $request_id = (int)$_GET['request_id'];
    
    // نتأكد أن الطلب يخص العميل الحالي فقط
    $stmt = $pdo->prepare("SELECT r.*, w.name AS worker_name, w.service AS worker_service 
                           FROM requests r 
                           JOIN workers w ON r.worker_id = w.id 
                           WHERE r.id = ? AND r.client_id = ?");
    $stmt->execute([$request_id, $_SESSION['user_id']]);
    $order = $stmt->fetch();

    if (!$order) {
        throw new Exception('لم يتم العثور على الطلب.');
    }

    // نفترض سعر ثابت لكل خدمة أو يمكن تحديده حسب التخصص (مثلاً)
    // هنا سعر تجريبي:
    $service_price = 100; // جنيه مثلا

    // حساب الضرائب أو الخصومات لو في (مثلاً 14% ضريبة)
    $tax_rate = 0.14;
    $tax_amount = $service_price * $tax_rate;
    $total_price = $service_price + $tax_amount;

    // عند الضغط على "إتمام الدفع" (تجريبي)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // ممكن تحط تحقق إضافي هنا

        // تحديث حالة الطلب إلى "مكتمل" بعد الدفع
        $stmt = $pdo->prepare("UPDATE requests SET status = 'مكتمل' WHERE id = ? AND client_id = ?");
        $stmt->execute([$request_id, $_SESSION['user_id']]);

        $success = "تم تأكيد الدفع بنجاح! شكراً لاستخدامك خدمتنا.";
    }

} catch (Exception $e) {
    $error = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>الدفع - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background-color: #f4f7f9;
    margin: 0; padding: 20px;
  }
  .container {
    max-width: 600px;
    background: white;
    margin: 40px auto;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }
  h1 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
  }
  .order-details {
    border: 1.5px solid #ddd;
    border-radius: 10px;
    padding: 20px;
    margin-bottom: 30px;
    background: #fafafa;
  }
  .order-details p {
    margin: 10px 0;
    font-size: 16px;
    color: #34495e;
  }
  .price-info {
    font-size: 18px;
    font-weight: bold;
    margin-top: 20px;
    color: #27ae60;
    text-align: center;
  }
  .btn-pay {
    background-color: #1abc9c;
    color: white;
    font-size: 18px;
    font-weight: bold;
    padding: 15px;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease;
  }
  .btn-pay:hover {
    background-color: #16a085;
  }
  .message {
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    font-weight: bold;
    text-align: center;
  }
  .error {
    background-color: #e74c3c;
    color: white;
  }
  .success {
    background-color: #27ae60;
    color: white;
  }
  a.back-link {
    display: block;
    margin-top: 15px;
    text-align: center;
    text-decoration: none;
    color: #34495e;
  }
  a.back-link:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="container" role="main" aria-label="صفحة الدفع">
  <h1>مراجعة الطلب والدفع</h1>

  <?php if ($error): ?>
    <div class="message error" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="message success" role="alert"><?= htmlspecialchars($success) ?></div>
    <a href="dashboard.php" class="back-link">العودة إلى لوحة التحكم</a>
  <?php elseif ($order): ?>
    <div class="order-details" aria-label="تفاصيل الطلب">
      <p><strong>رقم الطلب:</strong> <?= htmlspecialchars($order['id']) ?></p>
      <p><strong>اسم العامل:</strong> <?= htmlspecialchars($order['worker_name']) ?></p>
      <p><strong>الخدمة المطلوبة:</strong> <?= htmlspecialchars($order['service']) ?></p>
      <p><strong>الموقع:</strong> <?= htmlspecialchars($order['location']) ?></p>
      <p><strong>تاريخ الطلب:</strong> <?= date('Y-m-d H:i', strtotime($order['request_date'])) ?></p>
      <p><strong>حالة الطلب:</strong> <?= htmlspecialchars($order['status']) ?></p>
      <p class="price-info">السعر: <?= number_format($service_price, 2) ?> جنيه</p>
      <p class="price-info">الضريبة (14%): <?= number_format($tax_amount, 2) ?> جنيه</p>
      <p class="price-info">الإجمالي: <?= number_format($total_price, 2) ?> جنيه</p>
    </div>

    <form method="POST" action="checkout.php?request_id=<?= $order['id'] ?>" aria-label="نموذج إتمام الدفع">
      <button type="submit" class="btn-pay" aria-label="دفع الآن">دفع الآن</button>
    </form>
  <?php endif; ?>
  
</div>
<?php include 'footer.php'; ?>

</body>
</html>
