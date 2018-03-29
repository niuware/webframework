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

namespace Niuware\WebFramework\Database;

use Niuware\WebFramework\Auth\Security;

use App\Config\Settings;

use Phinx\Console\PhinxApplication;
use Phinx\Config\Config;
use Phinx\Console\Command\Create;
use Phinx\Console\Command\Migrate;
use Phinx\Console\Command\Rollback;
use Phinx\Console\Command\Status;
use Phinx\Console\Command\SeedCreate;
use Phinx\Console\Command\SeedRun;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;

/**
 * Executes a migration
 */
final class MigrationManager
{
    /**
     * The migration command
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
    private $availableCommands = ['create', 'migrate', 'rollback', 'status', 'seedcreate', 'seedrun'];
    
    /**
     * Initializes the migration
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
        
            $phinxApp = new PhinxApplication();
            
            $stream = fopen('php://temp', 'w+');
            
            $config = new Config($this->getConfig());
            
            call_user_func([$this, $this->command], $phinxApp, $config, $stream);
            
            $this->result = stream_get_contents($stream, -1, 0);
            
            fclose($stream);
            
            return;
        }
        
        echo sprintf("The option '%s' for the command 'migrations' does not exist.\n", $this->command);
    }
    
    /**
     * Sets the configuration for the migration adapter
     * 
     * @return array
     */
    private function getConfig()
    {
        $connection = "default";

        foreach ($this->commandArgs as $arg) {
            if (substr($arg, 0, 5) === "conn=") {
                $connection = substr($arg, 5);
            }
        }

        return [
            'paths' => [
                'migrations' => 'App/Migrations/Migrations',
                'seeds' => 'App/Migrations/Seeds'
            ],
            'migration_base_class' => 'Niuware\WebFramework\Database\Migration',
            'environments' => [
                'default_migration_table' => 'migrations_log',
                'default_database' => $connection,
                $connection => [
                    'adapter' => Settings::$databases[$connection]['engine'],
                    'host' => Settings::$databases[$connection]['host'],
                    'name' => \App\Config\DB_LANG . Settings::$databases[$connection]['schema'],
                    'user' => Settings::$databases[$connection]['user'],
                    'pass' => Settings::$databases[$connection]['pass'],
                    'port' => Settings::$databases[$connection]['port'],
                    'charset' => Settings::$databases[$connection]['charset'],
                    'collation' => Settings::$databases[$connection]['collation'],
                    'table_prefix' => Settings::$databases[$connection]['prefix']
                ]
            ]
        ];
    }
    
    /**
     * Sets the command arguments
     * 
     * @param array $command
     * @param string $argumentShort
     * @return void
     */
    private function setCommandArguments(&$command, $argumentShort = '-t')
    {
        if (count($this->commandArgs) > 2) {
            
            if ($this->commandArgs[1] === $argumentShort && 
                    $this->commandArgs[2] !== '') {
                
                $command[$argumentShort] = $this->commandArgs[2];
            }
        }
    }
    
    /**
     * Creates a migration definition file
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     * @return void
     */
    private function create(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'create',
            'name' => 'V' . time(),
            '--class' => 'Niuware\WebFramework\Database\MigrationTemplate'
        ];
        
        $arrayInput = new ArrayInput($command);
        
        $create = new Create();
        
        $create->setApplication($app);

        $create->setConfig($config);
            
        $create->run($arrayInput, new StreamOutput($stream));
    }
    
    /**
     * Runs a migration
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     * @return void
     */
    private function migrate(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'migrate'
        ];
        
        $this->setCommandArguments($command);
        
        $arrayInput = new ArrayInput($command);
        
        $migrate = new Migrate();
        
        $migrate->setApplication($app);

        $migrate->setConfig($config);
            
        $migrate->run($arrayInput, new StreamOutput($stream));
    }
    
    /**
     * Rollback from a migration
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     * @return void
     */
    private function rollback(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'rollback'
        ];
        
        $this->setCommandArguments($command);
        
        // Target date to rollback to
        $this->setCommandArguments($command, '-d');
        
        if (isset($command['-t']) && $command['-t'] === '0') {
            
            // Force rollback
            if (isset($this->commandArgs[3]) && 
                    $this->commandArgs[3] === '-f') {
                
                $command['-f'] = '';
            }
        }
        
        $arrayInput = new ArrayInput($command);
        
        $migrate = new Rollback();
        
        $migrate->setApplication($app);

        $migrate->setConfig($config);
            
        $migrate->run($arrayInput, new StreamOutput($stream));
    }
    
    /**
     * Shows the migration status
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     * @return void
     */
    private function status(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'status'
        ];
        
        $arrayInput = new ArrayInput($command);
        
        $create = new Status();
        
        $create->setApplication($app);

        $create->setConfig($config);
            
        $create->run($arrayInput, new StreamOutput($stream));
    }
    
    /**
     * Creates a seed migration definition file
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     * @return void
     */
    private function seedcreate(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'seed:create',
            'name' => 'Seed' . Security::generateToken(5)
        ];
        
        // Sets an specific seed class name
        if (isset($this->commandArgs[1]) && $this->commandArgs[1] !== '') { 
            
            $command['name'] = $this->commandArgs[1];
        }
        
        $arrayInput = new ArrayInput($command);
        
        $migrate = new SeedCreate();
        
        $migrate->setApplication($app);

        $migrate->setConfig($config);
            
        $migrate->run($arrayInput, new StreamOutput($stream));
    }
    
    /**
     * Runs a seed definition
     * 
     * @param Phinx\Console\PhinxApplication $app
     * @param Phinx\Config\Config $config
     * @param resource $stream
     */
    private function seedrun(PhinxApplication $app, Config $config, $stream)
    {
        $command = [
            'command' => 'seed:run'
        ];
        
        // Target a specific seed class
        $this->setCommandArguments($command, '-s');

        if (isset($command['-s'])) {
            
            $command['-s'] = [$command['-s']];
        }
        
        $arrayInput = new ArrayInput($command);
        
        $migrate = new SeedRun();
        
        $migrate->setApplication($app);

        $migrate->setConfig($config);
            
        $migrate->run($arrayInput, new StreamOutput($stream));
    }
}