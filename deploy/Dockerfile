FROM php:8.2-apache

# Instalar extensiones PHP necesarias
RUN docker-php-ext-install -j $(nproc) opcache
RUN docker-php-ext-install -j $(nproc) pdo_mysql

# Habilitar mod_rewrite para Apache
RUN a2enmod rewrite

# Copiar los archivos de la aplicación
COPY ./. /var/www/html/

# Establecer permisos adecuados
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 80
EXPOSE 8080

# Establecer Apache para que escuche en el puerto definido por la variable de entorno PORT
ENV PORT=8080
RUN sed -i "s/80/${PORT}/g" /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Comando para iniciar Apache
CMD ["apache2-foreground"]


