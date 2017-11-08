<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework\Application;

/**
* Defines static methods for autoloading 
* App namespace classes independently
*/
class Autoloader {
    
    private static $subSpace;

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
        
        if (substr($class, 0, 20) !== "Niuware\WebFramework") {
            
            $namespacePath = explode("\\", $class);
            
            $subNamespace = (isset($namespacePath[1])) ? $namespacePath[1] : "";
                        
            $className = (isset($namespacePath[2])) ? $namespacePath[2] : "";
            
            self::$subSpace = (isset($namespacePath[3])) ? $namespacePath[2] : "";
            
            if (!empty(self::$subSpace)) {
                
                $className = $namespacePath[3];
            }
            
            if (method_exists(get_called_class(), $subNamespace)) {
            
                $path = self::$subNamespace();
                
                self::load($path . $className . '.php');
            }
        }
    }
    
    /**
     * Registers the autoloading for configuration classes
     * @return string Path to the class
     */
    private static function config() {

        return 'App/Config/';
    }

    /**
     * Registers the autoloading for API classes
     * @return string Path to the class
     */
    private static function api() {

        $path = 'App/Api/';
        $subspace = str_replace('Api', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }

    /**
     * Registers the autoloading for controller classes
     * @return string Path to the class
     */
    private static function controllers() {

        $path = 'App/Controllers/';
        $subspace = str_replace('Controllers', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }

    /**
     * Registers the autoloading for model classes
     * @return string Path to the class
     */
    private static function models() {

        return 'App/Models/';
    }
    
    /**
     * Registers the autoloading for helper classes
     * @return string Path to the class
     */
    private static function helpers() {
        
        return 'App/Helpers/';
    }
    
    /**
     * Registers the autoloading for request classes
     * @return string Path to the class
     */
    private static function requests() {
        
        $path = 'App/Requests/';
        $subspace = str_replace('Requests', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }
}