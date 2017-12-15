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

use Niuware\WebFramework\Database\Database;
    
/**
 * Executes an API call
 */
final class Api
{
    /**
     * A flag to set the occurrence of an error
     * 
     * @var bool 
     */
    private $error;
    
    /**
     * The code error
     * 
     * @var string 
     */
    private $errCode;

    /**
     * The API class name
     * 
     * @var string 
     */
    private $className;
    
    /**
     * The API base class name
     * @var string 
     */
    private $baseClassName;
    
    /**
     * The API class method
     * 
     * @var string 
     */
    private $methodName;
    
    /**
     * The request URI
     * @var string 
     */
    private $currentUri;
    
    /**
     * The HTTP request method
     * 
     * @var string 
     */
    private $requestMethod;
    
    /**
     * The request instance
     * @var \Niuware\WebFramework\Http\Request 
     */
    private $params;
    
    /**
     * The API method result
     * 
     * @var array 
     */
    private $methodResponse;
    
    /**
     * The JSON encoding (JSON_CONSTANTS) options
     * 
     * @var int 
     */
    private $outputOpts;
    
    /**
     * The JSON encoding maximum depth
     * 
     * @var int 
     */
    private $outputDepth;
    
    /**
     * The API version to load
     * 
     * @var string 
     */
    private $versionNamespace;

    /**
     * Initializes the class
     * 
     * @param string $requestMethod
     * @return void
     */
    public function __construct($requestMethod)
    {
        $this->error = true;
        $this->requestMethod = $requestMethod;
        $this->methodResponse = [];
        $this->rendered = false;
        $this->params = new HttpRequest([]);
        
        register_shutdown_function(function() {
            
            $this->shutdown(error_get_last());
        });
    }
    
    /**
     * Gets an API execution error
     * 
     * @param array $error
     * @return array
     */
    private function getDetailedError($error)
    {
        $output = [];
        
        $output['error'] = 'There was an unknown error in the execution of this endpoint.';

        if (isset($error['message'])) {

            $output['error'] = 'Error while executing the endpoint: ' . $this->className . ':' . $this->methodName;
            $output['file'] = 'File: ' . $error['file']. ' at line ' . $error['line'];

            $errorListRaw = explode("\n", $error['message']);
            $errorList = [];

            foreach ($errorListRaw as $err) {

                $errorList[] = $err;
            }

            $output['trace'] = $errorList;
        }
        
        return $output;
    }
    
    /**
     * Renders the endpoint JSON output
     * 
     * @param array $error
     * @return void
     */
    private function shutdown($error)
    {
        if (!empty($error)) {
            
            $this->errCode = '0x205';
            $this->error = true;
            
            $output = $this->getDetailedError($error);
        }
        else {
            
            $output = $this->methodResponse;
        }
        
        $this->response($output);
    }
    
