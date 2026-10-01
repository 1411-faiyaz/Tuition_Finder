<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();
$errors = [];

$stmt = $conn->prepare('SELECT * FROM tutor_profiles WHERE user_id = ?');
$stmt->bind_param('i', $user['id']);
$stmt->execute();
$profile = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$profile) {
    $conn->query("INSERT INTO tutor_profiles (user_id) VALUES ({$user['id']})");
    $profile = ['education' => '', 'institution' => '', 'department' => '', 'experience_years' => '', 'curriculum' => '', 'subjects' => '', 'preferred_type' => '', 'preferred_gender' => '', 'location' => '', 'expected_salary' => '', 'bio' => ''];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $education   = trim($_POST['education'] ?? '');
    $institution = trim($_POST['institution'] ?? '');
    $department  = trim($_POST['department'] ?? '');
    $experience  = trim($_POST['experience_years'] ?? '');
    $curriculum  = trim($_POST['curriculum'] ?? '');
    $subjects    = trim($_POST['subjects'] ?? '');
    $pref_type   = $_POST['preferred_type'] ?? '';
    $pref_gender = $_POST['preferred_gender'] ?? '';
    $location    = trim($_POST['location'] ?? '');
    $salary      = trim($_POST['expected_salary'] ?? '');
    $bio         = trim($_POST['bio'] ?? '');

    $errors = validate_required([
        'Educational qualification' => $education,
        'Institution' => $institution,
        'Subjects you teach' => $subjects,
        'Location' => $location,
        'Short bio' => $bio,
    ]);
    if ($salary !== '' && !is_numeric($salary)) {
        $errors[] = 'Expected salary must be a number.';
    }

    if (empty($errors)) {
        $salaryVal = $salary === '' ? null : (int)$salary;
        $stmt = $conn->prepare('UPDATE tutor_profiles SET education=?, institution=?, department=?, experience_years=?, curriculum=?, subjects=?, preferred_type=?, preferred_gender=?, location=?, expected_salary=?, bio=? WHERE user_id=?');
        // types: education,institution,department,experience,curriculum,subjects,pref_type,pref_gender,location (9 strings) + expected_salary (int) + bio (string) + user_id (int)
        $stmt->bind_param('sssssssssisi', $education, $institution, $department, $experience, $curriculum, $subjects, $pref_type, $pref_gender, $location, $salaryVal, $bio, $user['id']);
        $stmt->execute();
        $stmt->close();

        set_flash('success', 'Your tutoring profile has been updated.');
        redirect('profile.php');
    } else {
        $profile = compact('education', 'institution', 'department', 'bio', 'location') + $profile;
        $profile['experience_years'] = $experience;
        $profile['curriculum'] = $curriculum;
        $profile['subjects'] = $subjects;
        $profile['preferred_type'] = $pref_type;
        $profile['preferred_gender'] = $pref_gender;
        $profile['expected_salary'] = $salary;
    }
}

$page_title = 'My Profile';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>

<div class="container" style="max-width:760px; padding-bottom:60px;">
  <div class="page-head">
    <h1>Tutoring Profile</h1>
    <p>Keep this up to date so guardians and admins can review your qualifications.</p>
  </div>

  <?php if ($errors): ?>
    <div class="alert alert-error"><?php foreach ($errors as $e) echo '<div>' . h($e) . '</div>'; ?></div>
  <?php endif; ?>

  <div class="card">
    <form method="post" class="validate-form" novalidate>
      <div class="row-2">
        <div class="form-group">
          <label for="education">Educational Qualification</label>
          <select id="education" name="education" required>
            <?php foreach (['', "Higher Secondary", "Bachelor's", "Master's", 'PhD'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($profile['education'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt === '' ? 'Select qualification' : h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="institution">Institution Name</label>
          <input type="text" id="institution" name="institution" required value="<?= h($profile['institution'] ?? '') ?>" placeholder="e.g. University of Dhaka">
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="department">Department</label>
          <input type="text" id="department" name="department" value="<?= h($profile['department'] ?? '') ?>" placeholder="e.g. Computer Science">
        </div>
        <div class="form-group">
          <label for="experience_years">Years of Tutoring Experience</label>
          <select id="experience_years" name="experience_years">
            <?php foreach (['No Experience', '1-2 years', '2-3 years', '3-5 years', '5+ years'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($profile['experience_years'] ?? '') === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="row-2">
        <div class="form-group">
          <label for="curriculum">Curriculum / Board</label>
          <select id="curriculum" name="curriculum">
            <?php foreach (['', 'Bangla Medium', 'English Version', 'English Medium'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($profile['curriculum'] ?? '') === $opt ? 'selected' : '' ?>><?= $opt === '' ? 'Select curriculum' : h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label for="location">Preferred Location / Area</label>
          <input type="text" id="location" name="location" required value="<?= h($profile['location'] ?? '') ?>" placeholder="e.g. Dhanmondi, Dhaka">
        </div>
      </div>

      <div class="form-group">
        <label for="subjects">Subjects You Teach (comma separated)</label>
        <input type="text" id="subjects" name="subjects" required value="<?= h($profile['subjects'] ?? '') ?>" placeholder="e.g. Physics, Chemistry, Higher Mathematics">
      </div>

      <div class="row-2">
        <div class="form-group">
          <label>Preferred Tuition Type</label>
          <select name="preferred_type">
            <?php foreach (['Online', 'In-Person', "Tutor's Place"] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($profile['preferred_type'] ?? '') === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Preferred Student Gender</label>
          <select name="preferred_gender">
            <?php foreach (['No Preference', 'Male', 'Female'] as $opt): ?>
              <option value="<?= h($opt) ?>" <?= ($profile['preferred_gender'] ?? '') === $opt ? 'selected' : '' ?>><?= h($opt) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="form-group">
        <label for="expected_salary">Expected Salary (Tk/month)</label>
        <input type="number" id="expected_salary" name="expected_salary" min="0" value="<?= h($profile['expected_salary'] ?? '') ?>" placeholder="e.g. 6000">
      </div>

      <div class="form-group">
        <label for="bio">Short Bio</label>
        <textarea id="bio" name="bio" required maxlength="500" placeholder="Tell guardians about your teaching style..."><?= h($profile['bio'] ?? '') ?></textarea>
      </div>

      <button type="submit" class="btn btn-primary btn-block">Save Profile</button>
    </form>
  </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
