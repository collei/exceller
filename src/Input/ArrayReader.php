<?php
namespace Collei\Exceller\Input;

use Closure;

/**
 * Input rows from a Spreadsheet one by one using a Closure;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
class ArrayReader extends Reader
{
	/**
	 * Import rows from spreadsheet into a PHP array
	 *
	 * @param string $fileName
	 * @param int $startLine = 1
	 * @param bool $hasDataHeader = true
	 * @return array
	 */
	public static function readExcelIntoArray(
		string $fileName,
		int $startLine = 1,
		bool $hasDataHeader = true
	) {
		$sheet = self::openSheet($fileName);

		$data = [];
		$dataHeader = null;

		$firstLine = $hasDataHeader;

		$linhas = self::processSheet($sheet, function($row) use (&$data, &$dataHeader, &$firstLine){
			if ($firstLine) {
				$dataHeader = $row;
				$firstLine = false;
			} else {
				$data[] = $row;
			}
		}, $startLine);

		return compact('dataHeader','data');
	}
}
