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

namespace Niuware\WebFramework\Exception;

/**
 * Default framework exception class
 */
final class FrameworkException extends \Exception
{
    /**
     * A custom trace for the exception
     * 
     * @var string
     */
    private $customTrace;
    
    /**
     * Initializes an exception
     * 
     * @param string $message
     * @param int $code
     * @param \Throwable $previous
     * @return void
     */
    public function __construct($message, $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Gets the string representation of the exception
     * 
     * @return string
     */
    public function __toString()
    {
        return "WebFramework Exception: {$this->message}\n";
    }
    
    /**
     * Sets the line number in which the exception was thrown
     * 
     * @param string $line
     * @return void
     */
    public function setLine($line)
    {
        $this->line = $line;
    }
    
    /**
     * Sets the file name where the exception was thrown
     * 
     * @param string $file
     * @return void
     */
    public function setFile($file)
    {
        $this->file = $file;
    }
    
    /**
     * Sets the custom trace
     * 
     * @param string $trace
     * @return void
     */
    public function setTrace($trace)
    {
        $this->customTrace = $trace;
    }
    
    /**
     * Renders the exception
     * 
     * @param bool $useHeader
     * @return void
     */
    public function renderAll($useHeader = true)
    {
        $html = $this->getHeader();
        $html.= $this->getAll();
        $html.= $this->getFooter();
        
        if ($useHeader) {
            
            header('HTTP/1.0 500 Internal Server Error');
        }
        
        echo $html;
    }
    
    /**
     * Gets the exception HTML body
     * 
     * @return string
     */
    private function getAll()
    {
        $count = 2;
        $body = $this->getBody($this, 1);     
        
        if (\App\Config\DEBUG_MODE === true) {
            $previous = $this->getPrevious();

            while ($previous !== null) {

                $body.= $this->getBody($previous, $count++);

                $previous = $previous->getPrevious();
            }
        }
        
        return $body;
    }
    
    /**
     * Gets the exception HTML header
     * 
     * @return string
     */
    private function getHeader()
    {
        $template = 
<<<EOD
<!DOCTYPE html>
<html>
    <head>
        <title>Niuware WebFramework Exception Found</title>
        <meta name="viewport" content="width=device-width,initial-scale=1.0,minimum-scale=1.0">
    </head>
    <body style="background-color:#fefefe;">
EOD;
        return $template;
    }
    
    /**
     * Gets the exception HTML footer
     * 
     * @return string
     */
    private function getFooter()
    {
        $template = '</body></html>';
        
        return $template;
    }
    
    /**
     * Gets the body for an exception
     * 
     * @param \Exception $exception
     * @param integer $count
     * @return string
     */
    private function getBody(\Exception $exception, $count)
    {
        $template = 
<<<EOD
    <div style="width:75%;border:1px solid #cccccc;background-color:#ffffff;margin:20px auto;border-radius:5px 5px;">
        <div style="font-size:1.5em;font-weight:lighter;padding:40px 30px;background-color:#f7f7f7;color:#4b4b4b;border-top:0;border-radius:5px 5px 0 0;border-bottom:1px dashed #ebebeb;">
            <div style="font-size:0.7em;color:#666666;">
EOD;
        if (\App\Config\DEBUG_MODE === true) {
            $template.= get_class($exception);
        }
        else {
            $template.= "Exception";
        }
        $template.=
<<<EOD
        (code: {$exception->getCode()})
            </div>
EOD;
        if (\App\Config\DEBUG_MODE === true) {
            $template.= $count . ". " . $exception->getMessage();
        }
        else {
            $template.= "This page found an exception and cannot be displayed.";
        }
        $template.=
<<<EOD
        </div>
        <div style="font-size:1em;padding:30px;line-height:1.8em;color:#181818;">
EOD;
        if (\App\Config\DEBUG_MODE === true) {
<<<EOD
        <div style="font-size:1.2em;margin-bottom:10px;color:#4b4b4b;">
            File: {$exception->getFile()} at line {$exception->getLine()} <br />
            Trace:
        </div>
EOD;
            if ($this->customTrace === null) {
                $template.= nl2br($exception->getTraceAsString());
            }
            else {
                $template.= nl2br($this->customTrace);
            }
        }
        else {
            $template.= 'The exception details are only visible when "debug mode" is enabled.';
        }
        
        $template.= '</div></div>';
        
        return $template;
    }
}