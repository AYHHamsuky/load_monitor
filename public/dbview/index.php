<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Database Viewer – Load Monitor</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ═══════════════════════════════════════════════════════════════════════════
   VARIABLES & RESET
   ═══════════════════════════════════════════════════════════════════════════ */
:root {
    --bg: #0f1923;
    --surface: #1a2634;
    --surface2: #223044;
    --border: #2a3a4e;
    --text: #e2e8f0;
    --text-dim: #8899aa;
    --accent: #3b82f6;
    --accent-hover: #2563eb;
    --success: #22c55e;
    --warning: #f59e0b;
    --danger: #ef4444;
    --radius: 8px;
    --shadow: 0 2px 12px rgba(0,0,0,.35);
    --sidebar-w: 280px;
    --header-h: 56px;
}
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
body {
    font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif;
    background: var(--bg);
    color: var(--text);
    height: 100vh;
    overflow: hidden;
}
a { color: var(--accent); text-decoration: none; }
button { cursor: pointer; font-family: inherit; }
::-webkit-scrollbar { width: 6px; height: 6px; }
::-webkit-scrollbar-track { background: transparent; }
::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }

/* ═══════════════════════════════════════════════════════════════════════════
   LAYOUT
   ═══════════════════════════════════════════════════════════════════════════ */
.layout {
    display: grid;
    grid-template-columns: var(--sidebar-w) 1fr;
    grid-template-rows: var(--header-h) 1fr;
    height: 100vh;
}

/* ── Header ─────────────────────────────────────────────────────────────── */
.header {
    grid-column: 1 / -1;
    background: var(--surface);
    border-bottom: 1px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    z-index: 10;
}
.header-left { display: flex; align-items: center; gap: 14px; }
.header-left i { font-size: 20px; color: var(--accent); }
.header-left h1 { font-size: 16px; font-weight: 600; letter-spacing: .3px; }
.header-right { display: flex; align-items: center; gap: 12px; }
.header-right .badge {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 4px 10px; border-radius: 20px; font-size: 12px;
    background: var(--surface2); border: 1px solid var(--border);
}
.header-right .badge i { font-size: 11px; }
.back-btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 6px 14px; border-radius: var(--radius); font-size: 13px;
    background: var(--surface2); color: var(--text-dim); border: 1px solid var(--border);
    transition: all .15s;
}
.back-btn:hover { color: var(--text); border-color: var(--accent); }

/* ── Sidebar ────────────────────────────────────────────────────────────── */
.sidebar {
    background: var(--surface);
    border-right: 1px solid var(--border);
    display: flex; flex-direction: column;
    overflow: hidden;
}
.sidebar-header {
    padding: 14px 16px 10px;
    border-bottom: 1px solid var(--border);
}
.sidebar-header h2 { font-size: 13px; text-transform: uppercase; letter-spacing: 1px; color: var(--text-dim); margin-bottom: 10px; }
.sidebar-search {
    width: 100%; padding: 8px 12px; border-radius: var(--radius);
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); font-size: 13px; outline: none;
    transition: border-color .15s;
}
.sidebar-search:focus { border-color: var(--accent); }
.sidebar-search::placeholder { color: var(--text-dim); }

.table-list {
    flex: 1; overflow-y: auto; padding: 6px 0;
}
.table-item {
    display: flex; align-items: center; justify-content: space-between;
    padding: 9px 16px; cursor: pointer; transition: background .12s;
    border-left: 3px solid transparent;
}
.table-item:hover { background: var(--surface2); }
.table-item.active {
    background: rgba(59,130,246,.12);
    border-left-color: var(--accent);
}
.table-item .name {
    font-size: 13px; font-weight: 500;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
}
.table-item .count {
    font-size: 11px; padding: 2px 8px; border-radius: 10px;
    background: var(--surface2); color: var(--text-dim); flex-shrink: 0;
}
.table-item.active .count { background: rgba(59,130,246,.2); color: var(--accent); }

