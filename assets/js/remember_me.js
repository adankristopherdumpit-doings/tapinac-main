document.addEventListener('DOMContentLoaded', function () {
  const usernameInput = document.querySelector('input[name="username"]');
  const rememberCheckbox = document.getElementById('rememberMe');

  // Load from localStorage if available
  if (localStorage.getItem('rememberedUsername')) {
    usernameInput.value = localStorage.getItem('rememberedUsername');
    rememberCheckbox.checked = true;
  }

  // Save or clear on checkbox change or form submit
  rememberCheckbox.addEventListener('change', function () {
    if (this.checked) {
      localStorage.setItem('rememberedUsername', usernameInput.value);
    } else {
      localStorage.removeItem('rememberedUsername');
    }
  });

  // Update localStorage if user edits the username
  usernameInput.addEventListener('input', function () {
    if (rememberCheckbox.checked) {
      localStorage.setItem('rememberedUsername', usernameInput.value);
    }
  });
});
