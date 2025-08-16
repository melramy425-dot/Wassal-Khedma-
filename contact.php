<?php
session_start();

// لو حابب تخلي الصفحة خاصة بالمستخدمين، شيل التعليق عن الكود التالي:
// if (!isset($_SESSION['user_id'])) {
//     header('Location: login.php');
//     exit();
// }

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

$error = '';
$success = '';

// جلب بيانات النموذج مع تهيئة القيم الافتراضية
$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$message = $_POST['message'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحقق من تعبئة الحقول
    if (!$name || !$email || !$message) {
        $error = 'يرجى ملء جميع الحقول.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صالح.';
    } else {
        try {
            // إنشاء اتصال بقاعدة البيانات
            $pdo = new PDO($dsn, $user, $pass, $options);

            // تحضير وتنفيذ الإدخال في جدول الرسائل
            $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, message, sent_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$name, $email, $message]);

            // نجاح الإرسال
            $success = "تم إرسال رسالتك بنجاح، سنرد عليك في أقرب وقت.";
            // تفريغ الحقول بعد الإرسال
            $name = $email = $message = '';
        } catch (PDOException $e) {
            // تسجيل الخطأ لاستخدامه من قبل المطور (اختياري)
            // error_log($e->getMessage());
            $error = 'حدث خطأ أثناء إرسال الرسالة. حاول مرة أخرى.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>تواصل معنا - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
    margin: 0;
    padding: 20px;
  }
  .container {
    max-width: 600px;
    margin: 40px auto;
    background: #fff;
    border-radius: 15px;
    padding: 30px 35px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }
  h1 {
    color: #2c3e50;
    text-align: center;
    margin-bottom: 30px;
    font-weight: 700;
  }
  label {
    font-weight: 600;
    display: block;
    margin-top: 20px;
    color: #34495e;
  }
  input, textarea {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  input:focus, textarea:focus {
    border-color: #1abc9c;
    outline: none;
  }
  textarea {
    resize: vertical;
    min-height: 140px;
  }
  button {
    margin-top: 30px;
    width: 100%;
    background-color: #1abc9c;
    border: none;
    color: white;
    font-weight: 700;
    font-size: 18px;
    padding: 15px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #16a085;
  }
  .error-msg, .success-msg {
    text-align: center;
    margin-bottom: 25px;
    padding: 14px;
    border-radius: 8px;
    font-weight: 600;
  }
  .error-msg {
    background-color: #e74c3c;
    color: white;
  }
  .success-msg {
    background-color: #27ae60;
    color: white;
  }
</style>
</head>
<body>

<div class="container" role="main" aria-label="نموذج تواصل معنا">
  <h1>تواصل معنا</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="contact.php" novalidate>
    <label for="name">الاسم الكامل</label>
    <input type="text" id="name" name="name" required placeholder="مثلاً: أحمد محمد" value="<?= htmlspecialchars($name) ?>" />

    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email" required placeholder="example@mail.com" value="<?= htmlspecialchars($email) ?>" />

    <label for="message">رسالتك</label>
    <textarea id="message" name="message" required placeholder="اكتب رسالتك"><?= htmlspecialchars($message) ?></textarea>

    <button type="submit">إرسال</button>
  </form>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
