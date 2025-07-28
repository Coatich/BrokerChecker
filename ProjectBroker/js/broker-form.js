document.addEventListener('DOMContentLoaded', () => {
  const brokerModalEl = document.getElementById('brokerModal');
  const brokerModal = new bootstrap.Modal(brokerModalEl);
  const brokerForm = document.getElementById('brokerForm');

  const showHideReason = (selectEl, reasonInputId) => {
    document.getElementById(reasonInputId).classList.toggle(
      'd-none',
      selectEl.value !== 'Not approved'
    );
  };
    const approvedOur = document.getElementById('approvedOurStatus');
    const approvedTheir = document.getElementById('approvedTheirStatus');

    console.log('approvedOur:', document.getElementById('approvedOur'));
    console.log('approvedTheir:', document.getElementById('approvedTheir'));


    if (approvedOur && approvedTheir) {
        approvedOur.addEventListener('change', e => {
        showHideReason(e.target, 'unapprovedReasonOur');
    });
        approvedTheir.addEventListener('change', e => {
        showHideReason(e.target, 'unapprovedReasonTheir');
    });
    } else {
        console.warn('Jedan ili oba elementa za odobrenje brokera nisu pronađena.');
    }
  // Funkcija za otvaranje modala sa podacima
  async function openBrokerModal({ mc, dot, ime, setup_status, approved_our_status, approved_their_status }) {
    if (!mc) return;

    if (!ime && !dot && !setup_status && !approved_our_status && !approved_their_status) {
      // Ako nema dodatnih podataka, resetuj formu i stavi MC
      brokerForm.reset();
      document.getElementById('brokerMc').value = mc;
      brokerModal.show();
      return;
    }
    window.openBrokerModal = openBrokerModal;
    // U suprotnom, fetchuj podatke za edit
    try {
      const res = await fetch('php/search.php?mc=' + mc);
      const data = await res.json();
      if (!data.success) return alert('Error loading broker');

      document.getElementById('brokerMc').value = data.broker.mc;
      document.getElementById('setupStatus').value = data.broker.setup_status;
      document.getElementById('approvedOur').value = data.broker.approved_our_status;
      document.getElementById('approvedTheir').value = data.broker.approved_their_status;
      document.getElementById('unapprovedReasonOur').value = data.broker.unapproved_reason_our || "";
      document.getElementById('unapprovedReasonTheir').value = data.broker.unapproved_reason_their || "";

      showHideReason(document.getElementById('approvedOur'), 'unapprovedReasonOur');
      showHideReason(document.getElementById('approvedTheir'), 'unapprovedReasonTheir');

      brokerModal.show();
    } catch (err) {
      alert('Failed to load broker data.');
    }
  }

  document.getElementById('resultsTable').addEventListener('click', async e => {
    const btn = e.target.closest('button');
    if (!btn) return;

    const mc = btn.dataset.mc;
    const action = btn.dataset.action;
    if (!mc || !action) return;

    if (action === 'add-broker') {
      openBrokerModal({ mc });
    } else if (action === 'edit-broker') {
      openBrokerModal({ mc });
    }
  });

  ['approvedOur', 'approvedTheir'].forEach(id => {
  const el = document.getElementById(id);
  if (el) {
    el.addEventListener('change', e => {
      const inputId = id === 'approvedOur' ? 'unapprovedReasonOur' : 'unapprovedReasonTheir';
      showHideReason(e.target, inputId);
    });
  } else {
    console.warn(`Element sa id=${id} nije pronađen u DOM-u.`);
  }
});


  brokerForm.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(brokerForm);

    const res = await fetch('php/save_broker.php', {
      method: 'POST',
      body: formData
    });

    const result = await res.json();
    if (result.success) {
      brokerModal.hide();
      alert("Broker saved.");
      document.getElementById('searchForm').dispatchEvent(new Event('submit')); // refresh results
    } else {
      alert(result.error || "Error saving broker");
    }
  });
});
