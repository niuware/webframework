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

namespace Niuware\WebFramework\Validation;

use Niuware\WebFramework\Http\Request;
    
/**
 * Validates a Request
 */
final class Validate
{
    /**
     * The validation rules
     * @var array 
     */
    private $rules;
    
    /**
     * The Request to validate
     * @var Niuware\WebFramework\Http\Request 
     */
    private $request;
    
    /**
     * The validation errors
     * 
     * @var array 
     */
    private $errors = [];
    
    /**
     * Initializes the validation
     * 
     * @param Niuware\WebFramework\Http\Request $request
     * @param array $rules
     * @return void
     */
    public function __construct(Request $request, $rules = [])
    {
        $this->setRequest($request);
        $this->setRules($rules);
    }
    
    /**
     * Sets the rules to validate
     * 
     * @param array $rules
     * @return void
     */
    public function setRules(array $rules)
    {
        $this->rules = $rules;
    }
    
    /**
     * Sets the Request object to validate
     * 
     * @param Niuware\WebFramework\Http\Request $request
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }
    
    /**
     * Runs the validation rules against the request
     * 
     * @return void
     */
    public function run()
    {
        foreach ($this->rules as $field => $rules) {
            
            $this->validateField($field, $rules);
        }
    }
    
    /**
     * Verifies the validation
     * 
     * @return bool
     */
    public function isValid()
    {
        return empty($this->errors);
    }
    
    /**
     * Gets all validation errors
     * 
     * @return array
     */
    public function getErrors()
    {
        return $this->errors;
    }
    
    /**
     * Gets the first detected error
     * 
     * @return array
     */
    public function getFirstError()
    {
        if (!empty($this->errors)) {
            
            $first = reset($this->errors);
            
            if ($first) {
                
                return [key($this->errors) => reset($first)];
            }
            
            return $first;
        }
        
        return [];
    }
    
    /**
     * Gets the last detected error
     * 
     * @return array
     */
    public function getLastError()
    {
        if (!empty($this->errors)) {
            
            $last = end($this->errors);
            
            if ($last) {
                
                return [key($this->errors) => end($last)];
            }
            
            return $last;
        }
        
        return [];
    }
    
    /**
     * Validate a field in the request
     * 
     * @param name $field
     * @param array $rules
     * @return void
     */
    private function validateField($field, $rules)
    {
        foreach ($rules as $rule => $msg) {
            
            $args = $this->parseRule($rule);
            
            if ($this->required($field, $args, $msg)) {
                continue;
            }
            
            if ($this->pattern($field, $args, $msg)) {
                continue;
            }
            
            if ($this->numericRules($field, $args, $msg)) {
                continue;
            }
            
            if ($this->mutableRules($field, $args, $msg)) {
                continue;
            }
        }
    }
    
    /**
     * Parses the rule name and its arguments
     * 
     * @param array $rule
     * @return array
     */
    private function parseRule($rule)
    {
        $args = explode('|', $rule);
        
        if (!isset($args[0])) {
            
            $args[0] = $rule;
        }
        
        if (!isset($args[1])) {
            
            $args[1] = null;
        }
        
        return $args;
    }
    
    /**
     * Run numeric rules
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function numericRules($field, $args, $msg)
    {
        if ($this->numeric($field, $args, $msg)) {
            return true;
        }

        if ($this->minlength($field, $args, $msg)) {
            return true;
        }

        if ($this->maxlength($field, $args, $msg)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Run field mutable rules
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function mutableRules($field, $args, $msg)
    {
        if ($this->defaultIfNull($field, $args, $msg)) {
            return true;
        }
        
        if ($this->cast($field, $args, $msg)) {
            return true;
        }

        if ($this->callback($field, $args, $msg)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Requires the existence of a field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function required($field, $args, $msg)
    {
        if ($args[0] === 'required') {
            
            if (!$this->request->has($field, $args[1])) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Requires a minimum length for the value of a field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function minlength($field, $args, $msg)
    {
        if ($args[0] === 'minlength') {
            
            if (strlen($this->request->$field) < $args[1]) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Requires a maximum length for the value of a field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function maxlength($field, $args, $msg)
    {
        if ($args[0] === 'maxlength') {
            
            if (strlen($this->request->$field) > $args[1]) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Requires a matching pattern for the value of a field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function pattern($field, $args, $msg)
    {
        if ($args[0] === 'pattern') {
            
            if (!preg_match($args[1], $this->request->$field)) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Requires a numeric string for the value of a field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function numeric($field, $args, $msg)
    {
        if ($args[0] === 'numeric') {
            
            if (!is_numeric($this->request->$field)) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Sets a value for the field if it is null or 
     * different from the default value
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function defaultIfNull($field, $args, $msg)
    {
        if ($args[0] === 'default') {
            
            if ($args[1] !== null) {
                
                $validValues = explode(',', $args[1]);
                $validation = true;
                
                if (count($validValues) > 1) {
                    
                    $validation = in_array($this->request->$field, $validValues);
                }
                else {
                    
                    $validation = $this->request->$field === $args[1];
                }
                
                if (($this->request->has($field) && !$validation) || 
                        !$this->request->has($field)){

                    $this->request->$field = $msg;
                }
            }
            else {
                
                $this->request->$field = $msg;
            }

            return true;
        }
        
        return false;
    }
    
    /**
     * Cast the field value to the given type
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function cast($field, $args, $msg)
    {
        if ($args[0] === 'cast') {
            
            $types = ['bool', 'int', 'double', 'object', 'string', 'array'];
                
            if ($this->request->has($field) && in_array($msg, $types)) {

                $value = $this->request->$field;
                
                settype($this->request->$field, $msg);
                
                $this->request->$field = $value;
            }

            return true;
        }
        
        return false;
    }
    
    /**
     * Executes a callback function in the field
     * 
     * @param string $field
     * @param array $args
     * @param string $msg
     * @return bool
     */
    private function callback($field, $args, $msg)
    {
        if ($args[0] === 'callback') {
                
            if ($this->request->has($field) && function_exists($msg)) {
                
                // The function has a return value
                if ($args[1] === null) {
                    
                    $this->request->$field = $msg($this->request->$field);
                }
                // The function mutates the variable by reference
                else if ($args[1] === "true") {
                    
                    $msg($this->request->$field);
                }
            }

            return true;
        }
        
        return false;
    }
    
    /**
     * Appends a validation error on the errors array
     * 
     * @param string $field
     * @param string $rule
     * @param string $error
     * @return void
     */
    private function appendError($field, $rule, $error)
    {
        if (!isset($this->errors[$field])) {
         
            $this->errors[$field] = [];
        }
            
        $this->errors[$field][$rule] = $error;
    }
}
