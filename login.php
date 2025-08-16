<?php
session_start();

// إذا المستخدم مسجل دخول بالفعل، نوجهه حسب دوره بدون تسجيل الدخول من جديد
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    switch ($_SESSION['user_role']) {
        case 'admin':
            header('Location: dashboard.php');
            exit;
        case 'worker':
            header('Location: worker_dashboard.php');
            exit;
        default:
            header('Location: client_dashboard.php');
            exit;
    }
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

$error = '';
$success = '';

// رسالة نجاح تسجيل الخروج
if (isset($_GET['message']) && $_GET['message'] === 'logout_success') {
    $success = 'تم تسجيل الخروج بنجاح. نراك قريبًا!';
}

// معالجة بيانات تسجيل الدخول عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'يرجى إدخال البريد الإلكتروني وكلمة المرور.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صالح.';
    } else {
        // تحقق خاص بحساب الأدمن الجاهز (بدون قاعدة بيانات)
        if ($email === 'admin@admin.com' && $password === 'admin') {
            $_SESSION['user_id'] = 1; // ثابت للأدمن
            $_SESSION['user_name'] = 'Admin';
            $_SESSION['user_email'] = $email;
            $_SESSION['user_role'] = 'admin';
            $_SESSION['logged_in'] = time();

            header('Location: dashboard.php');
            exit;
        }

        // تحقق من قاعدة البيانات للمستخدمين العاديين
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);
            $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                if (password_verify($password, $user['password_hash'])) {
                    // تسجيل الدخول ناجح
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    $_SESSION['user_role'] = $user['role'];
                    $_SESSION['logged_in'] = time();

                    // التوجيه حسب الدور
                    switch ($user['role']) {
                        case 'admin':
                            header('Location: dashboard.php');
                            exit;
                        case 'worker':
                            header('Location: worker_dashboard.php');
                            exit;
                        default:
                            header('Location: client_dashboard.php');
                            exit;
                    }
                } else {
                    $error = 'كلمة المرور غير صحيحة.';
                }
            } else {
                $error = 'البريد الإلكتروني غير مسجل.';
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
<title>تسجيل الدخول - Wassal Khedma</title>
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

  .login-container {
    background: white;
    padding: 40px 30px;
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0,0,0,0.1);
    width: 350px;
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

  input[type="email"],
  input[type="password"] {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 1.5px solid #ccc;
    border-radius: 8px;
    font-size: 16px;
    transition: border-color 0.3s;
  }

  input[type="email"]:focus,
  input[type="password"]:focus {
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

  .error-msg, .success-msg {
    padding: 10px;
    margin-bottom: 20px;
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

  .footer {
    margin-top: 15px;
    font-size: 14px;
    color: #888;
    text-align: center;
  }

  a {
    color: #1abc9c;
    text-decoration: none;
  }
  a:hover {
    text-decoration: underline;
  }
</style>
</head>
<body>

<div class="login-container" role="main" aria-label="نموذج تسجيل الدخول">
  <h1>تسجيل الدخول</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="login.php" novalidate>
    <label for="email">البريد الإلكتروني</label>
    <input type="email" id="email" name="email" placeholder="example@mail.com" required autofocus autocomplete="username" />

    <label for="password">كلمة المرور</label>
    <input type="password" id="password" name="password" placeholder="********" required autocomplete="current-password" />

    <button type="submit">دخول</button>
  </form>

  <div class="footer">
    <p>ليس لديك حساب؟ <a href="register.php">سجل هنا</a></p>
  </div>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
