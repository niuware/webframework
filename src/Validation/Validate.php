<?php 

/**
* This class is part of the core of Niuware WebFramework 
* and is not particularly intended to be modified.
* For information about the license please visit the 
* GIT repository at:
* https://github.com/niuware/web-framework
*/
namespace Niuware\WebFramework\Validation;

use Niuware\WebFramework\Http\Request;
    
/**
* Validates a request
*/
final class Validate {
    
    private $rules;
    
    private $request;
    
    private $errors = [];
    
    function __construct(Request $request, $rules = []) {
        
        $this->setRequest($request);
        $this->setRules($rules);
    }
    
    /**
     * Sets the rules array to validate
     * @param array $rules
     */
    public function setRules(array $rules) {
        
        $this->rules = $rules;
    }
    
    /**
     * Sets the Request object to validate
     * @param Request $request
     */
    public function setRequest(Request $request) {
        
        $this->request = $request;
    }
    
    /**
     * Runs the validation rules over the request
     */
    public function run() {
        
        foreach ($this->rules as $field => $rules) {
            
            $this->validateField($field, $rules);
        }
    }
    
    /**
     * Validation is valid?
     * @return bool
     */
    public function isValid() {
        
        return empty($this->errors);
    }
    
    /**
     * Get all validation errors
     * @return type
     */
    public function getErrors() {
        
        return $this->errors;
    }
    
    /**
     * Returns the first error
     * @return type
     */
    public function getFirstError() {
        
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
     * Returns the last error
     * @return type
     */
    public function getLastError() {
        
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
     * @param type $field
     * @param type $rules
     */
    private function validateField($field, $rules) {
        
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
     * Parses the rule name and its parameters
     * @param type $rule
     * @return type
     */
    private function parseRule($rule) {
        
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
     * Execute numeric rules
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function numericRules($field, $args, $msg) {
        
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
     * Execute field mutable rules
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function mutableRules($field, $args, $msg) {
        
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
     * Validates the required rule over a field
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function required($field, $args, $msg) {
        
        if ($args[0] === 'required') {
            
            if (!$this->request->has($field, $args[1])) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validates the minlength rule over a field
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function minlength($field, $args, $msg) {
        
        if ($args[0] === 'minlength') {
            
            if (strlen($this->request->$field) < $args[1]) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validates the maxlength rule over a field
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function maxlength($field, $args, $msg) {
        
        if ($args[0] === 'maxlength') {
            
            if (strlen($this->request->$field) > $args[1]) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validates the pattern rule over a field
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function pattern($field, $args, $msg) {
        
        if ($args[0] === 'pattern') {
            
            if (!preg_match($args[1], $this->request->$field)) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Validates the pattern rule over a field
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function numeric($field, $args, $msg) {
        
        if ($args[0] === 'numeric') {
            
            if (!is_numeric($this->request->$field)) {
                
                $this->appendError($field, $args[0], $msg);
            }
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Set a value if the field is null or different from the default value
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function defaultIfNull($field, $args, $msg) {
        
        if ($args[0] === 'default') {
            
            if ($args[1] !== null) {
                
                if (($this->request->has($field) && $this->request->$field !== $args[1]) || 
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
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function cast($field, $args, $msg) {
        
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
     * @param type $field
     * @param type $args
     * @param type $msg
     * @return boolean
     */
    private function callback($field, $args, $msg) {
        
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
     * @param type $field
     * @param type $rule
     * @param type $error
     */
    private function appendError($field, $rule, $error) {
        
        if (!isset($this->errors[$field])) {
         
            $this->errors[$field] = [];
        }
            
        $this->errors[$field][$rule] = $error;
    }
}
