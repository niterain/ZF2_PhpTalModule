<?php

namespace PhpTalModule\View\Strategy;


use PhpTalModule\View\Renderer\PhpTalRenderer,
	Zend\EventManager\EventCollection,
	Zend\EventManager\ListenerAggregate,
	Zend\View\ViewEvent;


class PhpTalStrategy implements ListenerAggregate
{
	
	/**
	* @var \Zend\Stdlib\CallbackHandler[]
	*/
	protected $listeners = array();
	
	
	/**
	 * @var PhpTalRenderer
	 */
	protected $renderer;
	
	
	/**
	 * Constructor
	 *
	 * @param  PhpTalRenderer $renderer
	 * @return void
	 */
	public function __construct(PhpTalRenderer $renderer)
	{
		$this->renderer = $renderer;
	}
	
	/**
	 * Retrieve the composed renderer
	 *
	 * @return PhpTalRenderer
	 */
	public function getRenderer()
	{
		return $this->renderer;
	}
	
	
	/**
	 * Attach the aggregate to the specified event manager
	 *
	 * @param  EventCollection $events
	 * @param  int $priority
	 * @return void
	 */
	public function attach(EventCollection $events, $priority = 1)
	{
		$this->listeners[] = $events->attach('renderer', array($this, 'selectRenderer'), $priority);
		$this->listeners[] = $events->attach('response', array($this, 'injectResponse'), $priority);
	}
	
	/**
	 * Detach aggregate listeners from the specified event manager
	 *
	 * @param  EventCollection $events
	 * @return void
	 */
	public function detach(EventCollection $events)
	{
		foreach ($this->listeners as $index => $listener) {
			if ($events->detach($listener)) {
				unset($this->listeners[$index]);
			}
		}
	}
	
	/**
	 * Select the PhpRenderer; typically, this will be registered last or at
	 * low priority.
	 *
	 * @param  ViewEvent $e
	 * @return PhpRenderer
	 */
	public function selectRenderer(ViewEvent $e)
	{
		return $this->renderer;
	}
	
	/**
	 * Populate the response object from the View
	 *
	 * Populates the content of the response object from the view rendering
	 * results.
	 *
	 * @param  ViewEvent $e
	 * @return void
	 */
	public function injectResponse(ViewEvent $e)
	{
		$renderer = $e->getRenderer();
		if ($renderer !== $this->renderer) {
			return;
		}
	
		$result   = $e->getResult();
		$response = $e->getResponse();
	
		// Set content
		// If content is empty, check common placeholders to determine if they are
		// populated, and set the content from them.
// 		if (empty($result)) {
// 			$placeholders = $renderer->plugin('placeholder');
// 			$registry     = $placeholders->getRegistry();
// 			foreach ($this->contentPlaceholders as $placeholder) {
// 				if ($registry->containerExists($placeholder)) {
// 					$result = (string) $registry->getContainer($placeholder);
// 					break;
// 				}
// 			}
// 		}
		$response->setContent($result);
	}
}
