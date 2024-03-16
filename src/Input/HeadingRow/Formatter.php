<?php
namespace Collei\Exceller\Input\HeadingRow;

use InvalidArgumentException;
use Exception;
use Throwable;

/**
 *	Base Formatter
 *
 *	@author alarido <alarido.su@gmail.com>
 *	@since 2024-03-08
 */
abstract class Formatter
{
	/**
	 * @var array
	 */
	protected static $formatters = [];

	/**
	 * @var string
	 */
	protected static $defaultFormatter = 'ascii';

	/**
	 * Extend the formatter list by adding a custom one.
	 *
	 * @param string $name
	 * @param callable|\Collei\Exceller\Formatters\HeadingRowFormatter
	 */
	public static function extend(string $name, $formatter)
	{
		if (! is_callable($formatter) && ! ($formatter instanceof self)) {
			throw new InvalidArgumentException(
				'formatter must be either a callable or a HeadingRowFormatter instance.'
			); 
		}

		self::$formatters[strtolower($name)] = $formatter;
	}

	/**
	 * Extend the formatter list by adding a custom one.
	 *
	 * @param mixed $value
	 * @param int|string $key
	 * @param string $formatterName = null
	 * @return string
	 * @throws \InvalidArgumentException if the given name was not found.
	 * @throws \Exception if no registered formatter was set.
	 */
	public static final function apply($value, $key, string $formatterName = null)
	{
		self::registerBasicFormatters();

		$which = $formatterName ?? self::$defaultFormatter;

		if ($formatter = self::$formatters[$which] ?? null) {
			if ($formatter instanceof self) {
				return $formatter->format($value, $key);
			}

			return $formatter($value, $key);
		}

		if ($formatterName) {
			throw new InvalidArgumentException(
				sprintf('The formatter \'%s\' was not found and no default was available.', $formatterName)
			);
		} elseif ($which) {
			throw new Exception(
				sprintf('The formatter \'%s\' was not found.', $which)
			);
		} else {
			throw new Exception('No registered formatter was found.');
		}
	}

	/**
	 * Register basic formatters if not yet.
	 *
	 * @return void
	 */
	protected static function registerBasicFormatters()
	{
		if (! array_key_exists('none', self::$formatters)) {
			self::extend('none', function($value, $key) {
				return $value;
			});

			self::extend('ascii', new AsciiFormatter());
		}
	}

	/**
	 * Format the value.
	 *
	 * @param  mixed  $value
	 * @param  int|string  $key
	 * @return string
	 */
	abstract public function format($value, $key);
}

