<?php declare(strict_types = 1);

namespace PHPStan\Platform;

use Doctrine\DBAL\Driver\AbstractMySQLDriver;
use Doctrine\DBAL\Driver\Connection as DriverConnection;
use Doctrine\DBAL\Driver\PDO\Connection as PdoDriverConnection;
use PDO;
use SensitiveParameter;

/**
 * Just a driver that does not inherit from any of the known drivers so that DriverDetector cannot detect it.
 */
class UnknownDriver extends AbstractMySQLDriver
{

	public function connect(
		#[SensitiveParameter]
		array $params
	): DriverConnection
	{
		$pdo = new PDO('mysql:host=localhost;dbname=dummy;charset=utf8mb4');
		return new PdoDriverConnection($pdo);
	}

}
