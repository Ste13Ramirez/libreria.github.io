# Usa la imagen oficial de PHP con Apache
FROM php:8.1-apache

# Habilita mod_rewrite (opcional si usas URL amigables)
RUN a2enmod rewrite

# Copia todo el código al directorio raíz de Apache
COPY . /var/www/html/

# Establece los permisos adecuados (opcional)
RUN chown -R www-data:www-data /var/www/html

# Expone el puerto 80
EXPOSE 80

# Inicia Apache
CMD ["apache2-foreground"]
