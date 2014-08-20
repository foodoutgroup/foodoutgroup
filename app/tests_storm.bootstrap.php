<?php
/* skirta PHP stormui */
ini_set('xdebug.max_nesting_level', 200);
ini_set('memory_limit', '256M');

putenv('SYMFONY__MYSQL__USER=' . (getenv('MYSQL_USER') ? getenv('MYSQL_USER') : 'root')); 
putenv('SYMFONY__MYSQL__PASSWORD=' . (getenv('MYSQL_PASSWORD') ? getenv('MYSQL_PASSWORD') : ''));
putenv('SYMFONY__MYSQL__DATABASE=' . (getenv('MYSQL_USER') ? 'test' : 'test_skanu'));

/* clear previous data */
passthru('php console doctrine:schema:drop --env=test --force --quiet');
passthru('php console doctrine:schema:create --env=test --quiet');
passthru('php console doctrine:fixtures:load --env=test --no-interaction');

require_once __DIR__.'/bootstrap.php.cache';
