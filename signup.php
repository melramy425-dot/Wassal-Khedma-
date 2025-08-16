<?php
session_start();

// لو المستخدم مسجل دخول يروح للداشبورد
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
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

$error = '';
$success = '';

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';
$password_confirm = $_POST['password_confirm'] ?? '';
$role = $_POST['role'] ?? 'client';  // القيمة الافتراضية للعضو "عميل"

$allowed_roles = ['admin', 'worker', 'client'];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // تحقق من صحة المدخلات
        if (!$name || !$email || !$password || !$password_confirm || !$role) {
            $error = "جميع الحقول مطلوبة.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "يرجى إدخال بريد إلكتروني صالح.";
        } elseif ($password !== $password_confirm) {
            $error = "كلمتا المرور غير متطابقتين.";
        } elseif (!in_array($role, $allowed_roles)) {
            $error = "دور المستخدم غير صالح.";
        } else {
            // تحقق إذا كان البريد موجود مسبقًا
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = "هذا البريد مستخدم بالفعل.";
            } else {
                // تشفير كلمة المرور
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // إدخال المستخدم الجديد
                $stmt = $pdo->prepare("INSERT INTO users (name, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$name, $email, $password_hash, $role]);

                $success = "تم التسجيل بنجاح. يمكنك الآن تسجيل الدخول.";
                
                // يمكن توجيه المستخدم مباشرةً إلى صفحة تسجيل الدخول مثلاً
                // header('Location: login.php');
                // exit();
            }
        }
    }
} catch (PDOException $e) {
    $error = "حدث خطأ في الخادم، يرجى المحاولة لاحقًا.";
    // يمكنك تسجيل الخطأ في ملف لغايات التصحيح:
    // error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>تسجيل مستخدم جديد - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
    margin: 0;
    padding: 20px;
  }
  .container {
    max-width: 480px;
    margin: 40px auto;
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 25px rgba(0,0,0,0.1);
  }
  h1 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 25px;
  }
  label {
    font-weight: bold;
    margin-top: 15px;
    display: block;
    color: #34495e;
  }
  input, select {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  input:focus, select:focus {
    border-color: #1abc9c;
    outline: none;
  }
  button {
    margin-top: 25px;
    background-color: #1abc9c;
    border: none;
    color: white;
    font-weight: bold;
    font-size: 18px;
    padding: 14px;
    border-radius: 8px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s;
  }
  button:hover {
    background-color: #16a085;
  }
  .error-msg, .success-msg {
    text-align: center;
    margin-bottom: 20px;
    padding: 12px;
    border-radius: 8px;
  }
  .error-msg {
    background-color: #e74c3c;
    color: white;
  }
  .success-msg {
    background-color: #27ae60;
    color: white;
  }
  .login-link {
    text-align: center;
    margin-top: 15px;
    font-size: 14px;
  }
  .login-link a {
    color: #1abc9c;
    text-decoration: none;
  }
  .login-link a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="container" role="main" aria-label="نموذج تسجيل مستخدم جديد">
  <h1>تسجيل مستخدم جديد</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="signup.php" novalidate>
    <label for="name">الاسم الكامل</label>
    <input type="text" id="name" name="name" required placeholder="مثلاً: أحمد محمد" value="<?= htmlspecialchars($name) ?>" aria-required="true" />

    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email" required placeholder="example@mail.com" value="<?= htmlspecialchars($email) ?>" aria-required="true" />

    <label for="password">كلمة المرور</label>
    <input type="password" id="password" name="password" required minlength="6" placeholder="********" aria-required="true" />

    <label for="password_confirm">تأكيد كلمة المرور</label>
    <input type="password" id="password_confirm" name="password_confirm" required minlength="6" placeholder="********" aria-required="true" />

    <label for="role">نوع المستخدم</label>
    <select id="role" name="role" required aria-required="true">
      <option value="client" <?= $role === 'client' ? 'selected' : '' ?>>عميل</option>
      <option value="worker" <?= $role === 'worker' ? 'selected' : '' ?>>عامل</option>
      <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>مسؤول</option>
    </select>

    <button type="submit">تسجيل</button>
  </form>

  <div class="login-link">
    <p>هل لديك حساب؟ <a href="login.php">تسجيل الدخول</a></p>
  </div>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
