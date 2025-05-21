FROM eliaslazcano/php:8.3.13

RUN apt-get update -yqq && apt-get install -yqq qpdf poppler-utils ghostscript mupdf-tools && apt-get clean

COPY . /var/www/html/

EXPOSE 80

CMD ["apache2-foreground"]