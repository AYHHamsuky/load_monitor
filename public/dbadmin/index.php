<?php
require __DIR__ . '/auth.php';

if (!dbadmin_check_auth()) {
    // Show login page
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DB Admin – Login</title>
<style>
:root{--bg:#0f1923;--surface:#1a2634;--border:#2a3a4e;--text:#e2e8f0;--dim:#8899aa;--accent:#3b82f6;--red:#ef4444;--radius:8px}
*{box-sizing:border-box;margin:0;padding:0}body{font-family:'Segoe UI',sans-serif;background:var(--bg);color:var(--text);height:100vh;display:flex;align-items:center;justify-content:center}
.login-box{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:40px;width:360px;text-align:center}
.login-box i{font-size:36px;color:#f97316;margin-bottom:12px}
.login-box h1{font-size:18px;margin-bottom:4px}
.login-box p{font-size:12px;color:var(--dim);margin-bottom:24px}
.login-box input{width:100%;padding:10px 14px;border-radius:var(--radius);background:var(--bg);border:1px solid var(--border);color:var(--text);font-size:14px;outline:none;margin-bottom:14px}
.login-box input:focus{border-color:var(--accent)}
.login-box button{width:100%;padding:10px;border-radius:var(--radius);background:var(--accent);color:#fff;font-size:14px;font-weight:600;border:none;cursor:pointer}
.login-box button:hover{opacity:.9}
.err{color:var(--red);font-size:12px;margin-bottom:12px}
</style>
</head>
<body>
<form class="login-box" method="POST">
    <i class="fas fa-database"></i>
    <h1>DB Admin</h1>
    <p>Enter the admin password to continue</p>
    <?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?><div class="err">Incorrect password</div><?php endif; ?>
    <input type="password" name="dbadmin_pass" placeholder="Password" autofocus required>
    <button type="submit">Sign In</button>
</form>
</body>
</html>
<?php exit; } ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>DB Admin – Load Monitor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
:root {
    --bg: #0f1923; --surface: #1a2634; --surface2: #223044; --border: #2a3a4e;
    --text: #e2e8f0; --dim: #8899aa; --accent: #3b82f6; --accent2: #2563eb;
    --green: #22c55e; --yellow: #f59e0b; --red: #ef4444; --orange: #f97316;
    --radius: 8px; --sidebar-w: 260px; --header-h: 54px;
}
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Segoe UI',-apple-system,sans-serif;background:var(--bg);color:var(--text);height:100vh;overflow:hidden}
a{color:var(--accent);text-decoration:none}button{cursor:pointer;font-family:inherit}
::-webkit-scrollbar{width:5px;height:5px}::-webkit-scrollbar-track{background:transparent}::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px}

/* Layout */
.layout{display:grid;grid-template-columns:var(--sidebar-w) 1fr;grid-template-rows:var(--header-h) 1fr;height:100vh}

/* Header */
.header{grid-column:1/-1;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 18px;z-index:10}
.header-l{display:flex;align-items:center;gap:12px}
.header-l i{font-size:18px;color:var(--orange)}
.header-l h1{font-size:15px;font-weight:600}
.header-l .tag{font-size:10px;padding:2px 8px;border-radius:10px;background:rgba(249,115,22,.15);color:var(--orange);font-weight:700;letter-spacing:.5px;text-transform:uppercase}
.header-r{display:flex;align-items:center;gap:10px}
.badge{display:inline-flex;align-items:center;gap:5px;padding:4px 10px;border-radius:14px;font-size:11px;background:var(--surface2);border:1px solid var(--border)}
.lnk{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:var(--radius);font-size:12px;background:var(--surface2);color:var(--dim);border:1px solid var(--border);transition:.15s}
.lnk:hover{color:var(--text);border-color:var(--accent)}

/* Sidebar */
.sidebar{background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;overflow:hidden}
.sb-head{padding:12px 14px 8px;border-bottom:1px solid var(--border)}
.sb-head h2{font-size:11px;text-transform:uppercase;letter-spacing:1px;color:var(--dim);margin-bottom:8px}
.sb-search{width:100%;padding:7px 10px;border-radius:var(--radius);background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:12px;outline:none}
.sb-search:focus{border-color:var(--accent)}
.tbl-list{flex:1;overflow-y:auto;padding:4px 0}
.tbl-item{display:flex;align-items:center;justify-content:space-between;padding:8px 14px;cursor:pointer;border-left:3px solid transparent;transition:.12s}
.tbl-item:hover{background:var(--surface2)}
.tbl-item.active{background:rgba(59,130,246,.1);border-left-color:var(--accent)}
.tbl-item .nm{font-size:12px;font-weight:500;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.tbl-item .ct{font-size:10px;padding:2px 7px;border-radius:10px;background:var(--surface2);color:var(--dim);flex-shrink:0}
.tbl-item.active .ct{background:rgba(59,130,246,.2);color:var(--accent)}

/* Main */
.main{overflow:hidden;display:flex;flex-direction:column}

/* Tabs */
.tabs{display:flex;background:var(--surface);border-bottom:1px solid var(--border);padding:0 14px;flex-shrink:0}
.tab{padding:10px 18px;font-size:12px;font-weight:500;color:var(--dim);background:none;border:none;border-bottom:2px solid transparent;transition:.15s}
.tab:hover{color:var(--text)}.tab.active{color:var(--accent);border-bottom-color:var(--accent)}

/* Toolbar */
.toolbar{display:flex;align-items:center;gap:10px;padding:10px 14px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0;flex-wrap:wrap}
.toolbar input[type=text]{flex:1;min-width:160px;max-width:280px;padding:7px 10px;border-radius:var(--radius);background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:12px;outline:none}
.toolbar input:focus{border-color:var(--accent)}
.toolbar select{padding:7px 10px;border-radius:var(--radius);background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:12px;outline:none}
.btn{display:inline-flex;align-items:center;gap:5px;padding:7px 14px;border-radius:var(--radius);font-size:12px;border:1px solid var(--border);background:var(--surface2);color:var(--text);transition:.15s;white-space:nowrap}
.btn:hover{border-color:var(--accent);color:var(--accent)}
.btn-p{background:var(--accent);border-color:var(--accent);color:#fff}.btn-p:hover{background:var(--accent2)}
.btn-g{background:var(--green);border-color:var(--green);color:#fff}.btn-g:hover{opacity:.85}
.btn-r{background:var(--red);border-color:var(--red);color:#fff}.btn-r:hover{opacity:.85}
.btn-o{background:var(--orange);border-color:var(--orange);color:#fff}.btn-o:hover{opacity:.85}
.btn-sm{padding:4px 8px;font-size:11px}

/* Content */
.content{flex:1;overflow:auto}

/* Table */
.dtw{overflow:auto;height:100%}
table.dt{width:100%;border-collapse:collapse;font-size:12px}
table.dt thead{position:sticky;top:0;z-index:2}
table.dt th{background:var(--surface2);padding:8px 10px;text-align:left;font-weight:600;font-size:11px;text-transform:uppercase;letter-spacing:.4px;color:var(--dim);white-space:nowrap;border-bottom:2px solid var(--border);cursor:pointer;user-select:none}
table.dt th:hover{color:var(--text)}
table.dt th.act-col{width:90px;cursor:default}
table.dt td{padding:7px 10px;border-bottom:1px solid var(--border);max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
table.dt tr:hover td{background:rgba(59,130,246,.05)}
table.dt tr.editing td{background:rgba(249,115,22,.08)}
.null{color:var(--dim);font-style:italic;font-size:11px}
.sort-i{margin-left:3px;font-size:9px}

/* Inline edit input */
td input.cell-edit{width:100%;padding:4px 6px;border-radius:4px;background:var(--bg);border:1px solid var(--accent);color:var(--text);font-size:12px;font-family:inherit;outline:none}

/* Row actions */
.row-acts{display:flex;gap:4px}
.row-acts .btn-sm i{font-size:11px}

/* Pagination */
.pgn{display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--surface);border-top:1px solid var(--border);flex-shrink:0;font-size:12px}
.pgn .info{color:var(--dim)}
.pgn .pages{display:flex;gap:3px}
.pg-btn{padding:5px 10px;border-radius:var(--radius);background:var(--surface2);border:1px solid var(--border);color:var(--text);font-size:11px;cursor:pointer}
.pg-btn:hover{border-color:var(--accent)}.pg-btn.on{background:var(--accent);border-color:var(--accent);color:#fff}.pg-btn:disabled{opacity:.3;cursor:not-allowed}

/* Modal */
.modal-bg{position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:100;display:flex;align-items:center;justify-content:center;backdrop-filter:blur(3px)}
.modal{background:var(--surface);border:1px solid var(--border);border-radius:12px;width:90%;max-width:600px;max-height:85vh;overflow:auto;box-shadow:0 20px 60px rgba(0,0,0,.5)}
@media(max-width:768px){.modal{max-height:95vh;width:95%;max-width:none}}
.modal-head{display:flex;align-items:center;justify-content:space-between;padding:14px 18px;border-bottom:1px solid var(--border)}
.modal-head h3{font-size:14px;font-weight:600}
.modal-close{background:none;border:none;color:var(--dim);font-size:18px;padding:4px 8px;border-radius:4px}.modal-close:hover{color:var(--text);background:var(--surface2)}
.modal-body{padding:18px}
.form-row{margin-bottom:14px}
.form-row label{display:block;font-size:11px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;color:var(--dim);margin-bottom:4px}
.form-row label .pk-badge{color:var(--yellow);margin-left:4px;font-size:10px}
.form-row label .type-badge{color:var(--accent);margin-left:6px;font-family:'Consolas',monospace;font-size:10px;text-transform:none;letter-spacing:0}
.form-row input,.form-row textarea{width:100%;padding:8px 10px;border-radius:var(--radius);background:var(--bg);border:1px solid var(--border);color:var(--text);font-size:13px;font-family:inherit;outline:none}
.form-row input:focus,.form-row textarea:focus{border-color:var(--accent)}
.form-row textarea{min-height:60px;resize:vertical}
.modal-foot{display:flex;align-items:center;justify-content:flex-end;gap:8px;padding:14px 18px;border-top:1px solid var(--border)}

/* SQL Console */
.sql-wrap{display:flex;flex-direction:column;height:100%}
.sql-ed{padding:14px;background:var(--surface);border-bottom:1px solid var(--border);flex-shrink:0}
.sql-ed textarea{width:100%;min-height:100px;padding:10px;border-radius:var(--radius);background:var(--bg);border:1px solid var(--border);color:var(--text);font-family:'Consolas',monospace;font-size:13px;resize:vertical;outline:none;line-height:1.5}
.sql-ed textarea:focus{border-color:var(--accent)}
.sql-bar{display:flex;align-items:center;gap:8px;margin-top:8px}
.sql-bar .tm{font-size:11px;color:var(--dim)}
.sql-res{flex:1;overflow:auto}

/* Welcome */
.welcome{display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:var(--dim);gap:10px}
.welcome i{font-size:42px;opacity:.25}.welcome p{font-size:13px}

/* Toast */
.toast-wrap{position:fixed;top:64px;right:16px;z-index:200;display:flex;flex-direction:column;gap:8px}
.toast{padding:10px 16px;border-radius:var(--radius);font-size:12px;color:#fff;box-shadow:0 4px 16px rgba(0,0,0,.4);animation:tIn .25s;display:flex;align-items:center;gap:8px}
.toast.ok{background:var(--green)}.toast.err{background:var(--red)}.toast.warn{background:var(--orange)}
@keyframes tIn{from{opacity:0;transform:translateY(-10px)}to{opacity:1;transform:translateY(0)}}

/* Loading */
.spinner{display:inline-block;width:14px;height:14px;border:2px solid var(--border);border-top-color:var(--accent);border-radius:50%;animation:spin .6s linear infinite}
@keyframes spin{to{transform:rotate(360deg)}}
.loading-ov{display:flex;align-items:center;justify-content:center;height:180px;gap:8px;color:var(--dim);font-size:13px}

/* Schema card */
.sch-grid{padding:18px;display:grid;gap:16px;grid-template-columns:1fr 1fr}
@media(max-width:1000px){.sch-grid{grid-template-columns:1fr}}
.sch-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);overflow:hidden}
.sch-card h3{padding:10px 14px;font-size:12px;font-weight:600;background:var(--surface2);border-bottom:1px solid var(--border);color:var(--dim);text-transform:uppercase;letter-spacing:.4px}
.sch-card table{width:100%;font-size:12px;border-collapse:collapse}
.sch-card td,.sch-card th{padding:7px 12px;text-align:left;border-bottom:1px solid var(--border)}
.sch-card th{font-weight:600;color:var(--dim);font-size:11px}
.sch-card .pk{color:var(--yellow);font-weight:700}
.sch-card .tp{color:var(--accent);font-family:'Consolas',monospace;font-size:11px}
.sch-card pre{padding:12px;font-size:11px;line-height:1.5;color:var(--dim);overflow-x:auto;font-family:'Consolas',monospace;background:var(--bg);margin:0}

/* Stats */
.st-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;padding:18px}
.st-card{background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);padding:18px;text-align:center}
.st-card i{font-size:24px;color:var(--accent);margin-bottom:6px}
.st-card .val{font-size:26px;font-weight:700;margin:2px 0}
.st-card .lbl{font-size:11px;color:var(--dim);text-transform:uppercase;letter-spacing:.4px}
</style>
</head>
<body>

<div class="toast-wrap" id="toasts"></div>

<div class="layout">
  <!-- Header -->
  <header class="header">
    <div class="header-l">
      <i class="fas fa-shield-halved"></i>
      <h1>Database Admin</h1>
      <span class="tag">Write Access</span>
    </div>
    <div class="header-r">
      <span class="badge" id="dbBadge"><i class="fas fa-database"></i> Loading…</span>
      <a class="lnk" href="../dbview/"><i class="fas fa-eye"></i> Read-Only View</a>
      <a class="lnk" href="../login.php"><i class="fas fa-arrow-left"></i> App</a>
      <a class="lnk" href="?logout=1" style="color:var(--red);border-color:var(--red)"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </header>

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sb-head">
      <h2><i class="fas fa-layer-group"></i> Tables</h2>
      <input type="text" class="sb-search" id="tblFilter" placeholder="Filter tables…">
    </div>
    <div class="tbl-list" id="tblList">
      <div class="loading-ov"><div class="spinner"></div> Loading…</div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <div class="tabs">
      <button class="tab active" data-t="data"><i class="fas fa-pen-to-square"></i> Edit Data</button>
      <button class="tab" data-t="schema"><i class="fas fa-sitemap"></i> Schema</button>
      <button class="tab" data-t="sql"><i class="fas fa-terminal"></i> SQL Console</button>
      <button class="tab" data-t="stats"><i class="fas fa-chart-pie"></i> Stats</button>
    </div>

    <!-- DATA TAB -->
    <div id="p-data" class="panel" style="display:flex;flex-direction:column;flex:1;overflow:hidden">
      <div class="toolbar" id="dtToolbar" style="display:none">
        <input type="text" id="dtSearch" placeholder="Search columns…">
        <select id="dtPerPage"><option value="25">25</option><option value="50" selected>50</option><option value="100">100</option><option value="200">200</option></select>
        <button class="btn btn-g" id="addRowBtn"><i class="fas fa-plus"></i> Add Row</button>
        <button class="btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
        <button class="btn" id="exportBtn"><i class="fas fa-file-csv"></i> Export</button>
      </div>
      <div class="content" id="dtContent">
        <div class="welcome"><i class="fas fa-hand-pointer"></i><p>Select a table to edit data</p></div>
      </div>
      <div class="pgn" id="dtPgn" style="display:none"></div>
    </div>

    <!-- SCHEMA TAB -->
    <div id="p-schema" class="panel" style="display:none;flex:1;overflow:auto">
      <div class="content" id="schContent"><div class="welcome"><i class="fas fa-sitemap"></i><p>Select a table to view schema</p></div></div>
    </div>

    <!-- SQL TAB -->
    <div id="p-sql" class="panel" style="display:none;flex:1;overflow:hidden">
      <div class="sql-wrap">
        <div class="sql-ed">
          <textarea id="sqlIn" placeholder="Enter any SQL statement... (Ctrl+Enter to run)"></textarea>
          <div class="sql-bar">
            <button class="btn btn-o" id="sqlRunBtn"><i class="fas fa-play"></i> Execute</button>
            <span class="tm" id="sqlTm"></span>
          </div>
        </div>
        <div class="sql-res" id="sqlRes">
          <div class="welcome" style="height:auto;padding:30px"><i class="fas fa-terminal"></i><p>Write SQL and press Execute or Ctrl+Enter</p></div>
        </div>
      </div>
    </div>

    <!-- STATS TAB -->
    <div id="p-stats" class="panel" style="display:none;flex:1;overflow:auto">
      <div id="stContent"><div class="loading-ov"><div class="spinner"></div> Loading…</div></div>
    </div>
  </div>
</div>

<script>
/* ═══════ GLOBALS ═══════ */
const API = 'api.php';
let curTable = null, curPage = 1, curSort = '', curDir = 'ASC';
let allTables = [], colInfo = [], pkCols = [];
let editingRowid = null;
let searchTm = null;

/* ═══════ HELPERS ═══════ */
async function api(action, params = {}, opts = {}) {
    const u = new URL(API, location.href);
    u.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) u.searchParams.set(k, v);
    const r = await fetch(u, opts);
    if (action === 'export') return r;
    return r.json();
}

function esc(s) {
    if (s === null || s === undefined) return '<span class="null">NULL</span>';
    const d = document.createElement('span'); d.textContent = String(s); return d.innerHTML;
}
function fmtBytes(b) { return b < 1024 ? b + ' B' : b < 1048576 ? (b/1024).toFixed(1)+' KB' : (b/1048576).toFixed(2)+' MB'; }

function toast(msg, type = 'ok') {
    const el = document.createElement('div');
    el.className = 'toast ' + type;
    el.innerHTML = `<i class="fas fa-${type === 'ok' ? 'check-circle' : type === 'err' ? 'times-circle' : 'exclamation-triangle'}"></i> ${esc(msg)}`;
    document.getElementById('toasts').appendChild(el);
    setTimeout(() => { el.style.opacity = '0'; setTimeout(() => el.remove(), 300); }, 3500);
}

/* ═══════ SIDEBAR ═══════ */
async function loadTables() {
    allTables = await api('tables');
    renderList();
    const st = await api('stats');
    document.getElementById('dbBadge').innerHTML = `<i class="fas fa-database"></i> ${st.db_path} · ${fmtBytes(st.db_size)} · SQLite ${st.sqlite_ver}`;
}

function renderList(filter = '') {
    const el = document.getElementById('tblList');
    const f = filter ? allTables.filter(t => t.name.toLowerCase().includes(filter.toLowerCase())) : allTables;
    if (!f.length) { el.innerHTML = '<div class="loading-ov" style="height:60px">No match</div>'; return; }
    el.innerHTML = f.map(t => `
        <div class="tbl-item ${t.name === curTable ? 'active':''}" data-t="${t.name}">
            <span class="nm"><i class="fas fa-table" style="margin-right:6px;font-size:10px;color:var(--dim)"></i>${esc(t.name)}</span>
            <span class="ct">${t.rows.toLocaleString()}</span>
        </div>`).join('');
    el.querySelectorAll('.tbl-item').forEach(e => e.addEventListener('click', () => selectTable(e.dataset.t)));
}

document.getElementById('tblFilter').addEventListener('input', e => renderList(e.target.value));

function selectTable(name) {
    curTable = name; curPage = 1; curSort = ''; curDir = 'ASC'; editingRowid = null;
    document.getElementById('dtSearch').value = '';
    renderList(document.getElementById('tblFilter').value);
    loadData();
    loadSchema();
}

/* ═══════ TABS ═══════ */
document.querySelectorAll('.tab').forEach(t => {
    t.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(x => x.classList.remove('active'));
        t.classList.add('active');
        document.querySelectorAll('.panel').forEach(p => p.style.display = 'none');
        const p = document.getElementById('p-' + t.dataset.t);
        p.style.display = ['data','sql'].includes(t.dataset.t) ? 'flex' : 'block';
        if (t.dataset.t === 'stats') loadStats();
    });
});

/* ═══════ DATA TAB ═══════ */
async function loadData() {
    if (!curTable) return;
    document.getElementById('dtToolbar').style.display = 'flex';
    const content = document.getElementById('dtContent');
    content.innerHTML = '<div class="loading-ov"><div class="spinner"></div> Loading…</div>';

    try {
        const d = await api('data', {
            table: curTable, page: curPage, per_page: document.getElementById('dtPerPage').value,
            search: document.getElementById('dtSearch').value, sort: curSort, dir: curDir
        });

        colInfo = d.col_info; pkCols = d.pk_cols;

        if (!d.rows.length) {
            content.innerHTML = '<div class="welcome" style="height:160px"><i class="fas fa-inbox"></i><p>No rows found</p></div>';
            document.getElementById('dtPgn').style.display = 'none';
            return;
        }

        let h = '<div class="dtw"><table class="dt"><thead><tr>';
        h += '<th class="act-col">Actions</th>';
        for (const c of d.columns) {
            const is = c === curSort;
            const ico = is ? (curDir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
            const pk = pkCols.includes(c) ? ' ★' : '';
            h += `<th data-c="${esc(c)}">${esc(c)}${pk} <i class="fas ${ico} sort-i"></i></th>`;
        }
        h += '</tr></thead><tbody>';

        for (const row of d.rows) {
            const rid = row.__rowid;
            h += `<tr data-rid="${rid}" id="row-${rid}">`;
            h += `<td class="row-acts">
                <button class="btn btn-sm btn-p" onclick="startEdit(${rid})" title="Edit"><i class="fas fa-pen"></i></button>
                <button class="btn btn-sm btn-r" onclick="deleteRow(${rid})" title="Delete"><i class="fas fa-trash"></i></button>
            </td>`;
            for (const c of d.columns) {
                const v = row[c];
                h += `<td data-col="${esc(c)}" data-val="${v !== null ? esc(String(v)) : ''}" title="${v !== null ? esc(String(v)) : 'NULL'}">${esc(v)}</td>`;
            }
            h += '</tr>';
        }
        h += '</tbody></table></div>';
        content.innerHTML = h;

        // Sort click
        content.querySelectorAll('th[data-c]').forEach(th => {
            th.addEventListener('click', () => {
                const c = th.dataset.c;
                if (curSort === c) curDir = curDir === 'ASC' ? 'DESC' : 'ASC';
                else { curSort = c; curDir = 'ASC'; }
                loadData();
            });
        });

        renderPgn(d);
    } catch (e) {
        content.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--red)"></i><p>${esc(e.message)}</p></div>`;
    }
}

/* ── Inline Edit ────────────────────────────────────────────────────────── */
function startEdit(rid) {
    // Cancel any existing edit
    if (editingRowid !== null) cancelEdit();
    editingRowid = rid;

    const tr = document.getElementById('row-' + rid);
    if (!tr) return;
    tr.classList.add('editing');

    // Replace actions
    tr.querySelector('.row-acts').innerHTML = `
        <button class="btn btn-sm btn-g" onclick="saveEdit(${rid})" title="Save"><i class="fas fa-check"></i></button>
        <button class="btn btn-sm" onclick="cancelEdit()" title="Cancel"><i class="fas fa-times"></i></button>`;

    // Make cells editable
    tr.querySelectorAll('td[data-col]').forEach(td => {
        const val = td.dataset.val;
        const col = td.dataset.col;
        td.innerHTML = `<input class="cell-edit" type="text" value="${esc(val)}" data-col="${esc(col)}" placeholder="NULL">`;
    });

    // Focus first input
    const first = tr.querySelector('input.cell-edit');
    if (first) first.focus();
}

function cancelEdit() {
    if (editingRowid === null) return;
    editingRowid = null;
    loadData(); // simplest: reload
}

async function saveEdit(rid) {
    const tr = document.getElementById('row-' + rid);
    if (!tr) return;

    const row = {};
    tr.querySelectorAll('input.cell-edit').forEach(inp => {
        row[inp.dataset.col] = inp.value;
    });

    try {
        const res = await api('update', {}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: curTable, row, rowid: rid })
        });
        if (res.error) { toast(res.error, 'err'); return; }
        toast(res.message);
        editingRowid = null;
        loadData();
        refreshTableCount();
    } catch (e) { toast(e.message, 'err'); }
}

