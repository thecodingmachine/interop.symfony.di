Extensible DI container for Symfony2
====================================

This package contains a class that extends the `Container` class of Symfony 2.
The extended class will let you add additional dependency injection containers (DIC) to Symfony 2's container.

This means you are no more forced into using Symfony's DIC only, you can use add any DIC you want!

How does it work?
-----------------

In your `app/AppKernel.php` file, add these 2 methods:

```php
/**
 * Gets the container's base class.
 * We use this to make Symfony use the ExtensibleContainer.
 *
 * @return string
 */
protected function getContainerBaseClass()
{
	return 'Mouf\\Symfony\\Component\\DependencyInjection\\ExtensibleContainer';
}

/**
 * Initializes the service container.
 *
 * Use this method to initialize your own DI container and register it
 * in Symfony DI container.
 */
protected function initializeContainer()
{
	parent::initializeContainer();
    	
	// Here, you can access the Symfony container using $this->container and register
	// your own container in it.

	// Here is an instance including Mouf's DI container:
	$this->container->registerPrependContainer(MoufManager::getMoufManager());
}
```

Your DI container must respect the [`ContainerInterface` described in this document.](https://github.com/moufmouf/fig-standards/blob/master/proposed/dependency-injection/dependency-injection.md)

Note: your container does not have to explicitly implement the `ContainerInterface` interface (because it is not standard yet),
but it needs to provide the `get` and `has` methods.

What DI containers can I plug in Symfony?
-----------------------------------------

Out of the box, you can plug in these DI containers, because they respect the `ContainerInterface` interface:

- Mouf (http://mouf-php.com)
- Aura DI (https://github.com/auraphp/Aura.Di)

But wait! Thanks to Jeremy Lindblom and its awesome [Acclimate package](https://github.com/jeremeamia/acclimate), you can now take almost any dependency injection container out there, and get an adapter on that container that respects the `ContainerInterface` interface.

Want an exemple? Let's add a Pimple container that will be queried if Symfony's DIC does not contain the instance we
are looking for.

```php
/**
 * Initializes the service container.
 *
 * Use this method to initialize your own DI container and register it
 * in Symfony DI container.
 */
protected function initializeContainer()
{
	parent::initializeContainer();
    	
	// Here, you can access the Symfony container using $this->container and register
	// your own container in it.

	// Create a Pimple container and store an SplQueue object
	$pimple = new Pimple();
	$pimple['queue'] = function() {
	    $queue = new SplQueue();
	    $queue->enqueue('Hello!');
	    return $queue;
	};

	// Create an instance of Acclimate and use it to adapt the Pimple container to the Acclimate ContainerInterface
	$acclimate = new Acclimate();
	$pimpleAdapter = $acclimate->adaptContainer($pimple);

	// Here is an instance including Mouf's DI container:
	$this->container->registerFallbackContainer($pimpleAdapter);
}
```

Prepending or appending containers
----------------------------------

When registering your container, you have 2 options:

- You can **preprend** your container. In this case, your container will be called before Symfony's container.
- You can use your container as a **fallback**. In this case, your container will be called only if Symfony's container does not contain the instance.

To preprend your container, use the `registerPrependContainer` method:
```php
$this->container->registerPrependContainer($myContainer);
```

To use your container has a fallback, use the `registerFallbackContainer` method:
```php
$this->container->registerFallbackContainer($myContainer);
```

<div class="alert alert-info"><strong>Note:</strong> you are not limited to one container, you can register as many as you want.</div>

Installation
------------

This class is distributed as a [Composer package](https://packagist.org/packages/mouf/interop.symfony.di):

```
{
	require: {
		"mouf/interop.symfony.di" : "2.3.*"
	}
}
```