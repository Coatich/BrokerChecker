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
  function openCommentModal(mc) {
    commentError.classList.add('d-none');
    commentError.textContent = '';
    commentForm.reset();
    document.getElementById('modalMc').value = mc;
    if (commentModalEl.classList.contains('show')) {
      bootstrap.Modal.getInstance(commentModalEl).hide();
    }
    commentModal.show();
  }

  // Event delegation za "Add a Comment" dugme u tabeli
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
        openCommentModal(mc);
      }
    });
  }

  // Obrada slanja forme
  commentForm.addEventListener('submit', async e => {
    e.preventDefault();
    commentError.classList.add('d-none');
    commentError.textContent = '';

    const korisnik = getSelectedUser();

    if (!korisnik || korisnik === 'Izaberite korisnika') {
      commentError.textContent = 'Molimo vas da izaberete korisnika u navbaru pre nego što dodate komentar.';
      commentError.classList.remove('d-none');
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
        commentModal.hide();
        document.getElementById('searchForm').dispatchEvent(new Event('submit'));
        alert('Komentar je uspešno dodat.');
      } else {
        commentError.textContent = result.error || 'Došlo je do greške prilikom dodavanja komentara.';
        commentError.classList.remove('d-none');
      }
    } catch (error) {
      commentError.textContent = 'Greška pri komunikaciji sa serverom.';
      commentError.classList.remove('d-none');
    }
  });
  window.openCommentModal = openCommentModal;
});
