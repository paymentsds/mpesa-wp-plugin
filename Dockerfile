FROM wordpress

RUN echo 'php_value upload_max_filesize 256M' > '/var/www/html/.htaccess'
RUN chown -R www-data:www-data /var/www/html/

COPY ./src /var/www/html/wp-content/plugins/mpesa-wp-plugin

EXPOSE 80
