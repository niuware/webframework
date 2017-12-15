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

use Niuware\WebFramework\Auth\Auth;
use Niuware\WebFramework\Http\Router;
use Niuware\WebFramework\Database\Database;
use Niuware\WebFramework\Exception\FrameworkException;
    
/**
 * Executes the application and renders the output for the current
 * router instance
 */
final class Application 
{
    /**
     * The Router instance
     * 
     * @var \Niuware\WebFramework\Http\Router
     */
    private $router;

    /**
     * The Controller instance
     * 
     * @var \Niuware\WebFramework\Application\Controller 
     */
    private $controller;

    /**
     * The language definition
     * 
     * @var array 
     */
    private $language = [];
    
    /**
     * A flag to set if the route has rendered an output
     * 
     * @var bool 
     */
    private $hasRendered = false;
    
    /**
     * Returns the singleton instance for this class
     * 
     * @return $this
     */
    public static function getInstance()
    {
        static $instance = null;
        
        if ($instance === null) {
            
            $instance = new Application();
        }
        
        return $instance;
    }

    /**
     * Initializes the framework autoloader
     * 
     * @return void
     */
    private function __construct()
    {
        
        spl_autoload_register(null, false);
        spl_autoload_register(__NAMESPACE__ . "\Autoloader::core");
    }

    /**
     * Executes the application
     * 
     * @return void
     */
    public function run()
    {
        Auth::start();

        $this->setLanguage();

        $this->router = new Router();
        
        try {
            
            register_shutdown_function(function() {

                $this->shutdown(error_get_last());
            });

            $this->start();
        }
        catch (FrameworkException $exception) {
            
            echo $exception->renderAll();
        }
    }
    
    /**
     * Initializes the application console mode
     * 
     * @return void
     */
    public function console()
    {
        $this->setLanguage();
        
        if (\App\Config\CONSOLE_MODE === 'terminal' || \App\Config\CONSOLE_MODE === 'enabled') {

            $command = $_SERVER['argv'];

            if ($command !== null) {

                $console = new Console($command);

                exit($console->getResult());
            }
        }
        else {
            
            echo "Niuware WebFramework console is disabled.\n";
            
            exit;
        }
    }
    
    /**
     * Sets the language definition found in the \App\Config\Settings class
     * 
     * @param string $lang
     * @return void
     */
    private function setLanguage($lang = 'default')
    {

        $this->language = \App\Config\Settings::$languages[$lang];

        define("App\Config\BASE_LANG", $this->language['prefix']);
        define("App\Config\DB_LANG", $this->language['db_prefix']);
    }
    
    /**
     * Calls the method associated with the URL query string if exists,
     * if not, the default method is called
     * 
     * @return void
     */
    private function loadController()
    {
        $baseMethodName = str_replace(['-', '_'], '', $this->router->getControllerAction());
        
        $methodPrefix = $this->router->getRequestMethod();
        
        if ($methodPrefix === null) {
            
            header('HTTP/1.0 405 Method Not Allowed');
            
            exit;
        }
        
        $methodName = $methodPrefix . $baseMethodName;
        
        if (!method_exists($this->controller, $methodName)) {
            
            $methodName = $baseMethodName;
            
            if (!method_exists($this->controller, $methodName)) {
                    
                return $this->methodNotFound();
            }
        }
        
        return $this->executeController($methodName);
    }
    
    /**
     * Sets the correct description for a "Method not found" FrameworkException
     * 
     * @return void
     * 
     * @throws \Niuware\WebFramework\Exception\FrameworkException
     */
    private function methodNotFound()
    {
        $rootMethodName = str_replace(['get', 'post'], '', $this->router->getControllerAction());
        $reason = "";

        if ($this->router->getRequestMethod() === 'get') {

            $reason = "'get" . strtolower($rootMethodName) . "()' or 'get" . ucfirst($rootMethodName);
        }
        elseif ($this->router->getRequestMethod() === 'post') {

            $reason = "'post" . strtolower($rootMethodName) . "()' or 'post" . ucfirst($rootMethodName);
        }

        throw new FrameworkException("There is no method called '$rootMethodName()' or " . $reason . "()'.", 100);
    }
    
    /**
     * Executes a method found in the loaded controller
     * 
     * @param string $methodName
     * @return void
     * 
     * @throws \Niuware\WebFramework\Exception\FrameworkException
     */
    private function executeController($methodName)
    {
        $reflectionMethod = new \ReflectionMethod($this->controller, $methodName);
        
        if ($reflectionMethod->isPublic()) {
            
            try {
                
                $redirectTo = $reflectionMethod->invoke($this->controller, $this->router->getControllerParams());
                
                $this->hasRendered = true;
                
                $this->router->redirect($redirectTo);
            }
            catch (\ReflectionException $exception) {
                
                throw new FrameworkException("Invocation of method '$methodName' failed.", 103, $exception);
            }
            catch (\Twig_Error_Runtime $exception) {
                
                throw new FrameworkException("Twig exception found when rendering '$methodName'().", 107, $exception);
            }
            catch (\Exception $exception) {
                
                throw new FrameworkException("Render of '$methodName' for the controller '{$this->router->getControllerName()}' failed.", 102, $exception);
            }
        }
        else {
            
            throw new FrameworkException("No callable method with the name '$methodName' was found.", 105);
        }
    }
    
    /**
     * Prepares the application for loading a controller
     * 
     * @return void
     * 
     * @throws \Niuware\WebFramework\Exception\FrameworkException
     */
    private function start()
    {
        Database::boot();

        try {
            
            $this->controller = $this->router->getControllerInstance();
        }
        catch (\Exception $exception) {
            
            throw new FrameworkException($exception->getMessage(), $exception->getCode());
        }
        
        $this->controller->view = $this->router->getDefaultView();

        $this->loadController();
    }
    
    /**
     * Renders the shutdown exception
     * 
     * @param array $error
     * 
     * @return void
     */
    private function shutdown($error)
    {
        if ($error !== null && is_array($error)) {
            
            $trace = null;
            
            if (($trace = strpos($error['message'], "Stack trace:")) !== false) {
                
                $message = substr($error['message'], 0, strpos($error['message'], "Stack trace:"));
                
                $trace = substr($error['message'], strpos($error['message'], "Stack trace:"));
            }
            else {
                
                $message = $error['message'];
            }

            $exception = new FrameworkException($message, $error['type']);

            $exception->setLine($error['line']);
            $exception->setFile($error['file']);
            $exception->setTrace($trace);

            $exception->renderAll((!$this->hasRendered));
        }
    }
}