<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['tutor'], '../login.php', '../index.php');
$user = current_user();
$firstName = trim(explode(' ', trim($user['name']))[0]) ?: 'there';

$page_title = 'Salary Prediction Assistant';
$root = '../';
include __DIR__ . '/../includes/header.php';
?>
<link rel="stylesheet" href="<?= $root ?>assets/css/chatbot.css">

<div class="container chatbot-page">
  <div class="chatbot-intro">
    <h1>Tutor Salary Prediction Assistant</h1>
    <p>Answer a few quick questions about the tuition and I'll estimate a fair monthly salary based on similar tuitions in your area.</p>
  </div>

  <div class="chatbot-card">
    <div class="chatbot-header">
      <div class="chatbot-avatar">🤖</div>
      <div class="chatbot-header-text">
        <div class="name">Salary Prediction Assistant</div>
        <div class="status"><span class="dot"></span> Online &bull; for tutors only</div>
      </div>
      <button type="button" class="chatbot-reset" id="cbReset">Start Over</button>
    </div>

    <div class="chatbot-messages" id="cbMessages"></div>

    <form class="chatbot-inputbar" id="cbForm">
      <input type="text" id="cbInput" placeholder="Type your answer or tap an option above..." autocomplete="off">
      <button type="submit" class="chatbot-send" id="cbSend" title="Send">&#10148;</button>
    </form>
  </div>
</div>

