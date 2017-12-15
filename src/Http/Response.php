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
 * Base class for a controller response
 */
final class Response
{
    /**
     * The response data
     * 
     * @var array 
     */
    private $data = [];

    /**
     * A flag to determine the occurrence of an error
     * 
     * @var bool 
     */
    private $error = false;
    
    /**
     * The default response values
     * 
     * @param array $defaultValues
     * @return void
     */
    public function __construct($defaultValues = [])
    {
        if (!empty($defaultValues)) {
            
            $this->add($defaultValues, false, true);
        }
    }
    
    /**
     * Gets a value from the response
     * 
     * @param string $name
     * @return mixed
     */
    public function __get($name)
    {
        if ($name !== 'error') {
            
            return $this->data[$name];
        }
        else {
            
            return $this->error;
        }
    }
    
    /**
     * Sets a response value
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        if ($name !== 'error') {
            
            $this->data[$name] = $value;
        }
        else {
            
            $this->error = $value;
        }
    }
    
    /**
     * Gets the response data
     * 
     * @return mixed
     */
    public function data() {
        
        return $this->data;
    }
    
    /**
     * Returns the error status
     * 
     * @return mixed
     */
    public function error()
    {
        return $this->error;
    }
    
    /**
     * Adds multiple values at once
     * 
     * @param array $data
     * @param bool $clear
     * @param bool $overwriteError
     * @return void
     */
    public function add(array $data, $clear = false, $overwriteError = false)
    {
        // Prevent error overwrite
        $saveError = $this->error;
        
        if ($clear === true) {
            
            $this->clear();
        }
        
        foreach ($data as $key => $value) {
            
            $this->{$key} = $value;
        }
        
        if ($overwriteError === false) {
            
            $this->error = $saveError;
        }
    }
    
    /**
     * Removes multiple keys from the data array
     * 
     * @param array $keys
     * @return void
     */
    public function remove(array $keys)
    {
        // Prevent error overwrite
        $saveError = $this->error;
        
        foreach ($keys as $key) {
            
            if (isset($this->data[$key])) {

                unset($this->data[$key]);
            }
        }
        
        $this->error = $saveError;
    }
    
    /**
     * Clears the response data
     * 
     * @return void
     */
    public function clear()
    {
        $this->data = [];
    }
    
    /**
     * Returns the response array (data and error)
     * 
     * @return array
     */
    public function output()
    {
        $response = [

            'data' => $this->data(),
            'error' => $this->error()
        ];
        
        return $response;
    }
    
    /**
     * Renders the output response as a JSON string
     * 
     * @return void
     */
    public function render($options = 0, $depth = 512)
    {
        echo json_encode($this->output(), $options, $depth);
    }
}