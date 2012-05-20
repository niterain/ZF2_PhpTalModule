<?php

namespace PhpTalModule\View\Resolver;

use PHPTAL_SourceResolver,
	PHPTAL_FileSource,
	Zend\View\Resolver;

class PhpTalResolver implements PHPTAL_SourceResolver
{
	/**
	 * @var Resolver
	 */
	private $zendResolver = null;
	
	/**
	 * @param Resolver $resolver
	 */
	public function __construct(Resolver $resolver)
	{
		$this->zendResolver = $resolver;
	}
	
	/**
	 * @return mixed
	 */
	public function resolve($path)
	{
		if(file_exists($path)){
			return new PHPTAL_FileSource($path);
		}
		
		$path = $this->zendResolver->resolve($path);
		
		if(file_exists($path)){
			return new PHPTAL_FileSource($path);
		}
		
		return null;
	}
}