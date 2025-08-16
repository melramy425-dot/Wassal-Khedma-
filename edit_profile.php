<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
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

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// جلب بيانات المستخدم الحالية
if ($user_role === 'worker') {
    $stmt = $pdo->prepare("SELECT name, email, phone, service FROM workers WHERE id = ?");
} else {
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE id = ?");
}
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    die("المستخدم غير موجود");
}

// متغيرات الحقول (افتراضية)
$name = $user['name'] ?? '';
$email = $user['email'] ?? '';
$phone = $user['phone'] ?? '';
$service = $user['service'] ?? '';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $service = trim($_POST['service'] ?? '');

    // تحقق بسيط
    if (!$name || !$email) {
        $error = 'الاسم والبريد الإلكتروني مطلوبان.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صالح.';
    } else {
        try {
            // تحقق من وجود البريد في مستخدم آخر
            if ($user_role === 'worker') {
                $stmt = $pdo->prepare("SELECT id FROM workers WHERE email = ? AND id != ?");
            } else {
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            }
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = "هذا البريد الإلكتروني مستخدم من قبل.";
            } else {
                // تحديث البيانات
                if ($user_role === 'worker') {
                    $update = $pdo->prepare("UPDATE workers SET name = ?, email = ?, phone = ?, service = ? WHERE id = ?");
                    $update->execute([$name, $email, $phone, $service, $user_id]);
                } else {
                    $update = $pdo->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $update->execute([$name, $email, $user_id]);
                }
                $success = "تم تحديث بياناتك بنجاح.";
                $_SESSION['user_name'] = $name;
                $_SESSION['user_email'] = $email;
            }
        } catch (PDOException $e) {
            $error = "حدث خطأ أثناء التحديث. حاول مرة أخرى.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>تعديل البيانات - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
    margin: 0; padding: 20px;
  }
  .container {
    max-width: 500px;
    background: white;
    margin: 30px auto;
    border-radius: 12px;
    padding: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }
  h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
  }
  label {
    display: block;
    margin-top: 15px;
    font-weight: bold;
    color: #34495e;
  }
  input {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  input:focus {
    border-color: #1abc9c;
    outline: none;
  }
  button {
    margin-top: 30px;
    width: 100%;
    background-color: #1abc9c;
    border: none;
    color: white;
    font-weight: bold;
    font-size: 18px;
    padding: 14px;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
  }
  button:hover {
    background-color: #16a085;
  }
  .error-msg, .success-msg {
    margin-top: 20px;
    padding: 12px;
    border-radius: 8px;
    text-align: center;
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

<div class="container" role="main" aria-label="نموذج تعديل البيانات">
  <h1>تعديل بيانات الحساب</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="edit_profile.php" novalidate>
    <label for="name">الاسم الكامل</label>
    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required autofocus />

    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email" value="<?= htmlspecialchars($email) ?>" required />

    <?php if ($user_role === 'worker'): ?>
      <label for="phone">رقم الهاتف</label>
      <input type="text" id="phone" name="phone" value="<?= htmlspecialchars($phone) ?>" />

      <label for="service">مهارات / الخدمات</label>
      <input type="text" id="service" name="service" value="<?= htmlspecialchars($service) ?>" placeholder="مثلاً: سباكة، كهرباء، تنظيف..." />
    <?php endif; ?>

    <button type="submit">حفظ التعديلات</button>
  </form>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
