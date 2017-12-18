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

namespace Niuware\WebFramework\Http;

use App\Config\Routes;

/**
 * Parses a route
 */
final class RouteParser
{
    /**
     * The route path
     * 
     * @var array 
     */
    private $path;
    
    /**
     * A flag to determine if the route requires an authentication
     * 
     * @var bool 
     */
    private $routeRequireLogin = false;
    
    /**
     * A flag to determine if the route requires a valid CSRF token
     * 
     * @var bool 
     */
    private $routeRequireCsrf = false;
    
    /**
     * A flag to determine if the route is in the Application Admin Space
     * 
     * @var bool 
     */
    private $routeIsAdmin = false;
    
    /**
     * The route controller path
     * 
     * @var string 
     */
    private $routeControllerPath;
    
    /**
     * The route controller name
     * 
     * @var string 
     */
    private $routeController;
    
    /**
     * The route controller action
     * 
     * @var string 
     */
    private $routeAction = "";
    
    /**
     * The route request class name
     * 
     * @var string 
     */
    private $routeRequest = "";
    
    /**
     * The route mapped parameters
     * 
     * @var array 
     */
    private $routeMappedParams = [];
    
    /**
     * The route Application Space
     * 
     * @var string 
     */
    private $routeMode = "main";
    
    /**
     * The request method
     * @var type 
     */
    private $method = "";
    
    /**
     * Initializes the default parser values
     * 
     * @param array $path
     * @param string $method
     * @return void
     */
    public function __construct($path, $method)
    {
        $this->path = $path;
        $this->method = $method;
    }
    
    /**
     * Parses the route parameters
     * 
     * @return void
     */
    public function parse()
    {
        $actionIndex = 0;

        if ($this->path[0] === 'admin') {
            
            $this->routeMode = 'admin';
            $actionIndex = 1;
            $this->routeIsAdmin = true;
            $this->routeRequireLogin = true;
            
            if ($this->method === "post" || $this->method === "delete") {
                
                $this->routeRequireCsrf = true;
            }
        }
        else {

            if (isset(Routes::$views[$this->path[0]])) {

                $this->routeMode = $this->path[0];
                $actionIndex = 1;
            }
            else {
                
                $this->routeMode = "main";
                $actionIndex = 0;
            }
        }
        
        $matchingRoutes = $this->getMatchingRoutes($actionIndex);
        
        $this->setRouteParameters($matchingRoutes, $actionIndex);
    }
    
    /**
     * Sets all route attributes
     * 
     * @param bool $routeRequireLogin
     * @param bool $routeRequireCsrf
     * @param bool $routeIsAdmin
     * @param string $routeControllerPath
     * @param string $routeController
     * @param string $routeAction
     * @param string $routeRequest
     * @param string $routeMappedParams
     * @param string $routeMode
     * @return void
     */
    public function setRouteDefinition(&$routeRequireLogin, &$routeRequireCsrf, &$routeIsAdmin,
            &$routeControllerPath, &$routeController, &$routeAction, &$routeRequest, 
            &$routeMappedParams, &$routeMode)
    {
        
        $routeRequireLogin = $this->routeRequireLogin;
        $routeRequireCsrf = $this->routeRequireCsrf;
        $routeIsAdmin = $this->routeIsAdmin;
        $routeControllerPath = $this->routeControllerPath;
        $routeController = $this->routeController;
        $routeRequest = $this->routeRequest;
        $routeMappedParams = $this->routeMappedParams;
        $routeMode = $this->routeMode;
        
        if ($this->routeAction !== "") {
            
            $routeAction = $this->routeAction;
        }
    }
    
    /**
     * Gets all matching controller routes
     * 
     * @param int $actionIndex
     * @return array
     */
    private function getMatchingRoutes($actionIndex)
    {
        $matchingRoutes = [];

        foreach (Routes::$views[$this->routeMode] as $route => $controller) {

            $localPath = explode("/", $route);

            if ($this->path[$actionIndex] === $localPath[0]) {
                
                $matchingRoutes[$route] = $controller;
            }
        }
        
        uksort($matchingRoutes, function($a, $b) {
            
             return strlen($b) - strlen($a);
        });
        
        return $matchingRoutes;
    }
    
    /**
     * Sets the controller and route parameters for the current route
     * 
     * @param array $matchingRoutes
     * @param int $actionIndex
     * @return void
     */
    private function setRouteParameters($matchingRoutes, $actionIndex)
    {
        $tmpPath = $this->path;
        
        if ($actionIndex > 0) {
            
            $matchingPath = implode('/', array_splice($tmpPath, $actionIndex));
        }
        else {
            
            $matchingPath = $this->removeTrailingSlash(implode('/', $tmpPath));
        }
        
        $matchingPath = $this->removeTrailingSlash($matchingPath);
        
        foreach ($matchingRoutes as $route => $controller) {
            
            $patternRaw = preg_replace('/\{(.*?)\}/', '(.*?)', $route);
            $pattern = '/(' . str_replace('/', '\/', $patternRaw) . ')$/';
            
            if (preg_match($pattern, $matchingPath)) {
                
                $customAction = "";
                $localPath = explode("/", $route);
                $this->setController($controller, $localPath[0], $customAction);
                $this->setRouteAction($localPath, $actionIndex, $customAction);
                $this->setMappedParameters($route, $matchingPath);
                
                break;
            }
        }
    }
    
