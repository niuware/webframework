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

// Verify if Phinx is available
if (!class_exists('\Phinx\Migration\AbstractMigration')) {
    
    die("ERROR: Add phinx to your composer.json file and run composer to use the Migration functionality.");
}

use Phinx\Migration\AbstractMigration;

/**
 * Base class for an application migration definition class
 */
class Migration extends AbstractMigration
{
    /**
     * The schema builder instance
     * 
     * @var \Illuminate\Database\Schema\Builder 
     */
    public $schema;
    
    /**
     * Initializes Eloquent engine
     * 
     * @return void
     */
    public function init() {

        $options = $this->getInput()->getOptions();

        $connection = null;
        if (isset($options['environment']) && $options['environment'] !== "") {
            $connection = $options['environment'];
        }
        
        Database::boot();
        
        $this->schema = \Illuminate\Database\Capsule\Manager::schema($connection);
    }
}