    /**
     * Sets the requested configuration for the API call
     * 
     * @return bool
     */
    private function initialize()
    {
        $this->currentUri = parse_url(filter_input(\App\Config\SERVER_ENV_VAR, 'REQUEST_URI', FILTER_SANITIZE_URL));

        $func = $this->actionPath($this->currentUri['path']);
        
        $offset = $this->setVersionNamespace($func);
        
        $this->setGetMethod($func, $offset);
        
        if (isset($func[1 + $offset]) && !empty($func[1 + $offset]))
        {
            $this->className = "App\Api\\";
            $this->baseClassName = ucfirst($func[1 + $offset]);
            
            if ($this->versionNamespace !== null) {
                
                $this->className.= ucfirst($this->versionNamespace) . "\\";
            }
            
            $this->className.= ucfirst($func[1 + $offset]);
            $this->methodName = str_replace(['-', '_'], '', $func[2 + $offset]);
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Sets the API version to load
     * 
     * @param array $func
     * @return int
     */
    private function setVersionNamespace($func)
    {
        $matches = [];
        
        if (isset($func[1]) && preg_match("/^(v\d+\.\d+\.\d+)$/", $func[1], $matches)) {
            
            $this->versionNamespace = str_replace(".", "", $matches[0]);
            
            return 1;
        }
        
        return 0;
    }

    /**
     * Executes an API endpoint
     * 
     * @return void
     */
    private function start()
    {
        $this->load();

        $this->execute();
    }

    /**
     * Loads the database
     * 
     * @return void
     */
    private function load()
    {
        Database::boot();
    }

    /**
     * Renders the API endpoint response
     * 
     * @param string $output
     * @return void
     */
    private function response($output)
    {
        header('Content-Type: application/json');
        
        if ($this->error) {
            
            echo json_encode(['error' => true, 'data' => ['errcode' => $this->errCode, 'error_message' => $output]]);
        }
        else {
            
            $json = json_encode($output, $this->outputOpts, $this->outputDepth);
            
            if (function_exists('mb_strlen')) {
                
                $size = mb_strlen($json, '8bit');
                
            } else {
                
                $size = strlen($json);
            }
            
            header('Content-Length: ' . $size);
            
            echo $json;
        }
    }

    /**
     * Instantiate an object of an API class and
     * executes the requested method
     * 
     * @return void
     */
    private function execute()
    {
        if (class_exists($this->className)) {

            $instance = new $this->className($this);
            
            if (get_parent_class($instance) !== __NAMESPACE__ . '\ApiResponse' ) {
                
                $this->errCode = "0x204";
                
                return;
            }
            
            if ($this->verifyMethod($instance)) {
                
                $this->error = false;

                $this->methodResponse = call_user_func([$instance, $this->methodName], $this->params);
                
            } else {
                
                $this->errCode = "0x202";
            }

        } else {

            $this->errCode = "0x203";
        }
    }
    
    /**
     * Verifies if the called API class method exists
     * 
     * @param \Niuware\WebFramework\Http\ApiResponse $obj
     * @return bool
     */
    private function verifyMethod(&$obj)
    {
        $baseMethodName = $this->methodName;
        $this->methodName = $this->requestMethod . $baseMethodName;

        if (!method_exists($obj, $this->methodName)) {

            $this->methodName = $baseMethodName;

            if (!method_exists($obj, $this->methodName)) {

                return false;
            }
        }
        
        $reflection = new \ReflectionMethod($obj, $this->methodName);
        
        if (!$reflection->isPublic()) {
            
            return false;
        }
        
        return true;
    }

    /**
     * Parses the requested URL
     * 
     * @param string $customPath
     * @return array
     */
    private function actionPath($customPath = "")
    {
        $currentPath = $customPath;

        if (\App\Config\BASE_PATH == "/") {

            $path = substr($currentPath, 1);

        } else {

            $path = str_replace('/' . \App\Config\BASE_PATH, '', $currentPath);
        }

        return explode('/', $path);
    }
    
    /**
     * Sets the default values for an unset requested method
     * 
     * @param array $func
     * @param int $offset
     * @return void
     */
    private function setGetMethod(array &$func, $offset)
    {
        if (!isset($func[2 + $offset])) {
            
            $func[2 + $offset] = "";
        }
    }

    /**
     * Executes an HTTP POST requested endpoint
     * 
     * @param array $params
     * @param array $files
     * @param Niuware\WebFramework\Http\Input $input
     * @return void
     */
    public function postApi($params, $files = null, $input = null)
    {
        if ($this->initialize()) {
            
            if ($input !== null) {
            
                $this->params = $input->getRequestInstance($this->baseClassName, $this->methodName, "api", [
                                'params' => $params,
                                'files' => $files,
                                'requestUri' => $this->currentUri,
                                'app' => []
                            ]);
            }

            $this->start();
        }
    }

    /**
     * Executes an HTTP GET requested endpoint
     * 
     * @param Niuware\WebFramework\Http\Input $input
     * @return void
     */
    public function getApi($input)
    {
        if ($this->initialize()) {
        
            // Parse the query for the requested URL
            if (isset($this->currentUri['query'])) {

                $params = [];

                parse_str($this->currentUri['query'], $params);

                $this->params = $input->getRequestInstance($this->baseClassName, $this->methodName, "api", [
                            'params' => $params,
                            'files' => null,
                            'requestUri' => $this->currentUri,
                            'app' => []
                        ]);
            }

            $this->start();
        }
    }

    /**
     * Sets the 'json_encode' output options
     * 
     * @param int $options
     * @param int $depth
     * @return void
     */
    public function setOutputOptions($options, $depth)
    {
        $this->outputOpts = $options;
        $this->outputDepth = $depth;
    }
    
    /**
     * Sets the 'Unsupported Request Method' code error
     */
    public function unsupportedRequestMethod()
    {
        $this->errCode = "0x206";
    }
}