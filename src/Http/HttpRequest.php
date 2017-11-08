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
* HTTP default request class
*/
final class HttpRequest extends Request implements RequestInterface {
    
    function __construct(array $params, $method = "") {
        parent::__construct($params, $method);
    }
    
    public function rules() {
        
        return [];
    }
    
    function validate() {
        parent::validateWith($this->rules());
    }
}