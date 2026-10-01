<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['guardian'], '../login.php', '../index.php');
$user = current_user();
$errors = [];
$old = [
    'title' => '', 'class_level' => '', 'curriculum' => '', 'subject' => '', 'medium' => '',
    'location' => '', 'tuition_type' => 'In-Person', 'days_per_week' => '', 'budget' => '',
    'student_gender' => 'Any', 'description' => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($old as $key => $default) {
        $old[$key] = trim($_POST[$key] ?? $default);
    }

    $errors = validate_required([
        'Title' => $old['title'],
        'Class / Level' => $old['class_level'],
        'Subject' => $old['subject'],
        'Location' => $old['location'],
        'Monthly budget' => $old['budget'],
    ]);
    if ($old['budget'] !== '' && !is_numeric($old['budget'])) {
        $errors[] = 'Budget must be a number.';
    }

    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO tuition_posts
            (guardian_id, title, class_level, curriculum, subject, medium, location, tuition_type, days_per_week, budget, student_gender, description)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $budget = (int)$old['budget'];
        $stmt->bind_param('issssssssiss',
            $user['id'], $old['title'], $old['class_level'], $old['curriculum'], $old['subject'], $old['medium'],
            $old['location'], $old['tuition_type'], $old['days_per_week'], $budget, $old['student_gender'], $old['description']
        );
        $stmt->execute();
        $stmt->close();
        set_flash('success', 'Tuition posted successfully!');
        redirect('tuitions.php');
    }
}

$page_title = 'Post New Tuition';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:760px; padding-bottom:60px;">
  <div class="page-head">
    <h1>Post a New Tuition</h1>
    <p>Describe what you're looking for and start receiving applications from qualified tutors.</p>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" class="validate-form" novalidate>
      <div class="form-group">
        <label for="title">Title</label>
        <input type="text" id="title" name="title" required value="<?= h($old['title']) ?>" placeholder="e.g. Tutor needed for HSC 1st Year">
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="class_level">Class / Level</label>
          <input type="text" id="class_level" name="class_level" required value="<?= h($old['class_level']) ?>" placeholder="e.g. Class 9 / HSC 1st Year">
        </div>
        <div class="form-group">
          <label for="curriculum">Curriculum</label>
          <select id="curriculum" name="curriculum">
            <?php foreach (['', 'Bangla Medium', 'English Version', 'English Medium'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= $old['curriculum'] === $opt ? 'selected' : '' ?>><?= $opt === '' ? 'Select curriculum' : h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="subject">Subject(s)</label>
          <input type="text" id="subject" name="subject" required value="<?= h($old['subject']) ?>" placeholder="e.g. Physics, Chemistry">
        </div>
        <div class="form-group">
          <label for="medium">Medium</label>
          <input type="text" id="medium" name="medium" value="<?= h($old['medium']) ?>" placeholder="e.g. Bangla Medium">
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="location">Location</label>
          <input type="text" id="location" name="location" required value="<?= h($old['location']) ?>" placeholder="e.g. Dhanmondi, Dhaka">
        </div>
        <div class="form-group">
          <label>Tuition Type</label>
          <select name="tuition_type">
            <?php foreach (['In-Person', 'Online', "Tutor's Place"] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= $old['tuition_type'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="days_per_week">Preferred Schedule</label>
          <input type="text" id="days_per_week" name="days_per_week" value="<?= h($old['days_per_week']) ?>" placeholder="e.g. 3-4 days/week">
        </div>
        <div class="form-group">
          <label for="budget">Monthly Budget (Tk)</label>
          <input type="number" id="budget" name="budget" required min="0" value="<?= h($old['budget']) ?>" placeholder="e.g. 6000">
        </div>
      </div>

      <div class="form-group">
        <label>Preferred Tutor Gender</label>
        <select name="student_gender">
          <?php foreach (['Any', 'Male', 'Female'] as $opt): ?>
            <option value="<?= h($opt) ?>" <?= $old['student_gender'] === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="description">Additional Details</label>
        <textarea id="description" name="description" placeholder="Any other requirements..."><?= h($old['description']) ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Post Tuition</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
