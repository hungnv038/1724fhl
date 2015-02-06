<?php

class DBConnection
{
    private static $db = null;

    public static function write()
    {
        if(self::$db) return self::$db;
        self::$db = DB::connection("mysql");
        return self::$db;
    }

    public static function read()
    {
        if (self::$db) return self::$db;
        self::$db= DB::connection("mysql"); //Slave

        return self::$db;
    }
}