/* ── Main Content ───────────────────────────────────────────────────────── */
.main { overflow: hidden; display: flex; flex-direction: column; }

/* Tabs */
.tab-bar {
    display: flex; gap: 0; background: var(--surface);
    border-bottom: 1px solid var(--border); padding: 0 16px; flex-shrink: 0;
}
.tab {
    padding: 12px 20px; font-size: 13px; font-weight: 500;
    color: var(--text-dim); background: none; border: none;
    border-bottom: 2px solid transparent; transition: all .15s;
}
.tab:hover { color: var(--text); }
.tab.active { color: var(--accent); border-bottom-color: var(--accent); }

/* Toolbar */
.toolbar {
    display: flex; align-items: center; gap: 12px;
    padding: 12px 16px; background: var(--surface);
    border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.toolbar input[type="text"] {
    flex: 1; max-width: 320px; padding: 8px 12px; border-radius: var(--radius);
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); font-size: 13px; outline: none;
}
.toolbar input:focus { border-color: var(--accent); }
.toolbar input::placeholder { color: var(--text-dim); }
.toolbar select {
    padding: 8px 12px; border-radius: var(--radius);
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); font-size: 13px; outline: none;
}
.btn {
    display: inline-flex; align-items: center; gap: 6px;
    padding: 8px 16px; border-radius: var(--radius); font-size: 13px;
    border: 1px solid var(--border); background: var(--surface2);
    color: var(--text); transition: all .15s;
}
.btn:hover { border-color: var(--accent); color: var(--accent); }
.btn-primary { background: var(--accent); border-color: var(--accent); color: #fff; }
.btn-primary:hover { background: var(--accent-hover); }
.btn-success { background: var(--success); border-color: var(--success); color: #fff; }
.btn-success:hover { opacity: .9; }

/* Content area */
.content { flex: 1; overflow: auto; padding: 0; }

/* ── Data Table ─────────────────────────────────────────────────────────── */
.data-table-wrap { overflow: auto; height: 100%; }
table.data-table {
    width: 100%; border-collapse: collapse; font-size: 13px;
}
table.data-table thead { position: sticky; top: 0; z-index: 2; }
table.data-table th {
    background: var(--surface2); padding: 10px 14px; text-align: left;
    font-weight: 600; font-size: 12px; text-transform: uppercase;
    letter-spacing: .5px; color: var(--text-dim); white-space: nowrap;
    border-bottom: 2px solid var(--border); cursor: pointer; user-select: none;
}
table.data-table th:hover { color: var(--text); }
table.data-table th .sort-icon { margin-left: 4px; font-size: 10px; }
table.data-table td {
    padding: 9px 14px; border-bottom: 1px solid var(--border);
    max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}
table.data-table tr:hover td { background: rgba(59,130,246,.06); }
.null-val { color: var(--text-dim); font-style: italic; font-size: 12px; }

/* Pagination */
.pagination {
    display: flex; align-items: center; justify-content: space-between;
    padding: 12px 16px; background: var(--surface);
    border-top: 1px solid var(--border); flex-shrink: 0; font-size: 13px;
}
.pagination .info { color: var(--text-dim); }
.pagination .pages { display: flex; gap: 4px; }
.pagination .page-btn {
    padding: 6px 12px; border-radius: var(--radius);
    background: var(--surface2); border: 1px solid var(--border);
    color: var(--text); font-size: 12px; cursor: pointer;
}
.pagination .page-btn:hover { border-color: var(--accent); }
.pagination .page-btn.active { background: var(--accent); border-color: var(--accent); color: #fff; }
.pagination .page-btn:disabled { opacity: .4; cursor: not-allowed; }

/* ── Schema View ────────────────────────────────────────────────────────── */
.schema-grid { padding: 20px; display: grid; gap: 20px; grid-template-columns: 1fr 1fr; }
@media (max-width: 1100px) { .schema-grid { grid-template-columns: 1fr; } }
.schema-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); overflow: hidden;
}
.schema-card h3 {
    padding: 12px 16px; font-size: 13px; font-weight: 600;
    background: var(--surface2); border-bottom: 1px solid var(--border);
    color: var(--text-dim); text-transform: uppercase; letter-spacing: .5px;
}
.schema-card table { width: 100%; font-size: 13px; border-collapse: collapse; }
.schema-card td, .schema-card th {
    padding: 8px 14px; text-align: left; border-bottom: 1px solid var(--border);
}
.schema-card th { font-weight: 600; color: var(--text-dim); font-size: 12px; }
.schema-card .pk { color: var(--warning); font-weight: 700; }
.schema-card .type { color: var(--accent); font-family: 'Consolas', monospace; font-size: 12px; }
.schema-card pre {
    padding: 14px 16px; font-size: 12px; line-height: 1.6;
    color: var(--text-dim); overflow-x: auto; font-family: 'Consolas', monospace;
    background: var(--bg); margin: 0;
}

/* ── SQL Console ────────────────────────────────────────────────────────── */
.sql-console { display: flex; flex-direction: column; height: 100%; }
.sql-editor {
    padding: 14px 16px; background: var(--surface);
    border-bottom: 1px solid var(--border); flex-shrink: 0;
}
.sql-editor textarea {
    width: 100%; min-height: 90px; padding: 12px; border-radius: var(--radius);
    background: var(--bg); border: 1px solid var(--border);
    color: var(--text); font-family: 'Consolas', monospace; font-size: 13px;
    resize: vertical; outline: none; line-height: 1.5;
}
.sql-editor textarea:focus { border-color: var(--accent); }
.sql-bar { display: flex; align-items: center; gap: 10px; margin-top: 10px; }
.sql-bar .time { font-size: 12px; color: var(--text-dim); }
.sql-results { flex: 1; overflow: auto; }

/* ── Stats Dashboard ────────────────────────────────────────────────────── */
.stats-grid {
    display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 16px; padding: 20px;
}
.stat-card {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: var(--radius); padding: 20px; text-align: center;
}
.stat-card i { font-size: 28px; color: var(--accent); margin-bottom: 8px; }
.stat-card .value { font-size: 28px; font-weight: 700; margin: 4px 0; }
.stat-card .label { font-size: 12px; color: var(--text-dim); text-transform: uppercase; letter-spacing: .5px; }

/* ── Welcome Screen ─────────────────────────────────────────────────────── */
.welcome {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    height: 100%; color: var(--text-dim); gap: 12px;
}
.welcome i { font-size: 48px; opacity: .3; }
.welcome p { font-size: 14px; }

/* ── Loading ────────────────────────────────────────────────────────────── */
.spinner { display: inline-block; width: 16px; height: 16px; border: 2px solid var(--border); border-top-color: var(--accent); border-radius: 50%; animation: spin .6s linear infinite; }
@keyframes spin { to { transform: rotate(360deg); } }
.loading-overlay {
    display: flex; align-items: center; justify-content: center;
    height: 200px; gap: 10px; color: var(--text-dim); font-size: 14px;
}
</style>
</head>
<body>

<div class="layout">
    <!-- ═══════ Header ═══════ -->
    <header class="header">
        <div class="header-left">
            <i class="fas fa-database"></i>
            <h1>Database Viewer</h1>
        </div>
        <div class="header-right">
            <span class="badge" id="dbInfo"><i class="fas fa-circle-info"></i> Loading…</span>
            <a class="back-btn" href="../login.php"><i class="fas fa-arrow-left"></i> Back to App</a>
        </div>
    </header>

    <!-- ═══════ Sidebar ═══════ -->
    <aside class="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-layer-group"></i> Tables</h2>
            <input type="text" class="sidebar-search" id="tableSearch" placeholder="Filter tables…">
        </div>
        <div class="table-list" id="tableList">
            <div class="loading-overlay"><div class="spinner"></div> Loading tables…</div>
        </div>
    </aside>

    <!-- ═══════ Main ═══════ -->
    <div class="main">
        <!-- Tab bar -->
        <div class="tab-bar">
            <button class="tab active" data-tab="data"><i class="fas fa-table"></i> Data</button>
            <button class="tab" data-tab="schema"><i class="fas fa-sitemap"></i> Schema</button>
            <button class="tab" data-tab="sql"><i class="fas fa-terminal"></i> SQL Console</button>
            <button class="tab" data-tab="stats"><i class="fas fa-chart-pie"></i> Stats</button>
        </div>

        <!-- ─── Data Tab ─────────────────────────────────────────────────── -->
        <div id="panel-data" class="panel" style="display:flex; flex-direction:column; flex:1; overflow:hidden;">
            <div class="toolbar" id="dataToolbar" style="display:none;">
                <input type="text" id="dataSearch" placeholder="Search all columns…">
                <select id="perPage">
                    <option value="25">25 rows</option>
                    <option value="50" selected>50 rows</option>
                    <option value="100">100 rows</option>
                    <option value="200">200 rows</option>
                </select>
                <button class="btn" id="refreshBtn"><i class="fas fa-sync-alt"></i> Refresh</button>
                <button class="btn btn-success" id="exportBtn"><i class="fas fa-file-csv"></i> Export CSV</button>
            </div>
            <div class="content" id="dataContent">
                <div class="welcome">
                    <i class="fas fa-hand-pointer"></i>
                    <p>Select a table from the sidebar to view its data</p>
                </div>
            </div>
            <div class="pagination" id="dataPagination" style="display:none;"></div>
        </div>

        <!-- ─── Schema Tab ───────────────────────────────────────────────── -->
        <div id="panel-schema" class="panel" style="display:none; flex:1; overflow:auto;">
            <div class="content" id="schemaContent">
                <div class="welcome">
                    <i class="fas fa-sitemap"></i>
                    <p>Select a table to view its schema</p>
                </div>
            </div>
        </div>

        <!-- ─── SQL Tab ──────────────────────────────────────────────────── -->
        <div id="panel-sql" class="panel" style="display:none; flex:1; overflow:hidden;">
            <div class="sql-console">
                <div class="sql-editor">
                    <textarea id="sqlInput" placeholder="SELECT * FROM staff_details LIMIT 20;"></textarea>
                    <div class="sql-bar">
                        <button class="btn btn-primary" id="sqlRun"><i class="fas fa-play"></i> Run Query</button>
                        <span class="time" id="sqlTime"></span>
                    </div>
                </div>
                <div class="sql-results" id="sqlResults">
                    <div class="welcome" style="height:auto; padding:40px;">
                        <i class="fas fa-terminal"></i>
                        <p>Write a SELECT query and press Run (or Ctrl+Enter)</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- ─── Stats Tab ────────────────────────────────────────────────── -->
        <div id="panel-stats" class="panel" style="display:none; flex:1; overflow:auto;">
            <div id="statsContent" class="content">
                <div class="loading-overlay"><div class="spinner"></div> Loading stats…</div>
            </div>
        </div>
    </div>
</div>

<script>
/* ═══════════════════════════════════════════════════════════════════════════
   APP STATE & HELPERS
   ═══════════════════════════════════════════════════════════════════════════ */
const API = 'api.php';
let currentTable = null;
let currentPage = 1;
let currentSort = '';
let currentDir = 'ASC';
let searchTimer = null;

async function api(action, params = {}, opts = {}) {
    const url = new URL(API, location.href);
    url.searchParams.set('action', action);
    for (const [k, v] of Object.entries(params)) url.searchParams.set(k, v);
    const resp = await fetch(url, opts);
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    if (opts.raw) return resp;
    return resp.json();
}

function esc(str) {
    if (str === null || str === undefined) return '<span class="null-val">NULL</span>';
    const s = String(str);
    const d = document.createElement('span');
    d.textContent = s;
    return d.innerHTML;
}

function formatBytes(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1048576) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / 1048576).toFixed(2) + ' MB';
}

