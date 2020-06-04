<?php

/*
 * This file is part of the Comhon Docker package.
 *
 * (c) Jean-Philippe <jeanphilippe.perrotton@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

$wait = true;
while ($wait) {
	try {
		$dataSourceName = 'pgsql:dbname=database;host=localhost';
		$pdo = new \PDO($dataSourceName, 'root', 'root', [\PDO::ATTR_TIMEOUT => 4]);
		$wait = false;
	} catch (\Exception $e) {
		echo "wait for postgres container".PHP_EOL;
		sleep(1);
	}
}

$pdo->exec(file_get_contents(__DIR__.'/../database/database_pgsql.sql'));
echo "database initialized".PHP_EOL;