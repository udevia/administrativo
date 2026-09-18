# ====================================================================
# mi ERP - Imagen de aplicación (PHP 8.2 + Apache)
# Construir:  docker build -t mi-erp:latest .
# ====================================================================
FROM php:8.2-apache

# --- Extensiones PHP requeridas por el proyecto ---
RUN docker-php-ext-install pdo_mysql

# --- Módulos Apache: rewrite (front controller) y headers (seguridad .htaccess) ---
RUN a2enmod rewrite headers

# --- Parámetros PHP para la operación del ERP (uploads de migración CSV, PDFs) ---
RUN { \
        echo "upload_max_filesize=64M"; \
        echo "post_max_size=64M"; \
        echo "memory_limit=256M"; \
        echo "max_execution_time=300"; \
        echo "date.timezone=America/Caracas"; \
    } > /usr/local/etc/php/conf.d/mi-erp.ini

# --- Servidor y DocumentRoot apuntando a public/ ---
RUN echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf \
    && a2enconf servername.conf \
    && { \
           echo "<VirtualHost *:80>"; \
           echo "    ServerName localhost"; \
           echo "    DocumentRoot /var/www/html/public"; \
           echo "    <Directory /var/www/html/public>"; \
           echo "        AllowOverride All"; \
           echo "        Require all granted"; \
           echo "    </Directory>"; \
           echo "</VirtualHost>"; \
       } > /etc/apache2/sites-available/000-default.conf

# --- Aplicación ---
WORKDIR /var/www/html
COPY . /var/www/html/

RUN mkdir -p config storage/backups storage/lic logs \
    && chown -R www-data:www-data storage config logs

# --- Punto de entrada (provisionamiento automático + Apache) ---
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