/* ── Delete ─────────────────────────────────────────────────────────────── */
async function deleteRow(rid) {
    if (!confirm('Delete this row? This cannot be undone.')) return;
    try {
        const res = await api('delete', {}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: curTable, rowid: rid })
        });
        if (res.error) { toast(res.error, 'err'); return; }
        toast(res.message);
        loadData();
        refreshTableCount();
    } catch (e) { toast(e.message, 'err'); }
}

/* ── Add Row Modal ──────────────────────────────────────────────────────── */
document.getElementById('addRowBtn').addEventListener('click', () => {
    if (!curTable || !colInfo.length) return;
    showAddModal();
});

function showAddModal() {
    const bg = document.createElement('div');
    bg.className = 'modal-bg';
    bg.id = 'addModal';

    let fields = '';
    for (const col of colInfo) {
        const pk = col.pk ? '<span class="pk-badge">PK</span>' : '';
        const tp = col.type ? `<span class="type-badge">${col.type}</span>` : '';
        const nn = col.notnull ? ' required' : '';
        const def = col.dflt_value !== null ? col.dflt_value.replace(/^'|'$/g, '') : '';
        fields += `<div class="form-row">
            <label>${esc(col.name)}${pk}${tp}</label>
            <input type="text" name="${esc(col.name)}" placeholder="${col.notnull ? 'Required' : 'NULL if empty'}" value="${esc(def)}"${nn}>
        </div>`;
    }

    bg.innerHTML = `<div class="modal">
        <div class="modal-head"><h3><i class="fas fa-plus" style="color:var(--green);margin-right:8px"></i>Insert Row into ${esc(curTable)}</h3><button class="modal-close" onclick="closeModal()">&times;</button></div>
        <div class="modal-body" id="addFields">${fields}</div>
        <div class="modal-foot">
            <button class="btn" onclick="closeModal()">Cancel</button>
            <button class="btn btn-g" onclick="submitAdd()"><i class="fas fa-plus"></i> Insert</button>
        </div>
    </div>`;

    document.body.appendChild(bg);
    bg.addEventListener('click', e => { if (e.target === bg) closeModal(); });
    bg.querySelector('input')?.focus();
}

