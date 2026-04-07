<?php
/**
 * Name: Muskan Sayyed                    Date: 30 Mar 2026
 * ---------------------------------------------------------
 * AACA Compliance – Pending Closure Report
 * ---------------------------------------------------------
 * Queries RMDLINRV (Client) and RMMNCFRV (Firm) AS400/DB2
 * tables for records under review code 'R', computes
 * New vs Total, emails a summary, and renders the dashboard.
 * ---------------------------------------------------------------
 */

// ============================================================
// CONFIGURATION  — edit these values for your environment
// ============================================================

// AS400 / IBM i DB2 connection
define('AS400_HOST',    '192.168.21.8');     // AS400 hostname or IP
define('AS400_LIB',     'AACALIB');          // Default library/schema
define('AS400_USER',    'TESTADMIN');        // AS400 user profile
define('AS400_PASS',    'x1ns8@p5d7');       // AS400 password

// AS400 DSN — choose the option matching your server OS:
//
//   Option A: iSeries Access ODBC Driver (Windows)
//     DRIVER={iSeries Access ODBC Driver}
//
//   Option B: unixODBC + IBM i Access ODBC Driver (Linux)
//     DRIVER={IBM i Access ODBC Driver}
//
//   Option C: Named DSN pre-configured in odbc.ini
//     DSN=MY_AS400_DSN;
//
/*define('AS400_DSN',   // ← use a named DSN for simplicity and better driver compatibility
    'DRIVER={iSeries Access ODBC Driver};'   // ← swap driver name for Linux
  . 'SYSTEM=' . AS400_HOST . ';'
  . 'DBQ='    . AS400_LIB  . ';'
  . 'TRANSLATE=1;'                            // auto-translate EBCDIC → ASCII
  . 'CCSID=37;'                               // AS400 default EBCDIC code page
);*/

define('AS400_DSN',
    'DRIVER={IBM i Access ODBC Driver};'    
  . 'SYSTEM='     . AS400_HOST . ';'
  . 'DBQ='        . AS400_LIB  . ';'
  . 'UID='        . AS400_USER . ';'
  . 'PWD='        . AS400_PASS . ';'
  . 'TRANSLATE=1;'                           // auto-translate EBCDIC → ASCII
  . 'CCSID=1208;'                            // Use UTF-8 (not 37) for web apps
  . 'LANGUAGEID=ENU;'
  . 'NAMING=0;'                              // 0=SQL naming, 1=System naming
);


// Table names (AS400 physical files inside AS400_LIB)
define('CLIENT_TABLE', 'RMDLINRV');   // Client closure requests
define('FIRM_TABLE',   'RMMNCFRV');   // Firm closure attempts
define('REVIEW_CODE',  'R');          // Status code for researching closure
define('REVIEW_CODES',  '');          // Accounts with blank RVCODE

// Email settings
define('MAIL_TO',        'muskansayyad45@gmail.com');
define('MAIL_FROM',      'muskan.sayyed.tech@gmail.com');
define('MAIL_FROM_NAME', 'AACA Report Engine');
define('SMTP_HOST',      'smtp.aacanet.org');
define('SMTP_PORT',      587);
define('SMTP_USER',      'reportengine@aacanet.org');
define('SMTP_PASS',      'smtp_password');
define('SMTP_SECURE',    'tls');

// ============================================================
// HELPERS
// ============================================================

/**
 * Return an AS400 ODBC connection resource.
 * Uses raw odbc_connect() — more reliable than PDO_ODBC with IBM i.
 *
 * @return resource
 * @throws RuntimeException on connection failure
 */
function get_db()
{
    $conn = @odbc_connect(AS400_DSN, AS400_USER, AS400_PASS);
    if (!$conn) {
        throw new RuntimeException(
            'AS400 ODBC connection failed: ' . odbc_errormsg()
        );
    }
    odbc_autocommit($conn, true);
    return $conn;
}

/**
 * Query Client Closure Requests from RMDLINRV on AS400.
 *
 * NEW_COUNT   = rows whose TRDATE = MAX(TRDATE) among all 'R' rows
 * TOTAL_COUNT = all rows where RVCODE = 'R'
 *
 * @return array{new: int, total: int}
 */