/* ═══════════════════════════════════════════════════════════════════════════
   SIDEBAR – TABLE LIST
   ═══════════════════════════════════════════════════════════════════════════ */
let allTables = [];

async function loadTables() {
    allTables = await api('tables');
    renderTableList();
    // Update header badge
    const stats = await api('stats');
    document.getElementById('dbInfo').innerHTML =
        `<i class="fas fa-database"></i> ${stats.db_path} &nbsp;·&nbsp; ${formatBytes(stats.db_size)} &nbsp;·&nbsp; SQLite ${stats.sqlite_version}`;
}

function renderTableList(filter = '') {
    const list = document.getElementById('tableList');
    const filtered = filter
        ? allTables.filter(t => t.name.toLowerCase().includes(filter.toLowerCase()))
        : allTables;

    if (filtered.length === 0) {
        list.innerHTML = '<div class="loading-overlay" style="height:80px;">No tables match filter</div>';
        return;
    }
    list.innerHTML = filtered.map(t => `
        <div class="table-item ${t.name === currentTable ? 'active' : ''}" data-table="${t.name}">
            <span class="name"><i class="fas fa-table" style="margin-right:8px;font-size:11px;color:var(--text-dim)"></i>${esc(t.name)}</span>
            <span class="count">${t.rows.toLocaleString()}</span>
        </div>
    `).join('');

    list.querySelectorAll('.table-item').forEach(el => {
        el.addEventListener('click', () => selectTable(el.dataset.table));
    });
}

