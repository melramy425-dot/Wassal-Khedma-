<?php
session_start();

// حماية الصفحة - دخول مسؤول فقط
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

// تمديد الجلسة (30 دقيقة)
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// إعدادات الاتصال بقاعدة البيانات
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}

// استلام رقم الطلب من الرابط
$request_id = isset($_GET['request_id']) ? (int)$_GET['request_id'] : 0;
if (!$request_id) {
    die("رقم الطلب غير موجود.");
}

// جلب بيانات الطلب مع معلومات العميل والعامل
$stmt = $pdo->prepare("
    SELECT r.*, w.name AS worker_name, u.name AS client_name
    FROM service_requests r
    JOIN workers w ON r.worker_id = w.id
    JOIN users u ON r.client_id = u.id
    WHERE r.id = ?
");
$stmt->execute([$request_id]);
$request = $stmt->fetch();

if (!$request) {
    die("الطلب غير موجود.");
}

// معالجة نموذج الدفع
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $card_name = trim($_POST['card_name'] ?? '');
    $card_number = preg_replace('/\s+/', '', $_POST['card_number'] ?? '');
    $expiry_month = $_POST['expiry_month'] ?? '';
    $expiry_year = $_POST['expiry_year'] ?? '';
    $cvv = $_POST['cvv'] ?? '';

    // تحقق من صحة البيانات
    if (!$card_name || !$card_number || !$expiry_month || !$expiry_year || !$cvv) {
        $error = "يرجى ملء جميع بيانات البطاقة.";
    } elseif (!preg_match('/^\d{16}$/', $card_number)) {
        $error = "رقم البطاقة غير صحيح. يجب أن يتكون من 16 رقمًا.";
    } elseif (!preg_match('/^\d{3,4}$/', $cvv)) {
        $error = "رمز CVV غير صحيح.";
    } else {
        $current_year = (int)date('Y');
        $current_month = (int)date('m');
        if ((int)$expiry_year < $current_year || ((int)$expiry_year == $current_year && (int)$expiry_month < $current_month)) {
            $error = "تاريخ انتهاء البطاقة غير صالح.";
        }
    }

    // إذا البيانات صحيحة
    if (!$error) {
        try {
            $amount = 100; // المبلغ ثابت تجريبي

            // إدخال بيانات الدفع في جدول المدفوعات
            $insert = $pdo->prepare("INSERT INTO payments (request_id, client_id, amount, payment_date, card_name) VALUES (?, ?, ?, NOW(), ?)");
            $insert->execute([$request_id, $request['client_id'], $amount, $card_name]);

            // تحديث حالة الطلب إلى مكتمل
            $update = $pdo->prepare("UPDATE service_requests SET status = 'مكتمل' WHERE id = ?");
            $update->execute([$request_id]);

            $success = "تمت عملية الدفع بنجاح. شكراً لاستخدامك خدمتنا.";
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء معالجة الدفع. حاول لاحقًا.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>دفع طلب الخدمة رقم #<?= htmlspecialchars($request['id']) ?> - Wassal Khedma</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: linear-gradient(135deg, #e0f2f1, #80cbc4);
      margin: 0;
      padding: 20px;
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: flex-start;
    }
    .container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 25px rgba(0,0,0,0.15);
      max-width: 520px;
      width: 100%;
      padding: 35px 40px;
      box-sizing: border-box;
    }
    h1 {
      color: #00796b;
      text-align: center;
      margin-bottom: 30px;
      font-weight: 700;
      letter-spacing: 0.05em;
    }
    .request-info {
      background: #e0f2f1;
      padding: 20px 25px;
      border-radius: 12px;
      margin-bottom: 30px;
      color: #004d40;
      font-weight: 600;
      font-size: 15px;
      line-height: 1.5;
    }
    .request-info p {
      margin: 8px 0;
    }
    label {
      display: block;
      margin-top: 18px;
      color: #00695c;
      font-weight: 600;
    }
    input[type="text"],
    input[type="password"],
    select {
      width: 100%;
      padding: 13px 15px;
      margin-top: 6px;
      border-radius: 10px;
      border: 2px solid #80cbc4;
      font-size: 16px;
      transition: border-color 0.3s ease;
      box-sizing: border-box;
    }
    input[type="text"]:focus,
    input[type="password"]:focus,
    select:focus {
      border-color: #00796b;
      outline: none;
    }
    button {
      margin-top: 32px;
      width: 100%;
      background-color: #00796b;
      border: none;
      color: white;
      font-size: 20px;
      font-weight: 700;
      padding: 14px;
      border-radius: 12px;
      cursor: pointer;
      transition: background-color 0.3s ease;
      letter-spacing: 0.05em;
    }
    button:hover {
      background-color: #004d40;
    }
    .error-msg, .success-msg {
      text-align: center;
      padding: 14px;
      border-radius: 12px;
      margin-bottom: 25px;
      font-weight: 700;
    }
    .error-msg {
      background-color: #ef5350;
      color: #b71c1c;
    }
    .success-msg {
      background-color: #66bb6a;
      color: #1b5e20;
    }
    /* شعارات الدفع */
    .cards-logos {
      display: flex;
      justify-content: center;
      gap: 25px;
      margin-top: 30px;
    }
    .cards-logos img {
      width: 60px;
      height: auto;
      filter: drop-shadow(0 1px 1px rgba(0,0,0,0.1));
    }
    /* رابط العودة */
    .back-link {
      display: inline-block;
      margin-bottom: 20px;
      color: #00796b;
      font-weight: 700;
      text-decoration: none;
      transition: color 0.3s ease;
      cursor: pointer;
    }
    .back-link:hover {
      color: #004d40;
      text-decoration: underline;
    }
  </style>
</head>
<body>

<div class="container" role="main" aria-label="نموذج دفع طلب الخدمة رقم <?= htmlspecialchars($request['id']) ?>">
  <a href="dashboard.php" class="back-link" aria-label="العودة إلى لوحة التحكم">&larr; العودة إلى لوحة التحكم</a>
  <h1>دفع طلب الخدمة رقم #<?= htmlspecialchars($request['id']) ?></h1>

  <section class="request-info" aria-live="polite">
    <p><strong>اسم العميل:</strong> <?= htmlspecialchars($request['client_name']) ?></p>
    <p><strong>اسم العامل:</strong> <?= htmlspecialchars($request['worker_name']) ?></p>
    <p><strong>نوع الخدمة:</strong> <?= htmlspecialchars($request['skill']) ?></p>
    <p><strong>تاريخ الخدمة:</strong> <?= htmlspecialchars($request['service_date']) ?> <?= htmlspecialchars($request['service_time']) ?></p>
    <p><strong>الحالة الحالية:</strong> <?= htmlspecialchars($request['status']) ?></p>
    <p><strong>المبلغ المطلوب:</strong> 100 جنيه (تجريبي)</p>
  </section>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <?php if (!$success): ?>
  <form method="POST" action="payments.php?request_id=<?= htmlspecialchars($request_id) ?>" novalidate aria-label="نموذج إدخال بيانات الدفع">
    <label for="card_name">اسم حامل البطاقة <span style="color:#e53935;">*</span></label>
    <input type="text" id="card_name" name="card_name" placeholder="مثلاً: Ahmed Mohamed" required autocomplete="cc-name" value="<?= htmlspecialchars($_POST['card_name'] ?? '') ?>" />

    <label for="card_number">رقم البطاقة <span style="color:#e53935;">*</span></label>
    <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required autocomplete="cc-number"
           oninput="this.value=this.value.replace(/[^\d ]/g,'').replace(/(.{4})/g,'$1 ').trim()" value="<?= htmlspecialchars($_POST['card_number'] ?? '') ?>" />

    <label for="expiry_month">شهر انتهاء الصلاحية <span style="color:#e53935;">*</span></label>
    <select id="expiry_month" name="expiry_month" required autocomplete="cc-exp-month">
      <option value="" disabled <?= !isset($_POST['expiry_month']) ? 'selected' : '' ?>>اختر الشهر</option>
      <?php for ($m=1; $m<=12; $m++):
        $month = str_pad($m, 2, '0', STR_PAD_LEFT);
      ?>
        <option value="<?= $month ?>" <?= (isset($_POST['expiry_month']) && $_POST['expiry_month'] === $month) ? 'selected' : '' ?>><?= $month ?></option>
      <?php endfor; ?>
    </select>

    <label for="expiry_year">سنة انتهاء الصلاحية <span style="color:#e53935;">*</span></label>
    <select id="expiry_year" name="expiry_year" required autocomplete="cc-exp-year">
      <option value="" disabled <?= !isset($_POST['expiry_year']) ? 'selected' : '' ?>>اختر السنة</option>
      <?php
        $currentYear = (int)date('Y');
        for ($y = $currentYear; $y <= $currentYear + 10; $y++):
      ?>
        <option value="<?= $y ?>" <?= (isset($_POST['expiry_year']) && (int)$_POST['expiry_year'] === $y) ? 'selected' : '' ?>><?= $y ?></option>
      <?php endfor; ?>
    </select>

    <label for="cvv">رمز CVV <span style="color:#e53935;">*</span></label>
    <input type="password" id="cvv" name="cvv" placeholder="مثلاً: 123" required maxlength="4" autocomplete="cc-csc" />

    <button type="submit">دفع الآن</button>
  </form>
  <?php endif; ?>
</div>

</body>
</html>
