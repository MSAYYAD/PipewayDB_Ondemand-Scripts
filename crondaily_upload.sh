#!/bin/bash

# ------------------------------------------------------------------------------------
# Author - KEANT Technologies       Date: 25 Mar 2026
# Description - This script uploads a single daily CSV file to MySQL database
#               and sends email notifications after completion
# ------------------------------------------------------------------------------------
# CHANGE LOG
# Author        Date            Description
# MS25Mar2026   25 Mar 2026     Shell script version - single file upload
# ------------------------------------------------------------------------------------

# ─── USAGE ──────────────────────────────────────────────────────────────────────────
# ./crondaily_upload.sh <FILENAME.csv>
# Example: ./crondaily_upload.sh WFAACAIMG.csv
# ────────────────────────────────────────────────────────────────────────────────────

set -euo pipefail

# ─── CONFIG ──────────────────────────────────────────────────────────────────────────
DB_HOST="localhost"
DB_USER="pipewaydb"
DB_PASS="A8EK5hKjq*CX&w"
DB_NAME="aaca_live"

CSV_DIR="/var/lib/mysql-files"
DATE_TRIGGER_FILE="$CSV_DIR/date_trigger.csv"
ERROR_LOG="crondaily_job_error_log.txt"

SMTP_HOST="smtp.office365.com"
SMTP_PORT="587"
SMTP_USER="pwadmin@aacanet.org"
SMTP_PASS="december.SURVEY.95"
MAIL_FROM="pwadmin@aacanet.org"
MAIL_FROM_NAME="CRONDAILY dbadmin script notification"

# Recipients - notification emails (start / warning / completion)
NOTIFY_RECIPIENTS=(
    "tbalcerzak@aacanet.org"
    "droberts@aacanet.org"
    "vishalkul94@gmail.com"
)

# Recipients - procedure emails (INCRMNT_UPDATE / COMPLETE_UPDATE)
PROC_RECIPIENTS=(
    "twright@aacanet.org"
    "Daniel@aacanet.org"
    "tbalcerzak@aacanet.org"
    "droberts@aacanet.org"
    "vishalkul94@gmail.com"
)

# Recipients - COMPLETE_UPDATE only (Monday, day=1) gets teasterling too
COMPLETE_RECIPIENTS=(
    "teasterling@aacanet.org"
    "twright@aacanet.org"
    "Daniel@aacanet.org"
    "tbalcerzak@aacanet.org"
    "droberts@aacanet.org"
    "vishalkul94@gmail.com"
)
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── HELPERS ─────────────────────────────────────────────────────────────────────────

log_error() {
    local message="$1"
    local timestamp
    timestamp=$(date '+%Y-%m-%d %H:%M:%S')
    echo "[$timestamp] $message" >> "$ERROR_LOG"
    echo "ERROR: $message" >&2
}

# Send an HTML email via curl + SMTP.
# Usage: send_mail <subject> <html_body> <recipient1> [recipient2] ...
send_mail() {
    local subject="$1"
    local html_body="$2"
    shift 2
    local recipients=("$@")

    # Build To: header
    local to_header
    to_header=$(IFS=", "; echo "${recipients[*]}")

    # Build RCPT TO lines for curl
    local rcpt_args=()
    for addr in "${recipients[@]}"; do
        rcpt_args+=(--mail-rcpt "$addr")
    done

    # Build raw RFC 2822 message
    local raw_message
    raw_message=$(cat <<EOF
From: $MAIL_FROM_NAME <$MAIL_FROM>
To: $to_header
Subject: $subject
MIME-Version: 1.0
Content-Type: text/html; charset=UTF-8

$html_body
<div style='margin-bottom:10px'>Don't Reply on this mail</div>
EOF
)

    if ! echo "$raw_message" | curl --silent --show-error \
        --url "smtp://$SMTP_HOST:$SMTP_PORT" \
        --ssl-reqd \
        --mail-from "$MAIL_FROM" \
        "${rcpt_args[@]}" \
        --user "$SMTP_USER:$SMTP_PASS" \
        --upload-file - 2>&1; then
        log_error "Failed to send email: $subject"
        return 1
    fi

    echo "Mail sent: $subject"
}

