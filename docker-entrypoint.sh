#!/bin/bash
set -e

# Iniciar o daemon do cron em background se instalado
if command -v cron > /dev/null 2>&1; then
    service cron start || cron
fi

# Executar o processo principal do Apache em foreground
exec apache2-foreground
