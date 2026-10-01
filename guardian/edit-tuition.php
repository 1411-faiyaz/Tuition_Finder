<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();

$id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$stmt = $conn->prepare('SELECT * FROM tuition_posts WHERE id = ? AND guardian_id = ?');
$stmt->bind_param('ii', $id, $user['id']);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$post) {
    set_flash('error', 'Tuition post not found.');
    redirect('tuitions.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach (['title', 'class_level', 'curriculum', 'subject', 'medium', 'location', 'tuition_type', 'days_per_week', 'budget', 'student_gender', 'description'] as $key) {
        $post[$key] = trim($_POST[$key] ?? '');
    }

    $errors = validate_required([
        'Title' => $post['title'],
        'Class / Level' => $post['class_level'],
        'Subject' => $post['subject'],
        'Location' => $post['location'],
        'Monthly budget' => $post['budget'],
    ]);
    if ($post['budget'] !== '' && !is_numeric($post['budget'])) {
        $errors[] = 'Budget must be a number.';
    }

    if (empty($errors)) {
        $budget = (int)$post['budget'];
        $stmt = $conn->prepare("UPDATE tuition_posts SET title=?, class_level=?, curriculum=?, subject=?, medium=?, location=?, tuition_type=?, days_per_week=?, budget=?, student_gender=?, description=? WHERE id=? AND guardian_id=?");
        $stmt->bind_param('ssssssssissii',
            $post['title'], $post['class_level'], $post['curriculum'], $post['subject'], $post['medium'],
            $post['location'], $post['tuition_type'], $post['days_per_week'], $budget, $post['student_gender'],
            $post['description'], $id, $user['id']
        );
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Tuition post updated.');
        redirect('tuitions.php');
    }
}

$page_title = 'Edit Tuition';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:760px; padding-bottom:60px;">
  <div class="page-head">
    <h1>Edit Tuition Post</h1>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" class="validate-form" novalidate>
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= h($post['title']) ?>">
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="class_level">Class / Level</label>
          <input type="text" id="class_level" name="class_level" required value="<?= h($post['class_level']) ?>">
        </div>
        <div class="form-group">
          <label for="curriculum">Curriculum</label>
          <select id="curriculum" name="curriculum">
            <?php foreach (['', 'Bangla Medium', 'English Version', 'English Medium'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= $post['curriculum'] === $opt ? 'selected' : '' ?>><?= $opt === '' ? 'Select curriculum' : h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="subject">Subject(s)</label>
          <input type="text" id="subject" name="subject" required value="<?= h($post['subject']) ?>">
        </div>
        <div class="form-group">
          <label for="medium">Medium</label>
          <input type="text" id="medium" name="medium" value="<?= h($post['medium']) ?>">
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" required value="<?= h($post['location']) ?>">
        </div>
        <div class="form-group">
          <label>Tuition Type</label>
          <select name="tuition_type">
            <?php foreach (['In-Person', 'Online', "Tutor's Place"] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= $post['tuition_type'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="days_per_week">Preferred Schedule</label>
          <input type="text" id="days_per_week" name="days_per_week" value="<?= h($post['days_per_week']) ?>">
        </div>
        <div class="form-group">
          <label for="budget">Monthly Budget (Tk)</label>
          <input type="number" id="budget" name="budget" required min="0" value="<?= h($post['budget']) ?>">
        </div>
      </div>

      <div class="form-group">
        <label>Preferred Tutor Gender</label>
        <select name="student_gender">
          <?php foreach (['Any', 'Male', 'Female'] as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $post['student_gender'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="description">Additional Details</label>
        <textarea id="description" name="description"><?= h($post['description']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Save Changes</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
