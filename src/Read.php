<?php
namespace Collei\Exceller;

use Closure;
use Collei\Exceller\Input\ArrayReader;
use Collei\Exceller\Input\ClosureReader;

abstract class Read
{
	/**
	 * Import rows from spreadsheet into a PHP array.
	 *
	 * @param string $fileName
	 * @param int $startLine = 1
	 * @param string $sheet = null
	 * @param bool $hasHeader = true
	 * @return array
	 */
	public static function toArray(
		string $fileName,
		int $startLine = 1,
		string $sheet = null,
		bool $hasHeader = true
	) {
		$reader = new ArrayReader($fileName);

		return $reader->selectSheet($sheet)->readIntoArray($startLine, $hasHeader);
	}

	/**
	 * Import rows from spreadsheet, one by one, by using a callback function.
	 *
	 * @param string $fileName
	 * @param \Closure $callback
	 * @param int $startLine = 1
	 * @param string $sheet = null
	 * @param bool $hasHeader = true
	 * @return array
	 */
	public static function withClosure(
		string $fileName,
		Closure $callback,
		int $startLine = 1,
		string $sheet = null,
		bool $hasHeader = true
	) {
		$reader = new ClosureReader($fileName);

		return $reader->selectSheet($sheet)->readRowsWith($callback, $startLine, $hasHeader);
	}

}
