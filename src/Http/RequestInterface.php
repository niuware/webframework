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

interface RequestInterface
{
    /**
     * Gets the request validation rules
     * 
     * @return array
     */
    public function rules();
    
    /**
     * Runs a validation
     * 
     * @return void
     */
    public function validate();
}
