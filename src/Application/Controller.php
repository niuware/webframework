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

/**
 * Base class for an application Controller class
 */
abstract class Controller {

    /**
     * A flag to set if the controller requires an authentication
     * 
     * @var bool 
     */
    private $authenticate;
    
    /**
     * A flag to set if the controller is in the Admin Application Space
     * 
     * @var bool 
     */
    private $isAdmin;
    
    /**
     * The controller renderer ('php' or 'twig')
     * 
     * @var string 
     */
    private $renderer;
    
    /**
     * The controller attributes
     * @var array 
     */
    private $attributes = [];

    /**
     * Sets the default values for the controller
     * 
     * @return void
     */
    public function __construct()
    {
        $this->renderer = \App\Config\DEFAULT_RENDERER;
        $this->authenticate = false;
        $this->isAdmin = false;
    }

    /**
     * Gets a controller attribute
     * 
     * @param string $name
     * @return mixed|null
     */
    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            
            return $this->attributes[$name];
        }
        
        return null;
    }

    /**
     * Sets a controller attribute
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function __set($name, $value)
    {
        $this->attributes[$name] = $value;
    }
    
    /**
     * Sets the renderer for the controller
     * 
     * @param string $renderer
     * @return void
     */
    public function setRenderer($renderer = 'twig')
    {
        if ($renderer === 'twig' || $renderer === 'php') {
            
            $this->renderer = $renderer;
        }
    }
    
    /**
     * Gets the renderer for the controller
     * 
     * @return string
     */
    public function getRenderer()
    {
        return $this->renderer;
    }

    /**
     * Renders the view for the controller
     * 
     * @param string $path
     * @return string|null
     * 
     * @throws \Niuware\WebFramework\Exception\FrameworkException
     */
    public function render($path = '')
    {
        if ($path !== '') {
            
            return $path;
        }
        
        $pathToView = "./public/views/";
        
        if (!file_exists($pathToView . $this->view)) {
            
            throw new \Exception("The view '$this->view' does not exist.", 106);
        }
        
        if ($this->renderer === 'twig') {
            
            $this->renderWithTwig($pathToView);
        }
        else {
            
            $phpView = str_replace(".twig", ".php", $this->view);
            
            include ($pathToView . $phpView);
        }
        
        return null;
    }
    
    /**
     * Renders the controller view
     * 
     * @return void
     */
    private function renderWithTwig()
    {
        $twigLoader = new \Twig_Loader_Filesystem('./public/views');
        
        $rendererSettings['cache'] = './App/cache';
        
        if (\App\Config\DEBUG_MODE === true) {
            
            $rendererSettings['debug'] = true;
            $rendererSettings['strict_variables'] = true;
            $rendererSettings['auto_reload'] = true;
        }
        
        $twig = new \Twig_Environment($twigLoader, $rendererSettings);
        
        $twig->addExtension(new \Twig_Extension_Debug());
        $twig->addExtension(new Extension());
        
        echo $twig->render($this->view, $this->attributes);
    }
}