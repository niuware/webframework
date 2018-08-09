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

use Niuware\WebFramework\Auth\Security;
use Niuware\WebFramework\Exception\FrameworkException;

/**
 * File handling
 */
final class File
{
    /**
     * The HTTP file request attributes
     * 
     * @var array 
     */
    private $original_request = [];
    
    /**
     * The file type
     * 
     * @var string 
     */
    public $filetype;
    
    /**
     * Initializes the default attributes
     * 
     * @param array $attributes
     * @return void
     */
    public function __construct($attributes = null)
    {
        $this->set($attributes, 'name');
        $this->set($attributes, 'type');
        $this->set($attributes, 'tmp_name');
        $this->set($attributes, 'error');
        $this->set($attributes, 'size');
    }
    
    /**
     * Sets the file attributes
     * 
     * @param array $array
     * @param string $name
     * @param bool $direct
     * @return void
     */
    private function set($array, $name, $direct = false)
    {
        if ($direct === true) {
        
            $this->$name = $array[$name];
            
            return;
        }
        
        if (isset($array[$name])) {
            
            $this->original_request[$name] = $array[$name];
        }
    }
    
    /**
     * Gets a file attribute
     * 
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->$name)) {
            
            return $this->$name;
        }
        
        return null;
    }
    
    /**
     * Gets the path for the file based on the MIME type
     * 
     * @param string $path
     * @param string $mimeTypeSuffix
     * @return string
     */
    private function getFilePath($path, $mimeTypeSuffix)
    {
        $mimeTypePath = '';
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
            
        $this->filetype = finfo_file($finfo, $this->original_request['tmp_name']);
        
        finfo_close($finfo);
        
        if ($mimeTypeSuffix === true) {
            
            $mimeTypePath = 'other/';
            
            if (strpos($this->filetype, 'image') !== false) {
                
                $mimeTypePath = 'image/';
            }
            elseif (strpos($this->filetype, 'video') !== false) {
                
                $mimeTypePath = 'video/';
            }
            elseif (strpos($this->filetype, 'audio') !== false) {
                
                $mimeTypePath = 'audio/';
            }
        }
        
        if ($path === 'auto') {
            
            $uploadPath = 'public/assets/';
        }
        else {
            
            if (substr($path, -1) !== '/') {
                
                $path.= '/';
            }
            
            $uploadPath = $path;
        }
            
        $uploadPath.= $mimeTypePath;
        
        return $uploadPath;
    }
    
    /**
     * Sets the filename and extension
     * 
     * @param string $fileName
     * @param string $name
     * @param string $extension
     * @return void
     */
    private function getFileName($fileName, &$name, &$extension, $allowedExtensions)
    {
        $names = explode('.', $fileName);
        $realName = '';
        
        $lastDot = count($names) - 1;
        $originExtension = strtolower($names[$lastDot]);
        
        for ($i = 0; $i < $lastDot; $i++) {
            
            $realName.= $names[$i];
        }
        
        $name = $realName;
        $extension = null;

        if (empty($allowedExtensions) || in_array($originExtension, $allowedExtensions)) {
            $extension = $originExtension;
        }
    }
    
    /**
     * Updates the file name
     * 
     * @param string $fileName
     * @param string $realFileExtension
     * @param string $realFileName
     * @return void
     */
    private function updateFileName(&$fileName, &$finalFileName, &$realFileExtension, &$realFileName)
    {
        if ($fileName !== '') {
            
            if ($fileName === 'unique') {
                
                $uniqueName = Security::generateToken();
                
                $finalFileName = $uniqueName . '.' . $realFileExtension;
                $fileName = $uniqueName;
            }
            
            $finalFileName = $fileName . '.' . $realFileExtension;
            $realFileName = $fileName;
        }
    }
    
    /**
     * Moves a temporary file to the destination path
     * 
     * @param string $fileName
     * @param string $path
     * @param bool $mimeTypeSuffix
     * @param bool $overwrite
     * @return bool|$this|null
     */
    public function save($fileName = '', $path = 'public', $mimeTypeSuffix = true, 
                         $overwrite = false, $allowedExtensions = [])
    {
        if (empty($this->original_request['tmp_name'])) {
            
            return null;
        }
        
        $finalFileName = $this->original_request['name'];
        
        $realFileName = '';
        $realFileExtension = '';

        $this->getFileName($finalFileName, $realFileName, $realFileExtension, $allowedExtensions);

        if ($realFileExtension === null) {

            throw new FrameworkException("The provided file extension is not allowed.", 110);
        }
        
        $this->updateFileName($fileName, $finalFileName, $realFileExtension, $realFileName);
        
        $uploadPath = $this->getFilePath($path, $mimeTypeSuffix);
        
        if (!file_exists($uploadPath)) {

            if (!mkdir($uploadPath, 0777, true)) {
                
                throw new FrameworkException("The file path " . $uploadPath . " could not be created. Verify the access permissions.", 111);
            }
        }
        
        $filePath = $uploadPath . $finalFileName;
        
        if (file_exists($filePath) && $overwrite === false) {
            
            $finalFileName = $realFileName . '_' . date('YmdHmss') . '.' . $realFileExtension;
            
            $filePath = $uploadPath . $finalFileName;
        }
        
        if(move_uploaded_file($this->original_request['tmp_name'], $filePath)) {
            
            $this->set(['filename' => $finalFileName], 'filename', true);
            $this->set(['filepath' => $uploadPath], 'filepath', true);
            $this->set(['filenameAndPath' => $filePath], 'filenameAndPath', true);
            $this->set(['extension' => $realFileExtension], 'extension', true);
            $this->set(['size' => $this->original_request['size']], 'size', true);
            
            $publicUrl = "";
            
            if (strpos($filePath, 'public/') !== false) {
                
                $publicUrl = \App\Config\BASE_URL . $filePath;
            }
            
            $this->set(['public_url' => $publicUrl], 'public_url', true);
            
            return $this;
        }
        
        return null;
    }
    
    /**
     * Deletes a file from disk
     * 
     * @param string $file
     * @return bool
     */
    public function delete($file = '')
    {
        if (empty($file)) {
            
            return false;
        }
        
        $path = str_replace(\App\Config\BASE_URL, '', $file);
        
        $defaultPath = 'public/assets/' . $file;

        if (file_exists($path)) {

            unlink($path);
            
            return true;
        }
        else if (file_exists($file)) {
            
            unlink($file);
            
            return true;
        }
        else if (file_exists($defaultPath)) {
                
            unlink($defaultPath);
            
            return true;
        }
        
        return false;
    }
}