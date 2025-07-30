document.addEventListener('DOMContentLoaded', () => {
  const commentModalEl = document.getElementById('commentModal');
  const commentModal = new bootstrap.Modal(commentModalEl, { backdrop: 'static' });
  const commentForm = document.getElementById('commentForm');
  const commentError = document.getElementById('commentError');
  const korisnikSelect = document.querySelector('nav select[name="korisnik"]');

  // Dohvati selektovanog korisnika iz navbar
  function getSelectedUser() {
    return korisnikSelect ? korisnikSelect.value : null;
  }

  // Otvori modal sa prosleđenim MC brojem i resetuj formu i greške
  // Show only comments (no form)
  async function openViewCommentsModal(mc) {
    commentError.classList.add('d-none');
    commentError.textContent = '';
    commentForm.reset();
    document.getElementById('modalMc').value = mc;
    // Hide form fields, show comments list
    document.getElementById('commentFormFields').style.display = 'none';
    document.getElementById('commentsList').style.display = '';
    document.getElementById('addCommentInModal').style.display = '';
    // Set modal title to 'Comments'
    document.getElementById('commentModalLabel').textContent = 'Comments';
    const commentsList = document.getElementById('commentsList');
    if (commentsList) commentsList.innerHTML = '<div class="text-muted">Loading...</div>';
    try {
      const res = await fetch('php/get_comments.php?mc=' + encodeURIComponent(mc));
      const data = await res.json();
      if (data.success && commentsList) {
        if (data.comments.length === 0) {
          commentsList.innerHTML = '<div class="text-muted">No comments yet.</div>';
        } else {
          commentsList.innerHTML = data.comments.map(c =>
            `<div class="border rounded p-2 mb-2 d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-bold">${c.added_by_user || 'Unknown'} <span class="text-secondary small">${c.created_at}</span></div>
                <div>${c.comment_text}</div>
              </div>
              <button class="btn btn-sm btn-danger ms-2 delete-comment-btn" data-comment-id="${c.id}" title="Delete comment">&times;</button>
            </div>`
          ).join('');
        }
      }
      const newCommentsList = document.getElementById('commentsList');
      if (newCommentsList._deleteListener) {
        newCommentsList.removeEventListener('click', newCommentsList._deleteListener);
      }
      newCommentsList._deleteListener = async function(e) {
        const btn = e.target.closest('.delete-comment-btn');
        if (btn) {
          const commentId = btn.getAttribute('data-comment-id');
          if (confirm('Are you sure you want to delete this comment?')) {
            try {
              const res = await fetch('php/delete_comment.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json' },
                body: new URLSearchParams({ id: commentId })
              });
              const data = await res.json();
              if (data.success) {
                // Refresh comments list
                openViewCommentsModal(document.getElementById('modalMc').value);
              } else {
                alert(data.error || 'Failed to delete comment.');
              }
            } catch (err) {
              alert('Error communicating with the server.');
            }
          }
        }
      };
      newCommentsList.addEventListener('click', newCommentsList._deleteListener);
    } catch (e) {
      if (commentsList) commentsList.innerHTML = '<div class="text-danger">Failed to load comments.</div>';
    }
  // (Delete comment event delegation is now handled inside openViewCommentsModal to avoid stacking listeners)
    if (commentModalEl.classList.contains('show')) {
      bootstrap.Modal.getInstance(commentModalEl).hide();
    }
    commentModal.show();
  }

  // Show only form (no comments)
  function openAddCommentModal(mc) {
    commentError.classList.add('d-none');
    commentError.textContent = '';
    commentForm.reset();
    document.getElementById('modalMc').value = mc;
    document.getElementById('commentFormFields').style.display = '';
    document.getElementById('commentsList').style.display = 'none';
    document.getElementById('addCommentInModal').style.display = 'none';
    // Set modal title to 'Add a Comment'
    document.getElementById('commentModalLabel').textContent = 'Add a Comment';
    // Do NOT hide and re-show the modal, just update content
  }
  window.openCommentModal = openViewCommentsModal;
  // Event delegation for Add a Comment button in table
  const resultsTable = document.getElementById('resultsTable');
  if (resultsTable) {
    resultsTable.addEventListener('click', e => {
      const button = e.target.closest('button');
      if (button && button.textContent.trim() === 'Add a Comment') {
        const tr = button.closest('tr');
        if (!tr) return;
        const mcCell = tr.querySelector('td:nth-child(2)');
        if (!mcCell) return;
        const mc = mcCell.textContent.trim();
        openAddCommentModal(mc);
      }
    });
  }

  // Add a comment button in modal
  document.addEventListener('click', function(e) {
    if (e.target && e.target.id === 'addCommentInModal') {
      openAddCommentModal(document.getElementById('modalMc').value);
    }
  });

  // Obrada slanja forme
  commentForm.addEventListener('submit', async e => {
    e.preventDefault();
    commentError.classList.add('d-none');
    commentError.textContent = '';
    commentError.classList.remove('alert-danger', 'alert-success');

    const korisnik = getSelectedUser();
    const commentText = commentForm.querySelector('[name="comment_text"]')?.value.trim();

    if (!korisnik || korisnik === 'Izaberite korisnika') {
      commentError.textContent = 'Please select a user in the navbar before adding a comment.';
      commentError.classList.remove('d-none');
      commentError.classList.remove('alert-success');
      commentError.classList.add('alert-danger');
      return;
    }
    if (!commentText) {
      commentError.textContent = 'Comment text cannot be empty.';
      commentError.classList.remove('d-none');
      commentError.classList.remove('alert-success');
      commentError.classList.add('alert-danger');
      return;
    }

    const formData = new FormData(commentForm);
    formData.append('added_by_user', korisnik);

    try {
      const response = await fetch('php/add_comment.php', {
        method: 'POST',
        body: formData
      });
      const result = await response.json();

      if (result.success) {
        const isGeneral = commentForm.querySelector('[name="is_general"]')?.checked;
        if (isGeneral) {
          location.reload();
          return;
        }
        commentError.textContent = 'Comment added successfully.';
        commentError.classList.remove('d-none');
        commentError.classList.remove('alert-danger');
        commentError.classList.add('alert-success');
        commentForm.reset();
      } else {
        commentError.textContent = result.error || 'An error occurred while adding the comment.';
        commentError.classList.remove('d-none');
        commentError.classList.remove('alert-success');
        commentError.classList.add('alert-danger');
      }
    } catch (error) {
      commentError.textContent = 'Error communicating with the server.';
      commentError.classList.remove('d-none');
      commentError.classList.remove('alert-success');
      commentError.classList.add('alert-danger');
    }
  });
});
