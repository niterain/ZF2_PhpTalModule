<?php

namespace PhpTalModule\View\Renderer;

use PhpTalModule\View\Resolver\PhpTalResolver;

use DirectoryIterator,
	PHPTAL,
	PHPTAL_PreFilter_Compress,
	PHPTAL_PreFilter_StripComments,
	PHPTAL_TemplateException,
	Zend\Loader\Pluggable,
	Zend\View\Exception,
	Zend\View\HelperBroker,
	Zend\View\Model,
	Zend\View\Renderer,
	Zend\View\Renderer\TreeRendererInterface,
	Zend\View\Resolver;

class PhpTalRenderer implements Renderer, Pluggable
{
	
	
	/**
	 * The PHPTal engine.
	 *
	 * @var PHPTAL
	 */
	protected $__engine = null;
	
	/**
	 * Template resolver
	 *
	 * @var Resolver
	 */
	private $__templateResolver;
	
	/**
	 * Helper broker
	 *
	 * @var HelperBroker
	 */
	private $__helperBroker;
	
	/**
	 * Whether to flush the template cache before rendering.
	 *
	 * @var bool
	 */
	protected $__purgeCacheBeforeRender = false;
	
	
	/**
	 * Whether to turn on the whitespace compression filter.
	 *
	 * @var bool
	 */
	protected $__compressWhitespace = false;
	
	
	
	/**
	 * Constructor.
	 *
	 * @param array $options Configuration options.
	 */
	public function __construct($options = array())
	{
		$this->setEngine(new PHPTAL());
	
		// configure the encoding
		if (isset($options['encoding']) && $options['encoding'] != '') {
			$this->getEngine()->setEncoding((string)$options['encoding']);
		} else {
			$this->getEngine()->setEncoding('UTF-8');
		}
	
		// change the compiled code destination if set in the config
		if (isset($options['cacheDirectory']) && $options['cacheDirectory'] != '') {
			$this->getEngine()->setPhpCodeDestination((string)$options['cacheDirectory']);
		}
	
		// configure the caching mode
		if (isset($options['cachePurgeMode'])) {
			$this->setCachePurgeMode($options['cachePurgeMode'] == '1');
		}
	
		// configure the whitespace compression mode
		if (isset($options['compressWhitespace'])) {
			$this->setCompressWhitespace($options['compressWhitespace'] == '1');
		}
	
		// configure the title separator
		if (isset($options['titleSeparator'])) {
			$this->headTitle()->setSeparator($options['titleSeparator']);
		}
	
		// configure the title
		if (isset($options['title'])) {
			$this->headTitle($options['title']);
		}
	
		// Set the remaining template repository directories;
		if (isset($options['globalTemplatesDirectory'])) {
			$directories = $options['globalTemplatesDirectory'];
			if (!is_array($directories)) {
				$directories = array($directories);
			}
			foreach ($directories as $currentDirectory) {
				$this->addTemplateRepositoryPath($currentDirectory);
			}
		}

	}
	
	
	
	/**
	 * Changes the current PHPTAL instance.
	 *
	 * @param mixed $tal The engine to use (supplied to the Zend system by the Resource View).
	 *
	 * @return void
	 */
	private function setEngine($tal)
	{
		$this->__engine = $tal;
		$this->__engine->this = $this;
		
		$this->__engine->addPreFilter(new PHPTAL_PreFilter_StripComments());
	}

	/**
	 * Returns the current PHPTAL instance.
	 *
	 * @return PHPTAL
	 */
	public function getEngine()
	{
		return $this->__engine;
	}
	
	
	
	/**
	 * Changes the cache purge mode.
	 *
	 * @param bool $newValue Whether to delete old template cache files before rendering.
	 *
	 * @return void
	 */
	public function setCachePurgeMode($newValue)
	{
		$this->__purgeCacheBeforeRender = $newValue;
	}
	
	/**
	 * Returns the cache purge mode.
	 *
	 * @return bool
	 */
	public function getCachePurgeMode()
	{
		return $this->__purgeCacheBeforeRender;
	}
	
	
	
	/**
	 * Sets whether whitespace compression should be performed.
	 *
	 * @param bool $flag Whether to compress whitespace.
	 *
	 * @return void
	 */
	public function setCompressWhitespace($flag)
	{
		$this->__compressWhitespace = (bool) $flag;
	}
	
	/**
	 * Gets whether whitespace compression is currently turned on.
	 *
	 * @return bool
	 */
	public function getCompressWhitespace()
	{
		return $this->__compressWhitespace;
	}
	
	
	/**
     * Set script resolver
     * 
     * @param  Resolver $resolver 
     * @return PhpRenderer
     * @throws Exception\InvalidArgumentException
     */
    public function setResolver(Resolver $resolver)
    {
        $this->__templateResolver = $resolver;
        
        $phpTalResolver = new PhpTalResolver($resolver);
        $this->__engine->addSourceResolver($phpTalResolver);
        
        return $this;
    }

    
    /**
     * Retrieve template name or template resolver
     * 
     * @param  null|string $name 
     * @return string|Resolver
     */
    public function resolver($name = null)
    {
        if (null === $this->__templateResolver) {
            throw new \Exception("No SourceResolver registered");
        	//$this->setResolver(new Resolver\TemplatePathStack());
        }

        if (null !== $name) {
            return $this->__templateResolver->resolve($name, $this);
        }

        return $this->__templateResolver;
    }
    
    
    
