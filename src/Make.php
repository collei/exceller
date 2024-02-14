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
	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var int
	 */
	protected $startLine = 1;

	/**
	 * @var bool
	 */
	protected $hasHeader = true;

	/**
	 * @var int|string|null
	 */
	protected $sheetIndex = null;

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
	 * @static
	 * @param string $fileName
	 * @return instanceof Make
	 */
	public static function read(string $fileName)
	{
		return new class($fileName) extends Make {};
	}

	/**
	 * Determine the start row from which proceed.
	 *
	 * @param int $start
	 * @return $this
	 */
	public function fromLine(int $start)
	{
		$this->startLine = ($start > 0) ? $start : 1;
		//
		return $this;
	}

	/** 
	 * Alias of static::fromLine().
	 *
	 * @param int $start
	 * @return $this
	 */
	public function fromRow(int $start)
	{
		return $this->fromLine($start);
	}

	/**
	 * Determine which sheet should be read.
	 *
	 * @param int $index
	 * @return $this
	 */
	public function sheet(int $index)
	{
		$this->sheetIndex = ($start >= 0) ? $start : null;
		//
		return $this;
	}

	/**
	 * Determine by name which sheet should be read.
	 *
	 * @param string $name
	 * @return $this
	 */
	public function sheetName(string $name)
	{
		$this->sheetIndex = $name ?: null;
		//
		return $this;
	}

	/**
	 * Determine if header should not be discarded.
	 *
	 * @return $this
	 */
	public function withHeader()
	{
		$this->hasHeader = true;
		//
		return $this;
	}

	/**
	 * Determine if header should be discarded.
	 *
	 * @return $this
	 */
	public function withoutHeader()
	{
		$this->hasHeader = false;
		//
		return $this;
	}

	/**
	 * Import rows from spreadsheet into a PHP array.
	 *
	 * @return array
	 */
	public function toArray()
	{
		$reader = new ArrayReader($this->fileName);

		return $reader->selectSheet($this->sheetIndex)
					->readIntoArray($this->startLine, $this->hasHeader);
	}

	/**
	 * Import rows from spreadsheet, one by one, by using a callback function.
	 *
	 * @param \Closure $callback
	 * @return array
	 */
	public function withClosure(Closure $callback)
	{
		$reader = new ClosureReader($this->fileName);

		return $reader->selectSheet($this->sheetIndex)
					->readRowsWith($callback, $this->startLine, $this->hasHeader);
	}

	/**
	 * Import rows from spreadsheet, one by one, by using a user-defined class.
	 *
	 * @param string $fileName
	 * @param object|string $classOrInstance
	 * @return int
	 */
	public function using($classOrInstance)
	{
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

		return new ClassReader($this->fileName, $instance);
	}

}