    /**
     * Sets the controller name and path
     * 
     * @param array $controller
     * @param string $controllerPath
     * @param string $customAction
     * @return void
     */
    private function setController($controller, $controllerPath, &$customAction)
    {
        if (isset($controller['use'])) {
            
            $customController = explode('@', $controller['use']);
            
            if (isset($customController[1])) {
                
                $this->routeController = $customController[0];
                $customAction = $customController[1];
            }
            else {
                $this->routeController = $controller['use'];
            }
            
            $this->routeControllerPath = $controllerPath;
        }
        
        $this->setControllerRequirements($controller);
        
        $this->setControllerRequest($controller);
    }
    
    /**
     * Sets the controller required attributes
     * 
     * @param array $controller
     * @return void
     */
    private function setControllerRequirements($controller)
    {
        if (isset($controller['require']) && is_array($controller['require'])) {

            if (in_array('login', $controller['require']) || 
                    key_exists('login', $controller['require'])) {
                
                $this->setLoginOptions($controller['require']);
            }
            
            if (in_array('csrf', $controller['require']) || 
                    key_exists('csrf', $controller['require'])) {
                
                $this->setCsrfOptions($controller['require']);
            }
        }
    }
    
    /**
     * Sets the login options for the route
     * 
     * @param array $options
     * @return void
     */
    private function setLoginOptions($options)
    {
        $loginOptions = null;
        
        if (isset($options['login'])) {
            
            $loginOptions = $options['login'];
        }
        
        if ($loginOptions === null || $loginOptions === true) {

            $this->routeRequireLogin = true;
        }
        else if ($loginOptions === false) {
            
            $this->routeRequireLogin = false;
        }
    }
    
    /**
     * Sets the CSRF validation options for the route
     * 
     * @param array $options
     * @return void
     */
    private function setCsrfOptions($options)
    {
        $csrfOptions = null;
        $this->routeRequireCsrf = false;
        
        if (isset($options['csrf'])) {
            
            $csrfOptions = $options['csrf'];
        }

        if (!is_array($csrfOptions)) {

            if($csrfOptions === null || $csrfOptions === true) {
                
                $this->routeRequireCsrf = true;
            }
        }
        else {

            if (in_array('get', $csrfOptions) && $this->method === 'get') {

                 $this->routeRequireCsrf = true;
            }
            if (in_array('post', $csrfOptions) && $this->method === 'post') {

                 $this->routeRequireCsrf = true;
            }
            if (in_array('delete', $csrfOptions) && $this->method === 'delete') {

                 $this->routeRequireCsrf = true;
            }
        }
    }
    
    /**
     * Sets the custom Request class
     * 
     * @param array $controller
     * @return void
     */
    private function setControllerRequest($controller)
    {
        if (isset($controller['request'])) {
            
            $this->routeRequest = $controller['request'];
        }
    }
    
    /**
     * Sets the controller's method to execute
     * 
     * @param array $path
     * @param int $actionIndex
     * @param string $customAction
     * @return void
     */
    private function setRouteAction($path, $actionIndex, $customAction)
    {
        if (isset($this->path[$actionIndex + 1]) && isset($path[1])) {

            if ($this->path[$actionIndex + 1] === $path[1]) {
                
                $this->routeAction = $this->path[$actionIndex + 1];
            }
        }
        
        if ($customAction !== "") {
            
            $this->routeAction = $customAction;
        }
    }
    
    /**
     * Maps the route parameters
     * 
     * @param string $route
     * @param string $matchingPath
     * @return void
     */
    private function setMappedParameters($route, $matchingPath)
    {
        $matches = [];
        
        if (preg_match('/\{(.*?)\}/', $route, $matches, PREG_OFFSET_CAPTURE) > 0) {

            $this->routeMappedParams = [];
            
            foreach ($matches as $param) {
                
                if (substr($param[0], 0, 1) !== '{') {
                    
                    continue;
                }
                
                $key = str_replace(['{', '}'], '', $param[0]);
                
                $paramString = substr($matchingPath, $param[1]);
                $limit = strpos($paramString, '/');

                if ($limit > 0) {
                    
                    $this->routeMappedParams[$key] = substr($paramString, 0, $limit);
                }
                else {
                    
                    $this->routeMappedParams[$key] = substr($paramString, 0); 
                }
            }
        }
    }
    
    /**
     * Removes a trailing slash of a string
     * 
     * @params string $input
     * @return string
     */
    private function removeTrailingSlash($input) {
        
        if (substr($input, -1, 1) === '/') {
            
            return substr($input, 0, -1);
        }
        
        return $input;
    }
}
