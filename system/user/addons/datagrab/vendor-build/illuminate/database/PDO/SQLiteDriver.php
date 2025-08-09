<?php

namespace BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO;

use BoldMinded\DataGrab\Dependency\Doctrine\DBAL\Driver\AbstractSQLiteDriver;
use BoldMinded\DataGrab\Dependency\Illuminate\Database\PDO\Concerns\ConnectsToDatabase;
class SQLiteDriver extends AbstractSQLiteDriver
{
    use ConnectsToDatabase;
}
