<?php
session_start();

// إعدادات قاعدة البيانات (غيرهم حسب بياناتك)
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $role = $_POST['role'] ?? '';

    // تحقق من الإدخالات
    if (!$name || !$email || !$password || !$password_confirm || !$role) {
        $error = 'يرجى تعبئة جميع الحقول.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صالح.';
    } elseif ($password !== $password_confirm) {
        $error = 'كلمتا المرور غير متطابقتين.';
    } elseif (!in_array($role, ['admin', 'worker', 'client'])) {
        $error = 'الدور غير صالح.';
    } else {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // تحقق إذا كان الإيميل موجود بالفعل
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'هذا البريد الإلكتروني مستخدم مسبقاً.';
            } else {
                // تشفير كلمة المرور
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                // إدخال المستخدم الجديد
                $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, ?)');
                $stmt->execute([$name, $email, $password_hash, $role]);

                $success = 'تم التسجيل بنجاح! يمكنك الآن تسجيل الدخول.';
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ في الاتصال بقاعدة البيانات.';
            // error_log($e->getMessage());
        }
    }
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
    background-color: #f4f7f9;
    margin: 0;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
  }

  .register-container {
    background: white;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0,0,0,0.1);
    width: 360px;
  }

  h1 {
    margin-bottom: 30px;
    text-align: center;
    color: #2c3e50;
  }

  label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #34495e;
  }

  input, select {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 1.5px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
  }

  input:focus, select:focus {
    border-color: #1abc9c;
    outline: none;
  }

  button {
    width: 100%;
    background-color: #1abc9c;
    border: none;
    padding: 14px;
    font-size: 18px;
    color: white;
    font-weight: bold;
    border-radius: 8px;
    cursor: pointer;
    transition: background-color 0.3s;
  }

  button:hover {
    background-color: #16a085;
  }

  .error-msg {
    background-color: #e74c3c;
    color: white;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 8px;
    text-align: center;
  }

  .success-msg {
    background-color: #27ae60;
    color: white;
    padding: 10px;
    margin-bottom: 20px;
    border-radius: 8px;
    text-align: center;
  }

  a {
    color: #1abc9c;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }

  .footer {
    margin-top: 15px;
    font-size: 14px;
    color: #888;
    text-align: center;
  }
</style>
</head>
<body>

<div class="register-container" role="main" aria-label="نموذج تسجيل مستخدم جديد">
  <h1>تسجيل مستخدم جديد</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="register.php" novalidate>
    <label for="name">الاسم الكامل</label>
    <input type="text" id="name" name="name" required placeholder="مثلاً: أحمد محمد" />

    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email" required placeholder="example@mail.com" />

    <label for="password">كلمة المرور</label>
    <input type="password" id="password" name="password" required placeholder="********" />

    <label for="password_confirm">تأكيد كلمة المرور</label>
    <input type="password" id="password_confirm" name="password_confirm" required placeholder="********" />

    <label for="role">اختر دور المستخدم</label>
    <select id="role" name="role" required>
      <option value="">-- اختر --</option>
      <option value="client">عميل</option>
      <option value="worker">عامل</option>
      <option value="admin">مسؤول</option>
    </select>

    <button type="submit">تسجيل</button>
  </form>

  <div class="footer">
    <p>هل لديك حساب؟ <a href="login.php">سجل دخولك هنا</a></p>
  </div>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
