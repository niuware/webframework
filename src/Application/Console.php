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

namespace Niuware\WebFramework\Application;

use Niuware\WebFramework\Database\MigrationManager;

/**
 * Executes commands for the application Console mode
 */
final class Console 
{    
    /**
     * The command to execute
     * 
     * @var string 
     */
    private $command;
    
    /**
     * The command options
     * 
     * @var string 
     */
    private $commandOption;
    
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
     * The console mode (web, terminal, enabled, disabled)
     * @var string 
     */
    private $mode;
    
    /**
     * Initializes the console
     * 
     * @param array $input
     * @param string $mode
     * @return void
     */
    public function __construct($input, $mode = 'terminal')
    {
        register_shutdown_function(function() {
            
            $this->shutdown(error_get_last());
        });
        
        $this->mode = $mode;
        
        $this->initialize($input);
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
     * Renders a console error
     * 
     * @param array $error
     * @return void
     */
    private function shutdown($error)
    {
        if (isset($error['type']) && 
                ($error['type'] === \E_ERROR || $error['type'] === \E_CORE_ERROR || 
                $error['type'] === \E_COMPILE_ERROR || $error['type'] === \E_USER_ERROR)) {
        
            if (!isset($error['message'])) {

                echo "There was an unknown error in the execution of your command.";

                return;
            }

            if ($this->mode === 'web') {

                echo 'Error while executing the command: ' . $this->command . ':' . $this->commandOption . '<br />';
                echo 'File: ' . $error['file']. ' at line ' . $error['line'];
                echo "<br /><br />";
                echo nl2br($error['message']);
            }
            else {

                echo 'Error while executing the command: ' . $this->command . ':' . $this->commandOption;
                echo "\n";
                echo 'File: ' . $error['file']. ' at line ' . $error['line'];
                echo "\n\n";
                echo $error['message'];
            }
        }
    }
    
    /**
     * Initializes the command
     * 
     * @param array $input
     * @return void
     */
    private function initialize($input)
    {
        $this->command = (isset($input[1])) ? $input[1] : null;
        $this->commandOption = (isset($input[2])) ? $input[2] : null;
        
        if ($this->command === null) {
            
            echo "Did you forgot to write the command?\n";
        }
        else if ($this->commandOption === null) {
            
            echo sprintf("The comand '%s' is missing the action.\n", $this->command);
        }
        else {
        
            $this->setCommandArgs($input);
            
            $this->executeCommand();
        }
    }
    
    /**
     * Sets the command arguments
     * 
     * @param array $input
     * @return void
     */
    private function setCommandArgs($input)
    {
        $this->commandArgs = [];
        
        if (count($input) > 2) {
            
            $this->commandArgs = array_slice($input, 2);
        }
    }
    
    /**
     * Executes a command
     * 
     * @return void
     */
    private function executeCommand()
    {
        switch ($this->command) {
            
            case 'migrations':
                $migration = new MigrationManager($this->commandOption, $this->commandArgs);
                
                $this->result = $migration->getResult();
            break;
            case 'routes':
                $routes = new RouteManager($this->commandOption, $this->commandArgs);
                
                $this->result = $routes->getResult();
            break;
            default: 
                echo sprintf("Command '%s' does not exist.\n", $this->command);
            break;
        }
    }
}
