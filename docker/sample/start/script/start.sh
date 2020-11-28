#!/bin/bash

# start postgres

service postgresql start

# populate database with sample data

php /var/www/html/start/script/start.php

# generate encryption key

php /var/www/html/start/script/key_generator.php

# php:apache entrypoint

/var/www/html/start/script/apache2.sh

