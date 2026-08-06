# 1. Base Image: PHP 8.2 com Apache
FROM php:8.2-apache

# 2. Variáveis de ambiente Apache
ENV APACHE_RUN_USER www-data
ENV APACHE_RUN_GROUP www-data

# 3. Instalar dependências e extensões PHP necessárias (pdo_mysql, gd, zip, intl)
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       libzip-dev \
       libpng-dev \
       libjpeg-dev \
       libfreetype6-dev \
       libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql zip intl \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# 4. Ativar módulo mod_rewrite do Apache
RUN a2enmod rewrite

# 5. Copiar configuração do VirtualHost com DocumentRoot em /var/www/html/public
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# 6. Copiar o código fonte da aplicação para o contêiner
COPY . /var/www/html/

# 7. Criar pasta de uploads se não existir e ajustar permissões
RUN mkdir -p /var/www/html/public/uploads \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads

# 8. Expor porta 80
EXPOSE 80

CMD ["apache2-foreground"]