# Run a MySQL query and return output.
# Usage: db_query <sql>
db_query() {
    mysql --host="$DB_HOST" \
          --user="$DB_USER" \
          --password="$DB_PASS" \
          --database="$DB_NAME" \
          --batch --skip-column-names \
          -e "$1" 2>&1
}
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── ARGUMENT CHECK ───────────────────────────────────────────────────────────────────
if [[ $# -ne 1 ]]; then
    echo "Usage: $0 <FILENAME.csv>"
    echo "Example: $0 WFAACAIMG.csv"
    exit 1
fi

INPUT_FILE="$1"
MYFILE=$(basename "$INPUT_FILE" .csv).csv   # ensure it has .csv extension
FILE_PATH="$CSV_DIR/$MYFILE"
TABLENAME=$(basename "$MYFILE" .csv)        # strip .csv to get table name
MAILTABLE="$TABLENAME"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── DATE CHECK ───────────────────────────────────────────────────────────────────────
DATE_TODAY=$(date '+%d%m%Y')          # matches PHP: date('dmY')
DATE_DB=$(date '+%Y-%m-%d')           # matches PHP: date('Y-m-d')
DATETIME=$(date '+%Y-%m-%d %I:%M:%S %P')

TRIGGER_DATE=$(awk -F',' 'NR==1{print $1}' "$DATE_TRIGGER_FILE" | tr -d '\r\n')

if [[ "$DATE_TODAY" != "$TRIGGER_DATE" ]]; then
    echo "No file found for today's date (trigger=$TRIGGER_DATE, today=$DATE_TODAY)"
    exit 0
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── FILE EXISTS & DATE CHECK ────────────────────────────────────────────────────────
if [[ ! -f "$FILE_PATH" ]]; then
    echo "File not found: $FILE_PATH"
    exit 1
fi

FILE_MOD_DATE=$(date -r "$FILE_PATH" '+%Y-%m-%d')
if [[ "$FILE_MOD_DATE" != "$DATE_DB" ]]; then
    echo "File $MYFILE was not modified today ($FILE_MOD_DATE). Skipping."
    exit 0
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── SKIP EXCLUDED FILES ─────────────────────────────────────────────────────────────
# Files whose names contain TENM or HSTL9094 are excluded (tablename set to blank in PHP)
if echo "$TABLENAME" | grep -qE '(TENM|HSTL9094)'; then
    echo "File $MYFILE is in the exclusion list (TENM/HSTL9094). Skipping."
    exit 0
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── ALREADY PROCESSED CHECK ─────────────────────────────────────────────────────────
EXISTING=$(db_query "SELECT COUNT(1) FROM CRON_DAILY WHERE DATETIME='$DATE_DB' AND TABLE_NAME='$TABLENAME' LIMIT 1;")
if [[ "$EXISTING" -gt 0 ]]; then
    echo "Already processed: $TABLENAME for $DATE_DB"
    exit 0
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── SEND START EMAIL ─────────────────────────────────────────────────────────────────
send_mail \
    "Daily tables update has been started" \
    "<p>Daily tables update has been started for <strong>$MAILTABLE</strong>.</p>" \
    "${NOTIFY_RECIPIENTS[@]}"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── PRE-UPLOAD STATUS UPDATES ───────────────────────────────────────────────────────
db_query "UPDATE report_status SET status=1;"
db_query "UPDATE DATA_UPDATE_STATUS_CHECK SET UPLOAD_STATUS=1;"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── TABLE EXISTENCE CHECK ────────────────────────────────────────────────────────────
TABLE_IN_LIST=$(db_query "SELECT TABLE_NAME FROM UPLD_TBL_LST WHERE TABLE_NAME='$TABLENAME';")
if [[ "$TABLE_IN_LIST" != "$TABLENAME" ]]; then
    echo "Table $TABLENAME not found in UPLD_TBL_LST. Skipping."
    db_query "UPDATE report_status SET status=0;"
    db_query "UPDATE DATA_UPDATE_STATUS_CHECK SET UPLOAD_STATUS=0;"
    exit 1
fi

TABLE_EXISTS=$(db_query "SHOW TABLES LIKE '$TABLENAME';" | wc -l | tr -d ' ')
if [[ "$TABLE_EXISTS" -lt 1 ]]; then
    echo "Table $TABLENAME does not exist in the database. Skipping."
    db_query "UPDATE report_status SET status=0;"
    db_query "UPDATE DATA_UPDATE_STATUS_CHECK SET UPLOAD_STATUS=0;"
    exit 1
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── TABLE ROTATION (rename cycle like PHP) ──────────────────────────────────────────
echo "Rotating tables for $TABLENAME ..."
db_query "RENAME TABLE ${TABLENAME} TO ${TABLENAME}_LATEST;"
db_query "RENAME TABLE ${TABLENAME}_bk TO ${TABLENAME};"
db_query "TRUNCATE TABLE ${TABLENAME};"
db_query "RENAME TABLE ${TABLENAME}_LATEST TO ${TABLENAME}_bk;"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── LOG TO CRON_DAILY ────────────────────────────────────────────────────────────────
db_query "INSERT INTO CRON_DAILY(DATETIME,STATUS,TABLE_NAME) VALUES ('$DATE_DB','YES','$MAILTABLE');"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── BUILD LOAD DATA INFILE QUERY ────────────────────────────────────────────────────
# Certain tables use escaped-by-'\b' (binary zero) to handle special chars in fields
ESCAPED_BY_FILES=("WFAACAIMG.csv" "HSFLCLNTWF.csv" "CMBPAACAB.csv" "PLACPFHS.csv")

USE_ESCAPED=false
for ef in "${ESCAPED_BY_FILES[@]}"; do
    if [[ "$MYFILE" == "$ef" ]]; then
        USE_ESCAPED=true
        break
    fi
done

if $USE_ESCAPED; then
    LOAD_SQL="LOAD DATA INFILE '$FILE_PATH' IGNORE INTO TABLE $TABLENAME \
CHARACTER SET ASCII FIELDS TERMINATED BY ',' ESCAPED BY '\b' \
OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n' ();"
else
    LOAD_SQL="LOAD DATA INFILE '$FILE_PATH' IGNORE INTO TABLE $TABLENAME \
CHARACTER SET ASCII FIELDS TERMINATED BY ',' \
OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\r\n';"
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── EXECUTE LOAD DATA ───────────────────────────────────────────────────────────────
echo "Loading data into $TABLENAME from $FILE_PATH ..."
LOAD_OUTPUT=$(mysql --host="$DB_HOST" \
                    --user="$DB_USER" \
                    --password="$DB_PASS" \
                    --database="$DB_NAME" \
                    -e "$LOAD_SQL" 2>&1)
LOAD_EXIT=$?

if [[ $LOAD_EXIT -ne 0 ]]; then
    ERROR_MSG="$TABLENAME Table Warning: $LOAD_OUTPUT"
    log_error "$ERROR_MSG"

    send_mail \
        "$TABLENAME Table Warning" \
        "<p>$ERROR_MSG</p>" \
        "${NOTIFY_RECIPIENTS[@]}"
else
    echo "Data loaded successfully into $TABLENAME."
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── ROW COUNT + LOG ─────────────────────────────────────────────────────────────────
ROW_COUNT=$(db_query "SELECT COUNT(1) FROM ${TABLENAME};")
db_query "INSERT INTO Tbl_File_Upload_Info(FileName,Num_of_file_from_mysql,DateTime) \
VALUES ('$MAILTABLE','$ROW_COUNT','$DATETIME');"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── SEND COMPLETION EMAIL ───────────────────────────────────────────────────────────
COMPLETION_BODY="<table border='1'>
<thead><tr><th>File Name</th><th>MySQL Row Count</th><th>DateTime</th></tr></thead>
<tbody><tr><td>$MAILTABLE</td><td>$ROW_COUNT</td><td>$DATETIME</td></tr></tbody>
</table>"

send_mail "Daily tables update" "$COMPLETION_BODY" "${NOTIFY_RECIPIENTS[@]}"
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── STORED PROCEDURE CALL (day-based, like PHP) ─────────────────────────────────────
# PHP date("w"): 0=Sunday, 1=Monday, 2=Tue ... 6=Sat
# bash date +%w: same convention
DAY_OF_WEEK=$(date '+%w')

call_procedure() {
    local proc_name="$1"
    local recipients=("${@:2}")

    echo "Calling procedure $proc_name ..."
    PROC_OUTPUT=$(mysql --host="$DB_HOST" \
                        --user="$DB_USER" \
                        --password="$DB_PASS" \
                        --database="$DB_NAME" \
                        -e "CALL ${proc_name}();" 2>&1)
    PROC_EXIT=$?

    if [[ $PROC_EXIT -ne 0 ]]; then
        log_error "$proc_name failed: $PROC_OUTPUT"
        send_mail \
            "$proc_name" \
            "<p>$PROC_OUTPUT</p>" \
            "${recipients[@]}"
    else
        if [[ "$proc_name" == "INCRMNT_UPDATE" ]]; then
            send_mail \
                "Daily Data Refresh Process Completed" \
                "<p>Daily data refresh process has been completed successfully.</p>" \
                "${recipients[@]}"
        else
            send_mail \
                "COMPLETE_UPDATE" \
                "<p>COMPLETE_UPDATE Procedure has been updated successfully.</p>" \
                "${recipients[@]}"
        fi
    fi
}

# Days 2–5 (Tue–Fri): incremental update
if [[ "$DAY_OF_WEEK" -ge 2 && "$DAY_OF_WEEK" -le 5 ]]; then
    call_procedure "INCRMNT_UPDATE" "${PROC_RECIPIENTS[@]}"
fi

# Day 1 (Monday): full/complete update
if [[ "$DAY_OF_WEEK" -eq 1 ]]; then
    call_procedure "COMPLETE_UPDATE" "${COMPLETE_RECIPIENTS[@]}"
fi
# ─────────────────────────────────────────────────────────────────────────────────────


# ─── RESET STATUS FLAGS ───────────────────────────────────────────────────────────────
db_query "UPDATE report_status SET status=0;"
db_query "UPDATE DATA_UPDATE_STATUS_CHECK SET UPLOAD_STATUS=0;"

echo "Done."
