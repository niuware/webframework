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
* Renders a JSON response
*/
abstract class ApiResponse
{
    /**
     * The Response instance
     * 
     * @var Niuware\WebFramework\Http\Response;
     */
    public $response;
    
    /**
     * The API instance
     * @var Niuware\WebFramework\Http\Api 
     */
    private $api;
    
    /**
     * Initializes the default values of the class
     * 
     * @param \Niuware\WebFramework\Http\Api $api
     * @return void
     */
    public function __construct(Api $api)
    {
        $this->response = new Response();
        $this->api = $api;
    }

    /**
     * Gets the API endpoint response
     * 
     * @param int $options
     * @param int $depth
     * @return string
    */
    protected function render($options = 0, $depth = 512) {
        
        $this->api->setOutputOptions($options, $depth);
        
        return $this->response->output();
    }
}