<?php
/**
 * SQLite Database Initialisation Script
 * Run:  php sql/init_sqlite.php
 *
 * Creates the database/load_monitor.sqlite file with all tables and seed data.
 */

$dbDir  = __DIR__ . '/../database';
$dbFile = $dbDir . '/load_monitor.sqlite';

if (!is_dir($dbDir)) mkdir($dbDir, 0777, true);

// Remove old DB so we start fresh
if (file_exists($dbFile)) {
    echo "Removing existing database...\n";
    unlink($dbFile);
}

$db = new PDO("sqlite:$dbFile", null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);
$db->exec('PRAGMA journal_mode=WAL');
$db->exec('PRAGMA foreign_keys=ON');

echo "Creating tables...\n";

// ──────────────────────────────────────────────────────────────────────────────
// TABLES
// ──────────────────────────────────────────────────────────────────────────────

$db->exec("
CREATE TABLE IF NOT EXISTS transmission_stations (
    ts_code   TEXT PRIMARY KEY,
    station_name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS area_offices (
    ao_id   TEXT PRIMARY KEY,
    ao_name TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS iss_locations (
    iss_code  TEXT PRIMARY KEY,
    iss_name  TEXT NOT NULL UNIQUE,
    created_at TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS fdr33kv (
    fdr33kv_code TEXT PRIMARY KEY,
    fdr33kv_name TEXT NOT NULL UNIQUE,
    ts_code      TEXT NOT NULL,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    max_load     REAL NOT NULL DEFAULT 12.50,
    CHECK (max_load <= 15.00)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS fdr11kv (
    fdr11kv_code TEXT PRIMARY KEY,
    fdr11kv_name TEXT NOT NULL UNIQUE,
    fdr33kv_code TEXT,
    band         TEXT NOT NULL,
    ao_code      TEXT NOT NULL,
    iss_code     TEXT NOT NULL,
    created_at   TEXT NOT NULL DEFAULT (datetime('now')),
    max_load     REAL NOT NULL DEFAULT 12.50,
    CHECK (max_load <= 15.00)
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_fdr11kv_33kv ON fdr11kv(fdr33kv_code)");

$db->exec("
CREATE TABLE IF NOT EXISTS staff_details (
    staff_name       TEXT NOT NULL,
    payroll_id       TEXT NOT NULL,
    iss_code         TEXT NOT NULL,
    phone            TEXT NOT NULL,
    staff_level      TEXT NOT NULL,
    sv_code          TEXT NOT NULL,
    assigned_33kv_code TEXT NOT NULL,
    email            TEXT NOT NULL UNIQUE,
    password_hash    TEXT NOT NULL,
    last_login       TEXT,
    is_active        TEXT NOT NULL DEFAULT 'Yes' CHECK(is_active IN ('Yes','No')),
    role             TEXT NOT NULL DEFAULT 'UL1' CHECK(role IN ('UL1','UL2','UL3','UL4','UL5','UL6','UL7','UL8')),
    created_at       TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT NOT NULL DEFAULT (datetime('now'))
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_payroll ON staff_details(payroll_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_iss     ON staff_details(iss_code)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_33kv    ON staff_details(assigned_33kv_code)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_email   ON staff_details(email)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_role    ON staff_details(role)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_staff_active  ON staff_details(is_active)");

$db->exec("
CREATE TABLE IF NOT EXISTS user_privilege (
    user_level   TEXT NOT NULL,
    user_code    TEXT PRIMARY KEY,
    '11kv_level' TEXT NOT NULL CHECK(\"11kv_level\" IN ('YES','NO')),
    '33kv_level' TEXT NOT NULL CHECK(\"33kv_level\" IN ('YES','NO')),
    can_write    TEXT NOT NULL CHECK(can_write    IN ('YES','NO')),
    can_read     TEXT NOT NULL CHECK(can_read     IN ('YES','NO')),
    can_edit     TEXT NOT NULL CHECK(can_edit     IN ('YES','NO')),
    view_report  TEXT NOT NULL CHECK(view_report  IN ('YES','NO')),
    can_download TEXT NOT NULL CHECK(can_download IN ('YES','NO')),
    create_user  TEXT NOT NULL CHECK(create_user  IN ('YES','NO')),
    edit_user    TEXT NOT NULL CHECK(edit_user    IN ('YES','NO'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS fdr11kv_data (
    entry_date   TEXT NOT NULL,
    fdr11kv_code TEXT NOT NULL,
    entry_hour   INTEGER NOT NULL,
    load_read    REAL NOT NULL,
    fault_code   TEXT CHECK(fault_code IN ('FO','BF','OS','DOff','MVR','OT','MS','LS','TF')),
    fault_remark TEXT,
    user_id      TEXT,
    timestamp    TEXT DEFAULT (datetime('now')),
    PRIMARY KEY (entry_date, fdr11kv_code, entry_hour)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS fdr33kv_data (
    entry_date   TEXT NOT NULL,
    fdr33kv_code TEXT NOT NULL,
    entry_hour   INTEGER NOT NULL,
    load_read    REAL NOT NULL,
    fault_code   TEXT CHECK(fault_code IN ('FO','BF','OS','DOff','MVR')),
    fault_remark TEXT,
    user_id      TEXT,
    timestamp    TEXT DEFAULT (datetime('now')),
    PRIMARY KEY (entry_date, fdr33kv_code, entry_hour)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS interruptions (
    id                       INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number            TEXT UNIQUE,
    serial_number            TEXT UNIQUE,
    interruption_code        TEXT NOT NULL,
    requires_approval        TEXT NOT NULL DEFAULT 'NO'  CHECK(requires_approval IN ('YES','NO')),
    approval_note            TEXT,
    approval_status          TEXT NOT NULL DEFAULT 'NOT_REQUIRED' CHECK(approval_status IN ('PENDING','ANALYST_APPROVED','APPROVED','REJECTED','NOT_REQUIRED')),
    form_status              TEXT NOT NULL DEFAULT 'PENDING_COMPLETION' CHECK(form_status IN ('PENDING_COMPLETION','AWAITING_APPROVAL','PENDING_COMPLETION_APPROVED','COMPLETED','CANCELLED')),
    stage                    TEXT NOT NULL DEFAULT 'OPEN' CHECK(stage IN ('OPEN','COMPLETED')),
    approval_id              INTEGER,
    fdr33kv_code             TEXT NOT NULL,
    interruption_type        TEXT NOT NULL,
    load_loss                REAL,
    datetime_out             TEXT NOT NULL,
    datetime_in              TEXT,
    duration                 REAL,
    reason_for_interruption  TEXT,
    resolution               TEXT,
    weather_condition        TEXT,
    reason_for_delay         TEXT,
    other_reasons            TEXT,
    late_entry_reason        TEXT,
    user_id                  TEXT,
    timestamp                TEXT DEFAULT (datetime('now')),
    started_by               TEXT,
    started_at               TEXT,
    completed_by             TEXT,
    completed_at             TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int_approval_status ON interruptions(approval_status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int_approval_id ON interruptions(approval_id)");

$db->exec("
CREATE TABLE IF NOT EXISTS interruptions_11kv (
    id                       INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number            TEXT UNIQUE,
    serial_number            TEXT UNIQUE,
    fdr11kv_code             TEXT NOT NULL,
    interruption_type        TEXT NOT NULL,
    interruption_code        TEXT NOT NULL,
    requires_approval        TEXT NOT NULL DEFAULT 'NO'  CHECK(requires_approval IN ('YES','NO')),
    approval_note            TEXT,
    approval_status          TEXT NOT NULL DEFAULT 'NOT_REQUIRED' CHECK(approval_status IN ('PENDING','ANALYST_APPROVED','APPROVED','REJECTED','NOT_REQUIRED')),
    form_status              TEXT NOT NULL DEFAULT 'PENDING_COMPLETION' CHECK(form_status IN ('PENDING_COMPLETION','AWAITING_APPROVAL','PENDING_COMPLETION_APPROVED','COMPLETED','CANCELLED')),
    stage                    TEXT NOT NULL DEFAULT 'OPEN' CHECK(stage IN ('OPEN','COMPLETED')),
    approval_id              INTEGER,
    load_loss                REAL,
    datetime_out             TEXT NOT NULL,
    datetime_in              TEXT,
    duration                 REAL,
    reason_for_interruption  TEXT,
    resolution               TEXT,
    weather_condition        TEXT,
    reason_for_delay         TEXT,
    other_reasons            TEXT,
    late_entry_reason        TEXT,
    is_late_entry            INTEGER NOT NULL DEFAULT 0,
    user_id                  TEXT,
    timestamp                TEXT DEFAULT (datetime('now')),
    started_by               TEXT,
    started_at               TEXT,
    completed_by             TEXT,
    completed_at             TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_fdr     ON interruptions_11kv(fdr11kv_code)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_dtout   ON interruptions_11kv(datetime_out)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_user    ON interruptions_11kv(user_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_type    ON interruptions_11kv(interruption_type)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_apst    ON interruptions_11kv(approval_status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_int11_apid    ON interruptions_11kv(approval_id)");

$db->exec("
CREATE TABLE IF NOT EXISTS interruption_approvals (
    id               INTEGER PRIMARY KEY AUTOINCREMENT,
    interruption_id  INTEGER NOT NULL,
    interruption_type TEXT NOT NULL CHECK(interruption_type IN ('11kV','33kV')),
    status           TEXT NOT NULL DEFAULT 'PENDING' CHECK(status IN ('PENDING','ANALYST_APPROVED','APPROVED','REJECTED')),
    requester_id     TEXT NOT NULL,
    requester_name   TEXT NOT NULL,
    requested_at     TEXT NOT NULL DEFAULT (datetime('now')),
    analyst_id       TEXT,
    analyst_name     TEXT,
    analyst_remarks  TEXT,
    analyst_action   TEXT CHECK(analyst_action IN ('APPROVED','REJECTED')),
    analyst_action_at TEXT,
    manager_id       TEXT,
    manager_name     TEXT,
    manager_remarks  TEXT,
    manager_action   TEXT CHECK(manager_action IN ('APPROVED','REJECTED')),
    manager_action_at TEXT,
    created_at       TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at       TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_ia_int  ON interruption_approvals(interruption_id, interruption_type)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_ia_stat ON interruption_approvals(status)");

$db->exec("
CREATE TABLE IF NOT EXISTS interruption_codes (
    interruption_code        TEXT PRIMARY KEY,
    interruption_description TEXT NOT NULL,
    interruption_type        TEXT NOT NULL,
    interruption_group       TEXT NOT NULL,
    body_responsible         TEXT NOT NULL,
    approval_requirement     TEXT NOT NULL CHECK(approval_requirement IN ('YES','NO'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS load_corrections (
    id                INTEGER PRIMARY KEY AUTOINCREMENT,
    feeder_code       TEXT NOT NULL,
    entry_date        TEXT NOT NULL,
    entry_hour        INTEGER NOT NULL,
    correction_type   TEXT NOT NULL DEFAULT '11kV' CHECK(correction_type IN ('11kV','33kV')),
    field_to_correct  TEXT NOT NULL,
    old_value         TEXT,
    new_value         TEXT NOT NULL,
    reason            TEXT NOT NULL,
    blank_hour_reason TEXT,
    requested_by      TEXT NOT NULL,
    requested_at      TEXT NOT NULL DEFAULT (datetime('now')),
    status            TEXT NOT NULL DEFAULT 'PENDING' CHECK(status IN ('PENDING','ANALYST_APPROVED','MANAGER_APPROVED','REJECTED')),
    analyst_id        TEXT,
    analyst_remarks   TEXT,
    analyst_action_at TEXT,
    manager_id        TEXT,
    manager_remarks   TEXT,
    manager_action_at TEXT
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lc_status ON load_corrections(status)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lc_feeder ON load_corrections(feeder_code, entry_date)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lc_reqby  ON load_corrections(requested_by)");

$db->exec("
CREATE TABLE IF NOT EXISTS corrections_33kv (
    correction_id     INTEGER PRIMARY KEY AUTOINCREMENT,
    feeder_code       TEXT NOT NULL,
    entry_date        TEXT NOT NULL,
    entry_hour        INTEGER NOT NULL CHECK(entry_hour >= 1 AND entry_hour <= 24),
    field_to_correct  TEXT NOT NULL,
    current_value     TEXT,
    new_value         TEXT NOT NULL,
    reason            TEXT NOT NULL,
    requested_by      TEXT NOT NULL,
    status            TEXT NOT NULL DEFAULT 'PENDING' CHECK(status IN ('PENDING','ANALYST_APPROVED','ANALYST_REJECTED','APPROVED','MANAGER_REJECTED')),
    analyst_id        TEXT,
    analyst_remarks   TEXT,
    analyst_reviewed_at TEXT,
    manager_id        TEXT,
    manager_remarks   TEXT,
    manager_approved_at TEXT,
    created_at        TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS correction_requests (
    request_id      INTEGER PRIMARY KEY AUTOINCREMENT,
    data_level      TEXT NOT NULL CHECK(data_level IN ('11KV','33KV')),
    feeder_code     TEXT NOT NULL,
    entry_date      TEXT NOT NULL,
    entry_hour      INTEGER NOT NULL,
    original_load   REAL,
    requested_load  REAL,
    original_fault  TEXT,
    requested_fault TEXT,
    original_remark TEXT,
    requested_remark TEXT,
    requested_by    TEXT NOT NULL,
    requested_at    TEXT NOT NULL DEFAULT (datetime('now')),
    analyst_status  TEXT NOT NULL DEFAULT 'PENDING' CHECK(analyst_status IN ('PENDING','APPROVED','REJECTED')),
    analyst_by      TEXT,
    analyst_at      TEXT,
    manager_status  TEXT NOT NULL DEFAULT 'PENDING' CHECK(manager_status IN ('PENDING','APPROVED','REJECTED')),
    manager_by      TEXT,
    manager_at      TEXT,
    final_status    TEXT NOT NULL DEFAULT 'PENDING' CHECK(final_status IN ('PENDING','APPROVED','REJECTED')),
    reason          TEXT NOT NULL
)");

$db->exec("
CREATE TABLE IF NOT EXISTS complaint_log (
    id                 INTEGER PRIMARY KEY AUTOINCREMENT,
    complaint_ref      TEXT NOT NULL UNIQUE,
    feeder_code        TEXT NOT NULL,
    complaint_type     TEXT NOT NULL CHECK(complaint_type IN ('NO_SUPPLY','LOW_VOLTAGE','INTERMITTENT','TRANSFORMER_FAULT','LINE_FAULT','OTHERS')),
    complaint_source   TEXT NOT NULL CHECK(complaint_source IN ('CUSTOMER_CALL','FIELD_PATROL','INTERNAL_MONITORING','DSO_REPORT')),
    affected_area      TEXT,
    customer_phone     TEXT,
    customer_name      TEXT,
    complaint_details  TEXT NOT NULL,
    fault_location     TEXT,
    priority           TEXT NOT NULL DEFAULT 'MEDIUM' CHECK(priority IN ('LOW','MEDIUM','HIGH','CRITICAL')),
    status             TEXT NOT NULL DEFAULT 'PENDING' CHECK(status IN ('PENDING','ASSIGNED','IN_PROGRESS','RESOLVED','CLOSED')),
    logged_by          TEXT NOT NULL,
    logged_at          TEXT NOT NULL DEFAULT (datetime('now')),
    assigned_to        TEXT,
    assigned_at        TEXT,
    resolution_details TEXT,
    resolved_by        TEXT,
    resolved_at        TEXT,
    closure_remarks    TEXT,
    closed_by          TEXT,
    closed_at          TEXT,
    downtime_hours     REAL
)");

$db->exec("
CREATE TABLE IF NOT EXISTS feeder_ticket_prefix (
    feeder_code   TEXT NOT NULL,
    voltage_level TEXT NOT NULL CHECK(voltage_level IN ('11kV','33kV')),
    slug          TEXT NOT NULL,
    assigned_at   TEXT NOT NULL DEFAULT (datetime('now')),
    PRIMARY KEY (feeder_code, voltage_level),
    UNIQUE (voltage_level, slug)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS myto_daily (
    entry_date       TEXT NOT NULL,
    entry_hour       INTEGER NOT NULL,
    myto_allocation  REAL NOT NULL,
    formula_version  INTEGER,
    user_id          TEXT,
    timestamp        TEXT DEFAULT (datetime('now')),
    PRIMARY KEY (entry_date, entry_hour)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS myto_ts_allocation (
    entry_date           TEXT NOT NULL,
    ts_code              TEXT NOT NULL,
    entry_hour           INTEGER NOT NULL,
    myto_hour_allocation REAL NOT NULL,
    formula_version      INTEGER,
    user_id              TEXT,
    timestamp            TEXT DEFAULT (datetime('now')),
    PRIMARY KEY (entry_date, ts_code, entry_hour)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS myto_sharing_formula (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    ts_code     TEXT NOT NULL,
    percentage  REAL NOT NULL,
    version     INTEGER NOT NULL DEFAULT 1,
    is_active   INTEGER NOT NULL DEFAULT 1,
    updated_by  TEXT NOT NULL,
    updated_at  TEXT NOT NULL DEFAULT (datetime('now')),
    created_at  TEXT NOT NULL DEFAULT (datetime('now'))
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_msf_ts     ON myto_sharing_formula(ts_code)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_msf_active ON myto_sharing_formula(is_active, ts_code)");

$db->exec("
CREATE TABLE IF NOT EXISTS myto_formula_history (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    version     INTEGER NOT NULL,
    ts_code     TEXT NOT NULL,
    percentage  REAL NOT NULL,
    changed_by  TEXT NOT NULL,
    changed_at  TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS activity_logs (
    activity_id          INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id           TEXT NOT NULL,
    session_id           INTEGER,
    activity_type        TEXT NOT NULL,
    activity_description TEXT,
    related_table        TEXT,
    related_id           TEXT,
    activity_time        TEXT NOT NULL,
    ip_address           TEXT,
    user_agent           TEXT,
    created_at           TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS staff_activity_logs (
    activity_id          INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id           TEXT NOT NULL,
    session_id           INTEGER,
    activity_type        TEXT NOT NULL,
    activity_description TEXT,
    related_table        TEXT,
    related_id           TEXT,
    activity_time        TEXT NOT NULL,
    ip_address           TEXT,
    user_agent           TEXT,
    created_at           TEXT NOT NULL DEFAULT (datetime('now'))
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_sal_payroll ON staff_activity_logs(payroll_id)");

$db->exec("
CREATE TABLE IF NOT EXISTS staff_sessions (
    session_id       INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id       TEXT NOT NULL,
    login_time       TEXT NOT NULL,
    logout_time      TEXT,
    session_duration REAL,
    ip_address       TEXT,
    user_agent       TEXT,
    is_active        TEXT NOT NULL DEFAULT 'Yes' CHECK(is_active IN ('Yes','No')),
    created_at       TEXT NOT NULL DEFAULT (datetime('now'))
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_ss_payroll ON staff_sessions(payroll_id)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_ss_active  ON staff_sessions(is_active)");

$db->exec("
CREATE TABLE IF NOT EXISTS staff_daily_metrics (
    metric_id              INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id             TEXT NOT NULL,
    metric_date            TEXT NOT NULL,
    total_hours            REAL DEFAULT 0,
    data_entries_11kv      INTEGER DEFAULT 0,
    data_entries_33kv      INTEGER DEFAULT 0,
    corrections_requested  INTEGER DEFAULT 0,
    corrections_approved   INTEGER DEFAULT 0,
    interruptions_logged   INTEGER DEFAULT 0,
    reports_generated      INTEGER DEFAULT 0,
    login_time             TEXT,
    logout_time            TEXT,
    created_at             TEXT NOT NULL DEFAULT (datetime('now')),
    updated_at             TEXT NOT NULL DEFAULT (datetime('now')),
    UNIQUE (payroll_id, metric_date)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS staff_reassignment_log (
    log_id         INTEGER PRIMARY KEY AUTOINCREMENT,
    payroll_id     TEXT NOT NULL,
    staff_name     TEXT NOT NULL,
    old_role       TEXT NOT NULL,
    new_role       TEXT NOT NULL,
    field_changed  TEXT NOT NULL,
    old_value      TEXT,
    new_value      TEXT,
    reason         TEXT NOT NULL,
    reassigned_by  TEXT NOT NULL,
    reassigned_at  TEXT NOT NULL DEFAULT (datetime('now'))
)");

$db->exec("
CREATE TABLE IF NOT EXISTS analytics_reports (
    id          INTEGER PRIMARY KEY AUTOINCREMENT,
    report_name TEXT NOT NULL,
    report_type TEXT NOT NULL,
    description TEXT,
    parameters  TEXT,
    created_by  TEXT NOT NULL,
    created_at  TEXT NOT NULL DEFAULT (datetime('now')),
    is_public   INTEGER DEFAULT 1
)");

$db->exec("
CREATE TABLE IF NOT EXISTS late_entry_log (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    voltage_level  TEXT NOT NULL DEFAULT '11kV' CHECK(voltage_level IN ('11kV','33kV')),
    user_id        TEXT NOT NULL,
    iss_code       TEXT NOT NULL,
    log_date       TEXT NOT NULL,
    specific_hour  INTEGER NOT NULL,
    entry_type     TEXT NOT NULL DEFAULT 'load' CHECK(entry_type IN ('load','fault')),
    operation      TEXT NOT NULL DEFAULT 'initial' CHECK(operation IN ('initial','edit')),
    explanation    TEXT NOT NULL,
    logged_at      TEXT NOT NULL DEFAULT (datetime('now'))
)");
$db->exec("CREATE INDEX IF NOT EXISTS idx_lel_user ON late_entry_log(user_id, log_date)");

$db->exec("
CREATE TABLE IF NOT EXISTS operational_day_batches (
    id             INTEGER PRIMARY KEY AUTOINCREMENT,
    op_date        TEXT NOT NULL,
    voltage_level  TEXT NOT NULL CHECK(voltage_level IN ('11kV','33kV')),
    closed_at      TEXT NOT NULL DEFAULT (datetime('now')),
    blank_cells    INTEGER NOT NULL DEFAULT 0,
    UNIQUE (op_date, voltage_level)
)");

$db->exec("
CREATE TABLE IF NOT EXISTS ticket_edit_cancel_log (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_number TEXT NOT NULL,
    voltage_level TEXT NOT NULL CHECK(voltage_level IN ('11kV','33kV')),
    action_type   TEXT NOT NULL CHECK(action_type IN ('EDIT','CANCEL')),
    action_by     TEXT NOT NULL,
    action_at     TEXT NOT NULL DEFAULT (datetime('now')),
    reason        TEXT,
    old_values    TEXT
)");

echo "Tables created.\n";

// ──────────────────────────────────────────────────────────────────────────────
// VIEWS (simplified – no MySQL GENERATED ALWAYS AS columns)
// ──────────────────────────────────────────────────────────────────────────────

$db->exec("
CREATE VIEW IF NOT EXISTS pending_approvals_view AS
SELECT
    ia.id,
    ia.interruption_id,
    ia.interruption_type,
    ia.status,
    ia.requester_id,
    ia.requester_name,
    ia.requested_at,
    i33.fdr33kv_code,
    f33.fdr33kv_name,
    i33.datetime_out  AS datetime_out_33kv,
    i33.interruption_code AS code_33kv,
    i11.fdr11kv_code,
    f11.fdr11kv_name,
    i11.datetime_out  AS datetime_out_11kv,
    i11.interruption_code AS code_11kv,
    CASE WHEN ia.interruption_type = '33kV' THEN i33.interruption_code ELSE i11.interruption_code END AS interruption_code,
    CASE WHEN ia.interruption_type = '33kV' THEN f33.fdr33kv_name     ELSE f11.fdr11kv_name     END AS feeder_name,
    CASE WHEN ia.interruption_type = '33kV' THEN i33.datetime_out     ELSE i11.datetime_out     END AS datetime_out
FROM interruption_approvals ia
LEFT JOIN interruptions   i33 ON ia.interruption_id = i33.id AND ia.interruption_type = '33kV'
LEFT JOIN fdr33kv         f33 ON i33.fdr33kv_code   = f33.fdr33kv_code
LEFT JOIN interruptions_11kv i11 ON ia.interruption_id = i11.id AND ia.interruption_type = '11kV'
LEFT JOIN fdr11kv         f11 ON i11.fdr11kv_code   = f11.fdr11kv_code
WHERE ia.status IN ('PENDING','ANALYST_APPROVED')
");

echo "Views created.\n";

// ──────────────────────────────────────────────────────────────────────────────
// TRIGGERS (replicate MySQL login/logout auto-session logic)
// ──────────────────────────────────────────────────────────────────────────────

$db->exec("
CREATE TRIGGER IF NOT EXISTS trg_staff_login
AFTER INSERT ON staff_activity_logs
WHEN NEW.activity_type = 'LOGIN'
BEGIN
    INSERT INTO staff_sessions (payroll_id, login_time, ip_address, is_active)
    VALUES (NEW.payroll_id, NEW.activity_time, NEW.ip_address, 'Yes');
END
");

$db->exec("
CREATE TRIGGER IF NOT EXISTS trg_staff_logout
AFTER INSERT ON staff_activity_logs
WHEN NEW.activity_type = 'LOGOUT'
BEGIN
    UPDATE staff_sessions
    SET logout_time = NEW.activity_time,
        is_active   = 'No',
        session_duration = ROUND((julianday(NEW.activity_time) - julianday(login_time)) * 24, 2)
    WHERE session_id = (
        SELECT session_id FROM staff_sessions
        WHERE payroll_id = NEW.payroll_id AND is_active = 'Yes'
        ORDER BY login_time DESC LIMIT 1
    );
END
");

// Trigger: auto-compute duration when datetime_in is updated on interruptions
$db->exec("
CREATE TRIGGER IF NOT EXISTS trg_int_duration_update
AFTER UPDATE OF datetime_in ON interruptions
WHEN NEW.datetime_in IS NOT NULL
BEGIN
    UPDATE interruptions
    SET duration = ROUND((julianday(NEW.datetime_in) - julianday(NEW.datetime_out)) * 24, 6)
    WHERE id = NEW.id;
END
");

$db->exec("
CREATE TRIGGER IF NOT EXISTS trg_int11_duration_update
AFTER UPDATE OF datetime_in ON interruptions_11kv
WHEN NEW.datetime_in IS NOT NULL
BEGIN
    UPDATE interruptions_11kv
    SET duration = ROUND((julianday(NEW.datetime_in) - julianday(NEW.datetime_out)) * 24, 6)
    WHERE id = NEW.id;
END
");

echo "Triggers created.\n";

// ──────────────────────────────────────────────────────────────────────────────
// SEED DATA
// ──────────────────────────────────────────────────────────────────────────────

echo "Inserting seed data...\n";

// Transmission Stations
$ts = [
    ['1','Birnin Kebbi Transmission Station'],['2','Gusau Transmission Station'],
    ['3','Jos Transmission Station'],['4','Kaduna Town Transmission Station'],
    ['5','Kafanchan Transmission Station'],['6','Mando Transmission Station'],
    ['7','Sokoto Transmission Station'],['8','Suleja Transmission Station'],
    ['9','Talata Mafara Transmission Station'],['10','Tegina Transmission Station'],
    ['11','Yauri Transmission Station'],['12','Zaria Transmission Station'],
];
$ins = $db->prepare("INSERT INTO transmission_stations(ts_code,station_name) VALUES(?,?)");
foreach ($ts as $r) $ins->execute($r);

// Area Offices
$ao = [
    ['1','Barnawa'],['2','Doka'],['3','Gonin Gora'],['4','Jaji'],['5','Kafanchan'],
    ['6','Kawo'],['7','Kebbi Central'],['8','Kebbi East'],['9','Kebbi North'],
    ['10','Kebbi South'],['11','Makera'],['12','Mando'],['13','Millennium City'],
    ['14','Rigasa'],['15','Sabon Gari'],['16','Samaru'],['17','Saminaka'],
    ['18','Sokoto Central'],['19','Sokoto East'],['20','Sokoto North'],
    ['21','Sokoto South'],['22','Tudun Wada'],['23','Zamfara Central'],
    ['24','Zamfara North'],['25','Zamfara West'],['26','Zaria City'],['27','Unaligned'],
];
$ins = $db->prepare("INSERT INTO area_offices(ao_id,ao_name) VALUES(?,?)");
foreach ($ao as $r) $ins->execute($r);

// ISS Locations
$iss = [
    ['1','Abakpa'],['2','Birnin Gwari'],['3','Birnin Kebbi Road'],['4','Borstal'],
    ['5','Bunza'],['6','Dawaki'],['7','Eastern Bye-Pass'],['8','Fadama'],['9','Farfaru'],
    ['10','Gagi'],['11','Garkuwa'],['12','Gonin Gora'],['13','Grid'],['14','Gusau Road'],
    ['15','Hanwa'],['16','Illela Road'],['17','Independence'],['18','Jaji'],['19','Jega'],
    ['20','Kafanchan'],['21','Kagoro'],['22','Kakuri'],['23','Katabu'],['24','Kawo'],
    ['25','Kebbi Gra'],['26','Kinkinau'],['27','Kofan Doka'],['28','Kudendan'],
    ['29','Main Office'],['30','Makarfi'],['31','Mechanics Village'],['32','Millenium City'],
    ['33','Mogadishu'],['34','Mothercat'],['35','Nafbase'],['36','Narayi'],['37','Nnpc'],
    ['38','Pan'],['39','Power House'],['40','Power Station'],['41','Rigasa'],
    ['42','Runjin Sambo'],['43','Shika'],['44','Tambuwal'],['45','Tsunami'],
    ['46','Unguwan Boro'],['47','Unguwan Dosa'],['48','Yauri'],['49','Zaria Ts'],['50','Zuru'],
];
$ins = $db->prepare("INSERT INTO iss_locations(iss_code,iss_name) VALUES(?,?)");
foreach ($iss as $r) $ins->execute($r);

// Interruption Codes
$ic = [
    ['B/F KE','Breaker Fault KE','Emergency','Breaker Fault','KE','NO'],
    ['B/F TCN','Breaker Fault TCN','Emergency','Breaker Fault','TCN','NO'],
    ['BRI KE','Bucholz relay indication KE','Emergency','By Transient Faults','KE','NO'],
    ['BRI TCN','Bucholz relay indication TCN','Emergency','By Transient Faults','TCN','NO'],
    ['C33kV Off/T','Corresponding 33kV Open','Unplanned','Forced Outage','TCN','NO'],
    ['C33kV Off/Y','Corresponding 33kV Open','Unplanned','Forced Outage','KE','NO'],
    ['D/R KE','Differential Relay KE','Unplanned','By Transient Faults','KE','NO'],
    ['D/R TCN','Differential Relay TCN','Unplanned','By Transient Faults','TCN','NO'],
    ['E/F','Earth fault','Unplanned','By Transient Faults','KE','NO'],
    ['F/C','Frequency Control','Unplanned','By Transient Faults','TCN','NO'],
    ['Inst. E/F','Instantaneous Earth Fault','Unplanned','By Transient Faults','KE','NO'],
    ['Inst. E/F and H/S','Inst. E/F with heavy Surge','Unplanned','By Transient Faults','KE','NO'],
    ['Inst. O/C','Instantaneous Over Current','Unplanned','By Transient Faults','KE','NO'],
    ['L/S KE','Limitation KE','Unplanned','Limitation','KE','NO'],
    ['L/S TCN','Limitation TCN','Unplanned','Limitation','TCN','NO'],
    ['NRI','No relay indication','Emergency','By Transient Faults','KE','NO'],
    ['O/C','Over Current','Unplanned','By Transient Faults','KE','NO'],
    ['O/C and E/F','Over Current and Earth fault','Unplanned','By Transient Faults','KE','NO'],
    ['O/S KE','Out of Supply','Unplanned','Forced Outage','KE','NO'],
    ['O/S TCN','Out of Supply','Unplanned','Forced Outage','TCN','NO'],
    ['P/O KE','Planned Outage KE','Planned','Planned outage','KE','YES'],
    ['P/O TCN','Planned Outage TCN','Planned','Planned outage','TCN','YES'],
    ['REF','Restricted Earth fault','Emergency','By Transient Faults','KE','NO'],
    ['S/C','System Collapse','Unplanned','By Transient Faults','TCN','NO'],
    ['SBT KE','Service Base Tariff (Load Shedding) KE','Unplanned','Load shedding - Service Base Tariff','KE','NO'],
    ['SBT TCN','Service Base Tariff (Load Shedding) TCN','Unplanned','Load shedding - Service Base Tariff','TCN','NO'],
];
$ins = $db->prepare("INSERT INTO interruption_codes VALUES(?,?,?,?,?,?)");
foreach ($ic as $r) $ins->execute($r);

// User Privileges
$up = [
    ['User level 1','UL1','YES','NO','YES','YES','NO','YES','NO','NO','NO'],
    ['User level 2','UL2','NO','YES','YES','YES','NO','YES','NO','NO','NO'],
    ['Analyst','UL3','NO','NO','NO','YES','NO','YES','YES','NO','NO'],
    ['Manager','UL4','NO','NO','NO','YES','NO','YES','YES','NO','NO'],
    ['Viewer','UL5','NO','NO','NO','NO','NO','YES','NO','NO','NO'],
    ['Admin','UL6','NO','NO','NO','NO','NO','YES','NO','YES','YES'],
    ['Lead Dispatch','UL8','NO','NO','NO','YES','NO','YES','YES','NO','NO'],
];
$ins = $db->prepare("INSERT INTO user_privilege VALUES(?,?,?,?,?,?,?,?,?,?,?)");
foreach ($up as $r) $ins->execute($r);

// 33kV Feeders (from load.sql)
$fdr33 = [
    ['1','33Kv Abakpa','6'],['2','33Kv Airport Road','6'],['3','33Kv Aliero','1'],
    ['4','33Kv Anka','9'],['5','33Kv Arewa','4'],['6','33Kv Argungu','1'],
    ['7','33Kv Aviation','12'],['8','33Kv Bakura','9'],['9','33Kv Birnin Gwari','10'],
    ['10','33Kv Bunza','1'],['11','33Kv Ccnn','7'],['12','33Kv Crown Flour Mills','4'],
    ['13','33Kv Danmani Leg','6'],['14','33Kv Doka','6'],['15','33Kv Fadama 1','1'],
    ['16','33Kv Fadama 2','1'],['17','33Kv Farfaru','7'],['18','33Kv Gonin Gora','4'],
    ['19','33Kv Gwagwada Leg','4'],['20','33Kv Gwandu','1'],['21','33Kv Hanwa','12'],
    ['22','33Kv Independence','4'],['23','33Kv Jaji','6'],['24','33Kv Jega','1'],
    ['25','33Kv Jere','8'],['26','33Kv Kachia Leg','4'],['27','33Kv Kafanchan','5'],
    ['28','33Kv Kamba','1'],['29','33Kv Kauran Namoda','2'],['30','33Kv Kinkinau','6'],
    ['31','33Kv Kofan Doka','12'],['32','33Kv Koko','11'],['33','33Kv Krpc (Dedicated)','4'],
    ['34','33Kv Kudan','12'],['35','33Kv Kware/University','7'],
    ['36','33Kv Labana (Dedicated)','1'],['37','33Kv Mafara','9'],['38','33Kv Magami','2'],
    ['39','33Kv Makarfi','12'],['40','33Kv Maradun','9'],['41','33Kv Mogadishu','4'],
    ['42','33Kv Mother Cat','6'],['43','33Kv Naf','6'],['44','33Kv Narayi Village','4'],
    ['45','33Kv Nasco/Yelwa','11'],['46','33Kv New Injection','7'],['47','33Kv New Nda','6'],
    ['48','33Kv Ngaski','11'],['49','33Kv Nnpc Gusau','2'],['50','33Kv Nnpc Saminaka','3'],
    ['51','33Kv Olam','4'],['52','33Kv Pan','4'],['53','33Kv Power House','2'],
    ['54','33Kv Power Station','7'],['55','33Kv Pz','12'],['56','33Kv Rigasa','6'],
    ['57','33Kv Soba','12'],['58','33Kv Tambuwal','1'],['59','33Kv Township','7'],
    ['60','33Kv Transmission Stationafe','2'],['61','33Kv Turunku','6'],
    ['62','33Kv Turunku/Igabi Leg','6'],['63','33Kv Unguwan Boro','4'],
    ['64','33Kv Ungwan Dosa','6'],['65','33Kv University (Dedicated)','1'],
    ['66','33Kv Untl (Dedicated)','4'],['67','33Kv Water Works','6'],
    ['68','33Kv Yabo/Shagari','7'],['69','33Kv Zaria Water Works (Dedicated)','12'],
    ['70','33Kv Zuru','11'],['71','T2A','2'],['72','T2B','2'],
    ['80','33Kv Yauri','11'],
];
$ins = $db->prepare("INSERT OR IGNORE INTO fdr33kv(fdr33kv_code,fdr33kv_name,ts_code) VALUES(?,?,?)");
foreach ($fdr33 as $r) $ins->execute($r);

// 11kV Feeders (from load.sql)
$fdr11 = [
    ['1','11Kv Kakuri','5','E','11','22'],['2','11Kv Nortex','5','C','11','22'],
    ['3','11Kv Commercial Kachia Rd','5','A','11','22'],['4','11Kv Barnawa Mkr','5','A','11','22'],
    ['5','11Kv Nassarawa Mkr','5','E','11','22'],['6','11Kv Village','44','E','1','36'],
    ['7','11Kv Barnawa Gra','44','A','1','36'],['8','11Kv High Cost','44','A','1','36'],
    ['9','11Kv Sunglass','52','C','11','28'],['10','11Kv Chelco','52','E','11','28'],
    ['11','11Kv Nocaco','52','C','11','38'],['12','11Kv Arewa Bottlers','52','C','11','38'],
    ['13','11Kv Government House Kd','41','A','22','33'],['14','11Kv Poly Road','41','A','22','33'],
    ['15','11Kv Leventis','41','A','22','33'],['16','11Kv Tudun Wada Rig','41','E','22','33'],
    ['17','11Kv Unguwan Yelwa','18','E','3','4'],['18','11Kv Federal Housing','18','C','3','12'],
    ['19','11Kv Commercial Barnawa','18','A','3','4'],['20','11Kv Gwari Avenue','18','A','3','4'],
    ['21','11Kv Costain','22','E','2','17'],['22','11Kv Teaching Hospital Dka','22','A','2','17'],
    ['23','11Kv Constitution Road','22','A','2','17'],['24','11Kv Commercial Dka','22','A','2','17'],
    ['25','11Kv Sabon Tasha','63','E','1','46'],['26','11Kv Mahuta','63','E','1','46'],
    ['27','11Kv Pama','63','E','1','46'],['28','11Kv Kurmin Mashi','1','E','6','1'],
    ['29','11Kv Isa Kaita','1','A','6','1'],['30','11Kv Nda','1','E','6','1'],
    ['31','11Kv Ahmadu Bello Way','1','A','6','1'],['32','11Kv Dankande','61','E','4','23'],
    ['33','11Kv Katabu','61','E','4','23'],['34','11Kv Fifth Chukker','61','A','4','23'],
    ['35','11Kv Jaji','61','E','4','18'],['36','11Kv Mc (Dedicated)','61','C','4','18'],
    ['37','11Kv Nta Dka','61','A','4','18'],['38','11Kv Nacb','67','A','13','6'],
    ['39','11Kv Unguwan Rimi','67','A','13','6'],['40','11Kv Malali','67','A','13','6'],
    ['41','11Kv Dawaki','67','A','13','6'],['42','11Kv Urban Shelter','67','A','13','32'],
    ['43','11Kv New Millennium City','67','A','13','32'],['44','11Kv Keke Leg','67','D','13','32'],
    ['45','11Kv Nafbase','43','A','12','35'],['46','11Kv Nasfat','43','C','12','24'],
    ['47','11Kv Statehouse','43','B','6','24'],['48','11Kv Kawo','43','E','6','24'],
    ['49','11Kv Zaria Road','43','C','4','24'],['50','11Kv Rabah Road','14','E','2','1'],
    ['51','11Kv Luggard Hall','14','A','2','24'],['52','11Kv Sabon Garin Rig','56','E','14','41'],
    ['53','11Kv Asikolaye','56','C','14','41'],['54','11Kv Hayin Rigasa','56','E','14','41'],
    ['55','11Kv Makarfi Road','56','E','14','41'],['56','11Kv Rafin Guza','64','E','6','47'],
    ['57','11Kv Legislative Quarters','64','A','6','47'],['58','11Kv Yantukwane','30','C','22','26'],
    ['59','11Kv Unguwan Muazu','30','C','14','26'],['60','11Kv Mando Road','42','E','12','34'],
    ['61','11Kv Water Resources Rig','42','A','12','34'],['62','11Kv Gra Zar','55','E','15','29'],
    ['63','11Kv Canteen','55','A','15','29'],['64','11Kv Sabon Garin Zar','55','E','15','29'],
    ['65','11Kv Wusasa','31','E','26','27'],['66','11Kv Zaria City','31','E','26','27'],
    ['67','11Kv Teaching Hospital Zar','31','E','26','27'],['68','11Kv Gaskiya','31','A','26','27'],
    ['69','11Kv Kofan Kibo','31','E','26','27'],['70','11Kv Dam','21','E','15','49'],
    ['71','11Kv Abu','21','C','15','49'],['72','11Kv Nnpc Zar','21','C','15','15'],
    ['73','11Kv Samaru','7','E','16','43'],['74','11Kv Shika','7','E','16','43'],
    ['75','11Kv Makarfi','39','E','16','30'],['76','11Kv Gra Zam','38','A','23','45'],
    ['77','11Kv Industrial Zam','38','E','23','45'],['78','11Kv Tudun Wada Zam','53','E','23','39'],
    ['79','11Kv Governmnet House Zam','53','A','23','39'],['80','11Kv Gada Biyu','49','C','24','37'],
    ['81','11Kv Fggc','49','C','24','37'],['82','11Kv Sabon Garin Zam','71','E','23','13'],
    ['83','11Kv Damba','72','E','23','13'],['84','11Kv Commercial Zam','72','A','23','13'],
    ['85','11Kv Kaduna Road','54','A','18','3'],['86','11Kv Lodge Road','54','A','18','14'],
    ['87','11Kv Commercial Sokoto','54','A','18','14'],['88','11Kv Mabera','54','E','19','14'],
    ['89','11Kv Sultan Palace','54','E','19','14'],['90','11Kv Army Barrack','54','E','19','40'],
    ['91','11Kv Waterworks Sok','54','E','19','10'],['92','11Kv Durbawa','54','E','19','10'],
    ['93','11Kv Kueppers','46','E','20','16'],['94','11Kv Diori Hammani','46','E','20','16'],
    ['95','11Kv Nta Sok','46','A','20','42'],['96','11Kv Startimes Sok','46','A','20','42'],
    ['97','11Kv Bado','17','C','21','9'],['98','11Kv Institute','17','A','21','9'],
    ['99','11Kv Town','59','E','18','3'],['100','11Kv Arkilla','59','B','18','3'],
    ['101','11Kv Industrial Sok','59','B','18','3'],['102','11Kv Gwadangwaji','15','A','7','8'],
    ['103','11Kv Tudun Wada Kbi','15','E','7','8'],['104','11Kv Gra Kbi','16','A','7','7'],
    ['105','11Kv Kara','16','C','7','7'],['106','11Kv Bulasa','16','E','7','31'],
    ['107','11Kv Commercial Kebbi','16','A','7','7'],['108','11Kv Nassarawa Kbi','16','E','7','31'],
    ['109','11Kv Gra Jega','24','E','8','19'],['110','11Kv Sabon Garin Jega','24','E','8','19'],
    ['111','11Kv Gra Argungu','6','E','9','25'],['112','11Kv Argungu City','6','E','9','11'],
    ['113','11Kv Mera','6','E','9','11'],['114','11Kv Kanta','6','E','9','25'],
    ['115','11Kv Sarkin Fada','58','E','21','44'],['116','11Kv Illela Road','58','E','21','44'],
    ['117','11Kv Bunza','10','E','8','5'],['118','11Kv Yelwa','80','E','10','48'],
    ['119','11Kv Yauri','80','C','10','48'],['120','11Kv Barracks Zuru','70','E','10','50'],
    ['121','11Kv Rikoto/Zuru','70','E','10','50'],['122','11Kv Garage Kafanchan','27','B','5','20'],
    ['123','11Kv Bank Kafanchan','27','C','5','20'],['124','11Kv Kafanchan (Township)','27','C','5','20'],
    ['125','11Kv Kagoro','27','E','5','21'],['126','11Kv Manchok','27','E','5','21'],
    ['127','11Kv Birnin Gwari','9','C','12','2'],
];
$ins = $db->prepare("INSERT INTO fdr11kv(fdr11kv_code,fdr11kv_name,fdr33kv_code,band,ao_code,iss_code) VALUES(?,?,?,?,?,?)");
foreach ($fdr11 as $r) $ins->execute($r);

// Staff Details — key accounts (admin, operator, analyst, manager, viewer, dispatch + a few UL1)
$hash = '$2y$10$A9j2vFzDZXLmIU6JGdwoM.ZNFdCph2g9Nfdz2U98LDq71X0Vah52G';
$staff = [
    // Special / admin accounts
    ['SysAdmin','222222','0','08012345678','Admin','100000','0','star.admin.user33kv@example.com',$hash,null,'Yes','UL6'],
    ['TheAnalyst','444444','0','08012345678','Analyst','100000','0','star.e.user33kv@example.com',$hash,null,'Yes','UL3'],
    ['TheManager','555555','0','08012345678','Manager','100000','0','star.f.user33kv@example.com',$hash,null,'Yes','UL4'],
    ['TheViewer','333333','0','08012345678','Viewer','100000','0','star.g.user33kv@example.com',$hash,null,'Yes','UL5'],
    ['Star A User 33kv','666666','0','08012345678','HDSO','100000','0','star.a.user33kv@example.com',$hash,null,'Yes','UL2'],
    ['Star B User 33kv','777777','0','08012345678','HDSO','100000','0','star.b.user33kv@example.com',$hash,null,'Yes','UL2'],
    ['Star C User 33kv','888888','0','08012345678','HDSO','100000','0','star.c.user33kv@example.com',$hash,null,'Yes','UL2'],
    ['Star D User 33kv','999999','0','08012345678','HDSO','100000','0','star.d.user33kv@example.com',$hash,null,'Yes','UL2'],
    ['Lead Dispatch','121212','0','0800000000','TL','100000','0','dispatchlead@example.com',$hash,null,'Yes','UL8'],
    // Sample UL1 operators
    ['Murtala Muhammad Bulama','105737','17','08012345678','UL1','100000','22','murtala.bulama@example.com',$hash,null,'Yes','UL1'],
    ['Lawal Garba Madaki','100901','17','08012345678','UL1','100000','22','lawal.madaki@example.com',$hash,null,'Yes','UL1'],
    ['Elizabeth Sunday Daniel','105421','36','08012345678','UL1','100000','44','elizabeth.sunday@example.com',$hash,null,'Yes','UL1'],
    ['Daniel Buhari Danjuma','744737','46','08012345678','DSO','100000','63','daniel.buhari@example.com',$hash,null,'Yes','UL1'],
    ['Sika Comfort Aribi','100611','46','08012345678','UL1','100000','63','sika.aribi@example.com',$hash,null,'Yes','UL1'],
    ['Bitrus Ahmadu','723954','46','08012345678','DSO','100000','63','bitrus.ahmadu@example.com',$hash,null,'Yes','UL1'],
    ['Aliyu Mohammed','744695','6','08012345678','DSO','100000','46','aliyu.mohammed@example.com',$hash,null,'Yes','UL1'],
];
$ins = $db->prepare("INSERT INTO staff_details(staff_name,payroll_id,iss_code,phone,staff_level,sv_code,assigned_33kv_code,email,password_hash,last_login,is_active,role) VALUES(?,?,?,?,?,?,?,?,?,?,?,?)");
foreach ($staff as $r) $ins->execute($r);

// Complaint seed
$db->exec("INSERT INTO complaint_log(id,complaint_ref,feeder_code,complaint_type,complaint_source,affected_area,customer_phone,customer_name,complaint_details,fault_location,priority,status,logged_by,logged_at) VALUES
    (1,'CMP-20260121-299587','24','TRANSFORMER_FAULT','FIELD_PATROL','bvvb v v v','08011252144','nmvhjvjvjhvhjv','b b zn zzn m j sc h z x m cs','bv vvmv','MEDIUM','PENDING','105737','2026-01-21 17:56:39'),
    (2,'CMP-20260126-8CCEFB','7','LOW_VOLTAGE','CUSTOMER_CALL','jkbhjhb','','jkbub','bhjvhvhvbbkb','jbjkbjbjb','MEDIUM','PENDING','105421','2026-01-26 01:26:34')
");

echo "Seed data inserted.\n";
echo "\nDone! Database created at: $dbFile\n";
echo "You can now run the application using: php -S localhost:8000 -t public\n";
