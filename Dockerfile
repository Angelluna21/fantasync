FROM php:8.2-apache

# Habilitar mod_rewrite (esto sí es necesario)
RUN a2enmod rewrite

# Copiar todo el proyecto
COPY . /var/www/html/

# Crear carpeta para la base de datos y dar permisos
RUN mkdir -p /var/www/html/database && \
    chown -R www-data:www-data /var/www/html/database && \
    chmod -R 775 /var/www/html/database

# Exponer puerto
EXPOSE 80

# Iniciar Apache
CMD ["apache2-foreground"]