<script>
(function () {
  'use strict';

  // These option lists mirror tutor/predict-salary.php's allow-lists and
  // backend/main.py's Literal[] validation -- they reflect the actual
  // categories the salary model was trained on. Keep all three in sync.
  var STEPS = [
    {
      key: 'place', label: 'Area',
      question: "First, which area is the tuition located in?",
      type: 'choice',
      options: ['Badda', 'Banani', 'Gulshan', 'Khilkhet', 'Middle Badda', 'Mirpur',
                'Mohakhali', 'New Market', 'Notun Bazar', 'Rampura', 'Shahbag', 'Uttara']
    },
    {
      key: 'class_number', label: 'Class',
      question: "Got it. Which class / grade is the student in?",
      type: 'choice',
      options: ['1','2','3','4','5','6','7','8','9','10','11','12']
    },
    {
      key: 'version', label: 'Curriculum',
      question: "Which curriculum does the student follow?",
      type: 'choice',
      options: ['Bangla', 'English Medium', 'English Version']
    },
    {
      key: 'subject', label: 'Subject',
      question: "Which subject will you be teaching?",
      type: 'choice',
      options: ['All Subjects', 'Bangla', 'Biology', 'Chemistry', 'English',
                'General Science', 'ICT', 'Math', 'Physics']
    },
    {
      key: 'days_per_week', label: 'Days/week',
      question: "How many days per week? (enter a number from 1 to 7)",
      type: 'number', min: 1, max: 7, step: 1, integer: true,
      quick: ['2','3','4','5','6']
    },
    {
      key: 'hours_per_day', label: 'Hours/session',
      question: "And how many hours per session? (0.5 to 6, e.g. 1.5)",
      type: 'number', min: 0.5, max: 6, step: 0.5, integer: false,
      quick: ['1','1.5','2','2.5','3']
    }
  ];

  var messagesEl = document.getElementById('cbMessages');
  var formEl     = document.getElementById('cbForm');
  var inputEl    = document.getElementById('cbInput');
  var sendBtn    = document.getElementById('cbSend');
  var resetBtn   = document.getElementById('cbReset');

  var stepIndex = 0;
  var answers   = {};
  var busy      = false; // true while waiting on the API / between scripted messages
  var chatGen   = 0;     // bumped on every reset, so stale timeouts/fetches from a
                          // previous conversation can detect they're outdated and no-op

  function escapeHtml(str) {
    var d = document.createElement('div');
    d.textContent = String(str);
    return d.innerHTML;
  }

  function scrollToBottom() {
    messagesEl.scrollTop = messagesEl.scrollHeight;
  }

  function timeNow() {
    return new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function addBotBubble(html, isError) {
    var row = document.createElement('div');
    row.className = 'cb-row bot';
    row.innerHTML =
      '<div class="cb-avatar">🤖</div>' +
      '<div class="cb-bubble' + (isError ? ' cb-error' : '') + '">' + html +
      '<span class="cb-time">' + timeNow() + '</span></div>';
    messagesEl.appendChild(row);
    scrollToBottom();
    return row;
  }

  function addUserBubble(text) {
    var row = document.createElement('div');
    row.className = 'cb-row user';
    row.innerHTML =
      '<div class="cb-avatar">🧑‍🏫</div>' +
      '<div class="cb-bubble">' + escapeHtml(text) +
      '<span class="cb-time">' + timeNow() + '</span></div>';
    messagesEl.appendChild(row);
    scrollToBottom();
  }

  function showTyping() {
    var row = document.createElement('div');
    row.className = 'cb-row bot';
    row.id = 'cbTypingRow';
    row.innerHTML =
      '<div class="cb-avatar">🤖</div>' +
      '<div class="cb-bubble cb-typing"><span></span><span></span><span></span></div>';
    messagesEl.appendChild(row);
    scrollToBottom();
  }

  function hideTyping() {
    var row = document.getElementById('cbTypingRow');
    if (row) row.remove();
  }

  function clearChoices() {
    var existing = messagesEl.querySelector('.cb-choices');
    if (existing) existing.remove();
  }

  function renderChoices(options) {
    clearChoices();
    var wrap = document.createElement('div');
    wrap.className = 'cb-choices';
    options.forEach(function (opt) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'cb-choice-btn';
      btn.textContent = opt;
      btn.addEventListener('click', function () {
        if (busy) return;
        submitAnswer(opt);
      });
      wrap.appendChild(btn);
    });
    messagesEl.appendChild(wrap);
    scrollToBottom();
  }

  function disableChoices() {
    messagesEl.querySelectorAll('.cb-choice-btn').forEach(function (b) { b.disabled = true; });
  }

  function setBusy(state) {
    busy = state;
    inputEl.disabled = state;
    sendBtn.disabled = state;
  }

  function currentStep() {
    return STEPS[stepIndex];
  }

  function askCurrentStep() {
    var gen = chatGen;
    setBusy(true);
    showTyping();
    setTimeout(function () {
      if (gen !== chatGen) return;
      hideTyping();
      var step = currentStep();
      addBotBubble(escapeHtml(step.question));
      if (step.type === 'choice') {
        renderChoices(step.options);
      } else {
        var hints = step.quick ? step.quick : [];
        if (hints.length) renderChoices(hints);
      }
      setBusy(false);
      inputEl.focus();
    }, 450);
  }

  function validateAnswer(step, raw) {
    var value = String(raw).trim();
    if (value === '') return { ok: false, msg: 'Please enter or select an answer.' };

    if (step.type === 'choice') {
      var match = step.options.filter(function (o) { return o.toLowerCase() === value.toLowerCase(); })[0];
      if (!match) return { ok: false, msg: 'Please tap one of the suggested options above, or type it exactly as shown.' };
      return { ok: true, value: match };
    }

    // numeric
    var num = parseFloat(value);
    if (isNaN(num)) return { ok: false, msg: 'That doesn\'t look like a number. Please enter a number between ' + step.min + ' and ' + step.max + '.' };
    if (step.integer && !Number.isInteger(num)) return { ok: false, msg: 'Please enter a whole number between ' + step.min + ' and ' + step.max + '.' };
    if (num < step.min || num > step.max) return { ok: false, msg: 'Please enter a value between ' + step.min + ' and ' + step.max + '.' };
    return { ok: true, value: num };
  }

  function submitAnswer(raw) {
    var step = currentStep();
    var result = validateAnswer(step, raw);

    if (!result.ok) {
      addUserBubble(raw);
      addBotBubble(escapeHtml(result.msg), true);
      inputEl.value = '';
      inputEl.focus();
      return;
    }

    disableChoices();
    addUserBubble(String(result.value));
    inputEl.value = '';
    answers[step.key] = result.value;

    stepIndex++;
    if (stepIndex < STEPS.length) {
      askCurrentStep();
    } else {
      showSummaryAndPredict();
    }
  }

  function showSummaryAndPredict() {
    var gen = chatGen;
    setBusy(true);
    showTyping();
    setTimeout(function () {
      if (gen !== chatGen) return;
      hideTyping();
      addBotBubble(
        'Great, here\'s what I have:<br>' +
        '<strong>Area:</strong> ' + escapeHtml(answers.place) + '<br>' +
        '<strong>Class:</strong> ' + escapeHtml(String(answers.class_number)) + '<br>' +
        '<strong>Curriculum:</strong> ' + escapeHtml(answers.version) + '<br>' +
        '<strong>Subject:</strong> ' + escapeHtml(answers.subject) + '<br>' +
        '<strong>Schedule:</strong> ' + escapeHtml(String(answers.days_per_week)) + ' days/week, ' +
          escapeHtml(String(answers.hours_per_day)) + ' hrs/session<br><br>' +
        'Let me calculate an estimated salary for this...'
      );
      requestPrediction();
    }, 400);
  }

  function requestPrediction() {
    var gen = chatGen;
    showTyping();
    var body = new URLSearchParams({
      place: answers.place,
      class_number: answers.class_number,
      version: answers.version,
      subject: answers.subject,
      days_per_week: answers.days_per_week,
      hours_per_day: answers.hours_per_day
    });

    fetch('predict-salary.php', { method: 'POST', body: body })
      .then(function (res) {
        return res.json().then(function (data) { return { ok: res.ok, data: data }; });
      })
      .then(function (result) {
        if (gen !== chatGen) return;
        hideTyping();
        if (!result.ok || !result.data || typeof result.data.predicted_salary === 'undefined') {
          var msg = (result.data && result.data.error) || "Sorry, I couldn't generate the salary prediction right now. Please try again.";
          addBotBubble(escapeHtml(msg), true);
          renderRetry();
          return;
        }
        renderResult(result.data.predicted_salary);
      })
      .catch(function () {
        if (gen !== chatGen) return;
        hideTyping();
        addBotBubble("Sorry, I couldn't generate the salary prediction right now. Please check your connection and try again.", true);
        renderRetry();
      });
  }

  function renderRetry() {
    setBusy(false);
    var wrap = document.createElement('div');
    wrap.className = 'cb-choices';
    var retry = document.createElement('button');
    retry.type = 'button';
    retry.className = 'cb-choice-btn';
    retry.textContent = '🔄 Try Again';
    retry.addEventListener('click', function () {
      clearChoices();
      requestPrediction();
    });
    var restart = document.createElement('button');
    restart.type = 'button';
    restart.className = 'cb-choice-btn';
    restart.textContent = 'Start Over';
    restart.addEventListener('click', resetChat);
    wrap.appendChild(retry);
    wrap.appendChild(restart);
    messagesEl.appendChild(wrap);
    scrollToBottom();
  }

  function formatTaka(n) {
    return '৳ ' + Number(n).toLocaleString('en-US');
  }

  function renderResult(salary) {
    var row = document.createElement('div');
    row.className = 'cb-row bot';
    row.innerHTML =
      '<div class="cb-avatar">🤖</div>' +
      '<div class="cb-bubble" style="padding:0; background:transparent; border:none; box-shadow:none;">' +
        '<div class="cb-result">' +
          '<div class="label">Estimated Tuition Salary</div>' +
          '<div class="amount">' + formatTaka(salary) + ' / month</div>' +
          '<div class="explain">This estimate reflects typical rates for the selected area, class, curriculum and subject, adjusted for your weekly schedule.</div>' +
          '<div class="summary">' +
            '<div><span>Area</span><span>' + escapeHtml(answers.place) + '</span></div>' +
            '<div><span>Class</span><span>' + escapeHtml(String(answers.class_number)) + '</span></div>' +
            '<div><span>Curriculum</span><span>' + escapeHtml(answers.version) + '</span></div>' +
            '<div><span>Subject</span><span>' + escapeHtml(answers.subject) + '</span></div>' +
            '<div><span>Schedule</span><span>' + escapeHtml(String(answers.days_per_week)) + ' d/wk &times; ' + escapeHtml(String(answers.hours_per_day)) + ' hr</span></div>' +
          '</div>' +
        '</div>' +
      '</div>';
    messagesEl.appendChild(row);
    scrollToBottom();

    var wrap = document.createElement('div');
    wrap.className = 'cb-choices';
    var again = document.createElement('button');
    again.type = 'button';
    again.className = 'cb-choice-btn';
    again.textContent = '💬 Predict Another Salary';
    again.addEventListener('click', resetChat);
    wrap.appendChild(again);
    messagesEl.appendChild(wrap);
    scrollToBottom();
    setBusy(false);
  }

  function resetChat() {
    chatGen++;
    var gen = chatGen;
    stepIndex = 0;
    answers = {};
    messagesEl.innerHTML = '';
    setBusy(false);
    showTyping();
    setTimeout(function () {
      if (gen !== chatGen) return;
      hideTyping();
      var tutorFirstName = <?= json_encode($firstName) ?>;
      addBotBubble('Hi ' + escapeHtml(tutorFirstName) + "! I'm your Salary Prediction Assistant. I'll ask a few quick questions about a tuition and estimate a fair monthly salary for it.");
      askCurrentStep();
    }, 400);
  }

  formEl.addEventListener('submit', function (e) {
    e.preventDefault();
    if (busy) return;
    var value = inputEl.value.trim();
    if (!value) return;
    submitAnswer(value);
  });

  resetBtn.addEventListener('click', function () {
    if (busy && stepIndex >= STEPS.length) return; // don't interrupt an in-flight prediction
    resetChat();
  });

  resetChat();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
