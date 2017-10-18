<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework;

/**
* Defines static methods for autoloading 
* App namespace classes independently
*/
class Autoloader {

    /**
     * Loads the requested file if exists
     * @param type $filename File to load
     * @return boolean
     */
    public static function load($filename) {
        
        if (!file_exists($filename))
        {
            return false;
        }

        require_once $filename;
    }

    /**
     * Registers the autoloading for core classes
     * @param type $class Class or Interface to load
     */
    public static function core($class) {
        
        if (substr($class, 0, 20) !== __NAMESPACE__) {
            
            $baseNamespace = str_replace('App', '', $class);
            
            $last = strrpos($baseNamespace, '\\');
            
            $subNamespace = str_replace('\\', '', lcfirst(substr($baseNamespace, 1, $last - 1)));
            
            $className = substr($class, strrpos($class, '\\') + 1);
            
            if (method_exists(get_called_class(), $subNamespace)) {
            
                $path = self::$subNamespace();
                
                self::load($path . $className . '.php');
            }
        }
    }
    
    /**
     * Registers the autoloading for configuration classes
     * @param type $class Class to load
     */
    private static function config() {

        return 'app/config/';
    }

    /**
     * Registers the autoloading for API classes
     * @param type $class Class to load
     */
    private static function api() {

        return 'app/api/';
    }

    /**
     * Registers the autoloading for controller classes
     * @param type $class Class to load
     */
    private static function controllers() {

        return 'app/controllers/';
    }

    /**
     * Registers the autoloading for model classes
     * @param type $class Class to load
     */
    private static function models() {

        return 'app/models/';
    }

    /**
     * Registers the autoloading for admin controller classes
     * @param type $class Class to load
     */
    private static function controllersAdmin() {

        return 'app/controllers/admin/';
    }
    
    /**
     * Registers the autoloading for helper classes
     * @param type $class Class to load
     */
    private static function helpers() {
        
        return 'app/helpers/';
    }
}