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
&& php /var/www/html/occ app:install richdocuments \
&& php /var/www/html/occ app:install tables \
&& php /var/www/html/occ app:install quota_warning
```

## Configure Nextcloud Office
```sh
until php /var/www/html/occ status --output json | grep -q '"installed":true'; do
    echo "Waiting for Nextcloud installation...";
    sleep 10;
done; echo "Nextcloud is installed. Waiting for Nextcloud Office..."
until php /var/www/html/occ app:list | grep -q 'richdocuments'; do
    echo "Waiting for Nextcloud Office installation..."
    sleep 10;
done; echo "Nextcloud Office is installed. Configuring...";
php /var/www/html/occ config:app:set --value https://collabora.${SECRET_DOMAIN} richdocuments wopi_url
php /var/www/html/occ richdocuments:activate-config
echo "Nextcloud Office configuration completed.";
```

## Add Missing Indices
```sh
php /var/www/html/occ maintenance:mode --on \
&& php /var/www/html/occ db:add-missing-indices \
&& php /var/www/html/occ maintenance:mode --off
```