function query_client_counts($conn): array
{
    $lib   = AS400_LIB;
    $table = CLIENT_TABLE;
    $code  = REVIEW_CODE;

    $sql = "
        SELECT
            SUM(CASE
                    WHEN TRDATE = (
                        SELECT MAX(TRDATE)
                        FROM {$lib}.{$table}
                        WHERE RVCODE = ?
                    ) THEN 1 ELSE 0
                END) AS NEW_COUNT,
            COUNT(*)  AS TOTAL_COUNT
        FROM {$lib}.{$table}
        WHERE RVCODE = ?
    ";

    $stmt = odbc_prepare($conn, $sql);
    if (!$stmt) {
        throw new RuntimeException('AS400 prepare failed (client): ' . odbc_errormsg($conn));
    }
    if (!odbc_execute($stmt, [$code, $code])) {
        throw new RuntimeException('AS400 execute failed (client): ' . odbc_errormsg($conn));
    }

    $row = odbc_fetch_array($stmt);
    odbc_free_result($stmt);

    return [
        'new'   => (int)($row['NEW_COUNT']   ?? 0),
        'total' => (int)($row['TOTAL_COUNT'] ?? 0),
    ];
}

/**
 * Query Firm Closure Attempts from RMMNCFRV on AS400.
 *
 * @return array{new: int, total: int}
 */
function query_firm_counts($conn): array
{
    $lib   = AS400_LIB;
    $table = FIRM_TABLE;
    $code  = REVIEW_CODE;
    $codes  = REVIEW_CODES;

    $sql = "
        SELECT
            SUM(CASE
                    WHEN TRDATE = (
                        SELECT MAX(TRDATE)
                        FROM {$lib}.{$table}
                        WHERE RVCODE = ? OR RVCODE = ?
                    ) THEN 1 ELSE 0
                END) AS NEW_COUNT,
            COUNT(*)  AS TOTAL_COUNT
        FROM {$lib}.{$table}
       
    ";

    $stmt = odbc_prepare($conn, $sql);
    if (!$stmt) {
        throw new RuntimeException('AS400 prepare failed (firm): ' . odbc_errormsg($conn));
    }
    if (!odbc_execute($stmt, [$code, $codes])) {
        throw new RuntimeException('AS400 execute failed (firm): ' . odbc_errormsg($conn));
    }

    $row = odbc_fetch_array($stmt);
    odbc_free_result($stmt);

    return [
        'new'   => (int)($row['NEW_COUNT']   ?? 0),
        'total' => (int)($row['TOTAL_COUNT'] ?? 0),
    ];
}

/**
 * Send summary email.
 * Testing mode active — writes to email_test.txt.
 * Comment out the testing block and use mail() / PHPMailer for production.
 */
function send_report_email(
    int $clientNew, int $clientTotal,
    int $firmNew,   int $firmTotal,
    DateTime $now
): bool {
    $dateLabel = $now->format('F j, Y \a\t H:i:s');
    $subject   = 'Closure Report Summary – ' . $now->format('M d, Y');

    $body = <<<TEXT
AACA Compliance Team,

Please find below the pending closure summary as of {$dateLabel}.

----------------------------------------------------------
CLIENT CLOSURE REQUESTS  (DB: rmdlinrv | code: R)
  New   = {$clientNew}
  Total = {$clientTotal}

FIRM CLOSURE ATTEMPTS  (DB: rmmncfrv | code: R)
  New   = {$firmNew}
  Total = {$firmTotal}
----------------------------------------------------------

This is an automated report generated by the AACA Compliance Report Engine.
Please do not reply to this message.
TEXT;

    // ── TESTING MODE — writes to file instead of sending ────
    $testFile   = __DIR__ . '/email_test.txt';
    $testOutput = implode("\n", [
        '============================================================',
        ' EMAIL CAPTURED: ' . $now->format('Y-m-d H:i:s'),
        '============================================================',
        'TO      : ' . MAIL_TO,
        'FROM    : ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . '>',
        'SUBJECT : ' . $subject,
        '------------------------------------------------------------',
        $body, '',
    ]);
    return (file_put_contents($testFile, $testOutput, FILE_APPEND) !== false);
    // ── END TESTING MODE ─────────────────────────────────────

    // ── Production mail() (uncomment when ready) ─────────────
    // $headers  = "From: " . MAIL_FROM_NAME . " <" . MAIL_FROM . ">\r\n";
    // $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    // $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    // return mail(MAIL_TO, $subject, $body, $headers);

    /* ── PHPMailer SMTP (composer require phpmailer/phpmailer) ──
    require 'vendor/autoload.php';
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = SMTP_HOST;
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_USER;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = SMTP_SECURE;
    $mail->Port       = SMTP_PORT;
    $mail->setFrom(MAIL_FROM, MAIL_FROM_NAME);
    $mail->addAddress(MAIL_TO);
    $mail->Subject = $subject;
    $mail->Body    = $body;
    return $mail->send();
    ── end PHPMailer ── */
}

