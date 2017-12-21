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

use Niuware\WebFramework\Auth\Auth;

use App\Config\Routes;

/**
 * Process a route
 */
class Router
{
    /**
     * The route path
     * 
     * @var array 
     */
    private $path;

    /**
     * A flag to determine if a route error has occurred
     * 
     * @var bool 
     */
    private $error = true;
    
    /**
     * The HTTP request method
     * 
     * @var string 
     */
    private $requestMethod;
    
    /**
     * The URL query
     * 
     * @var array 
     */
    private $queryString = [];
    
    /**
     * The requested URI
     * 
     * @var string 
     */
    private $currentUri = "";
    
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
     * The application language settings
     * 
     * @var array 
     */
    private $language;

    /**
     * Initializes the Router
     * 
     * @return void
     */
    public function __construct($language)
    {
        $this->language = $language;
        
        $this->initialize();

        $this->redirectFail();
    }

    /**
     * Runs the routing
     * 
     * @return void
     */
    private function initialize()
    {
        $this->setRequestMethod();

        if (\App\Config\BASE_PATH === '/') {

            $this->currentUri = substr(filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_URI', FILTER_SANITIZE_URL), 1);
        } else {

            $this->currentUri = str_replace('/' . \App\Config\BASE_PATH, '', filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_URI', FILTER_SANITIZE_URL));
        }
        
        $parsedUrl = parse_url($this->currentUri);
        
        $this->setPath($parsedUrl);
        
        $this->setQueryString($parsedUrl);
        
        $this->parseRoute();
        
        $this->redirectTask();
        
        if ($this->routeController !== null) {
            
            $this->error = $this->redirectMain();
        }
    }
    
    /**
     * Sets the loaded route parameters
     * 
     * @return void
     */
    private function parseRoute()
    {
        $parser = new RouteParser($this->path, $this->requestMethod);
        
        $parser->parse();
        
        $parser->setRouteDefinition($this->routeRequireLogin, $this->routeRequireCsrf, $this->routeIsAdmin, 
                $this->routeControllerPath, $this->routeController, $this->routeAction, $this->routeRequest, 
                $this->routeMappedParams, $this->routeMode);
    }
    
    /**
     * Sets the path to load
     * 
     * @param array $parsedUrl
     * @return void
     */
    private function setPath($parsedUrl)
    {
        if (isset($parsedUrl['path'])) {
        
            $this->path = preg_split('/\//', $parsedUrl['path'], -1, \PREG_SPLIT_NO_EMPTY);
        }

        if (!isset($this->path[0])) {

            $this->path[0] = '';
        }
        if (!isset($this->path[1])) {

            $this->path[1] = '';
        }
        if (!isset($this->path[2])) {
            
            $this->path[2] = '';
        }
    }
    
    /**
     * Sets the query parameters
     * 
     * @param array $parsedUrl
     * @return void
     */
    private function setQueryString($parsedUrl)
    {
        if (isset($parsedUrl['query'])) {
            
            parse_str($parsedUrl['query'], $this->queryString);
        }
    }
    
    /**
     * Sets the request method
     * 
     * @return void
     */
    private function setRequestMethod()
    {
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
     * Executes the routing for controllers in the Main Application Space
     * 
     * @return bool
     */
    private function redirectMain()
    {
        if (!$this->routeRequireLogin) {
            
            Auth::requireAuth(false, $this->routeMode);
            
            return false;
        }
        else {
            
            return $this->setRequireAuthMode();
        }
    }

    /**
     * Verifies if a redirection to an API class or Console mode
     * is required
     * 
     * @return void
     */
    private function redirectTask()
    {
        if ($this->path[0] === 'api') {
            
            $input = new HttpInput($this->requestMethod);
            
            $input->withApi($this->routeController, $this->getFilteredControllerAction(), $this->currentUri);

            exit;

        } 
        else if ($this->path[0] === 'console') {
            
            if (\App\Config\CONSOLE_MODE === 'web' || \App\Config\CONSOLE_MODE === 'enabled') {
                
                $console = new \Niuware\WebFramework\Application\Console($this->path, 'web');

                exit(nl2br($console->getResult()));
            }
        }
    }

    /**
     * Requires the authentication mode
     * 
     * @return bool
     */
    private function setRequireAuthMode()
    {
        Auth::requireAuth(true, $this->routeMode);

        return $this->redirectAuthMode();
    }

    /**
     * Redirects the browser to a default route
     * 
     * @return void
     */
    private function redirectFail()
    {
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
     * Redirects the application to the Application Main Space
     * 
     * @return void
     */
    private function redirectFailMain()
    {
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
     * Redirects the application to the Application Admin Space
     * 
     * @return void
     */
    private function redirectFailMode()
    {
        if (!empty(Routes::$views[$this->routeMode])) {
                    
            header("Location: " . \App\Config\BASE_URL . $this->routeMode . '/' . \App\Config\HOMEPAGE);
            
            exit;
        }
    }

    /**
     * Executes the routing for controllers requiring an authentication
     * 
     * @return bool
     */
    private function redirectAuthMode()
    {
        if (!Auth::verifiedAuth($this->routeMode)) {
            
            $this->routeController = "Login";
            $this->routeAction = "login";
            $this->routeControllerPath = "login";
            $this->routeRequireLogin = false;
            $this->routeRequireCsrf = false;
            
        } else {
            
            if ($this->routeAction === null) {
                
                $this->routeController = "";
            }
        }

        return ($this->routeController == "");
    }

    /**
     * Gets an instance of the requested controller
     * 
     * @return \Niuware\WebFramework\Application\Controller
     * 
     * @throws \Exception
     */
    public function getControllerInstance()
    {
        $controllerClass = "\App\Controllers\\";
        
        if ($this->routeMode !== 'main') {
            
            $controllerClass.= ucfirst($this->routeMode) . "\\";
        }
        
        $controllerClass.= $this->routeController;
        
        if (!class_exists($controllerClass)) {
            
            throw new \Exception("The controller class '" . $controllerClass 
                        . "' does not exist.", 106);
        }

        $controllerObject = new $controllerClass;
        
        if (is_object($controllerObject) && 
                get_parent_class($controllerObject) ==  'Niuware\WebFramework\Application\Controller') {
            
            return $controllerObject;
        }
        
        throw new \Exception("The controller class '" . $controllerClass 
                    . "' is not an instance of Niuware\WebFramework\Application\Controller.", 104);
    }

    /**
     * Gets the name of the requested view
     * 
     * @return string
     */
    public function getControllerName()
    {
        return $this->routeController;
    }

    /**
     * Gets the controller action name (method to execute)
     * 
     * @return string
     */
    public function getControllerAction()
    {
        return $this->routeAction;
    }
    
    /**
     * Gets the controller action name without hyphens
     * 
     * @return string
     */
    public function getFilteredControllerAction()
    {
        return str_replace(['-', '_'], '', $this->routeAction);
    }

    /**
     * Gets the parameters for the requested method
     * 
     * @return array
     */
    public function getControllerParams()
    {
        $allParams = array_merge($this->routeMappedParams, $this->queryString);
        
        $postParams = null;
        $postFiles = null;
        
        $input = new HttpInput($this->requestMethod);
        
        $input->parse($postParams, $postFiles);
        
        if (($this->requestMethod === 'post' || $this->requestMethod === 'delete') && $postParams !== null) {
            
            $allParams = array_merge($allParams, $postParams);
        }
        
        $httpRequest = $input->getRequestInstance($this->routeController, $this->getFilteredControllerAction(), $this->routeMode, [
                            'params' => $allParams,
                            'files' => $postFiles,
                            'requestUri' => $this->currentUri,
                            'app' => [
                                'controller' => $this->routeController,
                                'action' => $this->routeAction,
                                'mode' => $this->routeMode,
                                'requireLogin' => $this->routeRequireLogin,
                                'lang' => $this->language
                            ]
                        ], $this->routeRequest);

        if ($this->routeRequireCsrf && !$httpRequest->hasValidCsrf()) {
            
            header('HTTP/1.0 403 Forbidden');
            
            exit;
        }
            
        return $httpRequest;
    }
    
    /**
     * Verifies an Application Admin Space route
     * 
     * @return bool
     */
    public function isAdmin()
    {
        return $this->routeIsAdmin;
    }
    
    /**
     * Gets the request method
     * 
     * @return string
     */
    public function getRequestMethod()
    {
        return $this->requestMethod;
    }
    
    /**
     * Gets the default view name based on the requested path
     * 
     * @return string
     */
    public function getDefaultView()
    {
        $viewName = '';
        
        if ($this->routeMode !== 'main') {
            
            $viewName = $this->routeMode . '/';
        }
        
        if ($this->routeControllerPath !== '') {
            
            $viewName.= $this->routeControllerPath . '/';
        }
        
        $viewName.= $this->getControllerAction();
        $viewName.= '.twig';
        
        return $viewName;
    }
    
    /**
     * Redirects the browser to a path
     * 
     * @param \Niuware\WebFramework\Http\Response|string|null $path
     * @return void
     */
    public function redirect($path)
    {
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