<?php
require_once __DIR__ . '/includes/functions.php';
$root = '';
$page_title = 'Home';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
  <div class="container">
    <span class="badge-pill">100% Commission Free Platform</span>
    <h1>Find Your Perfect Tutor<span class="accent">Anytime, Anywhere</span></h1>
    <p>Find the right tutor with confidence. We connect you with verified, experienced tutors. Contact your tutor directly &mdash; no middleman, no hidden fees.</p>
    <div class="hero-actions">
      <a href="signup.php?role=guardian" class="btn btn-primary" id="find-tutor">Find a Tutor &rarr;</a>
      <a href="signup.php?role=tutor" class="btn btn-outline" id="join-tutor">Become a Tutor</a>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="section-head">
      <h2>Everything Tutors Need. Nothing They Don't.</h2>
      <p>A complete platform to help you find students, manage your schedule, and scale your tutoring career.</p>
    </div>
    <div class="grid-3">
      <div class="feature-card">
        <div class="feature-icon">🔍</div>
        <h3>Browse Tuition Jobs</h3>
        <p>Find students matching your expertise, location, and schedule preferences instantly.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">🔔</div>
        <h3>Instant Notifications</h3>
        <p>Get notified about new tuition jobs that match your profile in real time.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📈</div>
        <h3>Grow Your Career</h3>
        <p>Build your reputation with reviews and ratings from satisfied students.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">💬</div>
        <h3>Direct Messages</h3>
        <p>Connect with students and parents directly through our in-app chat system.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">✅</div>
        <h3>Verified Profiles</h3>
        <p>Build trust with identity verification and admin-approved tutor profiles.</p>
      </div>
      <div class="feature-card">
        <div class="feature-icon">📋</div>
        <h3>Manage Applications</h3>
        <p>Track every application you send and every offer you receive in one place.</p>
      </div>
    </div>
  </div>
</section>

<section class="section" style="background:#fff; border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
  <div class="container">
    <div class="section-head">
      <h2>How to Find Your Tutor</h2>
      <p>Finding a tutor has never been this easy. Follow these four simple steps to connect with the right tutor today.</p>
    </div>
    <div class="grid-3">
      <div class="step-card"><span class="step-num">1</span><h3>Find Your Tutor</h3><p>Search by subject, location, and availability. Browse profiles and compare rates.</p></div>
      <div class="step-card"><span class="step-num">2</span><h3>Send Direct Offer</h3><p>Send a direct offer to your preferred tutor or post a tuition to receive applications.</p></div>
      <div class="step-card"><span class="step-num">3</span><h3>Confirm Tutor</h3><p>Confirm your chosen tutor directly through the app.</p></div>
      <div class="step-card"><span class="step-num">4</span><h3>See Results</h3><p>Track your progress and achieve your academic goals with expert guidance.</p></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <div class="cta-box">
      <h2>Ready to get started?</h2>
      <p>Join guardians and tutors building successful learning experiences together.</p>
      <div class="hero-actions">
        <a href="signup.php?role=guardian" class="btn btn-primary">Find a Tutor Now</a>
        <a href="signup.php?role=tutor" class="btn btn-outline">Become a Tutor</a>
      </div>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
