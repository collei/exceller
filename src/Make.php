<?php
namespace Collei\Exceller;

use Collei\Exceller\Input\ArrayReader;
use Collei\Exceller\Input\ClassReader;
use Collei\Exceller\Input\ClosureReader;
use Collei\Exceller\Concerns\ShouldThrowExceptions;
use Closure;
use RuntimeException;
use InvalidArgumentException;

abstract class Make
{
	protected $fileName;

	/**
	 * Used by the Do::read() static method only.
	 *
	 * @param string $fileName
	 * @return void
	 */
	protected function __construct(string $fileName)
	{
		$this->fileName = $fileName;
	}

	/**
	 * Start a new instance with a known filename.
	 *
	 * @param string $fileName
	 * @return instanceof Make
	 */
	public static function read(string $fileName)
	{
		return new class($fileName) extends Make {};
	}

	/**
	 * Import rows from spreadsheet into a PHP array.
	 *
	 * @param int $startLine = 1
	 * @param string $sheet = null
	 * @param bool $hasHeader = true
	 * @return array
	 */
	public function toArray(
		int $startLine = 1,
		string $sheet = null,
		bool $hasHeader = true
	) {
		$reader = new ArrayReader($this->fileName);

		return $reader->selectSheet($sheet)->readIntoArray($startLine, $hasHeader);
	}

	/**
	 * Import rows from spreadsheet, one by one, by using a callback function.
	 *
	 * @param \Closure $callback
	 * @param int $startLine = 1
	 * @param string $sheet = null
	 * @param bool $hasHeader = true
	 * @return array
	 */
	public function withClosure(
		Closure $callback,
		int $startLine = 1,
		string $sheet = null,
		bool $hasHeader = true
	) {
		$reader = new ClosureReader($this->fileName);

		return $reader->selectSheet($sheet)->readRowsWith($callback, $startLine, $hasHeader);
	}

	/**
	 * Import rows from spreadsheet, one by one, by using a user-defined class.
	 *
	 * @param string $fileName
	 * @param object|string $classOrInstance
	 * @return int
	 */
	public function using(
		$classOrInstance
	) {
		if (is_string($classOrInstance)) {
			if (class_exists($classOrInstance)) {
				$instance = new $classOrInstance();
			} else {
				throw new RuntimeException(
					sprintf('Class not found: %s', $classOrInstance)
				);
			}
		} elseif (is_object($classOrInstance)) {
			$instance = $classOrInstance;
		} else {
			throw new InvalidArgumentException(
				sprintf('Argument 2: expected a class name or instance, but given %s.', gettype($classOrInstance))
			);
		}

		$throwOnError = $instance instanceof ShouldThrowExceptions;

		$reader = new ClassReader($this->fileName, $instance);

		return $reader->selectSheet($sheet)->readRowsWith($callback, $startLine, $hasHeader);
	}

}