function closeModal() {
    document.getElementById('addModal')?.remove();
}

async function submitAdd() {
    const row = {};
    document.querySelectorAll('#addFields input').forEach(inp => {
        if (inp.value !== '') row[inp.name] = inp.value;
    });

    try {
        const res = await api('insert', {}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ table: curTable, row })
        });
        if (res.error) { toast(res.error, 'err'); return; }
        toast(res.message);
        closeModal();
        loadData();
        refreshTableCount();
    } catch (e) { toast(e.message, 'err'); }
}

/* ── Helpers ────────────────────────────────────────────────────────────── */
async function refreshTableCount() {
    allTables = await api('tables');
    renderList(document.getElementById('tblFilter').value);
}

function renderPgn(d) {
    const el = document.getElementById('dtPgn');
    if (d.pages <= 1) { el.style.display = 'none'; return; }
    el.style.display = 'flex';
    const s = (d.page - 1) * d.per_page + 1, e = Math.min(d.page * d.per_page, d.total);
    let ph = `<button class="pg-btn" data-p="${d.page-1}" ${d.page<=1?'disabled':''}><i class="fas fa-chevron-left"></i></button>`;
    let pgs = [];
    if (d.pages <= 7) pgs = Array.from({length:d.pages},(_,i)=>i+1);
    else {
        pgs = [1]; let a=Math.max(2,d.page-2), b=Math.min(d.pages-1,d.page+2);
        if (a>2) pgs.push('…'); for(let i=a;i<=b;i++) pgs.push(i); if(b<d.pages-1) pgs.push('…'); pgs.push(d.pages);
    }
    for (const p of pgs) ph += p==='…' ? '<span style="padding:4px;color:var(--dim)">…</span>' : `<button class="pg-btn ${p===d.page?'on':''}" data-p="${p}">${p}</button>`;
    ph += `<button class="pg-btn" data-p="${d.page+1}" ${d.page>=d.pages?'disabled':''}><i class="fas fa-chevron-right"></i></button>`;
    el.innerHTML = `<span class="info">${s.toLocaleString()} – ${e.toLocaleString()} of ${d.total.toLocaleString()}</span><div class="pages">${ph}</div>`;
    el.querySelectorAll('.pg-btn:not(:disabled)').forEach(b => b.addEventListener('click', () => { curPage = parseInt(b.dataset.p); loadData(); }));
}

