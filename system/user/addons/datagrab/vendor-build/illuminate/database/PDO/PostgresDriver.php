<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO;

use BoldMinded\DataGrab\Dependency\Doctrine\DBAL\Driver\AbstractPostgreSQLDriver;
use BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class PostgresDriver extends AbstractPostgreSQLDriver
{
    use ConnectsToDatabase;
}
