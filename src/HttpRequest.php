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
* HTTP request class
*/
final class HttpRequest {
    
    private $headers = [];
    
    private $attributes = [];
    
    private $files = [];
    
    private $app = [];
    
    private $csrfValid = false;
    
    /**
     * Gets a request property
     * @param string $name
     * @return mixed
     */
    public function __get($name) {

        if (isset($this->attributes[$name])) {
            
            return $this->attributes[$name];
        }
        
        return null;
    }

    /**
     * Sets a request property
     * @param string $name
     * @param string $value
     */
    public function __set($name, $value) {

        $this->attributes[$name] = $value;
    }

    function __construct(array $params = null, $files = null, $requestUri = null, $app = null) { 
        
        if ($params !== null) {
            
            $this->attributes = $params;
            
            if (isset($params['csrf_token'])) {
                
                $this->verifyCsrfToken($params['csrf_token']);
            }
        }
        
        if ($files !== null) {
            
            $this->files = $files;
        }
        
        if ($app !== null) {
            
            $this->app = $app;
        }
        
        $this->setHeaders();
        
        $uri = $requestUri;
        
        if (is_array($requestUri)) {
            
            if (isset($requestUri['path'])) {
                
                $uri = $requestUri['path'];
            }
        }
        
        $this->headers['Request-Path'] = $uri;
        $this->headers['Request-Uri'] = \App\Config\BASE_URL . $uri;
    }
    
    private function verifyCsrfToken($token) {
        
        $this->csrfValid = Security::verifyCsrfToken($token);
    }
    
    /**
     * Sets all HTTP headers
     */
    private function setHeaders() {
        
        if (function_exists('getallheaders')) {
            
            $this->headers = getallheaders();
        }
        else {
        
            $headers = ""; 
            
            foreach (array_keys($_SERVER) as $header) {
                
                if (substr($header, 0, 5) == 'HTTP_') { 
                    
                    $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($header, 5)))))] = filter_input(INPUT_SERVER, $header); 
                } 
            } 

            $this->headers = $headers; 
        }
    }
    
    /**
     * Gets all HTTP headers
     * @return array
     */
    public function headers() {
        
        return $this->headers;
    }
    
    /**
     * Gets an HTTP header value if exists
     * @param string $name
     * @return string
     */
    public function header($name) {
        
        return (isset($this->headers[$name])) ? $this->headers[$name] : '';
    }
    
    /**
     * Gets all App info
     * @return array
     */
    public function app() {
        
        return (object)$this->app;
    }
    
    /**
     * Verifies if a request parameter is set
     * @param type $parameter
     * @param boolean $emptyIsValid
     * @return boolean
     */
    private function hasParameter($parameter, $emptyIsValid) {
        
        $value = $this->{$parameter};
        
        if ($value === null) {

             return false;
        }
        
        if ($emptyIsValid === false) {
            
            if (empty($value)) {
                
                return false;
            }
        }

        return true;
    }
    
    /**
     * Verifies if all request parameters are set
     * @param array $parameters
     * @param boolean $emptyIsValid
     * @return boolean
     */
    private function hasParameters(array $parameters, $emptyIsValid) {
        
        foreach ($parameters as $parameter) {
            
            if (!$this->hasParameter($parameter, $emptyIsValid)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifies if request parameters are set
     * @param mixed $value
     * @param boolean $emptyIsValid If true, an empty string is considered as a valid value
     * @return boolean
     */
    public function has($value, $emptyIsValid = false) {
        
        if (!is_array($value)) {
            
            return $this->hasParameter($value, $emptyIsValid);
        }
        
        return $this->hasParameters($value, $emptyIsValid);
    }
    
    /**
     * Verifies if a file exists
     * @param type $file
     * @return boolean
     */
    public function hasFile($file) {
        
        if (isset($this->files[$file])) {
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Gets a file
     * @param type $file
     * @return File
     */
    public function getFile($file) {
        
        if ($this->hasFile($file)) {   
            
            return new File($this->files[$file]);
        }
        
        return new File();
    }
    
    /**
     * Returns if the request has a valid CSRF valid token
     * @return type
     */
    public function hasValidCsrf() {
        
        return $this->csrfValid;
    }
}