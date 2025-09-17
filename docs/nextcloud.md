# Nextcloud

## Post-install Commands

```sh
until php /var/www/html/occ status --output json | grep -q '\"installed\":true'; do
    sleep 10;
done && php /var/www/html/occ upgrade \
&& php /var/www/html/occ db:add-missing-indices \
&& php /var/www/html/occ db:add-missing-columns \
&& php /var/www/html/occ db:add-missing-primary-keys \
&& php /var/www/html/occ db:convert-filecache-bigint \
&& php /var/www/html/occ maintenance:repair --include-expensive
```

## Add Apps

```sh
until php /var/www/html/occ status --output json | grep -q '\"installed\":true'; do
    sleep 10;
done && php /var/www/html/occ app:disable firstrunwizard \
&& php /var/www/html/occ app:disable support \
&& php /var/www/html/occ app:enable admin_audit \
&& php /var/www/html/occ app:enable files_pdfviewer \
&& php /var/www/html/occ app:enable groupfolders \
&& php /var/www/html/occ app:enable notes \
&& php /var/www/html/occ app:install contacts \
&& php /var/www/html/occ app:install deck \
&& php /var/www/html/occ app:install forms \
&& php /var/www/html/occ app:install tables \
&& php /var/www/html/occ app:install quota_warning
```

## Add Missing Indices

```sh
php /var/www/html/occ maintenance:mode --on \
&& php /var/www/html/occ db:add-missing-indices \
&& php /var/www/html/occ maintenance:mode --off
```
