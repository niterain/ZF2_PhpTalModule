<?php

namespace PhpTalModule;

use Zend\Module\Manager,
    Zend\EventManager\StaticEventManager,
    Zend\Module\Consumer\AutoloaderProvider;

class Module implements AutoloaderProvider
{
    public function init(Manager $moduleManager)
    {
    	include_once(__DIR__ . '/src/Tal/helper.php');
    	
    	$events = StaticEventManager::getInstance();
        $events->attach('bootstrap', 'bootstrap', array($this, 'bootstrap'));
    }

    public function getAutoloaderConfig()
    {
        return array(
            'Zend\Loader\ClassMapAutoloader' => array(
                __DIR__ . '/autoload_classmap.php',
            ),
            'Zend\Loader\StandardAutoloader' => array(
                'namespaces' => array(
                    __NAMESPACE__ => __DIR__ . '/src/',
                ),
            ),
        );
    }

    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
    
    
    public function bootstrap($e)
    {
	    // Register a "render" event, at high priority (so it executes prior
	  	// to the view attempting to render)
	    $app = $e->getParam('application');
	    $app->events()->attach('render', array($this, 'registerPhpTalStrategy'), 100);
    }
        
        
    public function registerPhpTalStrategy($e)
    {
	    $app          	= $e->getTarget();
	    $locator     	= $app->getLocator();
	    $view       	= $locator->get('Zend\View\View');
	    $phpTalStrategy	= $locator->get('PhpTalModule\View\Strategy\PhpTalStrategy');
	    
	    // Attach strategy, which is a listener aggregate, at high priority
	    $view->events()->attach($phpTalStrategy, 100);
    }
}
