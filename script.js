// Ticket creation + list rendering using MySQL endpoints

(function () {
  const form = document.getElementById('ticketForm');
  const ticketsContainer = document.getElementById('ticketsTableContainer');
  const pagination = document.getElementById('pagination');
  const clearBtn = document.getElementById('clearTickets');
  const exportBtn = document.getElementById('exportTickets');
  const importBtn = document.getElementById('importTickets');
  const importFile = document.getElementById('importFile');
  const isTicketsUiPresent = Boolean(ticketsContainer);

  const ticketsPerPage = 6;
  let ticketsData = [];
  let currentPage = 1;

  async function fetchTickets() {
    try {
      const res = await fetch('list_tickets.php', { cache: 'no-store' });
      const data = await res.json();
      if (!data.ok) return [];
      // Return both tickets and count so we can show today's tickets
      return {
        tickets: data.tickets || [],
        today_count: data.today_count ?? undefined,
      };
    } catch (e) {
      return [];
    }
  }


  function renderTickets(tickets, page = 1) {
    if (!ticketsContainer) return;
    ticketsContainer.innerHTML = '';

    if (!tickets || tickets.length === 0) {
      ticketsContainer.innerHTML = '<p>No tickets found.</p>';
      renderPagination(0, 0);
      return;
    }

    currentPage = page;
    const totalPages = Math.max(1, Math.ceil(tickets.length / ticketsPerPage));
    const startIndex = (page - 1) * ticketsPerPage;
    const pageTickets = tickets.slice(startIndex, startIndex + ticketsPerPage);

    const table = document.createElement('table');
    table.innerHTML = `
      <thead>
        <tr>
          <th>ID</th>
          <th>Issue</th>
          <th>Solution</th>
          <th>Company</th>
          <th>Department</th>
          <th>Assigned To</th>
          <th>Priority</th>
          <th>Status</th>
          <th>Date</th>
          <th>Created At</th>
          <th>Manage</th>
        </tr>
      </thead>
      <tbody></tbody>
    `;

    const tbody = table.querySelector('tbody');

    pageTickets.forEach((t) => {
      const row = document.createElement('tr');
      row.innerHTML = `
        <td>${escapeHtml(t.ticket_id || '')}</td>
        <td>${escapeHtml(t.issue || '')}</td>
        <td>${escapeHtml(t.solution || '')}</td>
        <td>${escapeHtml(t.company || '')}</td>
        <td>${escapeHtml(t.department || '')}</td>
        <td>${escapeHtml(t.assigned_to || '')}</td>
        <td>${escapeHtml(t.priority || '')}</td>
        <td>${escapeHtml(t.status || '')}</td>
        <td>${escapeHtml(t.date || '')}</td>
        <td>${escapeHtml(t.created_at || '')}</td>
        <td><a class="manage-button" href="manage_ticket.php?id=${encodeURIComponent(t.ticket_id || '')}">Manage</a></td>
      `;
      tbody.appendChild(row);
    });

    ticketsContainer.appendChild(table);
    renderPagination(totalPages, page);
  }

  function renderPagination(totalPages, page) {
    if (!pagination) return;
    pagination.innerHTML = '';

    if (totalPages <= 1) return;

    const prevButton = document.createElement('button');
    prevButton.textContent = 'Previous';
    prevButton.disabled = page <= 1;
    prevButton.addEventListener('click', () => renderTickets(ticketsData, page - 1));
    pagination.appendChild(prevButton);

    for (let i = 1; i <= totalPages; i++) {
      const pageButton = document.createElement('button');
      pageButton.textContent = i;
      if (i === page) {
        pageButton.classList.add('active');
        pageButton.disabled = true;
      }
      pageButton.addEventListener('click', () => renderTickets(ticketsData, i));
      pagination.appendChild(pageButton);
    }

    const nextButton = document.createElement('button');
    nextButton.textContent = 'Next';
    nextButton.disabled = page >= totalPages;
    nextButton.addEventListener('click', () => renderTickets(ticketsData, page + 1));
    pagination.appendChild(nextButton);
  }

  function escapeHtml(str) {
    return String(str)
      .replaceAll('&', '&amp;')
      .replaceAll('<', '&lt;')
      .replaceAll('>', '&gt;')
      .replaceAll('"', '&quot;')
      .replaceAll("'", '&#039;');
  }

  async function loadTickets(page = 1) {
    if (!isTicketsUiPresent) return;
    const data = await fetchTickets();
    // fetchTickets returns {tickets, today_count} when available
    ticketsData = data && Array.isArray(data.tickets) ? data.tickets : (data || []);
    renderTickets(ticketsData, page);
    const todayCountEl = document.getElementById('todayTicketsCount');
    if (todayCountEl && data && typeof data.today_count !== 'undefined') {
      todayCountEl.textContent = data.today_count;
    }
  }



  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const formData = new FormData(form);
      const res = await fetch('create_ticket.php', {
        method: 'POST',
        body: formData,
      });

      const data = await res.json();

      if (!data.ok) {
        alert(data.error || 'Failed to create ticket');
        return;
      }

      form.reset();
      loadTickets(1);
    });
  }

  if (clearBtn) {
    clearBtn.addEventListener('click', () => {
      alert('Clear all tickets is not enabled for MySQL in this version.');
    });
  }

  if (exportBtn) {
    exportBtn.addEventListener('click', async () => {
      const tickets = await fetchTickets();
      const blob = new Blob([JSON.stringify({ ok: true, tickets }, null, 2)], {
        type: 'application/json',
      });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url;
      a.download = 'tickets_export.json';
      a.click();
      URL.revokeObjectURL(url);
    });
  }

  if (importBtn) {
    importBtn.addEventListener('click', () => {
      if (importFile) importFile.click();
    });
  }

  if (importFile) {
    importFile.addEventListener('change', async () => {
      if (!importFile.files || !importFile.files[0]) return;
      const file = importFile.files[0];
      const text = await file.text();
      const payload = JSON.parse(text);
      const tickets = payload.tickets || payload;

      if (!Array.isArray(tickets)) {
        alert('Invalid import file');
        return;
      }

      alert('Bulk import is not enabled for MySQL in this version.');
    });
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (isTicketsUiPresent) loadTickets(1);
  });

})();

