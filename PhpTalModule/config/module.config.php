<?php
return array(
	'di' => array(
		'instance' => array(
			
			'PhpTalModule\View\Renderer\PhpTalRenderer' => array(
				'parameters' => array(
					'resolver' => 'Zend\View\Resolver\AggregateResolver',
					
					'options' => array(
						'encoding' => 'UTF-8',
						'cacheDirectory' => __DIR__ . '/../tmp/',
						'cachePurgeMode' => 0,
						'compressWhitespace' => 0,
						'titleSeparator' => ' - ',
						'title' => 'ZF2 PHPTAL Module',
						'globalTemplatesDirectory' => array(),
					),
				),
			),
			
		),
	),
);
