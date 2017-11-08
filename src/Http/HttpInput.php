<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework\Http;
    
/**
* Process an HTTP request
*/
final class HttpInput {
    
    private $requestMethod;

    function __construct($requestMethod) {
        
        $this->requestMethod = $requestMethod;
    }
    
    /**
     * Parse POST or DELETE input data
     */
    private function methodParse() {
        
        if ($this->requestMethod === 'delete') {
            
            $result = [];

            parse_str(file_get_contents('php://input'), $result);
            
            return $result;
        }
        else {

            return filter_input_array(INPUT_POST);
        }
    }
    
    /**
     * Parses a POST or DELETE input
     * @param array $data
     * @param array $files
     */
    public function parse(&$data, &$files) {
        
        $data = null;
        $files = null;
        
        if ($this->requestMethod === 'post' || $this->requestMethod === 'delete') {

            $contentType = filter_input(INPUT_SERVER, 'CONTENT_TYPE');

            if (substr($contentType, 0, 16) == 'application/json') {

                $data = json_decode(file_get_contents('php://input'), true);
            }
            else {
                
                $data = $this->methodParse();
            }

            $files = $_FILES;
        }
    }

    /**
    * Instantiate a new API class object to execute an 
    * API call, depending on the type of HTTP requested method
    */
    public function withApi() {

        $api = new Api($this->requestMethod);
        
        if ($this->requestMethod === 'post' || $this->requestMethod === 'delete') {
            
            $data = null;
            $files = null;
            
            $this->parse($data, $files);
            
            $api->postApi($data, $files, $this);

        } elseif ($this->requestMethod === 'get') {

            $api->getApi($this);
        }
        else {
            
            $api->unsupportedRequestMethod();
            
            exit;
        }
    }
    
    /**
     * Gets the Request instance for the current Http request
     * @param type $controller
     * @param type $action
     * @param type $subSpace
     * @param array $params
     * @return \Niuware\WebFramework\Http\HttpRequest
     * @throws \Exception
     */
    public function getRequestInstance($controller, $action, $subSpace, array $params) {
        
        if (empty($params)) {
            
            $params = ['params' => null, 'files' => null, 'requestUri' => null, 'app' => null];
        }
        
        $requestClass = $this->getRequestClassName($controller, $action, $subSpace);
        
        if (class_exists($requestClass)) {
            
            if (get_parent_class($requestClass) !== 'Niuware\WebFramework\Http\Request') {
                
                throw new \Exception("The class " . $requestClass . " does not inherit from Niuware\WebFramework\Http\Request class.");
            } 
            
            $implements = class_implements($requestClass);
        
            if (!in_array('Niuware\WebFramework\Http\RequestInterface', $implements)) {
                
                throw new \Exception("The class " . $requestClass . " does not implements the Niuware\WebFramework\Http\RequestInterface interface.");
            }
            
            $requestObj = new $requestClass($params, $this->requestMethod);
        }
        else {
            
            $requestObj = new HttpRequest($params, $this->requestMethod);
        }
        
        $requestObj->validate();
        
        return $requestObj;
    }
    
    /**
     * Gets the Request class name
     * @param type $controller
     * @param type $action
     * @param type $subSpace
     * @return string
     */
    private function getRequestClassName($controller, $action, $subSpace) {
        
        $requestClass = "\App\Requests\\";
        
        if ($subSpace !== 'main') {
            
            $requestClass.= ucfirst($subSpace) . "\\";
        }
        
        $requestClass.= $controller . $action . 'Request';
        
        return $requestClass;
    }
}