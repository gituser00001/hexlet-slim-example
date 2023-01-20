start:
	php -S localhost:8000 -t public public/index.php

nginx:
	sudo /etc/init.d/nginx start

fpm:
	sudo /etc/init.d/php8.1-fpm start