// ============================================================
// MAIN LOGIC
// ============================================================

$now       = new DateTime();
$runError  = null;
$emailSent = false;
$log       = [];

$clientNew = $clientTotal = $firmNew = $firmTotal = null;

$doRun = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['run_report']));

if ($doRun) {
    $conn = null;
    try {
        $log[] = ['type' => 'info',
                  'msg'  => 'Connecting to AS400 (' . AS400_HOST . ' / lib: ' . AS400_LIB . ')...'];

        $conn  = get_db();
        $log[] = ['type' => 'ok', 'msg' => 'AS400 ODBC connection established.'];

        $log[] = ['type' => 'info',
                  'msg'  => 'Querying ' . AS400_LIB . '.' . CLIENT_TABLE . ' (RVCODE=' . REVIEW_CODE . ')...'];
        $clientCounts = query_client_counts($conn);
        $clientNew    = $clientCounts['new'];
        $clientTotal  = $clientCounts['total'];
        $log[] = ['type' => 'ok', 'msg' => "Client query complete. New={$clientNew} Total={$clientTotal}"];

        $log[] = ['type' => 'info',
                  'msg'  => 'Querying ' . AS400_LIB . '.' . FIRM_TABLE . ' (RVCODE=' . REVIEW_CODE . ')...'];
        $firmCounts = query_firm_counts($conn);
        $firmNew    = $firmCounts['new'];
        $firmTotal  = $firmCounts['total'];
        $log[] = ['type' => 'ok', 'msg' => "Firm query complete. New={$firmNew} Total={$firmTotal}"];

        $log[] = ['type' => 'info', 'msg' => 'Dispatching email to ' . MAIL_TO . '...'];
        $emailSent = send_report_email($clientNew, $clientTotal, $firmNew, $firmTotal, $now);
        $log[] = $emailSent
            ? ['type' => 'ok',   'msg' => '✓ Email dispatched successfully.']
            : ['type' => 'warn', 'msg' => 'Email dispatch returned false — check mail config.'];

        $log[] = ['type' => 'ok', 'msg' => 'Report complete: ' . $now->format('Y-m-d H:i:s')];

    } catch (Throwable $e) {
        $runError = $e->getMessage();
        $log[]    = ['type' => 'err', 'msg' => 'ERROR: ' . $runError];
    } finally {
        if ($conn) { odbc_close($conn); }
    }
}

