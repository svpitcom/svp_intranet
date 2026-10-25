<?php
/** Machine tables share the Intranet database and connection. */
final class MachineDatabase
{
    public static function connect(): PDO { return Database::connect(); }
}
