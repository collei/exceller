<?php
namespace Collei\Exceller\Input;

use Closure;

/**
 * Input rows from a Spreadsheet one by one using a Closure;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
class ClosureReader extends Reader
{
	/**
	 * Import rows from spreadsheet by using a custom Closure
	 *
	 * @param string $fileName
	 * @param int $startLine = 1
	 * @param bool $hasDataHeader = true
	 * @return int
	 */
	public static function readExcelRows(
		string $fileName,
		Closure $callback,
		int $startLine = 1,
		bool $hasDataHeader = true
	) {
		$sheet = self::openSheet($fileName);

		$firstLine = $hasDataHeader;

		$lineCount = self::processSheet($sheet, function($row) use ($callback, &$firstLine) {
			if ($firstLine) {
				$firstLine = false;
			} else {
				$callback($row);
			}
		}, $startLine);

		return $lineCount;
	}	
}
