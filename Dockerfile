FROM php:8.2-apache

# Instalar extensiones necesarias (SQLite viene incluido con PDO_SQLITE)
RUN docker-php-ext-install pdo_sqlite

# Habilitar mod_rewrite
RUN a2enmod rewrite

# Copiar todo el proyecto
COPY . /var/www/html/

# Crear carpeta para la base de datos y dar permisos
RUN mkdir -p /var/www/html/database && \
    chown -R www-data:www-data /var/www/html/database && \
    chmod -R 775 /var/www/html/database

# Exponer puerto
EXPOSE 80

CMD ["apache2-foreground"]