document.getElementById('dtSearch').addEventListener('input', () => { clearTimeout(searchTm); searchTm = setTimeout(() => { curPage=1; loadData(); }, 350); });
document.getElementById('dtPerPage').addEventListener('change', () => { curPage=1; loadData(); });
document.getElementById('refreshBtn').addEventListener('click', loadData);
document.getElementById('exportBtn').addEventListener('click', () => { if(curTable) window.open(`${API}?action=export&table=${encodeURIComponent(curTable)}`); });

/* ═══════ SCHEMA TAB ═══════ */
async function loadSchema() {
    if (!curTable) return;
    const el = document.getElementById('schContent');
    el.innerHTML = '<div class="loading-ov"><div class="spinner"></div> Loading…</div>';
    try {
        const s = await api('schema', { table: curTable });
        let h = '<div class="sch-grid">';
        h += '<div class="sch-card"><h3><i class="fas fa-columns" style="margin-right:6px"></i>Columns</h3><table><thead><tr><th>#</th><th>Name</th><th>Type</th><th>Null</th><th>Default</th><th>PK</th></tr></thead><tbody>';
        for (const c of s.columns) {
            h += `<tr><td>${c.cid}</td><td><strong>${esc(c.name)}</strong></td><td class="tp">${esc(c.type)||'<span class="null">any</span>'}</td>
                <td>${c.notnull?'<span style="color:var(--red)">NOT NULL</span>':'<span style="color:var(--green)">YES</span>'}</td>
                <td>${c.dflt_value!==null?esc(c.dflt_value):'<span class="null">none</span>'}</td>
                <td>${c.pk?'<span class="pk">PK ★</span>':''}</td></tr>`;
        }
        h += '</tbody></table></div>';
        if (s.foreign_keys.length) {
            h += '<div class="sch-card"><h3><i class="fas fa-link" style="margin-right:6px"></i>Foreign Keys</h3><table><thead><tr><th>From</th><th>→ Table</th><th>→ Column</th></tr></thead><tbody>';
            for (const fk of s.foreign_keys) h += `<tr><td>${esc(fk.from)}</td><td>${esc(fk.table)}</td><td>${esc(fk.to)}</td></tr>`;
            h += '</tbody></table></div>';
        }
        h += `<div class="sch-card" style="grid-column:1/-1"><h3><i class="fas fa-code" style="margin-right:6px"></i>CREATE Statement</h3><pre>${esc(s.create_sql)}</pre></div>`;
        h += '</div>';
        el.innerHTML = h;
    } catch(e) { el.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--red)"></i><p>${esc(e.message)}</p></div>`; }
}

