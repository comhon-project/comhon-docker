#!/bin/bash

# generate encryption key

php /var/www/html/start/script/key_generator.php

# php:apache entrypoint

/var/www/html/start/script/apache2.sh

