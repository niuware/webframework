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

use Phinx\Migration\AbstractTemplateCreation;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Migration definition template
 */
final class MigrationTemplate extends AbstractTemplateCreation
{
    /**
     * Initializes the template generation class
     * 
     * @param Symfony\Component\Console\Input\InputInterface $input
     * @param Symfony\Component\Console\Output\OutputInterface $output
     * @return void
     */
    public function __construct(InputInterface $input = null, OutputInterface $output = null)
    {
        parent::__construct($input, $output);
    }
    
    /**
     * Gets the definition file template
     * 
     * @return string
     */
    public function getMigrationTemplate()
    {
        $date = date('Y/m/d H:i:s');
        $template = 
<<<EOD
<?php

use \$useClassName;
use Illuminate\Database\Schema\Blueprint;

class \$className extends \$baseClassName
{
    /**
     * Illuminate\Database\Schema\Builder \$schema
     * Use the \$schema object to execute your migration queries
     * 
     * Created on $date
     * Migration comments:
     * 
     */
    
    public function up()
    {
        
    }
    
    public function down()
    {
        
    }
}
EOD;
        return $template;
    }
    
    /**
     * Executes code after creating the migration definition file class
     * 
     * @param string $migrationFilename
     * @param string $className
     * @param string $baseClassName
     * @return void
     */
    public function postMigrationCreation($migrationFilename, $className, $baseClassName)
    {
    }
}