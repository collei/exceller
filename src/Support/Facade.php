<?php
namespace Collei\Exceller\Support;

use Collei\Exceller\Exceller;

/**
 *	Reunites facade capabilities
 *
 *	@author alarido <alarido.su@gmail.com>
 *	@since 2024-02-08
 */
abstract class Facade
{
	/**
	 * @static @var $instancePool
	 */
	private static $instancePool = [];

	/**
	 * Initialize (if needed) and returns the instance.
	 *
	 * @return object
	 */
	private static function getInstance()
	{
		if (isset(static::$instancePool[static::class])) {
			return static::$instancePool[static::class];
		}

		if (method_exists(static::class, 'make')) {
			return static::$instancePool[static::class] = static::make();
		}

		throw new RuntimeException(
			sprintf('The %s::make was not yet implemented', static::class)
		);
	}

	/**
	 * Initialize and returns the instance.
	 *
	 * @return object
	 */
	protected static function make()
	{
		return new Exceller('root');
	}

	/**
	 * Handle dynamic, static calls to the object.
	 *
	 * @param  string  $method
	 * @param  array  $args
	 * @return mixed
	 *
	 * @throws \RuntimeException
	 */
	public static function __callStatic($method, $args)
	{
		$instance = static::getInstance();

		if (! is_object($instance)) {
			throw new RuntimeException(
				sprintf('The %s::make method should return an instance', static::class)
			);
		}

		return $instance->$method(...$args);
	}
}
