FROM php:8.2-apache

# SQLite ya viene incluido en PHP, solo necesitamos habilitar la extensión
# En lugar de instalar, usamos docker-php-ext-enable
RUN docker-php-ext-enable pdo_sqlite

# Habilitar mod_rewrite para URLs amigables
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