/* ═══════ SQL CONSOLE ═══════ */
async function runSQL() {
    const sql = document.getElementById('sqlIn').value.trim();
    if (!sql) return;
    const res = document.getElementById('sqlRes');
    const tm = document.getElementById('sqlTm');
    res.innerHTML = '<div class="loading-ov"><div class="spinner"></div> Executing…</div>';
    tm.textContent = '';
    try {
        const d = await api('sql', {}, {
            method: 'POST', headers: {'Content-Type':'application/json'}, body: JSON.stringify({sql})
        });
        if (d.error) { res.innerHTML = `<div class="welcome" style="height:auto;padding:24px"><i class="fas fa-exclamation-circle" style="color:var(--red);font-size:22px"></i><p style="color:var(--red)">${esc(d.error)}</p></div>`; return; }
        if (d.type === 'exec') {
            tm.innerHTML = `<i class="fas fa-clock"></i> ${d.time_ms}ms`;
            res.innerHTML = `<div class="welcome" style="height:auto;padding:24px"><i class="fas fa-check-circle" style="color:var(--green);font-size:22px"></i><p>${esc(d.message)}</p></div>`;
            if (curTable) loadData();
            refreshTableCount();
            return;
        }
        tm.innerHTML = `<i class="fas fa-clock"></i> ${d.time_ms}ms · ${d.total} row${d.total!==1?'s':''}${d.truncated?' (first 1000)':''}`;
        if (!d.rows.length) { res.innerHTML = '<div class="welcome" style="height:auto;padding:24px"><i class="fas fa-check-circle" style="color:var(--green);font-size:22px"></i><p>0 rows returned</p></div>'; return; }
        let h = '<div class="dtw"><table class="dt"><thead><tr>';
        for (const c of d.columns) h += `<th>${esc(c)}</th>`;
        h += '</tr></thead><tbody>';
        for (const r of d.rows) { h += '<tr>'; for (const c of d.columns) h += `<td>${esc(r[c])}</td>`; h += '</tr>'; }
        h += '</tbody></table></div>';
        res.innerHTML = h;
    } catch(e) { res.innerHTML = `<div class="welcome" style="height:auto;padding:24px"><i class="fas fa-bomb" style="color:var(--red);font-size:22px"></i><p>${esc(e.message)}</p></div>`; }
}
document.getElementById('sqlRunBtn').addEventListener('click', runSQL);
document.getElementById('sqlIn').addEventListener('keydown', e => { if(e.ctrlKey && e.key==='Enter'){e.preventDefault();runSQL();} });