document.getElementById('tableSearch').addEventListener('input', e => {
    renderTableList(e.target.value);
});

function selectTable(name) {
    currentTable = name;
    currentPage = 1;
    currentSort = '';
    currentDir = 'ASC';
    document.getElementById('dataSearch').value = '';
    renderTableList(document.getElementById('tableSearch').value);
    loadData();
    // Pre-load schema
    loadSchema();
}

/* ═══════════════════════════════════════════════════════════════════════════
   TAB MANAGEMENT
   ═══════════════════════════════════════════════════════════════════════════ */
document.querySelectorAll('.tab').forEach(tab => {
    tab.addEventListener('click', () => {
        document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        document.querySelectorAll('.panel').forEach(p => p.style.display = 'none');
        const panel = document.getElementById('panel-' + tab.dataset.tab);
        panel.style.display = tab.dataset.tab === 'data' ? 'flex' : (tab.dataset.tab === 'sql' ? 'flex' : 'block');
        if (panel.style.display === 'flex' && tab.dataset.tab === 'sql') {
            panel.querySelector('.sql-console').style.display = 'flex';
        }
        if (tab.dataset.tab === 'stats') loadStats();
    });
});

/* ═══════════════════════════════════════════════════════════════════════════
   DATA TAB
   ═══════════════════════════════════════════════════════════════════════════ */
