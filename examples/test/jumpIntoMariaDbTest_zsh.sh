#!/bin/zsh
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
EXAMPLES_DIR="$(cd "$SCRIPT_DIR/.." && pwd)"
SQL_FILE="$EXAMPLES_DIR/00__mariaDB_tables.sql"
PHP_CONFIG="$SCRIPT_DIR/test_db_account.php"

if [[ ! -f "$SQL_FILE" ]]; then
  echo "SQL file not found: $SQL_FILE" >&2
  exit 1
fi

if [[ ! -f "$PHP_CONFIG" ]]; then
  echo "PHP config not found: $PHP_CONFIG" >&2
  exit 1
fi

extract_php_value() {
  local key="$1"
  local line
  line="$(grep -E '^\$_'"${key}"'[[:space:]]*=' "$PHP_CONFIG" | head -n 1 || true)"
  if [[ -z "$line" ]]; then
    echo "Could not read $_${key} from $PHP_CONFIG" >&2
    exit 1
  fi
  echo "$line" | sed -E 's/^[^"]*"([^"]*)".*$/\1/'
}

DB_HOST="$(extract_php_value test_db_host)"
DB_USER="$(extract_php_value test_db_user)"
DB_PASSWORD="$(extract_php_value test_db_password)"
DB_NAME="$(extract_php_value test_db_name)"

if command -v mariadb >/dev/null 2>&1; then
  DB_CLIENT="mariadb"
elif command -v mysql >/dev/null 2>&1; then
  DB_CLIENT="mysql"
else
  echo "Neither 'mariadb' nor 'mysql' client found in PATH." >&2
  exit 1
fi

cd "$EXAMPLES_DIR"
echo
echo "Current directory: $(pwd)"
echo "Using database '$DB_NAME' on '$DB_HOST' with user '$DB_USER' via $DB_CLIENT"
echo

read "updateDB?Do you want to update the test database with all entries from 00__mariaDB_tables.sql? (y/n): "

if [[ "$updateDB" =~ ^[Yy]$ ]]; then
  echo
  echo "Recreating database '$DB_NAME'..."
  "$DB_CLIENT" --protocol=tcp -h "$DB_HOST" -u "$DB_USER" "-p$DB_PASSWORD" -e "DROP DATABASE IF EXISTS \`$DB_NAME\`; CREATE DATABASE \`$DB_NAME\`;"
  echo "Database recreated successfully."
  echo
  echo "Importing SQL data from $SQL_FILE ..."
  "$DB_CLIENT" --protocol=tcp -h "$DB_HOST" -u "$DB_USER" "-p$DB_PASSWORD" "$DB_NAME" < "$SQL_FILE"
  echo "Database updated successfully."
  echo
fi

echo "Starting DB client connected to '$DB_NAME'..."
echo "Use 'exit' to quit and return to command prompt."
echo
"$DB_CLIENT" --protocol=tcp -h "$DB_HOST" -u "$DB_USER" "-p$DB_PASSWORD" "$DB_NAME"
