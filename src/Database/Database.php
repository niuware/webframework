<?php 
/**
 * 
 * This class is part of the core of Niuware WebFramework 
 * and it is not particularly intended to be modified.
 * For information about the license please visit the 
 * GIT repository at:
 * 
 * https://github.com/niuware/web-framework
 */

namespace Niuware\WebFramework\Database;

use App\Config\Settings;
use Illuminate\Database\Capsule\Manager as Capsule;
    
/**
 * Creates a connection to a database using Capsule (Eloquent)
 */
final class Database
{
    /**
     * A flag to verify if Eloquent is already loaded
     * 
     * @var bool 
     */
    private static $isLoaded = false;

    /**
     * Connects with the database registered in the \App\Config\Settings class
     * 
     * @return void
     */
    public static function boot()
    {
        if (self::$isLoaded == true) {
            
            return;
        }
        
        // Create an Eloquent object and attempt a connection to the database
        try {

            $capsule = new Capsule;
            
            self::addConnections($capsule);

            $capsule->bootEloquent();
            
            $capsule->setAsGlobal();
            
            self::$isLoaded = true;

        } catch (\Exception $e) {

            die("Error 0x102");
        }
    }
    
    /**
     * Gets the query builder instance 
     * 
     * @param string $tableName
     * @param string|null $connection
     * @return \Illuminate\Database\Query\Builder
     */
    public static function table($tableName, $connection = null)
    {
        return Capsule::table($tableName, $connection);
    }
    
    /**
     * Adds all available connections
     * 
     * @param \Illuminate\Database\Capsule\Manager $capsule
     * @return void
     */
    private static function addConnections($capsule)
    {
        $databases = Settings::$databases;
        
        foreach ($databases as $name => $database) {
            
            $capsule->addConnection([
                'driver' => $database['engine'],
                'host' => $database['host'],
                'port' => $database['port'],
                'database' => \App\Config\DB_LANG . $database['schema'],
                'prefix' => $database['prefix'],
                'username' => $database['user'],
                'password' => $database['pass'],
                'charset' => $database['charset'],
                'collation' => $database['collation']
            ], $name);
        }
    }
}