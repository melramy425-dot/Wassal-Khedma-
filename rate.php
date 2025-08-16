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
$service_id = $_GET['service_id'] ?? null;

if (!$service_id) {
    die('معرّف الخدمة غير موجود.');
}

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // تحقق إن الطلب يخص العميل الحالي
    $stmt = $pdo->prepare("SELECT sr.*, w.name AS worker_name FROM service_requests sr JOIN workers w ON sr.worker_id = w.id WHERE sr.id = ? AND sr.client_id = ?");
    $stmt->execute([$service_id, $_SESSION['user_id']]);
    $service = $stmt->fetch();

    if (!$service) {
        die('طلب الخدمة غير موجود أو غير مسموح لك بتقييمه.');
    }

    // تحقق إذا التقييم موجود مسبقًا
    $stmt = $pdo->prepare("SELECT * FROM service_ratings WHERE service_request_id = ?");
    $stmt->execute([$service_id]);
    $existing_rating = $stmt->fetch();

} catch (PDOException $e) {
    die('خطأ في الاتصال بقاعدة البيانات.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = intval($_POST['rating'] ?? 0);
    $review = trim($_POST['review'] ?? '');

    if ($rating < 1 || $rating > 5) {
        $error = 'يرجى اختيار تقييم صحيح من 1 إلى 5.';
    } else {
        try {
            if ($existing_rating) {
                // تحديث التقييم
                $stmt = $pdo->prepare("UPDATE service_ratings SET rating = ?, review = ?, updated_at = NOW() WHERE id = ?");
                $stmt->execute([$rating, $review, $existing_rating['id']]);
            } else {
                // إضافة تقييم جديد
                $stmt = $pdo->prepare("INSERT INTO service_ratings (service_request_id, client_id, rating, review, created_at) VALUES (?, ?, ?, ?, NOW())");
                $stmt->execute([$service_id, $_SESSION['user_id'], $rating, $review]);
            }
            $success = 'تم حفظ التقييم بنجاح. شكراً لك!';
            // لتحديث بيانات التقييم بعد الإضافة أو التحديث
            $existing_rating = ['rating' => $rating, 'review' => $review];
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء حفظ التقييم.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>تقييم الخدمة - Wassal Khedma</title>
<style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f4f7f9;
    margin: 0; padding: 20px;
  }
  .container {
    max-width: 600px;
    margin: 0 auto;
    background: white;
    border-radius: 15px;
    padding: 30px;
    box-shadow: 0 0 20px rgba(0,0,0,0.1);
  }
  h1 {
    color: #2c3e50;
    margin-bottom: 25px;
    text-align: center;
  }
  label {
    font-weight: bold;
    margin-top: 15px;
    display: block;
    color: #34495e;
  }
  select, textarea {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    border-radius: 8px;
    border: 1.5px solid #ccc;
    font-size: 16px;
    transition: border-color 0.3s;
  }
  select:focus, textarea:focus {
    border-color: #1abc9c;
    outline: none;
  }
  textarea {
    min-height: 100px;
    resize: vertical;
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
</style>
</head>
<body>

<div class="container" role="main" aria-label="نموذج تقييم الخدمة">
  <h1>تقييم الخدمة للعامل: <?= htmlspecialchars($service['worker_name']) ?></h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="rate.php?service_id=<?= urlencode($service_id) ?>" novalidate>
    <label for="rating">التقييم (عدد النجوم)</label>
    <select id="rating" name="rating" required>
      <option value="">اختر تقييمك</option>
      <?php for ($i = 1; $i <= 5; $i++): ?>
        <option value="<?= $i ?>" <?= (isset($existing_rating['rating']) && $existing_rating['rating'] == $i) ? 'selected' : '' ?>><?= $i ?> نجوم</option>
      <?php endfor; ?>
    </select>

    <label for="review">رأيك في الخدمة (اختياري)</label>
    <textarea id="review" name="review" placeholder="اكتب تعليقك"><?= htmlspecialchars($existing_rating['review'] ?? '') ?></textarea>

    <button type="submit">إرسال التقييم</button>
  </form>
</div>
<?php include 'footer.php'; ?>

</body>
</html>
