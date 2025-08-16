<?php
session_start();

// حماية الصفحة: فقط العملاء (client) يمكنهم الوصول
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'client') {
    header('Location: login.php');
    exit();
}

// تمديد الجلسة 30 دقيقة بدون نشاط
$timeout_duration = 1800;
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY']) > $timeout_duration) {
    session_unset();
    session_destroy();
    header('Location: login.php?timeout=1');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// إعدادات اتصال قاعدة البيانات (تأكد تعدلها حسب بيئتك)
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

    // جلب قائمة التخصصات الفريدة (skills) من جدول العمال لملء قائمة الخدمات
    $stmt = $pdo->query("SELECT DISTINCT skill FROM workers ORDER BY skill ASC");
    $skills = $stmt->fetchAll(PDO::FETCH_COLUMN);

} catch (PDOException $e) {
    die('فشل الاتصال بقاعدة البيانات. الرجاء المحاولة لاحقاً.');
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $skill = $_POST['skill'] ?? '';
    $worker_id = $_POST['worker_id'] ?? '';
    $details = trim($_POST['details'] ?? '');
    $date = $_POST['date'] ?? '';
    $time = $_POST['time'] ?? '';

    // تحقق من صحة الحقول الأساسية
    if (!$skill || !$worker_id || !$date || !$time) {
        $error = 'يرجى تعبئة جميع الحقول المطلوبة.';
    } else {
        try {
            // تحقق أن العامل موجود ومطابق للتخصص المحدد
            $stmt = $pdo->prepare("SELECT id FROM workers WHERE id = ? AND skill = ?");
            $stmt->execute([$worker_id, $skill]);
            if (!$stmt->fetch()) {
                $error = 'العامل المختار غير صالح أو لا يتوافق مع التخصص.';
            } else {
                // إدخال الطلب في قاعدة البيانات مع حالة مبدئية "قيد الانتظار"
                $stmt = $pdo->prepare("INSERT INTO service_requests (client_id, worker_id, skill, details, service_date, service_time, status, created_at) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())");
                $stmt->execute([$_SESSION['user_id'], $worker_id, $skill, $details, $date, $time]);
                $success = 'تم إرسال طلب الخدمة بنجاح. سيتم التواصل معك قريباً.';
                // مسح بيانات الإدخال بعد النجاح
                $_POST = [];
            }
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء معالجة الطلب. حاول مرة أخرى لاحقاً.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>طلب خدمة جديدة - Wassal Khedma</title>
<style>
  /* --- Base & Reset --- */
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #e0f7fa, #b2ebf2);
    margin: 0; padding: 20px;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: flex-start;
  }
  .container {
    background: #fff;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 650px;
    padding: 35px 40px;
  }
  h1 {
    text-align: center;
    color: #00796b;
    margin-bottom: 30px;
    font-weight: 700;
  }
  label {
    display: block;
    margin-top: 20px;
    font-weight: 600;
    color: #004d40;
  }
  select, input[type="date"], input[type="time"], textarea {
    width: 100%;
    padding: 12px 15px;
    margin-top: 8px;
    border-radius: 8px;
    border: 2px solid #80cbc4;
    font-size: 16px;
    transition: border-color 0.3s ease;
  }
  select:focus, input:focus, textarea:focus {
    border-color: #00796b;
    outline: none;
  }
  textarea {
    min-height: 120px;
    resize: vertical;
  }
  button {
    margin-top: 30px;
    background-color: #00796b;
    border: none;
    color: white;
    font-weight: 700;
    font-size: 20px;
    padding: 14px;
    border-radius: 12px;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease;
  }
  button:hover {
    background-color: #004d40;
  }
  .error-msg, .success-msg {
    margin-bottom: 25px;
    border-radius: 12px;
    padding: 15px;
    font-weight: 700;
    text-align: center;
  }
  .error-msg {
    background-color: #e57373;
    color: #b71c1c;
    border: 2px solid #b71c1c;
  }
  .success-msg {
    background-color: #81c784;
    color: #1b5e20;
    border: 2px solid #1b5e20;
  }
</style>

<script>
function fetchWorkers() {
    const skillSelect = document.getElementById('skill');
    const workerSelect = document.getElementById('worker_id');
    const skill = skillSelect.value;

    workerSelect.innerHTML = '<option value="">جاري التحميل...</option>';

    if (!skill) {
        workerSelect.innerHTML = '<option value="">اختر التخصص أولاً</option>';
        return;
    }

    fetch(`fetch_workers.php?skill=${encodeURIComponent(skill)}`)
        .then(res => res.json())
        .then(data => {
            if (data.length === 0) {
                workerSelect.innerHTML = '<option value="">لا يوجد عمال لهذا التخصص</option>';
            } else {
                workerSelect.innerHTML = '<option value="">اختر العامل</option>';
                data.forEach(worker => {
                    const option = document.createElement('option');
                    option.value = worker.id;
                    option.textContent = `${worker.name} (${worker.location ?? 'بدون موقع'})`;
                    workerSelect.appendChild(option);
                });
                // لو كان في اختيار سابق من POST خليه محدد
                <?php if (!empty($_POST['worker_id'])): ?>
                  workerSelect.value = <?= json_encode($_POST['worker_id']) ?>;
                <?php endif; ?>
            }
        })
        .catch(() => {
            workerSelect.innerHTML = '<option value="">حدث خطأ أثناء جلب العمال</option>';
        });
}

// تحميل العمال عند فتح الصفحة لو فيه تخصص محدد
window.addEventListener('DOMContentLoaded', () => {
    if(document.getElementById('skill').value){
      fetchWorkers();
    }
});
</script>
</head>
<body>

<div class="container" role="main" aria-label="نموذج طلب خدمة جديدة">
  <h1>طلب خدمة جديدة</h1>

  <?php if ($error): ?>
    <div class="error-msg" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="success-msg" role="alert"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="requests.php" novalidate aria-describedby="formInstructions">
    <p id="formInstructions" style="display:none;">يرجى تعبئة جميع الحقول المطلوبة بدقة.</p>

    <label for="skill">نوع الخدمة / التخصص <span aria-hidden="true" style="color:#e53935;">*</span></label>
    <select id="skill" name="skill" required aria-required="true" onchange="fetchWorkers()">
      <option value="">اختر التخصص</option>
      <?php foreach ($skills as $skill): ?>
        <option value="<?= htmlspecialchars($skill) ?>" <?= (isset($_POST['skill']) && $_POST['skill'] === $skill) ? 'selected' : '' ?>>
          <?= htmlspecialchars($skill) ?>
        </option>
      <?php endforeach; ?>
    </select>

    <label for="worker_id">اختر العامل <span aria-hidden="true" style="color:#e53935;">*</span></label>
    <select id="worker_id" name="worker_id" required aria-required="true" aria-disabled="true">
      <option value="">اختر التخصص أولاً</option>
    </select>

    <label for="details">تفاصيل الخدمة (اختياري)</label>
    <textarea id="details" name="details" placeholder="اشرح بالتفصيل ما تحتاجه"><?= htmlspecialchars($_POST['details'] ?? '') ?></textarea>

    <label for="date">تاريخ الخدمة <span aria-hidden="true" style="color:#e53935;">*</span></label>
    <input
      type="date"
      id="date"
      name="date"
      required
      aria-required="true"
      min="<?= date('Y-m-d') ?>"
      value="<?= htmlspecialchars($_POST['date'] ?? '') ?>"
    />

    <label for="time">وقت الخدمة <span aria-hidden="true" style="color:#e53935;">*</span></label>
    <input
      type="time"
      id="time"
      name="time"
      required
      aria-required="true"
      value="<?= htmlspecialchars($_POST['time'] ?? '') ?>"
    />

    <button type="submit" aria-label="إرسال طلب الخدمة">إرسال الطلب</button>
  </form>
</div>

</body>
</html>
