document.getElementById("editModal").addEventListener("hidden.bs.modal", function () {
  // Uncheck all row checkboxes when modal closes
  document.querySelectorAll(".row-checkbox:checked").forEach(cb => cb.checked = false);
});


document.addEventListener('DOMContentLoaded', () => {

  // ====== 1. Toggle Edit Mode ======
  const toggleBtn = document.getElementById('toggleEditMode');
  const icon = toggleBtn.querySelector('i');
  const label = toggleBtn.querySelector('span');
  const checkboxColumns = document.querySelectorAll('.edit-checkbox-column');
  const selectAll = document.getElementById('selectAll');

  toggleBtn.addEventListener('click', () => {
    checkboxColumns.forEach(col => col.classList.toggle('d-none'));
    const isEdit = label.textContent.trim() === 'Edit';

    if (isEdit) {
      label.textContent = 'Cancel';
      icon.classList.replace('fa-edit', 'fa-times');

      // Remove all color classes, add solid red
      toggleBtn.classList.remove('btn-dark', 'btn-secondary', 'btn-outline-secondary', 'btn-outline-dark');
      toggleBtn.classList.add('btn-danger');
    } else {
      label.textContent = 'Edit';
      icon.classList.replace('fa-times', 'fa-edit');

      // Remove all color classes, add solid black
      toggleBtn.classList.remove('btn-danger', 'btn-outline-danger', 'btn-secondary', 'btn-outline-secondary');
      toggleBtn.classList.add('btn-dark');
    }

    // reset checkboxes
    if (selectAll) selectAll.checked = false;
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
  });



  // ====== 2. Show Preview Modal When a Row is Selected ======
  const editModalEl = document.getElementById('editModal');
  const editModal = new bootstrap.Modal(editModalEl);
  const rowCheckboxes = document.querySelectorAll('.row-checkbox');

  rowCheckboxes.forEach(cb => {
    cb.addEventListener('change', function () {
      const checkedCount = document.querySelectorAll('.row-checkbox:checked').length;
      if (checkedCount === 1) {
        editModal.show();
      } else if (checkedCount === 0) {
        bootstrap.Modal.getInstance(editModalEl)?.hide();
      }
    });
  });

  // ====== 3. Proceed to Full Edit Modal ======
  const proceedBtn = document.getElementById('proceedEdit');
  const editAccountModalEl = document.getElementById('editAccountModal');
  const editAccountModal = new bootstrap.Modal(editAccountModalEl);
  const form = document.getElementById('editTeacherForm');
  const roleDropdown = document.getElementById('edit_role');

  proceedBtn?.addEventListener('click', () => {
    const selectedCheckbox = document.querySelector('.row-checkbox:checked');
    if (!selectedCheckbox) {
      alert("Please select a user to edit.");
      return;
    }

    const userId = selectedCheckbox.value;
    bootstrap.Modal.getInstance(editModalEl)?.hide();

    setTimeout(() => {
      fetch('function/update_teacher_info.php?user_id=' + userId)
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_fname').value = data.fname;
            document.getElementById('edit_mname').value = data.mname;
            document.getElementById('edit_lname').value = data.lname;
            document.getElementById('edit_username').value = data.username;
            document.getElementById('edit_email').value = data.email;

            fetch('function/get_roles.php')
              .then(response => response.json())
              .then(roles => {
                roleDropdown.innerHTML = '';
                roles.forEach(role => {
                  const option = document.createElement('option');
                  option.value = role.id;
                  option.text = role.role_name;
                  if (role.id == data.role_id) option.selected = true;
                  roleDropdown.appendChild(option);
                });
              });

            editAccountModal.show();
          } else {
            alert('Failed to fetch user info.');
          }
        });
    }, 300);
  });


  // ====== 4. Close Button Manual Fix (for static modal) ======
  const editCloseBtn = document.getElementById('editCloseBtn');
  editCloseBtn?.addEventListener('click', () => {
    const instance = bootstrap.Modal.getInstance(editAccountModalEl);
    instance?.hide();

    //Only uncheck selected checkbox
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    if (selectAll) selectAll.checked = false;
  });



  const editModalCloseBtn = editModalEl.querySelector('.btn-secondary');

  editModalCloseBtn?.addEventListener('click', () => {
    // Close the modal manually just to be safe
    bootstrap.Modal.getInstance(editModalEl)?.hide();

    // Uncheck selected checkboxes
    document.querySelectorAll('.row-checkbox').forEach(cb => cb.checked = false);
    if (selectAll) selectAll.checked = false;

    // Leave edit mode active (no reset)
  });
});



document.getElementById("editTeacherForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const spinner = document.getElementById("editSpinner");
  const btnText = document.getElementById("saveEditText");
  const saveBtn = document.getElementById("saveEditBtn");

  // Show spinner & disable button
  spinner.classList.remove("d-none");
  btnText.textContent = "Saving...";
  saveBtn.disabled = true;

  const formData = new FormData(this);

  fetch("function/update_teacher_info.php", {
    method: "POST",
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      // Success alert outside modal
      const globalAlert = document.getElementById("alertContainer");
      globalAlert.innerHTML = `
        <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow" role="alert">
          <svg class="bi flex-shrink-0 me-2 text-success" width="24" height="24" role="img" aria-label="Success:">
            <use xlink:href="#check-circle-fill"/>
          </svg>
          <div>${data.message}</div>
          <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;

      // Close modal
      const modalEl = document.getElementById("editAccountModal");
      const modal = bootstrap.Modal.getInstance(modalEl);
      modal?.hide();

      // Auto-hide success alert after 2s then refresh
      setTimeout(() => {
        globalAlert.innerHTML = "";
        location.reload(); // refresh page after alert disappears
      }, 2000);

    } else {
      // Show error alert
      const errorAlert = document.getElementById("editUserErrorAlert");
      errorAlert.innerHTML = `
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          ${data.message}
          <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
      `;
    }
  })
  .catch(err => {
    console.error(err);
    alert("An error occurred while updating.");
  })
  .finally(() => {
    // Reset button
    spinner.classList.add("d-none");
    btnText.textContent = "Save Changes";
    saveBtn.disabled = false;
  });
});