// ============================================================
// HTML HELPERS
// ============================================================
function h(mixed $v): string   { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function dash(mixed $v): string { return $v !== null ? h($v) : '—'; }

$nowLabel       = $now->format('M d, Y H:i:s');
$emailDateLabel = $now->format('F j, Y \a\t H:i:s');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AACA Compliance – Pending Closure Report</title>
<link href="https://fonts.googleapis.com/css2?family=Nunito+Sans:wght@300;400;600;700&family=Nunito:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>
  /* ── Pipeway-matched design tokens ── */
  :root {
    --bg: #f4f6fb;
    --surface: #ffffff;
    --border: #e2e8f0;
    --border-bright: #cbd5e1;

    /* Pipeway navy/blue palette */
    --navy: #1a2e4a;
    --navy-dark: #14243c;
    --navy-light: #243855;
    --blue: #1565c0;
    --blue-hover: #1251a3;
    --blue-light: #e8f0fc;
    --blue-mid: #bbcff5;

    /* Status colors */
    --green: #1b7a45;
    --green-bg: #edf7f2;
    --green-border: #a8dfc0;
    --red: #c0392b;
    --red-bg: #fdf2f2;
    --red-border: #f5a9a0;
    --yellow: #b45309;
    --yellow-bg: #fffbeb;
    --yellow-border: #fcd34d;

    /* Text */
    --text: #0f1e35;
    --text-dim: #334e6e;
    --text-muted: #7a90a8;
    --text-label: #526480;

    /* Sidebar */
    --sidebar-bg: #1a2e4a;
    --sidebar-text: #c8d8eb;
    --sidebar-text-muted: #7a9ab8;
    --sidebar-active-bg: rgba(255,255,255,0.10);
    --sidebar-active-border: #4a90d9;
    --sidebar-hover-bg: rgba(255,255,255,0.06);
    --sidebar-label: #4a7aaa;

    --sans: 'Nunito Sans', sans-serif;
    --heading: 'Nunito', sans-serif;
    --radius: 10px;
    --radius-sm: 7px;
    --shadow-sm: 0 1px 3px rgba(15,30,53,.07), 0 1px 2px rgba(15,30,53,.05);
    --shadow: 0 2px 8px rgba(15,30,53,.09), 0 1px 3px rgba(15,30,53,.06);
  }

  *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }

  body {
    background: var(--bg);
    color: var(--text);
    font-family: var(--sans);
    font-size: 14px;
    min-height: 100vh;
    -webkit-font-smoothing: antialiased;
  }

  /* ── Top Bar ── matches Pipeway's "AACA | Compliance Portal / Report Engine" bar */
  .topbar {
    background: var(--navy);
    height: 54px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    position: sticky;
    top: 0;
    z-index: 100;
    box-shadow: 0 2px 8px rgba(10,20,40,.20);
  }
  .topbar-left { display: flex; align-items: center; gap: 14px; }
  .logo-pill {
    background: var(--blue);
    color: #fff;
    font-family: var(--heading);
    font-size: 13px;
    font-weight: 800;
    padding: 5px 13px;
    border-radius: 7px;
    letter-spacing: .5px;
  }
  .topbar-title {
    font-size: 13px;
    color: rgba(200,216,235,.75);
    font-weight: 400;
    letter-spacing: .1px;
  }
  .topbar-title strong { color: #e0ecff; font-weight: 600; }
  .topbar-right {
    display: flex;
    align-items: center;
    gap: 20px;
    font-size: 12.5px;
    color: var(--sidebar-text-muted);
  }
  .topbar-right .sep { opacity: .3; }
  .status-indicator {
    display: flex;
    align-items: center;
    gap: 6px;
    color: #6fcf97;
    font-size: 12.5px;
    font-weight: 600;
  }
  .status-dot {
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #6fcf97;
    box-shadow: 0 0 6px rgba(111,207,151,.6);
    animation: blink 2.5s infinite;
  }
  @keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

  /* ── Layout Shell ── */
  .shell {
    display: grid;
    grid-template-columns: 230px 1fr;
    min-height: calc(100vh - 54px);
  }

  /* ── Sidebar ── matches Pipeway's dark navy sidebar */
  .sidebar {
    background: var(--sidebar-bg);
    padding: 20px 0 24px;
    border-right: 1px solid rgba(255,255,255,.05);
  }
  .sidebar-section { margin-bottom: 26px; }
  .sidebar-label {
    font-size: 10px;
    font-weight: 700;
    letter-spacing: 1.4px;
    text-transform: uppercase;
    color: var(--sidebar-label);
    padding: 0 18px;
    margin-bottom: 6px;
  }
  .sidebar-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 18px;
    font-size: 13.5px;
    color: var(--sidebar-text);
    cursor: pointer;
    text-decoration: none;
    font-weight: 500;
    border-left: 2.5px solid transparent;
    transition: background .15s, color .15s;
  }
  .sidebar-item:hover {
    background: var(--sidebar-hover-bg);
    color: #e8f2ff;
  }
  .sidebar-item.active {
    background: var(--sidebar-active-bg);
    color: #e8f2ff;
    border-left-color: var(--sidebar-active-border);
    font-weight: 700;
  }
  .sidebar-icon {
    width: 18px;
    text-align: center;
    font-size: 15px;
    opacity: .85;
    flex-shrink: 0;
  }

  /* ── Main Content ── */
  .content {
    padding: 28px 36px 40px;
    overflow-y: auto;
    animation: fadeUp .35s ease;
  }
  @keyframes fadeUp {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: none; }
  }

  /* ── Breadcrumb ── matches Pipeway "Home » Administration » Control" */
  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 6px;
    font-size: 12px;
    color: var(--text-muted);
    margin-bottom: 18px;
  }
  .breadcrumb a { color: var(--text-muted); text-decoration: none; }
  .breadcrumb a:hover { color: var(--blue); }
  .breadcrumb .sep { color: var(--border-bright); font-size: 13px; }
  .breadcrumb .current { color: var(--text-dim); font-weight: 600; }

  /* ── Page Header ── */
  .page-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    flex-wrap: wrap;
    gap: 12px;
  }
  .page-header h1 {
    font-family: var(--heading);
    font-size: 22px;
    font-weight: 800;
    color: var(--text);
    letter-spacing: -.3px;
  }

  /* ── Run Button ── matches Pipeway's dark navy button */
  .run-btn {
    display: flex;
    align-items: center;
    gap: 9px;
    background: var(--navy);
    color: #fff;
    border: none;
    border-radius: var(--radius-sm);
    padding: 10px 22px;
    font-family: var(--heading);
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    letter-spacing: .3px;
    transition: background .2s, transform .1s, box-shadow .2s;
    box-shadow: 0 2px 8px rgba(20,36,60,.25);
  }
  .run-btn:hover {
    background: var(--navy-light);
    transform: translateY(-1px);
    box-shadow: 0 4px 14px rgba(20,36,60,.30);
  }
  .run-btn:active { transform: translateY(0); }
  .run-btn:disabled { opacity: .5; cursor: not-allowed; }
  .run-btn-icon { font-size: 11px; }

  /* ── Alert Strips ── */
  .alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 13px 18px;
    border-radius: var(--radius-sm);
    font-size: 13.5px;
    font-weight: 600;
    margin-bottom: 22px;
    border: 1.5px solid;
  }
  .alert-icon {
    width: 22px; height: 22px;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 11px;
    flex-shrink: 0;
    font-style: normal;
  }
  .alert-ok   { background: var(--green-bg);  border-color: var(--green-border); color: var(--green); }
  .alert-ok   .alert-icon { background: var(--green); color: #fff; }
  .alert-err  { background: var(--red-bg);    border-color: var(--red-border);   color: var(--red); }
  .alert-err  .alert-icon { background: var(--red);   color: #fff; }
  .alert-warn { background: var(--yellow-bg); border-color: var(--yellow-border); color: var(--yellow); }
  .alert-warn .alert-icon { background: var(--yellow); color: #fff; }

  /* ── Section Divider ── matches Pipeway table section headers */
  .section-title {
    font-size: 11px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1.3px;
    color: var(--text-label);
    margin-bottom: 14px;
    display: flex;
    align-items: center;
    gap: 10px;
  }
  .section-title::after {
    content: '';
    flex: 1;
    height: 1px;
    background: var(--border);
  }

  /* ── Summary Cards ── */
  .cards-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 32px;
  }
  .card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 22px 24px;
    box-shadow: var(--shadow-sm);
    transition: box-shadow .2s, border-color .2s;
  }
  .card:hover {
    border-color: var(--blue-mid);
    box-shadow: var(--shadow);
  }
  .card-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
  }
  .card-title {
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-dim);
  }

  /* Badge styles matching Pipeway's pill tags */
  .card-badge {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 11px;
    border-radius: 20px;
    letter-spacing: .5px;
  }
  .badge-client {
    background: var(--blue-light);
    color: var(--blue);
    border: 1px solid var(--blue-mid);
  }
  .badge-firm {
    background: var(--red-bg);
    color: var(--red);
    border: 1px solid var(--red-border);
  }

  .db-tag-row {
    font-size: 13px;
    color: var(--text-muted);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 8px;
  }
  .db-tag {
    background: var(--bg);
    border: 1px solid var(--border-bright);
    border-radius: 5px;
    padding: 3px 10px;
    font-size: 12px;
    font-weight: 700;
    color: var(--text-dim);
    letter-spacing: .4px;
  }

  /* Metric boxes inside cards */
  .metrics {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
  }
  .metric-box {
    background: var(--bg);
    border: 1px solid var(--border);
    border-radius: var(--radius-sm);
    padding: 14px 16px;
  }
  .metric-label {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 1px;
    color: var(--text-muted);
    margin-bottom: 8px;
  }
  .metric-value {
    font-family: var(--heading);
    font-size: 38px;
    font-weight: 800;
    line-height: 1;
    letter-spacing: -1.5px;
  }
  .metric-value.new-val   { color: #b45309; }   /* amber — matches Pipeway's "14" */
  .metric-value.total-val { color: var(--navy); } /* navy — matches Pipeway's "587" */
  .metric-sub {
    font-size: 11px;
    color: var(--text-muted);
    margin-top: 6px;
  }

  /* ── Email Panel ── */
  .email-panel {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    margin-bottom: 28px;
    box-shadow: var(--shadow-sm);
  }
  .email-toolbar {
    background: var(--bg);
    border-bottom: 1px solid var(--border);
    padding: 11px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  .email-toolbar-left {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12.5px;
    color: var(--text-dim);
    font-weight: 600;
  }
  .email-status {
    font-size: 12px;
    color: var(--green);
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
  }
  .email-field {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 22px;
    border-bottom: 1px solid var(--border);
    font-size: 12.5px;
  }
  .email-field-label {
    color: var(--text-muted);
    width: 52px;
    flex-shrink: 0;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: .8px;
  }
  .email-field-value { color: var(--text-dim); }
  .email-content {
    padding: 22px 24px;
    font-size: 13px;
    line-height: 1.85;
    color: var(--text-dim);
  }
  .em-bold  { color: var(--text); font-weight: 700; }
  .em-new   { color: #b45309; font-weight: 700; }
  .em-total { color: var(--navy); font-weight: 700; }
  .em-sep   { border: none; border-top: 1px solid var(--border); margin: 14px 0; }
  .em-sig   { color: var(--text-muted); font-size: 11.5px; }

  /* ── Log Panel ── */
  .log-panel {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    padding: 16px 20px;
    font-size: 12.5px;
    line-height: 2;
    margin-bottom: 28px;
    min-height: 70px;
    box-shadow: var(--shadow-sm);
  }
  .log-line  { display: flex; gap: 16px; }
  .log-time  { color: var(--text-muted); flex-shrink: 0; }
  .log-ok    { color: var(--green); font-weight: 600; }
  .log-warn  { color: var(--yellow); font-weight: 600; }
  .log-err   { color: var(--red); font-weight: 600; }
  .log-info  { color: var(--blue); }
  .log-empty { color: var(--text-muted); }

  /* ── Config Table ── matches Pipeway's data table style exactly */
  .config-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    box-shadow: var(--shadow-sm);
  }
  .config-table thead {
    background: var(--navy);  /* Pipeway uses dark navy header */
  }
  .config-table th {
    color: #c8d8eb;
    font-size: 12px;
    font-weight: 700;
    letter-spacing: .5px;
    padding: 13px 18px;
    text-align: left;
    border-bottom: none;
  }
  .config-table td {
    padding: 12px 18px;
    border-bottom: 1px solid var(--border);
    color: var(--text-dim);
    vertical-align: middle;
  }
  .config-table tr:last-child td { border-bottom: none; }
  .config-table tbody tr:hover td { background: var(--blue-light); }
  .config-table code {
    background: #eaf4ee;
    color: var(--green);
    padding: 2px 8px;
    border-radius: 4px;
    font-size: 11.5px;
    font-weight: 700;
  }

  /* Status tags matching Pipeway style */
  .tag {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 20px;
    font-weight: 700;
    letter-spacing: .3px;
  }
  .tag-active  { background: var(--green-bg);  color: var(--green);  border: 1px solid var(--green-border); }
  .tag-pending { background: var(--yellow-bg); color: var(--yellow); border: 1px solid var(--yellow-border); }
  .tag-issue   { background: var(--red-bg);    color: var(--red);    border: 1px solid var(--red-border); }

  /* ── Pagination / Footer ── */
  .footer {
    margin-top: 10px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 11.5px;
    color: var(--text-muted);
    padding-top: 22px;
    border-top: 1.5px solid var(--border);
  }

  /* ── Responsive ── */
  @media (max-width: 800px) {
    .shell { grid-template-columns: 1fr; }
    .sidebar { display: none; }
    .cards-row { grid-template-columns: 1fr; }
    .content { padding: 18px 16px; }
  }
</style>
</head>
<body>

<!-- ══════════════════════════════════════
     TOP BAR  — matches Pipeway nav bar
═══════════════════════════════════════ -->
<div class="topbar">
  <div class="topbar-left">
    <div class="logo-pill">AACA</div>
    <span class="topbar-title">
      Compliance Portal &nbsp;<strong style="opacity:.4">/</strong>&nbsp; Report Engine
    </span>
  </div>
  <div class="topbar-right">
    <span class="status-indicator">
      <span class="status-dot"></span> AS400 Ready
    </span>
    <span class="sep">|</span>
    <span>User: <?= h(get_current_user() ?: 'compliance_admin') ?></span>
    <span class="sep">|</span>
    <span><?= h($now->format('H:i:s')) ?></span>
  </div>
</div>

<div class="shell">

  <!-- ══════════════════════════════════════
       SIDEBAR — matches Pipeway dark navy sidebar
  ═══════════════════════════════════════ -->
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-label">Reports</div>
      <a class="sidebar-item active" href="#">
        <span class="sidebar-icon">📋</span> Pending Closures
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">👤</span> Client Recalls
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">🏢</span> Firm Attempts
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">📨</span> Email Dispatch
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">Databases</div>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">🗄️</span> RMDLINRV
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">🗄️</span> RMMNCFRV
      </a>
    </div>

    <div class="sidebar-section">
      <div class="sidebar-label">System</div>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">⚙️</span> Process Config
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">📁</span> Pipeway Downloads
      </a>
      <a class="sidebar-item" href="#">
        <span class="sidebar-icon">🔍</span> Audit Log
      </a>
    </div>
  </nav>

  <!-- ══════════════════════════════════════
       MAIN CONTENT
  ═══════════════════════════════════════ -->
  <main class="content">

    <!-- Breadcrumb — matches "Home » Administration » Control" style -->
    <div class="breadcrumb">
      <a href="#">Home</a>
      <span class="sep">»</span>
      <a href="#">Compliance</a>
      <span class="sep">»</span>
      <a href="#">Reports</a>
      <span class="sep">»</span>
      <span class="current">Closure Report</span>
    </div>

    <!-- Page Header -->
    <div class="page-header">
      <h1>Closure Report Summary</h1>
      <form method="post" action="">
        <button type="submit" name="run_report" class="run-btn">
          <span class="run-btn-icon">▶</span> Run Report
        </button>
      </form>
    </div>

    <!-- ── Alerts ── -->
    <?php if ($doRun && $runError): ?>
    <div class="alert alert-err">
      <i class="alert-icon">✖</i>
      <?= h($runError) ?>
    </div>
    <?php elseif ($doRun && $emailSent): ?>
    <div class="alert alert-ok">
      <i class="alert-icon">✓</i>
      Report executed and email sent to <?= h(MAIL_TO) ?> at <?= h($nowLabel) ?>
    </div>
    <?php elseif ($doRun): ?>
    <div class="alert alert-warn">
      <i class="alert-icon">⚠</i>
      Report ran but email dispatch may have failed. Check mail server config.
    </div>
    <?php endif; ?>

    <!-- ══════════════════════════════════════
         SUMMARY CARDS
    ═══════════════════════════════════════ -->
    <div class="section-title">Summary</div>
    <div class="cards-row">

      <!-- Client Closure Attempts -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Client Closure Attempts</span>
          <span class="card-badge badge-client">CLIENT</span>
        </div>
        <div class="db-tag-row">
          Database: <span class="db-tag"><?= h(CLIENT_TABLE) ?></span>
        </div>
        <div class="metrics">
          <div class="metric-box">
            <div class="metric-label">New</div>
            <div class="metric-value new-val"><?= dash($clientNew) ?></div>
            <div class="metric-sub">since last report</div>
          </div>
          <div class="metric-box">
            <div class="metric-label">Total</div>
            <div class="metric-value total-val"><?= dash($clientTotal) ?></div>
            <div class="metric-sub">all pending (<?= h(REVIEW_CODE) ?>)</div>
          </div>
        </div>
      </div>

      <!-- Firm Closure Attempts -->
      <div class="card">
        <div class="card-header">
          <span class="card-title">Firm Closure Attempts</span>
          <span class="card-badge badge-firm">FIRM</span>
        </div>
        <div class="db-tag-row">
          Database: <span class="db-tag"><?= h(FIRM_TABLE) ?></span>
        </div>
        <div class="metrics">
          <div class="metric-box">
            <div class="metric-label">New</div>
            <div class="metric-value new-val"><?= dash($firmNew) ?></div>
            <div class="metric-sub">since last report</div>
          </div>
          <div class="metric-box">
            <div class="metric-label">Total</div>
            <div class="metric-value total-val"><?= dash($firmTotal) ?></div>
            <div class="metric-sub">all pending (<?= h(REVIEW_CODE) ?>)</div>
          </div>
        </div>
      </div>

    </div><!-- /.cards-row -->

    <!-- ══════════════════════════════════════
         EMAIL PREVIEW  (shown after successful run)
    ═══════════════════════════════════════ -->
    <!-- <?php if ($doRun && !$runError): ?>
    <div class="section-title">Email Preview</div>
    <div class="email-panel">
      <div class="email-toolbar">
        <div class="email-toolbar-left">📧 &nbsp; Outbound Report Draft</div>
        <div class="email-status">
          <span class="status-dot"></span>
          <?= $emailSent ? 'Sent ' . h($nowLabel) : 'Ready to dispatch' ?>
        </div>
      </div>
      <div class="email-field">
        <span class="email-field-label">To</span>
        <span class="email-field-value"><?= h(MAIL_TO) ?></span>
      </div>
      <div class="email-field">
        <span class="email-field-label">From</span>
        <span class="email-field-value"><?= h(MAIL_FROM_NAME) ?> &lt;<?= h(MAIL_FROM) ?>&gt;</span>
      </div>
      <div class="email-field">
        <span class="email-field-label">Subject</span>
        <span class="email-field-value">Closure Report Summary – <?= h($now->format('M d, Y')) ?></span>
      </div>
      <div class="email-content">
        AACA Compliance Team,<br><br>
        Please find below the pending closure summary as of
        <span class="em-bold"><?= h($emailDateLabel) ?></span>.<br>
        <hr class="em-sep">
        <span class="em-bold">CLIENT CLOSURE REQUESTS</span>
        <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">
          DB: <?= h(CLIENT_TABLE) ?> &nbsp;·&nbsp; code: <?= h(REVIEW_CODE) ?>
        </span><br>
        &nbsp;&nbsp; New = <span class="em-new"><?= dash($clientNew) ?></span>
        &nbsp;&nbsp;&nbsp; Total = <span class="em-total"><?= dash($clientTotal) ?></span>
        <br><br>
        <span class="em-bold">FIRM CLOSURE ATTEMPTS</span>
        <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">
          DB: <?= h(FIRM_TABLE) ?> &nbsp;·&nbsp; code: <?= h(REVIEW_CODE) ?>
        </span><br>
        &nbsp;&nbsp; New = <span class="em-new"><?= dash($firmNew) ?></span>
        &nbsp;&nbsp;&nbsp; Total = <span class="em-total"><?= dash($firmTotal) ?></span>
        <hr class="em-sep">
        <span class="em-sig">
          This is an automated report generated by the AACA Compliance Report Engine.<br>
          Please do not reply to this message. Contact compliance_admin for questions.
        </span>
      </div>
    </div>
    <?php endif; ?> --> 

    <!-- ══════════════════════════════════════
         PROCESS LOG  (shown after any run attempt)
    ═══════════════════════════════════════ -->
    <!-- <?php if ($doRun): ?>
    <div class="section-title">Process Log</div>
    <div class="log-panel">
      <?php if (empty($log)): ?>
        <div class="log-line">
          <span class="log-empty">Awaiting report execution — click Run Report to begin.</span>
        </div>
      <?php else: ?>
        <?php foreach ($log as $entry):
          $cls = match($entry['type']) {
            'ok'   => 'log-ok',
            'warn' => 'log-warn',
            'err'  => 'log-err',
            default => 'log-info',
          };
        ?>
        <div class="log-line">
          <span class="log-time"><?= h($now->format('H:i:s')) ?></span>
          <span class="<?= $cls ?>"><?= h($entry['msg']) ?></span>
        </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
    <?php endif; ?> --> 

    <!-- ══════════════════════════════════════
         PROCESS CONFIG TABLE — matches Pipeway's data table
    ═══════════════════════════════════════ -->
    <!-- <div class="section-title">Process Configuration</div>
    <table class="config-table">
      <thead>
        <tr>
          <th>#</th>
          <th>Process</th>
          <th>AS400 Library</th>
          <th>File / Table</th>
          <th>Review Code</th>
          <th>Destination</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td>1</td>
          <td>Client Closure Report</td>
          <td><code><?= h(AS400_LIB) ?></code></td>
          <td><code><?= h(CLIENT_TABLE) ?></code></td>
          <td><code><?= h(REVIEW_CODE) ?></code></td>
          <td><?= h(MAIL_TO) ?></td>
          <td><span class="tag tag-active">ACTIVE</span></td>
        </tr>
        <tr>
          <td>2</td>
          <td>Firm Closure Report</td>
          <td><code><?= h(AS400_LIB) ?></code></td>
          <td><code><?= h(FIRM_TABLE) ?></code></td>
          <td><code><?= h(REVIEW_CODE) ?></code></td>
          <td><?= h(MAIL_TO) ?></td>
          <td><span class="tag tag-active">ACTIVE</span></td>
        </tr>
        <tr>
          <td>3</td>
          <td>Pipeway → Downloads Routing</td>
          <td>—</td>
          <td>—</td>
          <td>—</td>
          <td>Compliance Downloads Box</td>
          <td><span class="tag tag-issue">ISSUE DETECTED</span></td>
        </tr>
        <tr>
          <td>4</td>
          <td>Client Recalls Batch (legacy)</td>
          <td><code><?= h(AS400_LIB) ?></code></td>
          <td><code><?= h(CLIENT_TABLE) ?></code></td>
          <td>—</td>
          <td>Green Screen / Batch</td>
          <td><span class="tag tag-active">ACTIVE</span></td>
        </tr>
      </tbody>
    </table> --> 

    <!-- ── Footer ── -->
    <div class="footer">
      <span>AACA Compliance Portal &nbsp;·&nbsp; Report Engine v2.1</span>
      <span>
        AS400 (<?= h(AS400_HOST) ?> / <?= h(AS400_LIB) ?>)
        &nbsp;·&nbsp;
        <?= h($nowLabel) ?>
      </span>
    </div>

  </main>
</div><!-- /.shell -->

</body>
</html>