	public function render($nameOrModel, $values = null)
	{
		if ($nameOrModel instanceof Model) {
			$model       = $nameOrModel;
			$nameOrModel = $model->getTemplate();
			if (empty($nameOrModel)) {
				throw new Exception\DomainException(sprintf(
		                    '%s: received View Model argument, but template is empty',
				__METHOD__
				));
			}
			$options = $model->getOptions();
			foreach ($options as $setting => $value) {
				$method = 'set' . $setting;
				if (method_exists($this, $method)) {
					$this->$method($value);
				}
				unset($method, $setting, $value);
			}
			unset($options);
		
			// Give view model awareness via ViewModel helper
			$helper = $this->plugin('view_model');
			$helper->setCurrent($model);
		
			$values = $model->getVariables();
			
			unset($model);
		}
		
		$this->__engine->set('doctype', $this->doctype());
		$this->__engine->set('headTitle', $this->headTitle());
		$this->__engine->set('headScript', $this->headScript());
		$this->__engine->set('headLink', $this->headLink());
		$this->__engine->set('headMeta', $this->headMeta());
		$this->__engine->set('headStyle', $this->headStyle());
		
		$this->__engine->set('content', '');
		$values = $values ?: array();
		
		foreach($values as $key => $value)
			$this->__engine->set($key, $value);
		
		
		if ($this->__purgeCacheBeforeRender) {
			$cacheFolder = $this->__engine->getPhpCodeDestination();
			if (is_dir($cacheFolder)) {
				foreach (new DirectoryIterator($cacheFolder) as $cacheItem) {
					if (strncmp($cacheItem->getFilename(), 'tpl_', 4) != 0 || $cacheItem->isdir()) {
						continue;
					}
					
					@unlink($cacheItem->getPathname());
				}
			}
		}
		
		$template = $this->resolver($nameOrModel);
		$this->__engine->setTemplate($template);
		
		unset($nameOrModel);
		unset($template);
		
		
		if ($this->__compressWhitespace == true) {
			$this->__engine->addPreFilter(new PHPTAL_PreFilter_Compress());
		}
		
		try {
			$result = $this->__engine->execute();					
		} 
		catch(PHPTAL_TemplateException $e) {
			// If the exception is a root PHPTAL_TemplateException
			// rather than a subclass of this exception and xdebug is enabled,
			// it will have already been picked up by xdebug, if enabled, and
			// should be shown like any other php error.
			// Any subclass of PHPTAL_TemplateException can be handled by
			// the phptal internal exception handler as it gives a useful
			// error output
			if (get_class($e) == 'PHPTAL_TemplateException'
				&& function_exists('xdebug_is_enabled')
				&& xdebug_is_enabled()
			) {
				exit();
			}
			
			throw $e;
		}
		
		return $result;
	}
	
	
	/**
	 * Get plugin broker instance
	 *
	 * @return HelperBroker
	 */
	public function getBroker()
    {
        if (null === $this->__helperBroker) {
            $this->setBroker(new HelperBroker());
        }
        return $this->__helperBroker;
    }
    
    
	/**
	 * Set plugin broker instance
	 *
	 * @param  string|HelperBroker $broker
	 * @return Zend\View\Abstract
	 * @throws Exception\InvalidArgumentException
	 */
	public function setBroker($broker)
	{
		if (is_string($broker)) {
			if (!class_exists($broker)) {
				throw new Exception\InvalidArgumentException(sprintf(
	                    'Invalid helper broker class provided (%s)',
				$broker
				));
			}
			$broker = new $broker();
		}
		if (!$broker instanceof HelperBroker) {
			throw new Exception\InvalidArgumentException(sprintf(
	                'Helper broker must extend Zend\View\HelperBroker; got type "%s" instead',
			(is_object($broker) ? get_class($broker) : gettype($broker))
			));
		}
		$broker->setView($this);
		$this->__helperBroker = $broker;
	}
	
	
    /**
     * Get plugin instance
     * 
     * @param  string     $plugin  Name of plugin to return
     * @param  null|array $options Options to pass to plugin constructor (if not already instantiated)
     * @return Helper
     */
    public function plugin($name, array $options = null)
    {
        return $this->getBroker()->load($name, $options);
    }
    
    
    /**
     * Overloading: proxy to helpers
     *
     * Proxies to the attached plugin broker to retrieve, return, and potentially
     * execute helpers.
     *
     * * If the helper does not define __invoke, it will be returned
     * * If the helper does define __invoke, it will be called as a functor
     *
     * @param  string $method
     * @param  array $argv
     * @return mixed
     */
    public function __call($method, $argv)
    {
    	$helper = $this->plugin($method);
    	if (is_callable($helper)) {
    		return call_user_func_array($helper, $argv);
    	}
    	return $helper;
    }
    
    
    /**
     * Handle cloning of the view by cloning the PHPTAL object correctly.
     *
     * @return void
     */
    public function __clone()
    {
    	$this->_engine = clone $this->_engine;
    	
    }
    
}