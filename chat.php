<?php
session_start();
if (!isset($_SESSION['user_id'])) {
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
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

// عشان نبسط، نفرض ان عندنا عميل وعامل وبنعرف ال IDs اللي هنتحدث بينهم (ممكن تجيبهم من طلب معين)
// هنا مثال ثابت، عدل حسب استخدامك:
$client_id = $_SESSION['user_role'] == 'client' ? $_SESSION['user_id'] : 1;  // مثال
$worker_id = $_SESSION['user_role'] == 'worker' ? $_SESSION['user_id'] : 2;  // مثال

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // إذا في رسالة جديدة مرسلة عبر AJAX (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['message'])) {
        $message = trim($_POST['message']);
        if ($message !== '') {
            $sender_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO chat_messages (sender_id, receiver_id, message, sent_at) VALUES (?, ?, ?, NOW())");
            // نحدد المستقبل حسب المرسل (لو العميل أرسل، المستقبل هو العامل والعكس صحيح)
            $receiver_id = ($sender_id == $client_id) ? $worker_id : $client_id;
            $stmt->execute([$sender_id, $receiver_id, $message]);
            echo json_encode(['status' => 'success']);
            exit();
        } else {
            echo json_encode(['status' => 'error', 'msg' => 'الرسالة فارغة']);
            exit();
        }
    }

    // جلب آخر 20 رسالة بين العميل والعامل
    $stmt = $pdo->prepare("SELECT cm.*, u.name AS sender_name FROM chat_messages cm JOIN users u ON cm.sender_id = u.id WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?) ORDER BY sent_at ASC LIMIT 20");
    $stmt->execute([$client_id, $worker_id, $worker_id, $client_id]);
    $messages = $stmt->fetchAll();

} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات.");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>الدردشة - Wassal Khedma</title>
<style>
  body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f4f7f9; margin: 0; padding: 20px; }
  .chat-container { max-width: 700px; margin: auto; background: white; border-radius: 10px; padding: 20px; box-shadow: 0 0 15px rgba(0,0,0,0.1); }
  .messages { height: 400px; overflow-y: auto; border: 1px solid #ddd; padding: 15px; border-radius: 8px; background: #fafafa; }
  .message { margin-bottom: 15px; max-width: 70%; padding: 10px 15px; border-radius: 15px; }
  .sent { background-color: #1abc9c; color: white; margin-left: auto; text-align: right; }
  .received { background-color: #ecf0f1; color: #2c3e50; text-align: left; }
  form { margin-top: 15px; display: flex; gap: 10px; }
  textarea { flex-grow: 1; padding: 12px; border-radius: 10px; border: 1.5px solid #ccc; resize: none; font-size: 16px; }
  button { background-color: #1abc9c; border: none; color: white; padding: 14px 20px; border-radius: 10px; font-weight: bold; cursor: pointer; }
  button:hover { background-color: #16a085; }
</style>
</head>
<body>

<div class="chat-container" role="main" aria-label="صفحة الدردشة">
  <h1 style="text-align:center; margin-bottom: 20px;">الدردشة بين العميل والعامل</h1>

  <div class="messages" id="chatMessages" aria-live="polite" aria-relevant="additions">
    <?php foreach ($messages as $msg): ?>
      <?php
        $isSent = ($msg['sender_id'] == $_SESSION['user_id']);
        $class = $isSent ? 'sent' : 'received';
      ?>
      <div class="message <?= $class ?>" tabindex="0" aria-label="<?= htmlspecialchars($msg['sender_name']) ?> يقول: <?= htmlspecialchars($msg['message']) ?>">
        <strong><?= htmlspecialchars($msg['sender_name']) ?>:</strong><br />
        <?= nl2br(htmlspecialchars($msg['message'])) ?>
        <div style="font-size: 11px; color: #777; margin-top: 5px;"><?= date('Y-m-d H:i', strtotime($msg['sent_at'])) ?></div>
      </div>
    <?php endforeach; ?>
  </div>

  <form id="chatForm" aria-label="نموذج إرسال رسالة">
    <textarea id="messageInput" name="message" placeholder="اكتب رسالتك هنا..." rows="3" required aria-required="true"></textarea>
    <button type="submit" aria-label="إرسال الرسالة">إرسال</button>
  </form>
</div>

<script>
  const chatForm = document.getElementById('chatForm');
  const messageInput = document.getElementById('messageInput');
  const chatMessages = document.getElementById('chatMessages');

  chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const message = messageInput.value.trim();
    if (message === '') return;

    fetch('chat.php', {
      method: 'POST',
      headers: {'Content-Type': 'application/x-www-form-urlencoded'},
      body: new URLSearchParams({message})
    })
    .then(res => res.json())
    .then(data => {
      if (data.status === 'success') {
        // إضافة الرسالة الجديدة في نهاية المحادثة
        const msgDiv = document.createElement('div');
        msgDiv.className = 'message sent';
        msgDiv.tabIndex = 0;
        msgDiv.setAttribute('aria-label', 'أنت تقول: ' + message);
        msgDiv.innerHTML = `<strong>أنت:</strong><br />${message.replace(/\n/g, '<br>')}<div style="font-size:11px;color:#777;margin-top:5px;">الآن</div>`;
        chatMessages.appendChild(msgDiv);
        chatMessages.scrollTop = chatMessages.scrollHeight;

        messageInput.value = '';
        messageInput.focus();
      } else {
        alert(data.msg || 'حدث خطأ أثناء إرسال الرسالة.');
      }
    })
    .catch(() => alert('فشل الاتصال بالسيرفر.'));
  });

  // تحديث المحادثة تلقائياً كل 5 ثواني (اختياري)
  setInterval(() => {
    fetch('fetch_chat_messages.php') // محتاج تعمل ملف PHP منفصل يعيد آخر الرسائل بصيغة JSON
      .then(res => res.json())
      .then(data => {
        chatMessages.innerHTML = '';
        data.forEach(msg => {
          const div = document.createElement('div');
          div.className = msg.sender_id === <?= json_encode($_SESSION['user_id']) ?> ? 'message sent' : 'message received';
          div.tabIndex = 0;
          div.setAttribute('aria-label', `${msg.sender_name} يقول: ${msg.message}`);
          div.innerHTML = `<strong>${msg.sender_name}:</strong><br />${msg.message.replace(/\n/g, '<br>')}<div style="font-size:11px;color:#777;margin-top:5px;">${msg.sent_at}</div>`;
          chatMessages.appendChild(div);
        });
        chatMessages.scrollTop = chatMessages.scrollHeight;
      })
      .catch(console.error);
  }, 5000);
</script>
<?php include 'footer.php'; ?>

</body>
</html>