/* ═══════ STATS TAB ═══════ */
async function loadStats() {
    const el = document.getElementById('stContent');
    try {
        const [st, tb] = await Promise.all([api('stats'), api('tables')]);
        let h = '<div class="st-grid">';
        [{i:'fa-database',v:fmtBytes(st.db_size),l:'DB Size'},{i:'fa-table',v:st.tables,l:'Tables'},{i:'fa-eye',v:st.views,l:'Views'},
         {i:'fa-bolt',v:st.triggers,l:'Triggers'},{i:'fa-list',v:st.indexes,l:'Indexes'},{i:'fa-bars',v:st.total_rows.toLocaleString(),l:'Total Rows'},
         {i:'fa-code-branch',v:st.sqlite_ver,l:'SQLite'}].forEach(c => {
            h += `<div class="st-card"><i class="fas ${c.i}"></i><div class="val">${c.v}</div><div class="lbl">${c.l}</div></div>`;
        });
        h += '</div>';
        const sorted = [...tb].sort((a,b) => b.rows-a.rows).slice(0,10);
        h += '<div style="padding:0 18px 18px"><div class="sch-card"><h3><i class="fas fa-trophy" style="margin-right:6px"></i>Top Tables</h3><table><thead><tr><th>Table</th><th style="text-align:right">Rows</th></tr></thead><tbody>';
        for (const t of sorted) {
            const pct = Math.max(2, (t.rows/Math.max(1,sorted[0].rows))*100);
            h += `<tr style="cursor:pointer" onclick="selectTable('${t.name}');document.querySelector('.tab').click()">
                <td>${esc(t.name)}</td>
                <td style="text-align:right"><div style="display:flex;align-items:center;justify-content:flex-end;gap:8px">
                    <div style="width:100px;height:5px;background:var(--surface2);border-radius:3px;overflow:hidden"><div style="width:${pct}%;height:100%;background:var(--orange);border-radius:3px"></div></div>
                    <span>${t.rows.toLocaleString()}</span></div></td></tr>`;
        }
        h += '</tbody></table></div></div>';
        el.innerHTML = h;
    } catch(e) { el.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--red)"></i><p>${esc(e.message)}</p></div>`; }
}

/* ═══════ KEYBOARD SHORTCUTS ═══════ */
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { if (editingRowid !== null) cancelEdit(); closeModal(); }
});

/* ═══════ INIT ═══════ */
loadTables();
</script>
</body>
</html>
