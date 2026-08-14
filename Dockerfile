# 1. Base Image: PHP 8.2 com Apache
FROM php:8.2-apache

# 2. Variáveis de ambiente Apache
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data

# 3. Instalar dependências, cliente mysql e cron (pdo_mysql, gd, zip, intl)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libzip-dev \
       libpng-dev \
       libjpeg-dev \
       libfreetype6-dev \
       libicu-dev \
       default-mysql-client \
       cron \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 4. Ativar módulo mod_rewrite do Apache
RUN a2enmod rewrite

# 5. Copiar configuração do VirtualHost com DocumentRoot em /var/www/html/public
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 6. Copiar o código fonte da aplicação para o contêiner
COPY . /var/www/html/

# 7. Criar pastas de uploads e backups protegidos, ajustar permissões e tornar scripts executáveis
RUN mkdir -p /var/www/html/public/uploads /var/www/html/storage/backups \
    && chmod +x /var/www/html/backup.sh /var/www/html/docker-entrypoint.sh \
    && cp /var/www/html/docker-entrypoint.sh /usr/local/bin/ \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads /var/www/html/storage/backups

# 8. Configurar Agendamento Cron (Diário às 3:00 AM com Log Protegido)
RUN echo "0 3 * * * root /bin/bash /var/www/html/backup.sh >> /var/www/html/storage/backups/cron_output.log 2>&1" > /etc/cron.d/backup-cron \
    && chmod 0644 /etc/cron.d/backup-cron \
    && crontab /etc/cron.d/backup-cron

# 9. Expor porta 80 e definir Entrypoint
EXPOSE 80

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

