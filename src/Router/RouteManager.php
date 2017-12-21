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

namespace Niuware\WebFramework\Router;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Generates a routing map file
 */
class RouteManager
{
    /**
     * The routing command
     * 
     * @var string 
     */
    private $command;
    
    /**
     * The command arguments
     * 
     * @var array 
     */
    private $commandArgs;
    
    /**
     * The command result output
     * 
     * @var string 
     */
    private $result;
    
    /**
     * All available commands
     * @var array 
     */
    private $availableCommands = ['update'];
    
    /**
     * Initializes the route manager
     * 
     * @param string $command
     * @param array $args
     * @return void
     */
    public function __construct($command, $args = [])
    {
        $this->command = $command;
        $this->commandArgs = $args;
        
        $this->initialize();
    }
    
    /**
     * Gets the command output
     * 
     * @return string
     */
    public function getResult()
    {
        return $this->result;
    }
    
    /**
     * Initializes the command
     * 
     * @return void
     */
    private function initialize()
    {
        if (in_array($this->command, $this->availableCommands)) {
            
            if (count($this->commandArgs) > 1 && 
                    ($this->commandArgs[1] !== "json" && $this->commandArgs[1] !== "yml")) {
            
                $this->result = "Please specify a valid file format (json or yml).\n";
            }
            else {
                
                call_user_func([$this, $this->command], $this->getFormat());
            }
            
            return;
        }
        
        echo sprintf("The option '%s' for the command 'routes' does not exist.\n", $this->command);
    }
    
    /**
     * Updates the routes file
     * 
     * @param string $format
     * @return void
     */
    private function update($format)
    {        
        if ($this->verifyFile($format)) {
            
            $routes = $this->parse($format);
            
            if ($routes !== null) {

                $content = $this->getHeader();
                $content.= $this->getContent($routes);
                $content.= $this->getFooter();

                file_put_contents('App/Config/Routes.php', $content);

                $this->result = "The routes were updated successfully.\n";
            }
        }
        else {
            
            $this->result = "The route file App/Config/routes.$format does not exist or is not accessible.\n";
        }
    }
    
    /**
     * Gets the file header
     * 
     * @return string
     */
    private function getHeader()
    {
        $template = 
<<<EOD
<?php

namespace App\Config;

/**
 * Defines the routes for the web application
 */
final class Routes {
    
    public static \$views = 
    
EOD;
        return $template;
    }
    
    /**
     * Gets the file footer
     * 
     * @return string
     */
    private function getFooter()
    {
        $template = 
<<<EOD
;
}
EOD;
        return $template;
    }
    
    /**
     * Gets the format of the routes file
     * 
     * @param string $userInput
     * @return string
     */
    private function getFormat() {
        
        $format = "yml";

        if (isset($this->commandArgs[1]) && !empty($this->commandArgs[1])) {

            $format = $this->commandArgs[1];
        }
        
        return $format;
    }
    
    /**
     * Verifies the existence of a routes file
     * 
     * @param string $format
     * @return bool
     */
    private function verifyFile($format)
    {
        if (file_exists('App/Config/routes.' . $format)) {
            
            return true;
        }
        
        return false;
    }
    
    /**
     * Parses the route file
     * 
     * @param string $format
     * @return string|null
     */
    private function parse($format)
    {
        $routes = null;
        
        if ($format === "json") {

            $routes = json_decode(file_get_contents('App/Config/routes.json'), true);
            
            $this->result = "Error while parsing the routes.json file.\n";
        }
        else if ($format === "yml") {

            try {

                $routes = Yaml::parse(file_get_contents('App/Config/routes.yml'));

            } catch (ParseException $ex) {

                $error = "Error while parsing the routes.yml file.\n";
                $error.= $ex->getMessage();
                $error.= "\n";

                $this->result = $error;
            }
        }
        
        return $routes;
    }
    
    /**
     * Filters the routes parsed data
     * 
     * @param string $input
     * @return string
     */
    private function getContent($input)
    {
        $stringValue = var_export($input, true);
        
        return str_replace(["array (", "Array (", ")"], ["[", "[", "]"], $stringValue);
    }
}
