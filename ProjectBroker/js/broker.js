document.addEventListener('DOMContentLoaded', () => {
  const brokerModalEl = document.getElementById('brokerModal');
  const brokerModal = new bootstrap.Modal(brokerModalEl);
  const brokerForm = document.getElementById('brokerForm');
  const brokerError = document.getElementById('brokerError');

  const approvedOurStatus = document.getElementById('approvedOurStatus');
  const approvedTheirStatus = document.getElementById('approvedTheirStatus');
  const unapprovedReasonOur = document.getElementById('unapprovedReasonOur');
  const unapprovedReasonTheir = document.getElementById('unapprovedReasonTheir');

  function toggleReasonInput(statusSelect, reasonInput) {
    reasonInput.classList.toggle('d-none', statusSelect.value !== 'Not approved');
  }

  approvedOurStatus.addEventListener('change', () => {
    toggleReasonInput(approvedOurStatus, unapprovedReasonOur);
  });

  approvedTheirStatus.addEventListener('change', () => {
    toggleReasonInput(approvedTheirStatus, unapprovedReasonTheir);
  });

  // Klik na "Add broker to system" dugme
  document.getElementById('resultsTable').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;

    if (btn.textContent.trim() === 'Add broker to system' || btn.textContent.trim() === 'Edit broker') {
      const tr = btn.closest('tr');
      const mc = tr.querySelector('td:nth-child(2)').textContent.trim();

      // Možeš ovde AJAX-om povući podatke ako broker već postoji
      document.getElementById('brokerMc').value = mc;

      brokerError.classList.add('d-none');
      brokerForm.reset();
      unapprovedReasonOur.classList.add('d-none');
      unapprovedReasonTheir.classList.add('d-none');

      brokerModal.show();
    }
  });

  // Slanje forme
  brokerForm.addEventListener('submit', async e => {
    e.preventDefault();
    brokerError.classList.add('d-none');

    const formData = new FormData(brokerForm);

    try {
      const response = await fetch('php/save_broker.php', {
        method: 'POST',
        body: formData
      });

      const result = await response.json();

      if (result.success) {
        brokerModal.hide();
        alert('Broker uspešno dodat/izmenjen!');
        // Osveži tabelu ako treba
      } else {
        brokerError.textContent = result.error || 'Greška pri čuvanju.';
        brokerError.classList.remove('d-none');
      }
    } catch (err) {
      brokerError.textContent = 'Greška pri komunikaciji sa serverom.';
      brokerError.classList.remove('d-none');
    }
  });
});
