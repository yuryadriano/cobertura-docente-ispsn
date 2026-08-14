#!/bin/bash
# ─────────────────────────────────────────────────────────────────────────────
# Script Oficial de Backup Automático (Seguro - Fora da Webroot)
# ─────────────────────────────────────────────────────────────────────────────
# 1. mysqldump da base de dados comprimido (.sql.gz)
# 2. tar.gz comprimido da pasta public/uploads/
# 3. Armazenamento em /var/www/html/storage/backups/ (FORA DE public/ -> Inacessível via HTTP)
# 4. Rotação automática de backups com mais de 30 dias
# 5. Registo de logs em backup_log.txt
# ─────────────────────────────────────────────────────────────────────────────

set -e

# Configurações de caminhos protegidos (Fora da Webroot Pública)
APP_DIR="/var/www/html"
UPLOADS_DIR="${APP_DIR}/public/uploads"
BACKUP_DIR="${APP_DIR}/storage/backups"
LOG_FILE="${BACKUP_DIR}/backup_log.txt"
TIMESTAMP=$(date +"%Y-%m-%d_%H%M")

# Credenciais da Base de Dados MySQL
DB_HOST="${DB_HOST:-mysql}"
DB_NAME="${DB_NAME:-sftcoordenacao_db}"
DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASSWORD:-test123}"

# Garantir existência da pasta de backups protegida no volume persistente
mkdir -p "${BACKUP_DIR}"

# Criar .htaccess de bloqueio adicional por segurança em profundidade
if [ ! -f "${BACKUP_DIR}/.htaccess" ]; then
    echo "Require all denied" > "${BACKUP_DIR}/.htaccess"
    echo "Deny from all" >> "${BACKUP_DIR}/.htaccess"
fi

log_msg() {
    local MSG="$1"
    echo "[$(date +'%Y-%m-%d %H:%M:%S')] ${MSG}" | tee -a "${LOG_FILE}"
}

log_msg "========================================================="
log_msg "  INÍCIO DA EXECUÇÃO DE BACKUP AUTOMÁTICO (PROTEGIDO)   "
log_msg "========================================================="

# 1. Backup da Base de Dados MySQL
DB_BACKUP_FILE="${BACKUP_DIR}/backup_db_${TIMESTAMP}.sql.gz"
log_msg "📦 A gerar dump da base de dados '${DB_NAME}'..."

if mysqldump -h "${DB_HOST}" -u "${DB_USER}" -p"${DB_PASS}" --single-transaction --quick "${DB_NAME}" | gzip > "${DB_BACKUP_FILE}"; then
    DB_SIZE=$(du -h "${DB_BACKUP_FILE}" | cut -f1)
    log_msg "✅ [SUCESSO] Backup BD gerado: $(basename "${DB_BACKUP_FILE}") (Tamanho: ${DB_SIZE})"
else
    log_msg "❌ [ERRO] Falha ao gerar backup da base de dados!"
fi

# 2. Backup da Pasta de Uploads (documentos, CVs, imagens)
UPLOADS_BACKUP_FILE="${BACKUP_DIR}/backup_uploads_${TIMESTAMP}.tar.gz"
log_msg "📁 A criar arquivo tar.gz dos ficheiros de uploads..."

if tar -czf "${UPLOADS_BACKUP_FILE}" -C "${UPLOADS_DIR}" . ; then
    UPLOADS_SIZE=$(du -h "${UPLOADS_BACKUP_FILE}" | cut -f1)
    log_msg "✅ [SUCESSO] Backup Uploads gerado: $(basename "${UPLOADS_BACKUP_FILE}") (Tamanho: ${UPLOADS_SIZE})"
else
    log_msg "❌ [ERRO] Falha ao gerar arquivo tar.gz dos uploads!"
fi

# 3. Rotação Automática de Backups (Remover ficheiros com mais de 30 dias)
log_msg "🧹 A verificar backups com mais de 30 dias para eliminação..."
DELETED_FILES=$(find "${BACKUP_DIR}" -type f \( -name "*.sql.gz" -o -name "*.tar.gz" \) -mtime +30 -print -delete || true)

if [ -n "${DELETED_FILES}" ]; then
    log_msg "🗑️ Backups antigos eliminados:"
    echo "${DELETED_FILES}" | while read -r f; do
        log_msg "   - $(basename "$f")"
    done
else
    log_msg "ℹ️ Nenhum backup com mais de 30 dias para eliminar."
fi

log_msg "========================================================="
log_msg "  BACKUP CONCLUÍDO COM SUCESSO!                         "
log_msg "========================================================="
