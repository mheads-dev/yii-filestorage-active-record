<?php

declare(strict_types=1);

use Yiisoft\Db\Connection\ConnectionInterface;

/**
 * Replace this helper in your project:
 * - get ConnectionInterface from DI container
 * - do not keep DSN/credentials in code
 */
function getDbConnection(): ConnectionInterface
{
    throw new \RuntimeException('Provide ConnectionInterface from your application container.');
}
