<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor', 'guardian'], '../login.php', '../index.php');
$user = current_user();

// Send a new message (Create)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['body']) && !empty($_POST['receiver_id'])) {
    $receiverId = (int)$_POST['receiver_id'];
    $body = trim($_POST['body']);
    $tuitionId = !empty($_POST['tuition_id']) ? (int)$_POST['tuition_id'] : null;

    if ($body !== '' && $receiverId !== (int)$user['id']) {
        $stmt = $conn->prepare('INSERT INTO messages (sender_id, receiver_id, tuition_id, body) VALUES (?,?,?,?)');
        $stmt->bind_param('iiis', $user['id'], $receiverId, $tuitionId, $body);
        $stmt->execute();
        $stmt->close();
    }
    redirect('inbox.php?with=' . $receiverId);
}

$withId = (int)($_GET['with'] ?? 0);
$tuitionId = (int)($_GET['tuition'] ?? 0);

// List of conversations: distinct users this person has exchanged messages with
$conversations = $conn->query("
  SELECT other_id, MAX(created_at) AS last_time FROM (
    SELECT CASE WHEN sender_id = {$user['id']} THEN receiver_id ELSE sender_id END AS other_id, created_at
    FROM messages WHERE sender_id = {$user['id']} OR receiver_id = {$user['id']}
  ) t GROUP BY other_id ORDER BY last_time DESC
");

$convList = [];
while ($row = $conversations->fetch_assoc()) {
    $convList[] = $row['other_id'];
}
// If arriving via ?with= a user we haven't messaged yet, make sure they appear in the list
if ($withId && !in_array($withId, $convList, true)) {
    array_unshift($convList, $withId);
}

// Fetch names for conversation list
$otherUsers = [];
if (!empty($convList)) {
    $ids = implode(',', array_map('intval', $convList));
    $res = $conn->query("SELECT id, name, role FROM users WHERE id IN ($ids)");
    while ($row = $res->fetch_assoc()) {
        $otherUsers[$row['id']] = $row;
    }
}

$activeUser = $withId && isset($otherUsers[$withId]) ? $otherUsers[$withId] : null;
$threadRows = [];
if ($activeUser) {
    $stmt = $conn->prepare("SELECT * FROM messages WHERE (sender_id=? AND receiver_id=?) OR (sender_id=? AND receiver_id=?) ORDER BY created_at ASC");
    $stmt->bind_param('iiii', $user['id'], $withId, $withId, $user['id']);
    $stmt->execute();
    $threadRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // mark incoming as read
    $conn->query("UPDATE messages SET is_read=1 WHERE sender_id=$withId AND receiver_id={$user['id']}");
}
$lastMsgId = !empty($threadRows) ? (int)end($threadRows)['id'] : 0;

$page_title = 'Messages';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding:24px 20px 60px;">
  <div class="page-head"><h1>Messages</h1></div>

  <div class="msg-layout">
    <div class="conv-list">
      <?php if (empty($convList)): ?>
        <div class="empty-state">No conversations yet.</div>
      <?php endif; ?>
      <?php foreach ($convList as $oid): if (!isset($otherUsers[$oid])) continue; $ou = $otherUsers[$oid]; ?>
        <a class="conv-item <?= $oid === $withId ? 'active' : '' ?>" href="inbox.php?with=<?= (int)$oid ?>">
          <div class="name"><?= h($ou['name']) ?></div>
          <div class="preview"><?= h(ucfirst($ou['role'])) ?></div>
        </a>
      <?php endforeach; ?>
    </div>

    <div>
      <?php if (!$activeUser): ?>
        <div class="thread-box" style="align-items:center; justify-content:center; display:flex;">
          <div class="empty-state">Select a conversation to view messages.</div>
        </div>
           <?php else: ?>
        <div class="thread-box" id="threadBox" data-with="<?= (int)$withId ?>" data-last-id="<?= (int)$lastMsgId ?>">
          <div style="padding:14px 16px; border-bottom:1px solid var(--border); font-weight:700;">
            <?= h($activeUser['name']) ?> <span style="color:var(--muted); font-weight:400; font-size:.85rem;">(<?= h(ucfirst($activeUser['role'])) ?>)</span>
          </div>
          <div class="thread-messages" id="threadMessages">
            <?php if (!empty($threadRows)): foreach ($threadRows as $m): ?>
              <div class="bubble <?= $m['sender_id'] == $user['id'] ? 'me' : 'them' ?>" data-id="<?= (int)$m['id'] ?>">
                <?= h($m['body']) ?>
                <small><?= date('M j, g:i A', strtotime($m['created_at'])) ?></small>
              </div>
            <?php endforeach; else: ?>
              <div class="empty-state" id="emptyThread">Say hello! Send the first message.</div>
            <?php endif; ?>
          </div>
          <form method="post" class="thread-form" id="sendForm">
            <input type="hidden" name="receiver_id" value="<?= (int)$withId ?>">
            <?php if ($tuitionId): ?><input type="hidden" name="tuition_id" value="<?= (int)$tuitionId ?>"><?php endif; ?>
            <textarea name="body" id="messageBody" required placeholder="Type a message..."></textarea>
            <button type="submit" class="btn btn-primary">Send</button>
          </form>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <script>
  (function () {
    var threadBox = document.getElementById('threadBox');
    if (!threadBox) return;

    var withId   = threadBox.dataset.with;
    var lastId   = parseInt(threadBox.dataset.lastId, 10) || 0;
    var msgList  = document.getElementById('threadMessages');
    var emptyMsg = document.getElementById('emptyThread');
    var form     = document.getElementById('sendForm');
    var textarea = document.getElementById('messageBody');

    function escapeHtml(str) {
      var d = document.createElement('div');
      d.textContent = str;
      return d.innerHTML;
    }

    function scrollToBottom() {
      msgList.scrollTop = msgList.scrollHeight;
    }

    function addBubble(msg) {
      if (emptyMsg) { emptyMsg.remove(); }
      var div = document.createElement('div');
      div.className = 'bubble ' + (msg.mine ? 'me' : 'them');
      div.dataset.id = msg.id;
      div.innerHTML = escapeHtml(msg.body) + '<small>' + escapeHtml(msg.time) + '</small>';
      msgList.appendChild(div);
      if (msg.id > lastId) lastId = msg.id;
    }

    scrollToBottom();

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var body = textarea.value.trim();
      if (!body) return;

      var data = new FormData(form);
      fetch('send.php', { method: 'POST', body: data })
        .then(function (res) { return res.json(); })
        .then(function (msg) {
          if (msg.error) return;
          addBubble({ id: msg.id, mine: true, body: msg.body, time: msg.time });
          textarea.value = '';
          scrollToBottom();
        });
    });

    setInterval(function () {
      fetch('fetch.php?with=' + withId + '&after=' + lastId)
        .then(function (res) { return res.json(); })
        .then(function (data) {
          if (!data.messages || data.messages.length === 0) return;
          data.messages.forEach(addBubble);
          scrollToBottom();
        });
    }, 3000);
  })();
  </script>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>