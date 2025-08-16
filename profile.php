<?php
session_start();

// لو مش مسجل دخول، نرجع للدخول
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

$error = '';
$success = '';

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // جلب بيانات المستخدم
    $stmt = $pdo->prepare("SELECT id, name, email, role, created_at, status FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();

    if (!$user) {
        // لو معرفش يجيب بيانات المستخدم يخرج
        session_destroy();
        header('Location: login.php');
        exit;
    }

    // تحديث كلمة المرور
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['current_password'], $_POST['new_password'], $_POST['confirm_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // تحقق من كلمة المرور الحالية
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user['id']]);
        $row = $stmt->fetch();

        if (!password_verify($current_password, $row['password_hash'])) {
            $error = 'كلمة المرور الحالية غير صحيحة.';
        } elseif (strlen($new_password) < 6) {
            $error = 'كلمة المرور الجديدة يجب أن تكون 6 أحرف على الأقل.';
        } elseif ($new_password !== $confirm_password) {
            $error = 'كلمتا المرور الجديدتان غير متطابقتين.';
        } else {
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->execute([$new_password_hash, $user['id']]);
            $success = 'تم تحديث كلمة المرور بنجاح.';
        }
    }
} catch (PDOException $e) {
    $error = 'حدث خطأ في قاعدة البيانات.';
    // error_log($e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>الصفحة الشخصية - <?= htmlspecialchars($user['name'] ?? '') ?></title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f8;
    margin: 0;
    padding: 0;
  }

  header {
    background-color: #2c3e50;
    color: white;
    padding: 20px;
    text-align: center;
    font-size: 1.6rem;
    font-weight: bold;
  }

  nav {
    background-color: #34495e;
    display: flex;
    justify-content: center;
    gap: 25px;
    padding: 15px 0;
  }

  nav a {
    color: white;
    text-decoration: none;
    font-weight: bold;
    font-size: 1rem;
    padding: 6px 15px;
    border-radius: 8px;
    transition: background-color 0.3s ease;
  }

  nav a:hover, nav a.active {
    background-color: #1abc9c;
  }

  main {
    max-width: 900px;
    margin: 40px auto;
    background: white;
    border-radius: 12px;
    box-shadow: 0 0 25px rgba(0,0,0,0.1);
    padding: 30px 40px;
  }

  h2 {
    text-align: center;
    color: #2c3e50;
    margin-bottom: 30px;
  }

  .info {
    display: grid;
    grid-template-columns: 1fr 2fr;
    gap: 15px 40px;
    font-size: 1.1rem;
    color: #34495e;
    margin-bottom: 40px;
  }

  .info label {
    font-weight: bold;
    text-align: right;
  }

  .info div {
    background: #ecf0f1;
    border-radius: 8px;
    padding: 12px 15px;
  }

  form {
    max-width: 500px;
    margin: 0 auto;
  }

  form label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
    color: #2c3e50;
  }

  form input {
    width: 100%;
    padding: 12px 15px;
    margin-bottom: 20px;
    border: 1.5px solid #ccc;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
  }

  form input:focus {
    border-color: #1abc9c;
    outline: none;
  }

  form button {
    width: 100%;
    background-color: #1abc9c;
    border: none;
    padding: 15px;
    font-size: 1.1rem;
    color: white;
    font-weight: bold;
    border-radius: 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
  }

  form button:hover {
    background-color: #16a085;
  }

  .alert {
    max-width: 500px;
    margin: 15px auto 30px;
    padding: 15px;
    border-radius: 10px;
    font-weight: bold;
    text-align: center;
  }

  .alert.error {
    background-color: #e74c3c;
    color: white;
  }

  .alert.success {
    background-color: #27ae60;
    color: white;
  }

  footer {
    background-color: #2c3e50;
    color: white;
    text-align: center;
    padding: 18px;
    margin-top: 60px;
    font-size: 0.9rem;
  }

  @media(max-width: 600px) {
    .info {
      grid-template-columns: 1fr;
    }
  }
</style>
</head>
<body>

<header>الصفحة الشخصية</header>

<nav>
  <a href="dashboard.php">لوحة التحكم</a>
  <a href="profile.php" class="active">الصفحة الشخصية</a>
  <a href="logout.php" onclick="return confirm('هل أنت متأكد من تسجيل الخروج؟')">تسجيل خروج</a>
</nav>

<main>
  <h2>مرحبا، <?= htmlspecialchars($user['name']) ?>!</h2>

  <?php if ($error): ?>
    <div class="alert error" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert success" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <section class="info">
    <label>الاسم الكامل:</label>
    <div><?= htmlspecialchars($user['name']) ?></div>

    <label>البريد الإلكتروني:</label>
    <div><?= htmlspecialchars($user['email']) ?></div>

    <label>الدور:</label>
    <div>
      <?php 
      switch($user['role']) {
        case 'admin': echo "مسؤول"; break;
        case 'worker': echo "عامل"; break;
        case 'client': echo "عميل"; break;
        default: echo "غير محدد"; 
      }
      ?>
    </div>

    <label>تاريخ التسجيل:</label>
    <div><?= date("Y-m-d", strtotime($user['created_at'])) ?></div>

    <label>حالة الحساب:</label>
    <div><?= $user['status'] == 1 ? "نشط" : "موقوف" ?></div>
  </section>

  <section>
    <h3 style="text-align:center; margin-bottom: 20px; color:#2c3e50;">تغيير كلمة المرور</h3>
    <form method="POST" action="profile.php" novalidate>
      <label for="current_password">كلمة المرور الحالية</label>
      <input type="password" id="current_password" name="current_password" required placeholder="ادخل كلمة المرور الحالية" />

      <label for="new_password">كلمة المرور الجديدة</label>
      <input type="password" id="new_password" name="new_password" required placeholder="كلمة مرور جديدة" />

      <label for="confirm_password">تأكيد كلمة المرور الجديدة</label>
      <input type="password" id="confirm_password" name="confirm_password" required placeholder="تأكيد كلمة المرور" />

      <button type="submit">تحديث كلمة المرور</button>
    </form>
  </section>
</main>

<footer>
  &copy; 2025 wassal khedma - كل الحقوق محفوظة.
</footer>
<?php include 'footer.php'; ?>

</body>
</html>
