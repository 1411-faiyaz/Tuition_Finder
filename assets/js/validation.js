// Lightweight, dependency-free form validation.
// Add class "validate-form" to any <form> and data attributes to inputs:
//   required            -> field can't be empty
//   data-min-length="6" -> minimum characters
//   data-match="#id"    -> must equal the value of another field (e.g. confirm password)
//   data-phone          -> must look like 01XXXXXXXXX (11 digits)

document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('form.validate-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      let valid = true;

      form.querySelectorAll('[required], [data-phone], [data-match], [data-min-length]').forEach(function (field) {
        clearError(field);
        const value = field.value.trim();

        if (field.hasAttribute('required') && value === '') {
          showError(field, 'This field is required.');
          valid = false;
          return;
        }

        if (field.hasAttribute('data-phone') && value !== '' && !/^01[0-9]{9}$/.test(value)) {
          showError(field, 'Enter a valid 11-digit phone number (e.g. 01712345678).');
          valid = false;
          return;
        }

        const minLen = field.getAttribute('data-min-length');
        if (minLen && value.length < parseInt(minLen, 10)) {
          showError(field, 'Must be at least ' + minLen + ' characters.');
          valid = false;
          return;
        }

        const matchSelector = field.getAttribute('data-match');
        if (matchSelector) {
          const other = document.querySelector(matchSelector);
          if (other && other.value !== value) {
            showError(field, 'Values do not match.');
            valid = false;
          }
        }
      });

      if (!valid) {
        e.preventDefault();
      }
    });
  });

  // Toggle password visibility for inputs with a sibling .toggle-pass button
  document.querySelectorAll('.toggle-pass').forEach(function (btn) {
    btn.addEventListener('click', function () {
      const input = document.querySelector(btn.getAttribute('data-target'));
      if (!input) return;
      input.type = input.type === 'password' ? 'text' : 'password';
      btn.textContent = input.type === 'password' ? 'Show' : 'Hide';
    });
  });
});

function showError(field, message) {
  const group = field.closest('.form-group') || field.parentElement;
  group.classList.add('has-error');
  let err = group.querySelector('.field-error');
  if (!err) {
    err = document.createElement('div');
    err.className = 'field-error';
    group.appendChild(err);
  }
  err.textContent = message;
  err.style.display = 'block';
}

function clearError(field) {
  const group = field.closest('.form-group') || field.parentElement;
  group.classList.remove('has-error');
  const err = group.querySelector('.field-error');
  if (err) err.style.display = 'none';
}
