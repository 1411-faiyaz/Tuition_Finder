<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

$curriculum = trim($_GET['curriculum'] ?? '');
$gender     = trim($_GET['gender'] ?? '');
$keyword    = trim($_GET['q'] ?? '');

$sql = "SELECT u.id, u.name, tp.* FROM users u
        JOIN tutor_profiles tp ON tp.user_id = u.id
        WHERE u.role = 'tutor' AND u.status = 'approved'";
$params = [];
$types = '';

if ($curriculum !== '') { $sql .= " AND tp.curriculum = ?"; $params[] = $curriculum; $types .= 's'; }
if ($gender !== '')     { $sql .= " AND tp.preferred_gender = ?"; $params[] = $gender; $types .= 's'; }
if ($keyword !== '')    { $sql .= " AND (tp.subjects LIKE ? OR u.name LIKE ? OR tp.institution LIKE ?)"; $params[] = "%$keyword%"; $params[] = "%$keyword%"; $params[] = "%$keyword%"; $types .= 'sss'; }

$sql .= " ORDER BY tp.updated_at DESC";

$stmt = $conn->prepare($sql);
if ($types !== '') $stmt->bind_param($types, ...$params);
$stmt->execute();
$tutors = $stmt->get_result();

$page_title = 'Explore Tutors';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container">
  <div class="page-head">
    <h1>Find Your Perfect Tutor</h1>
    <p>Search from qualified, admin-approved tutors.</p>
  </div>

  <form method="get" class="search-bar">
    <select name="curriculum">
      <option value="">All Curriculums</option>
      <?php foreach (['Bangla Medium', 'English Version', 'English Medium'] as $opt): ?>
        <option value="<?= h($opt) ?>" <?= $curriculum === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="gender">
      <option value="">Any Gender</option>
      <?php foreach (['Male', 'Female', 'No Preference'] as $opt): ?>
        <option value="<?= h($opt) ?>" <?= $gender === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
      <?php endforeach; ?>
    </select>
    <input type="text" name="q" placeholder="Search by name, subject, institution..." value="<?= h($keyword) ?>">
    <button class="btn btn-primary" type="submit">Search</button>
    <?php if ($curriculum || $gender || $keyword): ?><a class="btn btn-outline" href="browse-tutors.php">Reset</a><?php endif; ?>
  </form>

  <div class="listing-head"><strong><?= $tutors->num_rows ?> Tutors Found</strong></div>

  <?php if ($tutors->num_rows === 0): ?>
    <div class="empty-state">No tutors match your search yet.</div>
  <?php endif; ?>

  <div class="grid-2">
    <?php while ($t = $tutors->fetch_assoc()): ?>
      <div class="result-card">
        <div class="result-top">
          <div>
            <div class="result-title"><?= h($t['name']) ?></div>
            <div class="result-meta"><?= h($t['institution']) ?> &bull; <?= h($t['department']) ?></div>
          </div>
          <?php if ($t['expected_salary']): ?><div class="price">&#2547;<?= (int)$t['expected_salary'] ?>/mo</div><?php endif; ?>
        </div>
        <div class="result-tags">
          <?php if ($t['curriculum']): ?><span class="tag"><?= h($t['curriculum']) ?></span><?php endif; ?>
          <?php if ($t['experience_years']): ?><span class="tag"><?= h($t['experience_years']) ?></span><?php endif; ?>
          <?php if ($t['preferred_type']): ?><span class="tag"><?= h($t['preferred_type']) ?></span><?php endif; ?>
        </div>
        <p style="color:var(--muted); font-size:.9rem;"><strong>Subjects:</strong> <?= h($t['subjects']) ?></p>
        <?php if ($t['bio']): ?><p style="color:var(--muted); font-size:.9rem;"><?= h($t['bio']) ?></p><?php endif; ?>
        <div class="result-meta"><span>📍 <?= h($t['location']) ?></span></div>
        <div class="result-actions">
          <a class="btn btn-primary" href="../messages/inbox.php?with=<?= (int)$t['id'] ?>">Message Tutor</a>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
