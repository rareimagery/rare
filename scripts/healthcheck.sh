#!/bin/bash
# Drupal Health Check Script
# Runs outside Drupal via docker exec to detect module conflicts, errors, and system issues.
# Scheduled via system crontab every 6 hours.

LOGFILE="/var/log/drupal-healthcheck.log"
DRUSH="docker exec rare-drupal /opt/drupal/vendor/bin/drush"
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
STATUS="OK"
ISSUES=""

# Expected custom modules that must be enabled
EXPECTED_MODULES=("rareimagery_xstore" "rareimagery_store" "rareimagery_fees" "rareimagery_x_import" "jsonapi_basic_auth")

log() {
  echo "[$TIMESTAMP] $1" | tee -a "$LOGFILE"
}

add_issue() {
  local severity="$1"
  local message="$2"
  ISSUES="${ISSUES}\n  [$severity] $message"
  if [ "$severity" = "CRITICAL" ] || [ "$severity" = "ERROR" ]; then
    STATUS="FAILED"
  elif [ "$STATUS" = "OK" ] && [ "$severity" = "WARNING" ]; then
    STATUS="WARNING"
  fi
}

log "=========================================="
log "Drupal Health Check Starting"
log "=========================================="

# -------------------------------------------------------------------
# Check 1: Container alive
# -------------------------------------------------------------------
log "Check 1: Container status..."
CONTAINER_RUNNING=$(docker inspect --format='{{.State.Running}}' rare-drupal 2>/dev/null)
if [ "$CONTAINER_RUNNING" != "true" ]; then
  add_issue "CRITICAL" "rare-drupal container is NOT running"
  log "CRITICAL: Container not running. Aborting remaining checks."
  log "Health check complete. Status: $STATUS"
  log "Issues:$ISSUES"
  exit 1
fi
log "  Container is running."

# -------------------------------------------------------------------
# Check 2: Drupal bootstrap
# -------------------------------------------------------------------
log "Check 2: Drupal bootstrap..."
DRUSH_STATUS=$($DRUSH status --format=json 2>&1)
if [ $? -ne 0 ]; then
  add_issue "CRITICAL" "Drupal cannot bootstrap: $DRUSH_STATUS"
  log "CRITICAL: Drupal bootstrap failed. Aborting remaining checks."
  log "Health check complete. Status: $STATUS"
  log "Issues:$ISSUES"
  exit 1
fi

DB_STATUS=$(echo "$DRUSH_STATUS" | jq -r '.["db-status"]' 2>/dev/null)
if [ "$DB_STATUS" != "Connected" ]; then
  add_issue "CRITICAL" "Database status: $DB_STATUS (expected: Connected)"
fi
log "  Drupal bootstrapped. DB status: $DB_STATUS"

# -------------------------------------------------------------------
# Check 3: Core requirements (errors only, severity >= 2)
# -------------------------------------------------------------------
log "Check 3: Core requirements..."
REQUIREMENTS=$($DRUSH core:requirements --severity=2 --format=json 2>&1)
REQ_EXIT=$?
if [ $REQ_EXIT -ne 0 ]; then
  # Non-zero exit means there ARE error-level requirements
  REQ_COUNT=$(echo "$REQUIREMENTS" | jq 'length' 2>/dev/null)
  if [ -n "$REQ_COUNT" ] && [ "$REQ_COUNT" -gt 0 ]; then
    add_issue "ERROR" "$REQ_COUNT error-level requirement(s) found"
    # Log each requirement title
    echo "$REQUIREMENTS" | jq -r 'to_entries[] | "    - \(.key): \(.value.value // .value.description // "see status report")"' 2>/dev/null | while read -r line; do
      log "$line"
    done
  else
    add_issue "ERROR" "core:requirements returned error (non-JSON output)"
  fi
else
  log "  No error-level requirements found."
fi

# -------------------------------------------------------------------
# Check 4: Recent watchdog errors (severity 0-3 = emergency to error)
# -------------------------------------------------------------------
log "Check 4: Recent watchdog errors..."
WATCHDOG=$($DRUSH watchdog:show --severity=3 --count=20 --format=json 2>&1)
if [ $? -eq 0 ]; then
  ERROR_COUNT=$(echo "$WATCHDOG" | jq 'length' 2>/dev/null)
  if [ -n "$ERROR_COUNT" ] && [ "$ERROR_COUNT" -gt 0 ]; then
    add_issue "WARNING" "$ERROR_COUNT recent watchdog error(s) in last 20 entries"
    echo "$WATCHDOG" | jq -r '.[] | "    - [\(.type)] \(.message[0:120])"' 2>/dev/null | head -5 | while read -r line; do
      log "$line"
    done
  else
    log "  No recent watchdog errors."
  fi
