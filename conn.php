<?php

require __DIR__ . '/vendor/autoload.php';

use \WilliamCosta\DotEnv\Environment;
use Kreait\Firebase\Factory;

Environment::load(__DIR__);

define('HOST_URL', getenv('HOST_URL'));
define('DB_URL', getenv('DB_URL'));


$saPath = getenv('GOOGLE_APPLICATION_CREDENTIALS');
if (!$saPath || !file_exists($saPath)) {
	throw new \RuntimeException("Missing Firebase service account.\n\n" .
		"Download a service account JSON from the Firebase Console and set the\n" .
		"environment variable GOOGLE_APPLICATION_CREDENTIALS to its path (or add it to your .env).\n" .
		"Example .env entry:\nGOOGLE_APPLICATION_CREDENTIALS=/full/path/to/service-account.json\n");
}

$factory = (new Factory)
	->withServiceAccount($saPath)
	->withDatabaseUri(DB_URL);

$database = $factory->createDatabase();

