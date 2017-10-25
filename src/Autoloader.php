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
    
    private static $controllerSubspace;

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
            
            $subNamespace = str_replace('\\', '', substr($baseNamespace, 1, $last - 1));
            
            if (substr($subNamespace, 0, 11) === 'Controllers') {
                
                self::$controllerSubspace = $subNamespace;
                $subNamespace = 'Controllers';
            }
            
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

        return 'App/Config/';
    }

    /**
     * Registers the autoloading for API classes
     * @param type $class Class to load
     */
    private static function api() {

        return 'App/Api/';
    }

    /**
     * Registers the autoloading for controller classes
     * @param type $class Class to load
     */
    private static function controllers() {

        $path = 'App/Controllers/';
        $subspace = str_replace('Controllers', '', self::$controllerSubspace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }

    /**
     * Registers the autoloading for model classes
     * @param type $class Class to load
     */
    private static function models() {

        return 'App/Models/';
    }
    
    /**
     * Registers the autoloading for helper classes
     * @param type $class Class to load
     */
    private static function helpers() {
        
        return 'App/Helpers/';
    }
}