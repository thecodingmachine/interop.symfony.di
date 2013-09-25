<?php
namespace Mouf\Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;

/**
 * This class extends the Symfony container and adds a capability to add a fallback dependency injection
 * container.
 * 
 * @author David Négrier
 */
class ExtensibleContainer extends Container {
	
	protected $prependContainers = array();
	protected $fallbackContainers = array();
	
	/**
	 * 
	 * @param string $id
	 * @return boolean
	 */
	public function has($id)
	{
		foreach ($this->prependContainers as $container) {
			if ($container->has($id)) {
				return true;
			}
		}
		
		$has = parent::has($id);
		if ($has) {
			return true;
		}
		
		foreach ($this->fallbackContainers as $container) {
			if ($container->has($id)) {
				return true;
			}
		}
		
		return false;
	}
	
	/**
	 * Gets a service.
	 *
	 * If a service is defined both through a set() method and
	 * with a get{$id}Service() method, the former has always precedence.
	 *
	 * @param string  $id              The service identifier
	 * @param integer $invalidBehavior The behavior when the service does not exist
	 *
	 * @return object The associated service
	 *
	 * @throws InvalidArgumentException if the service is not defined
	 * @throws ServiceCircularReferenceException When a circular reference is detected
	 * @throws ServiceNotFoundException When the service is not defined
	 *
	 * @see Reference
	 *
	 * @api
	 */
	public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE)
	{
		// Let's search in the prepended containers:
		foreach ($this->prependContainers as $container) {
			if ($container->has($id)) {
				return $container->get($id);
			}
		}
		
		$result = parent::get($id, self::NULL_ON_INVALID_REFERENCE);
		
		if ($result !== null) {
			return $result;	
		}
		
		// Let's search in the fallback mode:
		foreach ($this->fallbackContainers as $container) {
			if ($container->has($id)) {
				return $container->get($id);
			}
		}
		
		// Finally, if we have nothing, let's trigger an exception if requested:
		if (self::EXCEPTION_ON_INVALID_REFERENCE === $invalidBehavior) {
			if (!$id) {
				throw new ServiceNotFoundException($id);
			}
		
			$alternatives = array();
			foreach (array_keys($this->services) as $key) {
				$lev = levenshtein($id, $key);
				if ($lev <= strlen($id) / 3 || false !== strpos($key, $id)) {
					$alternatives[] = $key;
				}
			}
		
			throw new ServiceNotFoundException($id, null, null, $alternatives);
		}
		return null;
	}
	
	/**
	 * Registers a container that will be queried if the Symfony container does not
	 * contain the requested instance.
	 * 
	 * Note: we are not enforcing an interface yet because we lack a standard on the interface name.
	 * 
	 * @param ContainerInterface $container
	 */
	public function registerFallbackContainer($container) {
		$this->fallbackContainers[] = $container;
	}
	
	/**
	 * Registers a container that will be queried before Symfony's container.
	 *
	 * Note: we are not enforcing an interface yet because we lack a standard on the interface name.
	 *
	 * @param ContainerInterface $container
	 */
	public function registerPrependContainer($container) {
		array_unshift($this->prependContainers, $container);
	}
}
