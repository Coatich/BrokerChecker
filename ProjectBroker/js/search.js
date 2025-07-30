document.addEventListener('DOMContentLoaded', () => {
  const searchForm = document.getElementById('searchForm');
  const resultsTable = document.getElementById('resultsTable');
  console.log('openBrokerModal:', window.openBrokerModal);
  console.log('openCommentModal:', window.openCommentModal);


  if (!searchForm || !resultsTable) {
    console.error('searchForm or resultsTable not found!');
    return;
  }

  // Helper: fill form from URL params
  function fillFormFromParams(params) {
    for (const [key, value] of params.entries()) {
      const el = searchForm.elements[key];
      if (el) el.value = value;
    }
  }

  // On submit, update URL and fetch
  searchForm.addEventListener('submit', function (e) {
    e.preventDefault();

    const formData = new FormData(this);
    const query = new URLSearchParams(formData).toString();

    // Update URL without reloading
    if (window.history.replaceState) {
      const newUrl = window.location.pathname + (query ? '?' + query : '');
      window.history.replaceState({}, '', newUrl);
    }

    fetch("php/search.php?" + query)
      .then(res => res.json())
      .then(data => {
        const tbody = resultsTable.querySelector("tbody");
        tbody.innerHTML = "";

        if (!data.results || data.results.length === 0) {
          const message = data.message || "No results found.";
          tbody.innerHTML = `<tr><td colspan="10" class="text-center text-danger">${message}</td></tr>`;
          return;
        }

        data.results.forEach((item, index) => {
          let dropdownOptions = "";

          if (item.exists_in_db === false) {
            dropdownOptions = `
              <li><button class="dropdown-item add-broker-btn" data-action="add-broker" data-mc="${item.mc}">Add broker to system</button></li>`;
          } else {
            dropdownOptions = `
              <li><button class="dropdown-item edit-broker-btn" data-mc="${item.mc}" data-action="add-broker">Edit broker</button></li>
              <li><button class="dropdown-item view-comments-btn" data-mc="${item.mc}">View Comments</button></li>
              <li><a class="dropdown-item book-load-btn" href="html/loads.html?mc=${item.mc}" target="_blank">Book a load</a></li>`;
          }

          let rowClass = "";
          if (item.approved_status === "Approved") rowClass = "table-success";
          else if (item.approved_status === "No info") rowClass = "table-warning";
          else rowClass = "table-danger";

          const row = `
            <tr class="${rowClass}">
              <td>${index + 1}</td>
              <td>${item.mc}</td>
              <td>${item.dot}</td>
              <td>${item.ime}</td>
              <td>${item.setup_status}</td>
              <td>${item.approved_status === "Approved" ? "Yes" : (item.approved_status === "No info" ? "No Info" : "No")}</td>
              <td>${item.updated_at}</td>
              <td>${item.general_comment || "No General Comment"}</td>
              <td>
                <a href="https://rtspro.com/credit" target="_blank"
                   class="view-link" data-mc="${item.mc}" data-bs-toggle="tooltip" data-bs-trigger="manual" title="">
                   View
                </a>
              </td>
              <td>
                <div class="dropdown">
                  <button class="btn btn-warning dropdown-toggle btn-sm" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear-fill"></i>
                  </button>
                  <ul class="dropdown-menu">
                    ${dropdownOptions}
                  </ul>
                </div>
              </td>
            </tr>`;
          tbody.insertAdjacentHTML("beforeend", row);
        });

        // Aktiviraj tooltip za view-link
        const viewLinks = document.querySelectorAll(".view-link");
        viewLinks.forEach(link => {
          const tooltip = new bootstrap.Tooltip(link);
          link.addEventListener("click", function () {
            const mc = this.dataset.mc;
            navigator.clipboard.writeText(mc).then(() => {
              this.setAttribute("data-bs-original-title", `MC# ${mc} copied!`);
              tooltip.show();
              setTimeout(() => tooltip.hide(), 1500);
            });
          });
        });
      });
  
  });

  // On page load: if URL has params, fill form and auto-submit
  const urlParams = new URLSearchParams(window.location.search);
  if ([...urlParams.keys()].length > 0) {
    fillFormFromParams(urlParams);
    searchForm.dispatchEvent(new Event('submit'));
  }

  // Delegacija klikova na dugmad u tabeli
  resultsTable.addEventListener('click', function (e) {
    const target = e.target;
    const button = target.closest('button');
    const tr = target.closest('tr');
    if (!button || !tr) return;

    const mc = tr.children[1]?.textContent.trim();
    const ime = tr.children[3]?.textContent.trim();
    const dot = tr.children[2]?.textContent.trim();

    switch (true) {
      case button.classList.contains('add-broker-btn'):
        openBrokerModal({
          mc,
          dot,
          ime,
          setup_status: tr.children[4].textContent.trim() === 'Yes',
          approved_our_status: tr.children[5].textContent.trim() !== 'No',
          approved_their_status: tr.children[5].textContent.trim() !== 'No'
        });
        break;
      case button.classList.contains('edit-broker-btn'):
        openBrokerModal({
          mc,
          dot,
          ime,
          setup_status: tr.children[4].textContent.trim() === 'Yes',
          approved_our_status: tr.children[5].textContent.trim() !== 'No',
          approved_their_status: tr.children[5].textContent.trim() !== 'No'
        });
        break;
      case button.classList.contains('view-comments-btn'):
        openCommentModal(mc);
        break;
      case button.textContent.trim() === 'Add a Comment':
        // Stari Add a Comment dugme
        openCommentModal(mc);
        break;
    }
  });
});
