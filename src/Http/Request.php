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

use Niuware\WebFramework\Auth\Security;
use Niuware\WebFramework\Validation\Validate;

/**
 * Base class for a request
 */
abstract class Request
{
    /**
     * The request headers
     * 
     * @var array 
     */
    private $headers = [];
    
    /**
     * The request attributes
     * 
     * @var array 
     */
    private $attributes = [];
    
    /**
     * The request files
     * 
     * @var array 
     */
    private $files = [];
    
    /**
     * The request application attributes
     * 
     * @var array 
     */
    private $app = [];
    
    /**
     * The validation instance
     * 
     * @var Niuware\WebFramework\Validation\Validate 
     */
    private $validate;
    
    /**
     * The request method
     * 
     * @var string 
     */
    private $method = "";
    
    /**
     * A flag that determines if the request has a valid CSRF token
     * 
     * @var bool 
     */
    private $csrfValid = false;
    
    /**
     * Gets a request property
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            
            return $this->attributes[$name];
        }
        
        return null;
    }

    /**
     * Sets a request property
     * 
     * @param string $name
     * @param mixed $value
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }

    /**
     * Initializes the request
     * 
     * @param array $params
     * @param string $method
     * @return void
     */
    public function __construct(array $params, $method = "")
    {
        $parameters = (isset($params['params'])) ? $params['params'] : [];
        $files = (isset($params['files'])) ? $params['files'] : null;
        $requestUri = (isset($params['requestUri'])) ? $params['requestUri'] : null;
        $app = (isset($params['app'])) ? $params['app'] : [];
        
        $this->method = $method;
        
        if ($parameters !== null) {
            
            $this->attributes = $parameters;
            
            if (isset($parameters['csrf_token'])) {
                
                $this->verifyCsrfToken($parameters['csrf_token']);
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
        
        $this->validate = new Validate($this);
    }
    
    /**
     * Validates a CSRF token
     * 
     * @param string $token
     * @return void
     */
    private function verifyCsrfToken($token)
    {
        $this->csrfValid = Security::verifyCsrfToken($token);
    }
    
    /**
     * Sets all HTTP request headers
     * 
     * @return void
     */
    private function setHeaders()
    {
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
     * Gets all HTTP request headers
     * 
     * @return array
     */
    public function headers()
    {
        return $this->headers;
    }
    
    /**
     * Gets an HTTP header value
     * 
     * @param string $name
     * @return string
     */
    public function header($name)
    {
        return (isset($this->headers[$name])) ? $this->headers[$name] : '';
    }
    
    /**
     * Gets the application details
     * 
     * @return object
     */
    public function app()
    {
        return (object)$this->app;
    }
    
    /**
     * Verifies a request parameter
     * 
     * @param string $parameter
     * @param bool $emptyIsValid
     * @return bool
     */
    private function hasParameter($parameter, $emptyIsValid)
    {
        $value = $this->{$parameter};
        
        if ($value === null) {

             return false;
        }
        
        // If $emptyIsValid is true, an empty string 
        // is considered as a valid value
        if ($emptyIsValid === false) {
            
            if (empty($value)) {
                
                return false;
            }
        }

        return true;
    }
    
    /**
     * Verifies all request parameters are set
     * 
     * @param array $parameters
     * @param bool $emptyIsValid
     * @return bool
     */
    private function hasParameters(array $parameters, $emptyIsValid)
    {
        foreach ($parameters as $parameter) {
            
            if (!$this->hasParameter($parameter, $emptyIsValid)) {
                
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Verifies multiple request parameters
     * 
     * @param mixed $value
     * @param bool $emptyIsValid
     * @return bool
     */
    public function has($value, $emptyIsValid = false)
    {
        if (!is_array($value)) {
            
            return $this->hasParameter($value, $emptyIsValid);
        }
        
        return $this->hasParameters($value, $emptyIsValid);
    }
    
    /**
     * Verifies the existence of a file within the request
     * 
     * @param string $file
     * @return bool
     */
    public function hasFile($file)
    {
        if (isset($this->files[$file])) {
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Gets a file
     * 
     * @param string $file
     * @return \Niuware\WebFramework\Http\File
     */
    public function getFile($file)
    {
        if ($this->hasFile($file)) {   
            
            return new File($this->files[$file]);
        }
        
        return new File();
    }
    
    /**
     * Verifies a valid CSRF token in the request
     * 
     * @return bool
     */
    public function hasValidCsrf()
    {
        return $this->csrfValid;
    }
    
    /**
     * Returns the validation instance
     * 
     * @return \Niuware\WebFramework\Validation\Validate
     */
    public function validation()
    {
        return $this->validate;
    }
    
    /**
     * Validates the request against the provided rules
     * 
     * @param array $rules
     * @return void
     */
    protected function validateWith($rules)
    {
        $methodRules = (isset($rules[$this->method])) ? $rules[$this->method] : [];
        
        $this->validate->setRules($methodRules);
        
        $this->validate->run();
    }
}
