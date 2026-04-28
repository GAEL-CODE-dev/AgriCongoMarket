# Utiliser une image PHP avec Apache
FROM php:8.1-apache

# Installer les extensions PHP nécessaires
RUN docker-php-ext-install pdo pdo_mysql

# Copier les fichiers du projet
COPY . /var/www/html/

# Donner les permissions
RUN chown -R www-data:www-data /var/www/html/

# Exposer le port 80
EXPOSE 80

# Démarrer Apache
CMD ["apache2-foreground"]