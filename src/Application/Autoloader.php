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

namespace Niuware\WebFramework\Application;

/**
 * Autoloads application classes 
 */
class Autoloader 
{
    /**
     * The application space ('main', 'admin', etc.)
     * 
     * @var string 
     */
    private static $subSpace;

    /**
     * Loads the requested file
     * 
     * @param string $filename
     * @return void|bool
     */
    public static function load($filename)
    {
        if (!file_exists($filename)) {
            return false;
        }

        require_once $filename;
    }

    /**
     * Autoloads a class
     * 
     * @param string $class
     * @return void
     */
    public static function core($class)
    {
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
     * Gets the autoload path for Configuration classes
     * 
     * @return string
     */
    private static function config()
    {
        return 'App/Config/';
    }

    /**
     * Gets the autoload path for Api classes
     * 
     * @return string
     */
    private static function api()
    {
        $path = 'App/Api/';
        $subspace = str_replace('Api', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }

    /**
     * Gets the autoload path for Controller classes
     * 
     * @return string
     */
    private static function controllers()
    {
        $path = 'App/Controllers/';
        $subspace = str_replace('Controllers', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }

    /**
     * Gets the autoload path for Model classes
     * @return string Path to the class
     */
    private static function models()
    {
        return 'App/Models/';
    }
    
    /**
     * Registers the autoloading for Helper classes
     * 
     * @return string
     */
    private static function helpers()
    {
        return 'App/Helpers/';
    }
    
    /**
     * Gets the autoload path for Request classes
     * 
     * @return string
     */
    private static function requests()
    {
        $path = 'App/Requests/';
        $subspace = str_replace('Requests', '', self::$subSpace);
        
        if ($subspace !== '') {
            
            $path.= $subspace . '/';
        }
        
        return $path;
    }
}