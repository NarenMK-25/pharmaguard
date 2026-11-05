// Hardcoded backend URL for Render deployment
const API = 'https://pharmaguard-tmk3.onrender.com/'; 

const $ = (s) => document.querySelector(s);
let lowRows = [];

async function api(path, opts = {}) {
  const res = await fetch(API + path, { credentials: 'include', ...opts });
  let data = {};
  try { data = await res.json(); } catch (_) { /* non-JSON error */ }
  if (!res.ok) throw new Error(data.error || 'Request failed (' + res.status + ')');
  return data;
}

function fill(table, rows, cols, emptyText) {
  const body = table.tBodies[0];
  body.replaceChildren();
  if (!rows.length) {
    const td = body.insertRow().insertCell();
    td.colSpan = cols.length; td.className = 'empty'; td.textContent = emptyText;
    return;
  }
  for (const r of rows) {
    const tr = body.insertRow();
    for (const c of cols) {
      const td = tr.insertCell();
      td.textContent = c.v(r);
      td.className = (c.num ? 'num ' : '') + (c.cls ? c.cls(r) : '');
    }
  }
}

async function loadDashboard() {
  try {
    const d = await api('/alerts');
    lowRows = d.lowStock;
    $('#c-exp').textContent = d.expiring.length;
    $('#c-expired').textContent = d.expired.length;
    $('#c-low').textContent = d.lowStock.length;
    fill($('#t-exp'), d.expiring, [
      { v: r => r.medicine }, { v: r => r.batch_no }, { v: r => r.expiry_date },
      { v: r => r.days_left, cls: r => (r.days_left <= 7 ? 'urgent' : 'soon') }, { v: r => r.quantity, num: 1 }
    ], 'Nothing expires in the next 30 days.');
    fill($('#t-expired'), d.expired, [
      { v: r => r.medicine }, { v: r => r.batch_no }, { v: r => r.expiry_date },
      { v: r => r.days_ago, cls: () => 'urgent' }, { v: r => r.quantity, num: 1 }
    ], 'No expired stock on the shelf.');
    fill($('#t-low'), d.lowStock, [
      { v: r => r.medicine }, { v: r => r.supplier || '-' }, { v: r => r.stock, num: 1, cls: () => 'urgent' },
      { v: r => r.reorder_level, num: 1 }, { v: r => r.used30, num: 1 }, { v: r => r.suggested, num: 1 }
    ], 'All medicines are above their reorder level.');
  } catch (e) {
    const box = $('#alert-error');
    box.hidden = false;
    box.textContent = e.message + ' If this says "Not signed in", sign out and sign in again.';
  }
}

function downloadCsv() {
  const q = (s) => '"' + String(s ?? '').replace(/"/g, '""') + '"';
  const lines = ['Medicine,Supplier,In stock,Reorder level,Used 30 days,Order quantity'];
  for (const r of lowRows) lines.push([q(r.medicine), q(r.supplier), r.stock, r.reorder_level, r.used30, r.suggested].join(','));
  const a = document.createElement('a');
  a.href = URL.createObjectURL(new Blob([lines.join('\n')], { type: 'text/csv' }));
  a.download = 'reorder-list.csv';
  a.click();
  URL.revokeObjectURL(a.href);
}

if ($('#t-low')) {
  loadDashboard();
