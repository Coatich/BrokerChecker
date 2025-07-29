document.addEventListener('DOMContentLoaded', () => {

  const brokerModalEl = document.getElementById('brokerModal');
  const brokerForm = document.getElementById('brokerForm');
  const brokerModal = brokerModalEl ? new bootstrap.Modal(brokerModalEl) : null;
  const approvedOurStatus = document.getElementById('approvedOurStatus');
  const approvedTheirStatus = document.getElementById('approvedTheirStatus');

  const showHideReason = (selectEl, reasonInputId) => {
    document.getElementById(reasonInputId).classList.toggle(
      'd-none',
      selectEl.value !== '0'
    );
  };

  if (approvedOurStatus && approvedTheirStatus) {
    approvedOurStatus.addEventListener('change', e => {
      showHideReason(e.target, 'unapprovedReasonOur');
    });
    approvedTheirStatus.addEventListener('change', e => {
      showHideReason(e.target, 'unapprovedReasonTheir');
    });
  } else {
    console.warn('Elementi approvedOurStatus ili approvedTheirStatus nisu pronađeni u DOM-u.');
  }

  async function openBrokerModal({ mc }) {
    if (!mc) return;

    // Reset form and set MC before fetch
    brokerForm.reset();
    document.getElementById('brokerMc').value = mc;

    try {
      const res = await fetch('php/search.php?mc=' + encodeURIComponent(mc));
      const data = await res.json();
      if (!data.success) {
        brokerModal.show();
        return;
      }

      const broker = data.broker;
      document.getElementById('setupStatus').value = broker.setup_status != null ? String(broker.setup_status) : '';
      approvedOurStatus.value = broker.approved_our_status != null ? String(broker.approved_our_status) : '';
      approvedTheirStatus.value = broker.approved_their_status != null ? String(broker.approved_their_status) : '';
      document.getElementById('unapprovedReasonOur').value = broker.unapproved_reason_our || '';
      document.getElementById('unapprovedReasonTheir').value = broker.unapproved_reason_their || '';
      document.getElementById('additionalNotes').value = broker.general_comment || '';

      showHideReason(approvedOurStatus, 'unapprovedReasonOur');
      showHideReason(approvedTheirStatus, 'unapprovedReasonTheir');

      brokerModal.show();
    } catch (err) {
      alert('Failed to load broker data.');
      brokerModal.show();
    }
  }

  // Eksportuj funkciju globalno da je search.js može pozvati
  window.openBrokerModal = openBrokerModal;

  // Klikovi na dugmad u tabeli za add/edit
  document.getElementById('resultsTable').addEventListener('click', e => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const mc = btn.dataset.mc;
    const action = btn.dataset.action;
    if (!mc || !action) return;

    if (action === 'add-broker' || action === 'edit-broker') {
      openBrokerModal({ mc });
    }
  });

  // Slanje forme
  brokerForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(brokerForm);
    const notes = document.getElementById('additionalNotes').value.trim();
    const mc = document.getElementById('brokerMc').value;
    // Get selected user from navbar
    const korisnikSelect = document.querySelector('nav select[name="korisnik"]');
    const added_by_user = korisnikSelect ? korisnikSelect.value : '';

    try {
      const res = await fetch('php/save_broker.php', {
        method: 'POST',
        body: formData
      });
      const result = await res.json();
      if (result.success) {
        // If notes are filled, submit as general comment
        if (notes) {
          await fetch('php/add_comment.php', {
            method: 'POST',
            headers: { },
            body: new URLSearchParams({
              mc,
              comment_text: notes,
              is_general: 1,
              added_by_user
            })
          });
        }
        brokerModal.hide();
        alert('Broker uspešno sačuvan.');
        document.getElementById('searchForm').dispatchEvent(new Event('submit'));
      } else {
        alert(result.error || 'Greška pri čuvanju brokera.');
      }
    } catch (error) {
      alert('Greška u komunikaciji sa serverom.');
    }
  });
});
