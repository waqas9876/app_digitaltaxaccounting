/* =============================================
   DIGITAL TAX ACCOUNTING - MAIN JS
   ============================================= */

'use strict';

// ---- SIDEBAR TOGGLE ----
const sidebar        = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');
const menuBtn        = document.getElementById('menuBtn');
const sidebarClose   = document.getElementById('sidebarClose');

function openSidebar() {
  sidebar?.classList.add('open');
  sidebarOverlay?.classList.add('open');
  document.body.style.overflow = 'hidden';
}

function closeSidebar() {
  sidebar?.classList.remove('open');
  sidebarOverlay?.classList.remove('open');
  document.body.style.overflow = '';
}

menuBtn?.addEventListener('click', openSidebar);
sidebarClose?.addEventListener('click', closeSidebar);
sidebarOverlay?.addEventListener('click', closeSidebar);

// ---- MODALS ----
function openModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.add('open');
    document.body.style.overflow = 'hidden';
  }
}

function closeModal(id) {
  const m = document.getElementById(id);
  if (m) {
    m.classList.remove('open');
    document.body.style.overflow = '';
  }
}

document.querySelectorAll('[data-open-modal]').forEach(btn => {
  btn.addEventListener('click', () => openModal(btn.dataset.openModal));
});

document.querySelectorAll('[data-close-modal]').forEach(btn => {
  btn.addEventListener('click', () => closeModal(btn.dataset.closeModal));
});

document.querySelectorAll('.modal-backdrop').forEach(backdrop => {
  backdrop.addEventListener('click', e => {
    if (e.target === backdrop) closeModal(backdrop.id);
  });
});

document.addEventListener('keydown', e => {
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-backdrop.open').forEach(m => m.classList.remove('open'));
    document.body.style.overflow = '';
  }
});

// ---- ALERTS ----
function showAlert(message, type = 'success', containerId = 'alertContainer') {
  const container = document.getElementById(containerId);
  if (!container) return;

  const icons = {
    success: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
    danger:  '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>',
    warning: '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    info:    '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
  };

  const alert = document.createElement('div');
  alert.className = `alert alert-${type} fade-in`;
  alert.innerHTML = `${icons[type] || icons.info}<span>${message}</span>`;
  container.innerHTML = '';
  container.appendChild(alert);

  setTimeout(() => alert.remove(), 5000);
}

// ---- API CALL HELPER ----
async function apiCall(url, data = {}, method = 'POST') {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const body = method === 'GET' ? null : JSON.stringify({ ...data, csrf_token: csrfToken });

  const res = await fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body,
  });
  return res.json();
}

// ---- FORM LOADING STATE ----
function setLoading(btn, loading) {
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<span class="spinner"></span> Processing...';
    btn.disabled = true;
  } else {
    btn.innerHTML = btn.dataset.originalText || 'Submit';
    btn.disabled = false;
  }
}

// ---- DELETE CONFIRMATION ----
function confirmDelete(message = 'Are you sure you want to delete this?') {
  return confirm(message);
}

