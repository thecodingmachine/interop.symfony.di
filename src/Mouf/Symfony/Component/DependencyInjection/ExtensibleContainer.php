<?php
namespace Mouf\Symfony\Component\DependencyInjection;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use Interop\Container\ParentAwareContainerInterface;
use Interop\Container\ContainerInterface;

/**
 * This class extends the Symfony container and adds a capability to add a fallback dependency injection
 * container.
 * 
 * @author David Négrier
 */
class ExtensibleContainer extends Container implements ParentAwareContainerInterface {
	
	/**
	 * 
	 * @var ContainerInterface
	 */
	protected $parentContainer;
	
	/**
	 * The number of time this container was called recursively.
	 * @var int
	 */
	protected $nbLoops = 0;
	
	const MODE_STANDARD_COMPLIANT = 1;
	const MODE_ACT_AS_MASTER = 2;
	
	/**
	 *
	 * @var int
	 */
	protected $mode = self::MODE_ACT_AS_MASTER;
	
	/**
	 * Sets the mode of pimple-interop.
	 * There are 2 possible modes:
	 *
	 * - PimpleInterop::MODE_STANDARD_COMPLIANT => a mode that respects the container-interop standard.
	 * - PimpleInterop::MODE_ACT_AS_MASTER => in this mode, if Pimple does not contain the requested
	 *   identifier, it will query the fallback container.
	 *
	 * @param int $mode
	 */
	public function setMode($mode) {
		$this->mode = $mode;
	}
	
	/**
	 * 
	 * @param string $id
	 * @return boolean
	 */
	public function has($id)
	{
		if (!$this->parentContainer || $this->mode == self::MODE_STANDARD_COMPLIANT) {
			return parent::has($id);
		} elseif ($this->mode == self::MODE_ACT_AS_MASTER) {
			if ($this->nbLoops != 0) {
				return parent::has($id);
			} else {
				$this->nbLoops++;
				$has = $this->parentContainer->has($id);
				$this->nbLoops--;
				return $has;
			}
		} else {
			throw new \Exception("Invalid mode set");
		}
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
		if (!$this->parentContainer || $this->mode == self::MODE_STANDARD_COMPLIANT) {
			return parent::get($id);
		} elseif ($this->mode == self::MODE_ACT_AS_MASTER) {
			if ($this->nbLoops != 0) {
				/*if (!array_key_exists($id, $this->values)) {
					throw new PimpleNotFoundException(sprintf('Identifier "%s" is not defined.', $id));
				}
				
				$isFactory = is_object($this->values[$id]) && method_exists($this->values[$id], '__invoke');
				
				return $isFactory ? $this->values[$id]($this->wrappedFallbackContainer) : $this->values[$id];*/
				return parent::get($id);
				
			} else {
				$this->nbLoops++;
				$instance = $this->parentContainer->get($id);
				$this->nbLoops--;
				return $instance;
			}
		} else {
			throw new \Exception("Invalid mode set");
		}
		
		return null;
	}
	
	/* (non-PHPdoc)
	 * @see \Interop\Container\ParentAwareContainerInterface::setParentContainer()
	*/
	public function setParentContainer(ContainerInterface $container) {
		$this->parentContainer = $container;
	}
}