async function loadData() {
    if (!currentTable) return;
    const toolbar = document.getElementById('dataToolbar');
    toolbar.style.display = 'flex';

    const content = document.getElementById('dataContent');
    content.innerHTML = '<div class="loading-overlay"><div class="spinner"></div> Loading data…</div>';

    const search = document.getElementById('dataSearch').value;
    const perPage = document.getElementById('perPage').value;

    try {
        const data = await api('data', {
            table: currentTable,
            page: currentPage,
            per_page: perPage,
            search: search,
            sort: currentSort,
            dir: currentDir,
        });

        if (data.rows.length === 0) {
            content.innerHTML = '<div class="welcome" style="height:200px;"><i class="fas fa-inbox"></i><p>No data found</p></div>';
            document.getElementById('dataPagination').style.display = 'none';
            return;
        }

        // Build table
        let html = '<div class="data-table-wrap"><table class="data-table"><thead><tr>';
        for (const col of data.columns) {
            const isSort = col === currentSort;
            const icon = isSort ? (currentDir === 'ASC' ? 'fa-sort-up' : 'fa-sort-down') : 'fa-sort';
            html += `<th data-col="${esc(col)}">${esc(col)} <i class="fas ${icon} sort-icon"></i></th>`;
        }
        html += '</tr></thead><tbody>';

        for (const row of data.rows) {
            html += '<tr>';
            for (const col of data.columns) {
                const val = row[col];
                html += `<td title="${val !== null ? esc(String(val)) : 'NULL'}">${esc(val)}</td>`;
            }
            html += '</tr>';
        }
        html += '</tbody></table></div>';
        content.innerHTML = html;

        // Sort click
        content.querySelectorAll('th').forEach(th => {
            th.addEventListener('click', () => {
                const col = th.dataset.col;
                if (currentSort === col) {
                    currentDir = currentDir === 'ASC' ? 'DESC' : 'ASC';
                } else {
                    currentSort = col;
                    currentDir = 'ASC';
                }
                loadData();
            });
        });

        // Pagination
        renderPagination(data);

    } catch (err) {
        content.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i><p>Error: ${esc(err.message)}</p></div>`;
    }
}

function renderPagination(data) {
    const pg = document.getElementById('dataPagination');
    if (data.pages <= 1) { pg.style.display = 'none'; return; }
    pg.style.display = 'flex';

    const start = (data.page - 1) * data.per_page + 1;
    const end = Math.min(data.page * data.per_page, data.total);

    let pagesHtml = '';
    // Prev
    pagesHtml += `<button class="page-btn" data-p="${data.page - 1}" ${data.page <= 1 ? 'disabled' : ''}><i class="fas fa-chevron-left"></i></button>`;

    // Page numbers (show max 7)
    let pages = [];
    if (data.pages <= 7) {
        pages = Array.from({length: data.pages}, (_, i) => i + 1);
    } else {
        pages = [1];
        let s = Math.max(2, data.page - 2);
        let e = Math.min(data.pages - 1, data.page + 2);
        if (s > 2) pages.push('...');
        for (let i = s; i <= e; i++) pages.push(i);
        if (e < data.pages - 1) pages.push('...');
        pages.push(data.pages);
    }
    for (const p of pages) {
        if (p === '...') {
            pagesHtml += '<span style="padding:6px 4px;color:var(--text-dim)">…</span>';
        } else {
            pagesHtml += `<button class="page-btn ${p === data.page ? 'active' : ''}" data-p="${p}">${p}</button>`;
        }
    }
    pagesHtml += `<button class="page-btn" data-p="${data.page + 1}" ${data.page >= data.pages ? 'disabled' : ''}><i class="fas fa-chevron-right"></i></button>`;

    pg.innerHTML = `
        <span class="info">Showing ${start.toLocaleString()} – ${end.toLocaleString()} of ${data.total.toLocaleString()} rows</span>
        <div class="pages">${pagesHtml}</div>
    `;

    pg.querySelectorAll('.page-btn:not(:disabled)').forEach(btn => {
        btn.addEventListener('click', () => {
            currentPage = parseInt(btn.dataset.p);
            loadData();
        });
    });
}

// Search debounce
document.getElementById('dataSearch').addEventListener('input', () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => { currentPage = 1; loadData(); }, 350);
});

// Per page change
document.getElementById('perPage').addEventListener('change', () => { currentPage = 1; loadData(); });

// Refresh
document.getElementById('refreshBtn').addEventListener('click', loadData);

// Export CSV
document.getElementById('exportBtn').addEventListener('click', () => {
    if (!currentTable) return;
    window.open(`${API}?action=export&table=${encodeURIComponent(currentTable)}`);
});

/* ═══════════════════════════════════════════════════════════════════════════
   SCHEMA TAB
   ═══════════════════════════════════════════════════════════════════════════ */
async function loadSchema() {
    if (!currentTable) return;
    const content = document.getElementById('schemaContent');
    content.innerHTML = '<div class="loading-overlay"><div class="spinner"></div> Loading schema…</div>';

    try {
        const schema = await api('schema', { table: currentTable });

        let html = '<div class="schema-grid">';

        // Columns card
        html += '<div class="schema-card"><h3><i class="fas fa-columns" style="margin-right:8px"></i>Columns</h3>';
        html += '<table><thead><tr><th>#</th><th>Name</th><th>Type</th><th>Nullable</th><th>Default</th><th>PK</th></tr></thead><tbody>';
        for (const col of schema.columns) {
            html += `<tr>
                <td>${col.cid}</td>
                <td><strong>${esc(col.name)}</strong></td>
                <td class="type">${esc(col.type) || '<span class="null-val">any</span>'}</td>
                <td>${col.notnull ? '<span style="color:var(--danger)">NOT NULL</span>' : '<span style="color:var(--success)">YES</span>'}</td>
                <td>${col.dflt_value !== null ? esc(col.dflt_value) : '<span class="null-val">none</span>'}</td>
                <td>${col.pk ? '<span class="pk">PK ★</span>' : ''}</td>
            </tr>`;
        }
        html += '</tbody></table></div>';

        // Foreign Keys card
        if (schema.foreign_keys.length > 0) {
            html += '<div class="schema-card"><h3><i class="fas fa-link" style="margin-right:8px"></i>Foreign Keys</h3>';
            html += '<table><thead><tr><th>From</th><th>→ Table</th><th>→ Column</th></tr></thead><tbody>';
            for (const fk of schema.foreign_keys) {
                html += `<tr><td>${esc(fk.from)}</td><td>${esc(fk.table)}</td><td>${esc(fk.to)}</td></tr>`;
            }
            html += '</tbody></table></div>';
        }

        // Indexes card
        if (schema.indexes.length > 0) {
            html += '<div class="schema-card"><h3><i class="fas fa-bolt" style="margin-right:8px"></i>Indexes</h3>';
            html += '<table><thead><tr><th>Name</th><th>Unique</th></tr></thead><tbody>';
            for (const idx of schema.indexes) {
                html += `<tr><td>${esc(idx.name)}</td><td>${idx.unique ? '<span style="color:var(--warning)">UNIQUE</span>' : 'No'}</td></tr>`;
            }
            html += '</tbody></table></div>';
        }

        // CREATE SQL card
        html += '<div class="schema-card" style="grid-column:1/-1;"><h3><i class="fas fa-code" style="margin-right:8px"></i>CREATE Statement</h3>';
        html += `<pre>${esc(schema.create_sql)}</pre></div>`;

        html += '</div>';
        content.innerHTML = html;
    } catch (err) {
        content.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i><p>${esc(err.message)}</p></div>`;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   SQL CONSOLE
   ═══════════════════════════════════════════════════════════════════════════ */
async function runSQL() {
    const sql = document.getElementById('sqlInput').value.trim();
    if (!sql) return;
    const results = document.getElementById('sqlResults');
    const timeEl = document.getElementById('sqlTime');
    results.innerHTML = '<div class="loading-overlay"><div class="spinner"></div> Executing…</div>';
    timeEl.textContent = '';

    try {
        const data = await api('sql', {}, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ sql }),
        });

        if (data.error) {
            results.innerHTML = `<div class="welcome" style="height:auto;padding:30px;"><i class="fas fa-exclamation-circle" style="color:var(--danger);font-size:24px;"></i><p style="color:var(--danger)">${esc(data.error)}</p></div>`;
            return;
        }

        timeEl.innerHTML = `<i class="fas fa-clock"></i> ${data.time_ms}ms &nbsp;·&nbsp; ${data.total} row${data.total !== 1 ? 's' : ''}${data.truncated ? ' (showing first 500)' : ''}`;

        if (data.rows.length === 0) {
            results.innerHTML = '<div class="welcome" style="height:auto;padding:30px;"><i class="fas fa-check-circle" style="color:var(--success);font-size:24px;"></i><p>Query executed – 0 rows returned</p></div>';
            return;
        }

        let html = '<div class="data-table-wrap"><table class="data-table"><thead><tr>';
        for (const col of data.columns) html += `<th>${esc(col)}</th>`;
        html += '</tr></thead><tbody>';
        for (const row of data.rows) {
            html += '<tr>';
            for (const col of data.columns) html += `<td>${esc(row[col])}</td>`;
            html += '</tr>';
        }
        html += '</tbody></table></div>';
        results.innerHTML = html;

    } catch (err) {
        results.innerHTML = `<div class="welcome" style="height:auto;padding:30px;"><i class="fas fa-bomb" style="color:var(--danger);font-size:24px;"></i><p>${esc(err.message)}</p></div>`;
    }
}

