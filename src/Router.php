<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework;

require_once 'app/config/routes.php';

use App\Config\Routes;

/**
* Process the URL to the correct route
*/
class Router {

    private $path;

    private $error = true;
    
    private $requestMethod;
    
    private $queryString = [];
    
    private $currentUri = "";
    
    private $routeRequireLogin = false;
    
    private $routeIsAdmin = false;
    
    private $routeControllerPath;
    
    private $routeController;
    
    private $routeAction = "";
    
    private $routeMappedParams = [];
    
    private $routeMode = "main";

    function __construct() {

        $this->initialize();

        $this->redirectFail();
    }

    /**
    * Parse the request URL and executes the routing
    */
    private function initialize() {
        
        $this->setRequestMethod();

        if (\App\Config\BASE_PATH === '/') {

            $this->currentUri = substr(filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_URI', FILTER_SANITIZE_URL), 1);
        } else {

            $this->currentUri = str_replace('/' . \App\Config\BASE_PATH, '', filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_URI', FILTER_SANITIZE_URL));
        }
        
        $parsedUrl = parse_url($this->currentUri);
        
        $this->setQueryString($parsedUrl);
        
        $this->path = preg_split('/\//', $parsedUrl['path'], -1, \PREG_SPLIT_NO_EMPTY);

        if (!isset($this->path[1])) {

            $this->path[1] = "";
        }
        if (!isset($this->path[2])) {
            
            $this->path[2] = "";
        }
        
        $this->parseRoute();
        
        if ($this->routeMode === 'main' && $this->routeController !== null) {
            
            $this->redirectMain();
            
            $this->error = false;
        }
        else {
            
            $this->redirectTask();
        }
    }
    
    private function setQueryString($parsedUrl) {
        
        if (isset($parsedUrl['query'])) {
            
            parse_str($parsedUrl['query'], $this->queryString);
        }
    }
    
    private function parseRoute() {
        
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

        foreach (Routes::$views[$this->routeMode] as $route => $controller) {

            $localPath = explode("/", $route);

            if ($this->path[$actionIndex] === $localPath[0]) {
                
                $this->setController($controller, $localPath[0]);
                
                $this->setRouteAction($localPath, $actionIndex);

                $this->setMappedParameters($localPath, $actionIndex);
                
                if (isset($this->routeController) && isset($this->routeAction) 
                        && !empty($this->routeMappedParams)) {

                    break;
                }
            }
        }
    }
    
    private function setController($controller, $controllerPath) {
        
        if (isset($controller['use'])) {

            $this->routeController = $controller['use'];
            $this->routeControllerPath = $controllerPath;
        }
        
        if (isset($controller['login'])) {

            $this->routeRequireLogin = $controller['login'];
        }
    }
    
    private function setRouteAction($path, $actionIndex) {
        
        if (isset($this->path[$actionIndex + 1]) && isset($path[1])) {

            if ($this->path[$actionIndex + 1] === $path[1]) {

                $this->routeAction = $this->path[$actionIndex + 1];
            }
        }
    }
    
    private function setMappedParameters($path, $actionIndex) {
        
        if (count($path) > 2) {
            
            $this->routeMappedParams = [];

            $params = array_splice($path, 2);
            $index = $actionIndex + 2;

            foreach($params as $param) {

                $key = str_replace(['{', '}'], '', $param);

                if (isset($this->path[$index])) {
                    
                    $this->routeMappedParams[$key] = $this->path[$index++];
                }
            }
        }
    }
    
    /**
     * Sets the request method
     */
    private function setRequestMethod() {
        
        $requestMethod = filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_METHOD', FILTER_SANITIZE_URL);
        
        if ($requestMethod === 'GET') {
            
            $this->requestMethod = 'get';
            $this->routeAction = 'index';
            
        } elseif ($requestMethod === 'POST') {
            
            $this->requestMethod = 'post';
            $this->routeAction = 'index';
            
        } elseif ($requestMethod === 'DELETE') {
            
            $this->requestMethod = 'delete';
            $this->routeAction = '';
        }
    }

    /**
    * Executes the routing for controllers (NOT API calls or admin controllers)
    */
    private function redirectMain() {
        
        if (!$this->routeRequireLogin) {
            
            Auth::requireAuth(false, $this->routeMode);
        }
        else {
            
            $this->setRequireAuthMode();
        }
    }

    /**
     * Redirects to an API call or admin controller 
     * @param type $action
     * @return type
     */
    private function redirectTask() {

        if ($this->path[0] === 'api') {
            
            $input = new HttpInput($this->requestMethod);
            
            $input->withApi();

            exit;

        } 
        else if ($this->path[0] === 'admin') {

            return $this->setRequireAdminAuthMode();
        }
        else if ($this->path[0] === 'console:nwf') {
            
            if (\App\Config\CONSOLE_MODE === 'web' || \App\Config\CONSOLE_MODE === 'enabled') {
                
                $console = new Console($this->path, 'web');

                exit(nl2br($console->getResult()));
            }
        }
    }
    
    /**
     * Sets the Router as require admin authenticating mode
     */
    private function setRequireAdminAuthMode() {
        
//        $this->admin = true;
        
        Auth::requireAuth(true, 'admin');
        
        $this->redirectAuthAdminMode();
    }

    /**
    * Sets the Router as require authenticating mode
    */
    private function setRequireAuthMode() {
        
        Auth::requireAuth(true, $this->routeMode);

        $this->redirectAuthMode();
    }

    /**
    * Redirects the browser to a default route, if an error was 
    * generated by the routing
    */
    private function redirectFail() {

        if ($this->error) {

            if (!$this->routeIsAdmin) {
                    
                $this->redirectFailMain();

            } else {

                $this->redirectFailMode();
            }

            header('HTTP/1.0 403 Forbidden');
            
            exit;
        }
    }
    
    /**
     * Redirects the browser to the default main application route
     */
    private function redirectFailMain() {
        
        if ($this->routeMode === 'main') {
            
            if (!empty(Routes::$views['main'])) {

                header("Location: " . \App\Config\BASE_URL . \App\Config\HOMEPAGE);

                exit;
            }
        }
        else {
            
            $this->redirectFailMode();
        }
    }
    
    /**
     * Redirects the browser to the default admin application route
     */
    private function redirectFailMode() {
        
        if (!empty(Routes::$views[$this->routeMode])) {
                    
            header("Location: " . \App\Config\BASE_URL . $this->routeMode . '/' . \App\Config\HOMEPAGE);
            
            exit;
        }
    }

    /**
    * Executes the routing for controllers requiring authentication
    */
    private function redirectAuthMode() {
        
        if (!Auth::verifiedAuth($this->routeMode)) {
            
            $this->routeController = "Login";
            $this->routeAction = "login";
            
        }

        $this->error = false;
    }
    
    /**
     * Executes the routing for admin controllers
     */
    private function redirectAuthAdminMode() {
        
        if (!Auth::verifiedAuth('admin')) {

            $this->routeController = "Login";
            $this->routeAction = "login";

        } else {
            
            if ($this->routeAction === null) {
                
                $this->routeController = "";
            }
        }

        $this->error = ($this->routeController == "");
    }

    /**
    * Returns a new instance of the requested controller
    * @return Controller instance
    */
    public function getControllerInstance() {

        $controllerClass = "\App\Controllers\\";
        
        if ($this->routeMode !== 'main') {
            
            $controllerClass.= ucfirst($this->routeMode) . "\\";
        }
        
        $controllerClass.= $this->routeController;
        
        if (!class_exists($controllerClass)) {
            
            throw new \Exception("The controller class '" . $this->getControllerName() 
                        . "' does not exist.", 106);
        }

        $controllerObject = new $controllerClass;
        
        if (is_object($controllerObject) && 
                get_parent_class($controllerObject) == __NAMESPACE__ . '\Controller') {
            
            return $controllerObject;
        }
        
        throw new \Exception("The controller class '" . $this->getControllerName() 
                    . "' is not an instance of ". __NAMESPACE__ . "\Controller.", 104);
    }

    /**
    * Returns the name of the requested view
    * @return string View name
    */
    public function getControllerName() {

        return $this->routeController;
    }

    /**
     * Gets the controller action (method to execute)
     * @return type
     */
    public function getControllerAction() {
        
        return $this->routeAction;
    }

    /**
     * Gets the parameters for the current method (Uri query)
     * @return array
     */
    public function getControllerParams() {
            
        $allParams = array_merge($this->routeMappedParams, $this->queryString);
        
        $postParams = null;
        $postFiles = null;
        
        $input = new HttpInput($this->requestMethod);
        
        $input->parse($postParams, $postFiles);
        
        if (($this->requestMethod === 'post' || $this->requestMethod === 'delete') && $postParams !== null) {
            
            $allParams = array_merge($allParams, $postParams);
        }
        
        return new HttpRequest($allParams, $postFiles, $this->currentUri, [
            'controller' => $this->routeController,
            'action' => $this->routeAction,
            'mode' => $this->routeMode,
            'requireLogin' => $this->routeRequireLogin
            ]);
    }
    
    /**
     * Returns true if the current routing requires admin validation
     * @return bool
     */
    public function isAdmin() {
        
        return $this->admin;
    }
    
    /**
     * Gets the request method
     * @return string
     */
    public function getRequestMethod() {
        
        return $this->requestMethod;
    }
    
    /**
     * Gets a default view name based on the requested path
     * @return string
     */
    public function getDefaultView() {
        
        $viewName = '';
        
        if ($this->routeMode !== 'main') {
            
            $viewName = $this->routeMode . '/';
        }
        
        $viewName.= $this->routeControllerPath . '/';
        $viewName.= $this->getControllerAction();
        $viewName.= '.twig';
        
        return $viewName;
    }
    
    /**
     * Redirects the browser to a path
     * @param type $path
     * @return type
     */
    public function redirect($path) {
        
        if ($path === null) {
            
            return;
        }
        else if (is_a($path, __NAMESPACE__ . '\Response')) {
            
            $path->render();
            
            return;
        }
        
        $redirectBaseUrl = \App\Config\BASE_URL;
        $redirectPath = $path;
            
        if ($this->routeMode !== 'main') {

            $redirectBaseUrl.= $this->routeMode . '/';
        }
            
        if (isset(Routes::$views[$this->routeMode])) {
                
            header("Location: " . $redirectBaseUrl . $redirectPath);

            exit;
        }
    }
}