else
  log "  Could not retrieve watchdog (may not have dblog enabled)."
fi

# -------------------------------------------------------------------
# Check 5: Pending database updates
# -------------------------------------------------------------------
log "Check 5: Pending database updates..."
UPDATEDB=$($DRUSH updatedb:status --format=json 2>&1)
if [ $? -eq 0 ]; then
  UPDATE_COUNT=$(echo "$UPDATEDB" | jq 'length' 2>/dev/null)
  if [ -n "$UPDATE_COUNT" ] && [ "$UPDATE_COUNT" -gt 0 ]; then
    add_issue "WARNING" "$UPDATE_COUNT pending database update(s)"
    echo "$UPDATEDB" | jq -r '.[] | "    - \(.module): \(.description // "update pending")"' 2>/dev/null | while read -r line; do
      log "$line"
    done
  else
    log "  No pending database updates."
  fi
else
  # updatedb:status exits 0 with empty output when no updates pending
  log "  No pending database updates."
fi

# -------------------------------------------------------------------
# Check 6: Config sync status
# -------------------------------------------------------------------
log "Check 6: Config sync status..."
CONFIG_STATUS=$($DRUSH config:status --format=json 2>&1)
if [ $? -eq 0 ]; then
  CONFIG_DIFF=$(echo "$CONFIG_STATUS" | jq 'length' 2>/dev/null)
  if [ -n "$CONFIG_DIFF" ] && [ "$CONFIG_DIFF" -gt 0 ]; then
    log "  INFO: $CONFIG_DIFF config difference(s) between active and sync."
  else
    log "  Config is in sync."
  fi
else
  log "  Config sync directory not configured (common in this setup)."
fi

# -------------------------------------------------------------------
# Check 7: Expected modules enabled
# -------------------------------------------------------------------
log "Check 7: Expected modules..."
MODULE_LIST=$($DRUSH pm:list --status=enabled --type=module --format=json 2>&1)
if [ $? -eq 0 ]; then
  for mod in "${EXPECTED_MODULES[@]}"; do
    MOD_FOUND=$(echo "$MODULE_LIST" | jq -r --arg m "$mod" '.[$m] // empty' 2>/dev/null)
    if [ -z "$MOD_FOUND" ]; then
      add_issue "ERROR" "Expected module '$mod' is NOT enabled"
    else
      log "  $mod: enabled"
    fi
  done
else
  add_issue "ERROR" "Could not retrieve module list"
fi

# -------------------------------------------------------------------
# Check 8: Disk space
# -------------------------------------------------------------------
log "Check 8: Disk space..."
DISK_USAGE=$(docker exec rare-drupal df -h /var/www/html 2>/dev/null | tail -1)
if [ -n "$DISK_USAGE" ]; then
  USAGE_PCT=$(echo "$DISK_USAGE" | awk '{print $5}' | tr -d '%')
  if [ "$USAGE_PCT" -ge 90 ]; then
    add_issue "WARNING" "Disk usage at ${USAGE_PCT}% on /var/www/html"
  fi
  log "  Disk usage: ${USAGE_PCT}%"
else
  log "  Could not check disk usage."
fi

# -------------------------------------------------------------------
# Check 9: PostgreSQL connectivity
# -------------------------------------------------------------------
log "Check 9: PostgreSQL connectivity..."
PG_READY=$(docker exec rare-postgres pg_isready 2>&1)
if [ $? -eq 0 ]; then
  log "  PostgreSQL is accepting connections."
else
  add_issue "CRITICAL" "PostgreSQL is NOT accepting connections: $PG_READY"
fi

# -------------------------------------------------------------------
# Check 10: PHP-level detail checks (if script exists)
# -------------------------------------------------------------------
if docker exec rare-drupal test -f /var/www/html/healthcheck_detail.php 2>/dev/null; then
  log "Check 10: PHP detail checks..."
  DETAIL_OUTPUT=$($DRUSH php:script /var/www/html/healthcheck_detail.php 2>&1)
  DETAIL_EXIT=$?
  if [ $DETAIL_EXIT -ne 0 ]; then
    add_issue "ERROR" "PHP detail check failed"
  fi
  echo "$DETAIL_OUTPUT" | while read -r line; do
    log "  $line"
  done
else
  log "Check 10: Skipped (healthcheck_detail.php not found)."
fi

# -------------------------------------------------------------------
# Summary
# -------------------------------------------------------------------
log "------------------------------------------"
log "Health check complete. Status: $STATUS"
if [ "$STATUS" != "OK" ]; then
  log "Issues:$(echo -e "$ISSUES")"
fi
if [ "$STATUS" = "FAILED" ]; then
  exit 1
fi
if [ "$STATUS" = "OK" ]; then
  log "All checks passed."
fi
exit 0
