<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();

// ---- Filters ----
$class_level = trim($_GET['class_level'] ?? '');
$curriculum  = trim($_GET['curriculum'] ?? '');
$location    = trim($_GET['location'] ?? '');
$keyword     = trim($_GET['q'] ?? '');

$sql = "SELECT tp.*, u.name AS guardian_name,
        (SELECT COUNT(*) FROM applications a WHERE a.tuition_id = tp.id) AS applicant_count,
        (SELECT COUNT(*) FROM applications a WHERE a.tuition_id = tp.id AND a.tutor_id = ?) AS already_applied
        FROM tuition_posts tp
        JOIN users u ON u.id = tp.guardian_id
        WHERE tp.status = 'active'";
$params = [$user['id']];
$types = 'i';

if ($class_level !== '') { $sql .= " AND tp.class_level = ?"; $params[] = $class_level; $types .= 's'; }
if ($curriculum !== '')  { $sql .= " AND tp.curriculum = ?"; $params[] = $curriculum; $types .= 's'; }
if ($location !== '')    { $sql .= " AND tp.location LIKE ?"; $params[] = "%$location%"; $types .= 's'; }
if ($keyword !== '')     { $sql .= " AND (tp.subject LIKE ? OR tp.title LIKE ?)"; $params[] = "%$keyword%"; $params[] = "%$keyword%"; $types .= 'ss'; }

$sql .= " ORDER BY tp.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$posts = $stmt->get_result();

$classLevels = $conn->query("SELECT DISTINCT class_level FROM tuition_posts ORDER BY class_level");
$curriculums = $conn->query("SELECT DISTINCT curriculum FROM tuition_posts WHERE curriculum IS NOT NULL AND curriculum <> '' ORDER BY curriculum");

$page_title = 'Tuition Board';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Tuition Board</h1>
    <p>Find the suitable student you want to teach.</p>
  </div>

  <form method="get" class="search-bar">
    <select name="class_level">
      <option value="">All Classes</option>
      <?php while ($c = $classLevels->fetch_assoc()): ?>
        <option value="<?= h($c['class_level']) ?>" <?= $class_level === $c['class_level'] ? 'selected' : '' ?>><?= h($c['class_level']) ?></option>
      <?php endwhile; ?>
    </select>
    <select name="curriculum">
      <option value="">All Curriculums</option>
      <?php while ($c = $curriculums->fetch_assoc()): ?>
        <option value="<?= h($c['curriculum']) ?>" <?= $curriculum === $c['curriculum'] ? 'selected' : '' ?>><?= h($c['curriculum']) ?></option>
      <?php endwhile; ?>
    </select>
    <input type="text" name="location" placeholder="Location" value="<?= h($location) ?>">
    <input type="text" name="q" placeholder="Search by subject..." value="<?= h($keyword) ?>">
    <button class="btn btn-primary" type="submit">Filter</button>
    <?php if ($class_level || $curriculum || $location || $keyword): ?><a class="btn btn-outline" href="browse-tuitions.php">Reset</a><?php endif; ?>
  </form>

  <div class="listing-head">
    <strong><?= $posts->num_rows ?> Tuitions Found</strong>
  </div>

  <?php if ($posts->num_rows === 0): ?>
    <div class="empty-state">No tuitions match your filters right now.</div>
  <?php endif; ?>

  <?php while ($t = $posts->fetch_assoc()): ?>
    <div class="result-card">
      <div class="result-top">
        <div>
          <div class="result-title"><?= h($t['title']) ?></div>
          <div class="result-meta"><span>Posted by <?= h($t['guardian_name']) ?></span></div>
        </div>
        <div class="price">&#2547;<?= (int)$t['budget'] ?>/month</div>
      </div>
      <div class="result-tags">
        <span class="tag"><?= h($t['class_level']) ?></span>
        <?php if ($t['curriculum']): ?><span class="tag"><?= h($t['curriculum']) ?></span><?php endif; ?>
        <span class="tag"><?= h($t['subject']) ?></span>
        <?php if ($t['tuition_type']): ?><span class="tag"><?= h($t['tuition_type']) ?></span><?php endif; ?>
      </div>
      <div class="result-meta">
        <span>📍 <?= h($t['location']) ?></span>
        <span>🗓️ <?= h($t['days_per_week']) ?></span>
        <span>👤 Student: <?= h($t['student_gender']) ?></span>
        <span>📨 <?= (int)$t['applicant_count'] ?> tutor(s) applied</span>
      </div>
      <?php if ($t['description']): ?><p style="color:var(--muted); font-size:.9rem;"><?= h($t['description']) ?></p><?php endif; ?>
      <div class="result-actions">
        <?php if ($t['already_applied']): ?>
          <button class="btn btn-outline" disabled>Already Applied</button>
        <?php elseif ($user['status'] !== 'approved'): ?>
          <button class="btn btn-outline" disabled title="Your profile must be approved before applying">Pending Approval</button>
        <?php else: ?>
          <form method="post" action="apply.php">
            <input type="hidden" name="tuition_id" value="<?= (int)$t['id'] ?>">
            <button type="submit" class="btn btn-primary">Apply Now</button>
          </form>
        <?php endif; ?>
        <a class="btn btn-outline" href="../messages/inbox.php?with=<?= (int)$t['guardian_id'] ?>&tuition=<?= (int)$t['id'] ?>">Message Guardian</a>
      </div>
    </div>
  <?php endwhile; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
