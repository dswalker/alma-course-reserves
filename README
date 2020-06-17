=Alma Course Reserves

== Example Apache config

```
<VirtualHost *:80>

	ServerAdmin library@example.edu
	ServerName reserves.example.edu:80
	DocumentRoot /var/www/html

	Alias /example /var/www/reserves/campuses/example/web

	<Directory "/var/www/reserves/campuses">
		Require all granted
		DirectoryIndex index.html index.php
	</Directory>

	<Directory "/var/www/reserves/campuses/example/web">
		SetEnv APPLICATION_ENV production
		RewriteEngine On
		RewriteBase /example/

		RewriteCond %{REQUEST_FILENAME} -s [OR]
		RewriteCond %{REQUEST_FILENAME} -l [OR]
		RewriteCond %{REQUEST_FILENAME} -d
		RewriteRule ^.*$ - [NC,L]
		RewriteRule ^.*$ index.php [NC,L]
	</Directory>

  </VirtualHost>
  ```