document.getElementById('sqlRun').addEventListener('click', runSQL);
document.getElementById('sqlInput').addEventListener('keydown', e => {
    if (e.ctrlKey && e.key === 'Enter') { e.preventDefault(); runSQL(); }
});

/* ═══════════════════════════════════════════════════════════════════════════
   STATS TAB
   ═══════════════════════════════════════════════════════════════════════════ */
async function loadStats() {
    const content = document.getElementById('statsContent');
    try {
        const [stats, tables] = await Promise.all([api('stats'), api('tables')]);

        let html = '<div class="stats-grid">';
        const cards = [
            { icon: 'fa-database', value: formatBytes(stats.db_size), label: 'Database Size' },
            { icon: 'fa-table', value: stats.tables, label: 'Tables' },
            { icon: 'fa-eye', value: stats.views, label: 'Views' },
            { icon: 'fa-bolt', value: stats.triggers, label: 'Triggers' },
            { icon: 'fa-list', value: stats.indexes, label: 'Indexes' },
            { icon: 'fa-bars', value: stats.total_rows.toLocaleString(), label: 'Total Rows' },
            { icon: 'fa-code-branch', value: stats.sqlite_version, label: 'SQLite Version' },
        ];
        for (const c of cards) {
            html += `<div class="stat-card"><i class="fas ${c.icon}"></i><div class="value">${c.value}</div><div class="label">${c.label}</div></div>`;
        }
        html += '</div>';

        // Top tables by row count
        const sorted = [...tables].sort((a, b) => b.rows - a.rows).slice(0, 10);
        html += '<div style="padding:0 20px 20px;"><div class="schema-card"><h3><i class="fas fa-trophy" style="margin-right:8px"></i>Top Tables by Row Count</h3>';
        html += '<table><thead><tr><th>Table</th><th style="text-align:right">Rows</th></tr></thead><tbody>';
        for (const t of sorted) {
            const pct = tables.length > 0 ? Math.max(2, (t.rows / Math.max(1, sorted[0].rows)) * 100) : 0;
            html += `<tr style="cursor:pointer" onclick="selectTable('${t.name}')">
                <td><i class="fas fa-table" style="margin-right:8px;color:var(--text-dim);font-size:11px"></i>${esc(t.name)}</td>
                <td style="text-align:right">
                    <div style="display:flex;align-items:center;justify-content:flex-end;gap:10px;">
                        <div style="width:120px;height:6px;background:var(--surface2);border-radius:3px;overflow:hidden;">
                            <div style="width:${pct}%;height:100%;background:var(--accent);border-radius:3px;"></div>
                        </div>
                        <span>${t.rows.toLocaleString()}</span>
                    </div>
                </td>
            </tr>`;
        }
        html += '</tbody></table></div></div>';

        content.innerHTML = html;
    } catch (err) {
        content.innerHTML = `<div class="welcome"><i class="fas fa-exclamation-triangle" style="color:var(--danger)"></i><p>${esc(err.message)}</p></div>`;
    }
}

/* ═══════════════════════════════════════════════════════════════════════════
   INIT
   ═══════════════════════════════════════════════════════════════════════════ */
loadTables();
</script>

</body>
</html>