// ---- CURRENCY FORMAT ----
function formatCurrency(amount) {
  return '$' + parseFloat(amount || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// ---- DATE FORMAT ----
function formatDate(dateStr) {
  if (!dateStr) return '—';
  return new Date(dateStr).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

// ---- ANIMATE NUMBERS ----
function animateNumber(el, target, duration = 1000, prefix = '$') {
  const start = 0;
  const step  = (target - start) / (duration / 16);
  let current = start;

  const timer = setInterval(() => {
    current += step;
    if (current >= target) {
      current = target;
      clearInterval(timer);
    }
    const formatted = prefix + current.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    el.textContent = formatted;
  }, 16);
}

// Trigger on page load
document.querySelectorAll('[data-animate-number]').forEach(el => {
  const val = parseFloat(el.dataset.animateNumber || 0);
  const pfx = el.dataset.prefix || '$';
  animateNumber(el, val, 1200, pfx);
});

// ---- TABLE SEARCH ----
function initTableSearch(inputId, tableId) {
  const input = document.getElementById(inputId);
  const table = document.getElementById(tableId);
  if (!input || !table) return;

  input.addEventListener('input', () => {
    const q = input.value.toLowerCase();
    table.querySelectorAll('tbody tr').forEach(row => {
      row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
  });
}

// ---- YEAR SELECTOR ----
const yearSel = document.getElementById('yearSelector');
if (yearSel) {
  yearSel.addEventListener('change', () => {
    const url = new URL(window.location.href);
    url.searchParams.set('year', yearSel.value);
    window.location.href = url.toString();
  });
}

// ---- PARTICLES (auth pages) ----
function initParticles() {
  const container = document.querySelector('.auth-particles');
  if (!container) return;

  for (let i = 0; i < 20; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    p.style.cssText = `
      left: ${Math.random() * 100}%;
      width: ${2 + Math.random() * 4}px;
      height: ${2 + Math.random() * 4}px;
      animation-duration: ${6 + Math.random() * 12}s;
      animation-delay: ${Math.random() * 8}s;
      opacity: ${.2 + Math.random() * .5};
    `;
    container.appendChild(p);
  }
}

initParticles();

// ---- TOAST NOTIFICATIONS ----
function toast(message, type = 'success', duration = 3500) {
  let container = document.getElementById('toastContainer');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toastContainer';
    container.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
    document.body.appendChild(container);
  }

  const colors = {
    success: { bg: '#ECFDF5', border: '#10B981', text: '#065f46' },
    danger:  { bg: '#FEF2F2', border: '#EF4444', text: '#991b1b' },
    warning: { bg: '#FFFBEB', border: '#F59E0B', text: '#92400e' },
    info:    { bg: '#EFF6FF', border: '#3B82F6', text: '#1e40af' },
  };
  const c = colors[type] || colors.info;

  const t = document.createElement('div');
  t.style.cssText = `
    background: ${c.bg};
    border-left: 3px solid ${c.border};
    color: ${c.text};
    padding: 12px 16px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
    box-shadow: 0 4px 16px rgba(0,0,0,.12);
    max-width: 320px;
    animation: slideUp .3s ease;
  `;
  t.textContent = message;
  container.appendChild(t);

  setTimeout(() => {
    t.style.opacity = '0';
    t.style.transform = 'translateX(20px)';
    t.style.transition = 'all .3s';
    setTimeout(() => t.remove(), 300);
  }, duration);
}

// ---- INLINE EDIT HELPERS ----
function enableEdit(fieldId, saveUrl) {
  const field = document.getElementById(fieldId);
  if (!field) return;
  field.removeAttribute('readonly');
  field.focus();
  field.classList.add('editing');

  const saveHandler = async () => {
    field.setAttribute('readonly', '');
    field.classList.remove('editing');
    const data = { field: fieldId, value: field.value };
    const result = await apiCall(saveUrl, data);
    toast(result.message || 'Saved!', result.success ? 'success' : 'danger');
  };

  field.addEventListener('blur', saveHandler, { once: true });
}

// ---- PRINT PAGE ----
function printPage() { window.print(); }

// ---- EXPORT CSV ----
function exportTableToCsv(tableId, filename = 'export.csv') {
  const table = document.getElementById(tableId);
  if (!table) return;

  const rows  = Array.from(table.querySelectorAll('tr'));
  const csv   = rows.map(row =>
    Array.from(row.querySelectorAll('th,td'))
      .map(cell => `"${cell.textContent.trim().replace(/"/g, '""')}"`)
      .join(',')
  ).join('\n');

  const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
  const link = document.createElement('a');
  link.href = URL.createObjectURL(blob);
  link.download = filename;
  link.click();
}

// ---- RESPONSIVE TABLE LABELS (mobile) ----
document.querySelectorAll('.data-table').forEach(table => {
  const headers = Array.from(table.querySelectorAll('thead th')).map(h => h.textContent.trim());
  table.querySelectorAll('tbody tr').forEach(row => {
    Array.from(row.querySelectorAll('td')).forEach((td, i) => {
      if (headers[i]) td.dataset.label = headers[i];
    });
  });
});
