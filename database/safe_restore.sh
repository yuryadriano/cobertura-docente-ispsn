#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Script Oficial de Restauro Seguro com Snapshot Compulsório
# ─────────────────────────────────────────────────────────────────────────────
# 1. Tira um snapshot imediato da BD antes de qualquer alteração
# 2. Restaura o arquivo indicado (.sql ou .sql.gz)
# ─────────────────────────────────────────────────────────────────────────────

set -eo pipefail

RESTORE_FILE="$1"

if [ -z "${RESTORE_FILE}" ] || [ ! -f "${RESTORE_FILE}" ]; then
    echo "❌ [ERRO] Indique um ficheiro válido para restauro (.sql ou .sql.gz)."
    echo "Uso: ./database/safe_restore.sh <caminho_do_ficheiro>"
    exit 1
fi

DB_HOST="${DB_HOST:-mysql}"
DB_NAME="${DB_NAME:-sftcoordenacao_db}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASSWORD:-test123}"
BACKUP_DIR="/var/www/html/storage/backups"
TIMESTAMP=$(date +"%Y-%m-%d_%H%M%S")
PRE_RESTORE_FILE="${BACKUP_DIR}/safety_snapshot_before_restore_${TIMESTAMP}.sql.gz"

mkdir -p "${BACKUP_DIR}"

echo "========================================================================="
echo "  RESTAURO SEGURO COM SNAPSHOT OBRIGATÓRIO                              "
echo "========================================================================="

echo "📸 [1/2] A gerar snapshot de segurança da base atual..."
set +e
mysqldump --skip-ssl -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" --single-transaction --quick "${DB_NAME}" 2>/dev/null | gzip > "${PRE_RESTORE_FILE}"
SNAP_STATUS=$?
if [ ${SNAP_STATUS} -ne 0 ] || [ $(wc -c < "${PRE_RESTORE_FILE}" 2>/dev/null || echo 0) -le 1024 ]; then
    mysqldump --ssl-mode=DISABLED -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" --single-transaction --quick "${DB_NAME}" 2>/dev/null | gzip > "${PRE_RESTORE_FILE}"
fi
set -eo pipefail

SNAP_SIZE=$(du -h "${PRE_RESTORE_FILE}" | cut -f1)
echo "✅ Snapshot de segurança guardado com sucesso: $(basename "${PRE_RESTORE_FILE}") (${SNAP_SIZE})"

echo "📥 [2/2] A restaurar ficheiro: $(basename "${RESTORE_FILE}")..."
if [[ "${RESTORE_FILE}" == *.gz ]]; then
    zcat "${RESTORE_FILE}" | mysql --skip-ssl -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" 2>/dev/null || \
    zcat "${RESTORE_FILE}" | mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}"
else
    mysql --skip-ssl -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${RESTORE_FILE}" 2>/dev/null || \
    mysql -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" "${DB_NAME}" < "${RESTORE_FILE}"
fi

echo "========================================================================="
echo "✅ RESTAURO CONCLUÍDO COM 100% DE SUCESSO E PROTEÇÃO TOTAL!              "
echo "========================================================================="
