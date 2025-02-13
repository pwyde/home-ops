<?php
  $CONFIG = array(
    'backgroundjobs_mode' => 'cron',
    'activity_expire_days' => 14,
    'allow_local_remote_servers' => true,
    'auth.bruteforce.protection.enabled' => true,
    'overwriteprotocol' => 'https',
    'overwrite.cli.url' => 'https://nextcloud.${SECRET_DOMAIN}',
    'trusted_domains' => array(
      0 => 'localhost',
      1 => 'nextcloud.${SECRET_DOMAIN}',
    ),
    'trusted_proxies' => array(
      0 => '127.0.0.1',
      1 => '${CLUSTER_POD_CIDR}',
    ),
    'forwarded_for_headers' => array(
      0 => 'HTTP_X_FORWARDED_FOR',
    ),

    'forbidden_filenames' => array(
      0 => '.htaccess',
      1 => 'Thumbs.db',
      2 => 'thumbs.db',
    ),

    'appstoreenabled' => true,
    'knowledgebaseenabled' => false,
    'quota_include_external_storage' => false,
    'share_folder' => '/Shared',
    'skeletondirectory' => '',
    'trashbin_retention_obligation' => 'auto, 7',

    'log_type' => 'file',
    'logfile' => '/var/log/nextcloud.log',
    'loglevel' => 0,

    'memcache.local' => '\OC\Memcache\APCu',
    'memcache.distributed' => '\OC\Memcache\Redis',
    'memcache.locking' => '\OC\Memcache\Redis',
    'redis' => array(
      'host' => getenv('REDIS_HOST'),
      'port' => getenv('REDIS_HOST_PORT') ?: 6379,
      'password' => getenv('REDIS_HOST_PASSWORD'),
      'dbindex'       => 5,
      'timeout'       => 1.5,
      'read_timeout'  => 1.5,
    ),

    'apps_paths' => array(
      0 => array(
        'path'     => OC::$SERVERROOT.'/apps',
        'url'      => '/apps',
        'writable' => false,
      ),
      1 => array(
        'path'     => OC::$SERVERROOT.'/custom_apps',
        'url'      => '/custom_apps',
        'writable' => true,
      ),
    ),

    'htaccess.RewriteBase' => '/',

    'mail_sendmailmode' => 'smtp',
    'mail_smtphost' => getenv('SMTP_HOST'),
    'mail_smtpport' => getenv('SMTP_PORT'),
    'mail_smtpsecure' => 'ssl',
    'mail_smtpauth' => true,
    'mail_smtpauthtype' => 'LOGIN',
    'mail_smtpname' => getenv('SMTP_USERNAME'),
    'mail_smtppassword' => getenv('SMTP_PASSWORD'),
    'mail_from_address' => getenv('MAIL_FROM_ADDRESS'),
    'mail_domain' => getenv('MAIL_DOMAIN'),
    "mail_smtptimeout"  => 30,

    'enable_previews' => true,
    'preview_max_x' => '2048',
    'preview_max_y' => '2048',
    'preview_max_scale_factor' => 1,
    'jpeg_quality' => '60',
    'enabledPreviewProviders' => array(
      0 => 'OC\\Preview\\AVI',
      1 => 'OC\\Preview\\BMP',
      2 => 'OC\\Preview\\GIF',
      3 => 'OC\\Preview\\HEIC',
      4 => 'OC\\Preview\\Imaginary',
      5 => 'OC\\Preview\\Image',
      6 => 'OC\\Preview\\ImaginaryPDF',
      7 => 'OC\\Preview\\JPEG',
      8 => 'OC\\Preview\\Krita',
      9 => 'OC\\Preview\\MarkDown',
      10 => 'OC\\Preview\\Movie',
      11 => 'OC\\Preview\\MKV',
      12 => 'OC\\Preview\\MP3',
      13 => 'OC\\Preview\\MP4',
      14 => 'OC\\Preview\\OpenDocument',
      15 => 'OC\\Preview\\PDF',
      16 => 'OC\\Preview\\PNG',
      17 => 'OC\\Preview\\TXT',
      18 => 'OC\\Preview\\XBitmap',
    ),

    // Circumvention for client freezes. See https://github.com/nextcloud/desktop/issues/5094
    'bulkupload.enabled' => false,

    'backgroundjobs_mode' => 'webcron',
    'default_language' => 'en',
    'default_locale' => 'sv_SE',
    'default_phone_region' => 'SE',
    'default_timezone' => 'Europe/Stockholm',
    'maintenance_window_start' => 1,
  );
