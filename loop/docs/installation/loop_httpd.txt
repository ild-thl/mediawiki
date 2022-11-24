<VirtualHost [...]:80  [xxx]:80>
  ServerName    example.de
  ServerAlias   *.example.de
  [...]
</VirtualHost>

<VirtualHost [...]:443  [xxx]:443>
  ServerName    example.de
  ServerAlias   *.example.de

  [...]

  Alias /loop /path/to/mediawiki/index.php
  Alias /mediawiki /path/to/mediawiki
  RewriteEngine on
  RewriteRule ^/$ /mediawiki [R]

  <Directory "/path/to/mediawiki/">
    Options FollowSymLinks
    AllowOverride none
    Require all granted
  </Directory>

</VirtualHost>

