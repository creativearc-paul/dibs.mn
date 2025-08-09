<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO;

use BoldMinded\DataGrab\Dependency\Doctrine\DBAL\Driver\AbstractMySQLDriver;
use BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class MySqlDriver extends AbstractMySQLDriver
{
    use ConnectsToDatabase;
}
