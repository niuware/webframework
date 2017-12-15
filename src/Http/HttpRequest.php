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

/**
 * HTTP default request class
 */
final class HttpRequest extends Request implements RequestInterface
{
    /**
     * Initializes the parent class
     * 
     * @param array $params
     * @param string $method
     * @return void
     */
    public function __construct(array $params, $method = "")
    {
        parent::__construct($params, $method);
    }
    
    /**
     * Gets the request validation rules
     * 
     * @return array
     */
    public function rules() {
        
        return [];
    }
    
    /**
     * Runs a validation
     * 
     * @return void
     */
    public function validate()
    {
        parent::validateWith($this->rules());
    }
}