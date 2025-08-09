<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO;

use BoldMinded\DataGrab\Dependency\Doctrine\DBAL\Driver\AbstractSQLServerDriver;
class SqlServerDriver extends AbstractSQLServerDriver
{
    /**
     * @return \Doctrine\DBAL\Driver\Connection
     */
    public function connect(array $params)
    {
        return new SqlServerConnection(new Connection($params['pdo']));
    }
}
