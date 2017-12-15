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

namespace Niuware\WebFramework\Auth; 

/**
 * Handles the Application Space authentication sessions
 */
final class Auth
{
    /**
     * Starts a new session
     * 
     * @return void
     */
    public static function start()
    {
        session_start();
        
        self::requireAuth(false);
        self::requireAuth(false, 'admin');
    }
    
    /**
     * Requires an authentication
     * 
     * @param bool $value   
     * @param string $mode
     * @return void
     */
    public static function requireAuth($value, $mode = 'main')
    {
        $_SESSION['nwf_auth_' . $mode . '_' . session_id()] = $value;
    }
    
    /**
     * Verifies an authentication requirement
     * 
     * @param string $mode
     * @return bool
     */
    public static function useAuth($mode = 'main')
    {
        if (isset($_SESSION['nwf_auth_' . $mode . '_' . session_id()])) {
            
            return $_SESSION['nwf_auth_' . $mode . '_' . session_id()];
        }
        
        return false;
    }
    
    /**
     * Verifies an authentication
     * 
     * @param string $mode
     * @return bool
     */
    public static function verifiedAuth($mode = 'main')
    {
        if (isset($_SESSION['nwf_auth_' . $mode . '_' . '_login_' . session_id()])) {
            
            return $_SESSION['nwf_auth_' . $mode . '_' . '_login_' . session_id()];
        }
        
        return false;
    }
    
    /**
     * Sets a valid authentication
     * 
     * @param string $mode
     * @return void
     */
    public static function grantAuth($mode = 'main')
    {
        $_SESSION['nwf_auth_' . $mode . '_' . '_login_' . session_id()] = true;
    }
    
    /**
     * Revokes an authentication
     * 
     * @param string $mode
     * @return void
     */
    public static function revokeAuth($mode = 'main')
    {
        $_SESSION['nwf_auth_' . $mode . '_' . '_login_' . session_id()] = false;
    }
    
    /**
     * Adds a value to the current session
     * 
     * @param string $name
     * @param mixed $value
     * @param string $mode
     * @return void
     */
    public static function add($name, $value, $mode = 'main')
    {
        $_SESSION['nwf_user_' . $mode . '_' . $name . '_' . session_id()] = $value;
    }
    
    /**
     * Verifies a value within the session
     * 
     * @param string $name
     * @param string $mode
     * @return bool
     */
    public static function has($name = '', $mode = 'main')
    {
        return isset($_SESSION['nwf_user_' . $mode . '_' . $name . '_' . session_id()]);
    }
    
    /**
     * Removes a value from the session
     * 
     * @param string $name
     * @param string $mode
     * @return void
     */
    public static function remove($name = '', $mode = 'main')
    {
        unset($_SESSION['nwf_user_' . $mode . '_' . $name . '_' . session_id()]);
    }
    
    /**
     * Gets a value from the session
     * 
     * @param string $name
     * @param string $mode
     * @return mixed|null
     */
    public static function get($name, $mode = 'main')
    {
        if (self::has($name, $mode)) {
            
            return $_SESSION['nwf_user_' . $mode . '_' . $name . '_' . session_id()];
        }
        else {
            
            return null;
        }
    }
    
    /**
     * Unset session variables by name
     * 
     * @param string $filter
     * @param string $mode
     * @return void
     */
    private static function destroyWithFilter($filter, $mode)
    {
        $prefix = $filter . '_' . $mode . '_';
        $prefixLength = strlen($prefix);
        
        foreach ($_SESSION as $var => $value) {
            
            if (substr($var, 0, $prefixLength) === $prefix) {
                
                unset($_SESSION[$var]);
            }
        }
    }
    
    /**
     * Destroys all session variables
     * 
     * @param string $mode
     * @return void
     */
    public static function destroy($mode = 'main')
    {
        self::destroyWithFilter('nwf_user', $mode);
    }
    
    /**
     * Destroys all authentication and session variables
     * 
     * @param string $mode
     * @return void
     */
    public static function end($mode = 'main')
    {
        self::destroyWithFilter('nwf_user', $mode);
        self::destroyWithFilter('nwf_auth', $mode);
    }
}
