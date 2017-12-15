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
 * Hash and token generation and verification
 */
class Security
{
    /**
     * Creates a hash from a string
     * 
     * @param string $toHash
     * @return string
     */
    public static function hash($toHash)
    {
        return password_hash($toHash, \PASSWORD_DEFAULT);
    }
    
    /**
     * Verifies a hash
     * 
     * @param string $source
     * @param string $fromHash
     * @return bool
     */
    public static function verifyHash($source, $fromHash)
    {
        return password_verify($source, $fromHash);
    }
    
    /**
     * Generates a cryptographic token
     * 
     * @param int $length
     * @return string
     */
    public static function generateToken($length = 32)
    {
        $bytes = random_bytes($length);
        
        return bin2hex($bytes);
    }
    
    /**
     * Sets the application csrf token
     * 
     * @param string|null $data
     * @return string
     */
    public static function getCsrfToken($data = null)
    {
        if (Auth::has('token', 'csrf') === false) {
            
            Auth::add('token', self::generateToken(), 'csrf');
        }
        
        if (Auth::has('token2', 'csrf') === false) {
            
            Auth::add('token2', self::generateToken(), 'csrf');
        }
        
        if ($data === null) {
            
            return Auth::get('token', 'csrf');
        }
        
        return hash_hmac('sha256', $data, Auth::get('token2', 'csrf'));
    }
    
    /**
     * Verifies a token
     * 
     * @param string $token
     * @param string|null $data
     * @return bool
     */
    public static function verifyCsrfToken($token, $data = null)
    {
        if (empty($token)) {
            
            return false;
        }
        
        if ($data === null) {
            
            $session = Auth::get('token', 'csrf');
            
            if ($session !== null) {
            
                return hash_equals(Auth::get('token', 'csrf'), $token);
            }
        }
        else {
            $session = Auth::get('token2', 'csrf');

            if ($session !== null) {

                $hash = hash_hmac('sha256', $data, Auth::get('token2', 'csrf'));

                return hash_equals($hash, $token);
            }
        }
        
        return false;
    }
}
