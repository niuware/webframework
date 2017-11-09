<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework\Http;

use App\Config\Routes;

/**
 * Parses a route
 */
final class RouteParser {
    
    private $path;
    
    private $routeRequireLogin = false;
    
    private $routeRequireCsrf = false;
    
    private $routeIsAdmin = false;
    
    private $routeControllerPath;
    
    private $routeController;
    
    private $routeAction = "";
    
    private $routeRequest = "";
    
    private $routeMappedParams = [];
    
    private $routeMode = "main";
    
    function __construct($path) {
        
        $this->path = $path;
    }
    
    /**
     * Parse the route parameters based on the current URL
     */
    public function parse() {
        
        $actionIndex = 0;

        if ($this->path[0] === 'admin') {
            
            $this->routeMode = 'admin';
            $actionIndex = 1;
            $this->routeIsAdmin = true;
            $this->routeRequireLogin = true;
        }
        else {

            if (isset(Routes::$views[$this->path[0]])) {

                $this->routeMode = $this->path[0];
                $actionIndex = 1;
            }
        }
        
        $matchingRoutes = $this->getMatchingRoutes($actionIndex);
        
        $this->setRouteParameters($matchingRoutes, $actionIndex);
    }
    
    /**
     * Sets all route parameters
     * @param type $routeRequireLogin
     * @param type $routeRequireCsrf
     * @param type $routeIsAdmin
     * @param type $routeControllerPath
     * @param type $routeController
     * @param type $routeAction
     * @param type $routeRequest
     * @param type $routeMappedParams
     * @param type $routeMode
     */
    public function setRouteDefinition(&$routeRequireLogin, &$routeRequireCsrf, &$routeIsAdmin,
            &$routeControllerPath, &$routeController, &$routeAction, &$routeRequest, 
            &$routeMappedParams, &$routeMode) {
        
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
     * Get all matching controller routes
     * @param type $actionIndex
     * @return type
     */
    private function getMatchingRoutes($actionIndex) {
        
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
     * Sets controller and route parameters for the current route
     * @param type $matchingRoutes
     * @param type $actionIndex
     */
    private function setRouteParameters($matchingRoutes, $actionIndex) {
        
        $tmpPath = $this->path;
        $matchingPath = implode('/', array_splice($tmpPath, $actionIndex));
        
        if (substr($matchingPath, -1, 1) === '/') {
            
            $matchingPath = substr($matchingPath, 0, -1);
        }
        
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
     * Sets the controller loading name and path
     * @param type $controller
     * @param type $controllerPath
     */
    private function setController($controller, $controllerPath, &$customAction) {
        
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
     * Sets the controller require attributes if any
     * @param type $controller
     */
    private function setControllerRequirements($controller) {
        
        if (isset($controller['require']) && is_array($controller['require'])) {

            if (in_array('login', $controller['require'])) {
                
                $this->routeRequireLogin = true;
            }
            
            if (in_array('csrf', $controller['require'])) {
                
                $this->routeRequireCsrf = true;
            }
        }
    }
    
    /**
     * Sets the custom controller Request class if any
     * @param type $controller
     */
    private function setControllerRequest($controller) {
        
        if (isset($controller['request'])) {
            
            $this->routeRequest = $controller['request'];
        }
    }
    
    /**
     * Sets the controller's action to load
     * @param type $path
     * @param type $actionIndex
     */
    private function setRouteAction($path, $actionIndex, $customAction) {
        
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
     * Maps the route parameters if any
     * @param type $route
     * @param type $matchingPath
     */
    private function setMappedParameters($route, $matchingPath) {
        
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
}
