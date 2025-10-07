console.log("create_acc.js loaded and running");

document.addEventListener('DOMContentLoaded', function () {
  // Elements
  const createForm = document.getElementById('createUserForm');
  const createBtn = document.getElementById('createBtn');
  const spinner = document.getElementById('createSpinner');
  const createModalEl = document.getElementById('createUserModal');
  const createModal = bootstrap.Modal.getOrCreateInstance(createModalEl);

  // Cancel Confirmation Modal
  const cancelConfirmModalEl = document.getElementById('cancelConfirmModal');
  const cancelConfirmModal = bootstrap.Modal.getOrCreateInstance(cancelConfirmModalEl);
  const cancelBtn = document.getElementById('cancelCreateUser');
  const cancelNoBtn = document.getElementById('cancelNo');
  const cancelYesBtn = document.getElementById('cancelYes');

  // Confirm Creation Modal
  const confirmCreateModalEl = document.getElementById('confirmCreateUserModal');
  const confirmCreateModal = bootstrap.Modal.getOrCreateInstance(confirmCreateModalEl);
  const confirmBtn = document.getElementById('confirmCreateUserBtn');

  // Add blur effect when confirmation or cancel modal is open
  [cancelConfirmModalEl, confirmCreateModalEl].forEach(modalEl => {
    modalEl.addEventListener('show.bs.modal', () => createModalEl.classList.add('blur-background'));
    modalEl.addEventListener('hidden.bs.modal', () => createModalEl.classList.remove('blur-background'));
  });

  let formDataToSubmit = null;

  // Cancel logic
  cancelBtn.addEventListener('click', () => {
    if (hasUnsavedChanges()) {
      cancelConfirmModal.show();
    } else {
      resetCreateModal();
    }
  });

  cancelNoBtn.addEventListener('click', () => cancelConfirmModal.hide());

  cancelYesBtn.addEventListener('click', () => {
    cancelConfirmModal.hide();
    resetCreateModal();
  });

  // Form submission: open confirmation modal
  createForm.addEventListener('submit', function (e) {
    e.preventDefault();
    formDataToSubmit = new FormData(createForm);
    confirmCreateModal.show();
  });

  // Final confirmation and submission
  confirmBtn.addEventListener('click', function () {
    confirmCreateModal.hide();

    confirmCreateModalEl.addEventListener('hidden.bs.modal', function handleConfirmHidden() {
      confirmCreateModalEl.removeEventListener('hidden.bs.modal', handleConfirmHidden);

      spinner.classList.remove('d-none');
      createBtn.disabled = true;
      cancelBtn.disabled = true;

      fetch('../../process_form.php', {
        method: 'POST',
        body: formDataToSubmit,
      })
      .then(response => response.json())
      .then(data => {
        spinner.classList.add('d-none');
        createBtn.disabled = false;
        cancelBtn.disabled = false;

        if (data.success) {
          createModal.hide();
          createForm.reset();

          // Force remove lingering backdrop after modal animation
          setTimeout(() => {
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
            document.body.classList.remove('modal-open');
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
          }, 500);

          showAlert('User account created successfully!', 'success');
        } else {
          showCreateUserErrorAlert(data.message || 'Email or username already exists.');
        }
      })
      .catch(error => {
        spinner.classList.add('d-none');
        createBtn.disabled = false;
        console.error('Error:', error);
        showCreateUserErrorAlert('Failed to submit the form. Please try again.');
      });
    });
  });

  // Global alert outside modal (success, on main page)
  function showAlert(message, type = 'success') {
    const iconId = type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill';
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-${type} d-flex align-items-center mt-3`;
    alertBox.setAttribute('role', 'alert');
    alertBox.innerHTML = `
      <svg class="bi flex-shrink-0 me-2 text-${type}" width="24" height="24" role="img">
        <use xlink:href="#${iconId}" />
      </svg>
      <div>${message}</div>
    `;

    const alertContainer = document.getElementById('alertContainer');
    alertContainer.innerHTML = '';
    alertContainer.appendChild(alertBox);

    setTimeout(() => alertBox.remove(), 5000);
  }

  // Error alert inside the Create User modal
  function showCreateUserErrorAlert(message) {
    const alertBox = document.createElement('div');
    alertBox.className = `alert alert-danger d-flex align-items-center mt-2`;
    alertBox.setAttribute('role', 'alert');
    alertBox.innerHTML = `
      <svg class="bi flex-shrink-0 me-2 text-danger" width="24" height="24" role="img">
        <use xlink:href="#exclamation-triangle-fill" />
      </svg>
      <div>${message}</div>
    `;

    const container = document.getElementById('createUserErrorAlert');
    container.innerHTML = '';
    container.appendChild(alertBox);

    setTimeout(() => alertBox.remove(), 5000);
  }

  // Check if any input has unsaved changes
  function hasUnsavedChanges() {
    const inputs = createForm.querySelectorAll('input, select, textarea');
    return Array.from(inputs).some(input => input.value.trim() !== '');
  }

  // Cleanup and reset modal
  function resetCreateModal() {
    createForm.reset();
    createModal.hide();

    // Force remove any leftover backdrop
    setTimeout(() => {
      document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';
    }, 100);
  }

  // Allow only letters and spaces
  function allowOnlyLetters(input) {
    input.addEventListener('input', function () {
      this.value = this.value.replace(/[^a-zA-Z\s]/g, '');
    });
  }

  allowOnlyLetters(document.getElementById('fname'));
  allowOnlyLetters(document.getElementById('mname'));
  allowOnlyLetters(document.getElementById('lname'));


  // Ensure backdrop is removed after create modal fully hides
  createModalEl.addEventListener('hidden.bs.modal', () => {
    // Remove any lingering backdrops
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
    document.body.classList.remove('modal-open');
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
  });
});
