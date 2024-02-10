<?php
namespace Collei\Exceller\Input;

use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIOFactory;
use Collei\Exceller\Support\Str;

/**
 * Basic input engine for spreadsheet row readers;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
abstract class Reader
{
	/**
	 * Open a worksheet from the given $fileName
	 *
	 * @param string $fileName
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	protected static function openSheet(string $fileName)
	{
		/**  Identify the type of $fileName  **/
		$fileType = PhpSpreadsheetIOFactory::identify($fileName);
		/**  Create a new Reader of the type that has been identified  **/
		$reader = PhpSpreadsheetIOFactory::createReader($fileType);
		/**  Load $fileName to a Spreadsheet Object  **/
		$spreadsheet = $reader->load($fileName);
		/**  Get the active sheet (usually the first) **/
		return $spreadsheet->getActiveSheet();
	}

	/**
	 * Process sheet lines, one by one, bringing them to the Closure.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Closure $callback
	 * @param int $start = 1
	 * @return array
	 * @throws \RuntimeException if $start is lesser then 1
	 */
	protected static function processSheet(
		Worksheet $sheet,
		Closure $callback,
		int $start = 1
	) {
		if ($start < 1) {
			throw new RuntimeException('Primeira linha não pode ser menor que 1');
		}

		// Data limits
		$maxRow = $sheet->getHighestRow();
		$maxColumn = $sheet->getHighestColumn();
		$lines = range($start, $maxRow);

		// data extraction
		$emptyLinesCount = 0;
		$emptyLinesLimit = 10;

		// header guide
		$headerLine = null;

		foreach ($lines as $lineNumber) {
			// define row range (the columns whose data must get captured)
			$rangeStr = str_replace(['_','?'], [$lineNumber, $maxColumn], 'A_:?_');
			// extract row data
			$row = ($sublin = $sheet->rangeToArray($rangeStr))[0];
			// filter empty cells
			$cleanedRow = array_filter($row, [self::class, 'isNotEmpty']);

			// if got an empty row...
			if (0 == count($cleanedRow)) {
				// ...increase empty rows counter
				++$emptyLinesCount;
			} else {
				// if not, reset the counter
				$emptyLinesCount = 0;
			}

			// if the maximum of empty lines found in the midst of data
			// got reached, stop capturing data
			if ($emptyLinesCount > $emptyLinesLimit) break;

			// skip sheet empty rows
			if ($emptyLinesCount > 0) continue;

			// trimmed row of data
			$trimmedLine = [];

			// if the title row got captured
			if ($headerLine) {
				// capture data from existing title row columns only
				foreach ($headerLine as $k => $v) {
					$trimmedLine[$v['sanitized']] = $row[$k];
				}
				// send the row via the callback
				$callback($trimmedLine);
			} else {
				// filter empty cells from the title row
				$headerLine = $originalTitleLine = array_filter($row, [self::class, 'isNotEmpty']);
				// make sanitization for extracting identifiers
				$header = array_map([self::class, 'sanitizeIdentifier'], $headerLine);
				//
				foreach ($headerLine as $k => $original) {
					$sanitized = $header[$k];
					//
					$headerLine[$k] = compact('original','sanitized');
				}
				//
				foreach ($headerLine as $k => $v) {
					$trimmedLine[$v['sanitized']] = $originalTitleLine[$k];
				}
				// send the row via the callback
				$callback($header = $trimmedLine);
			}

		}

		// header goes here
		return $header;
	}

	/**
	 * Sanitizes title contents to make them become identifier.
	 *
	 * @param string $identifier
	 * @return string
	 */
	private static function sanitizeIdentifier(string $identifier)
	{
		// remover acentos de letras, troca espaços para underscore
		$identifier = Str::ascii($identifier);
		// tudo em minúsculas
		$identifier = strtolower($identifier);
		// trocar espaços para underscores
		$identifier = str_replace([' ',"\t","\xA0"], '_', $identifier);
		// somente letras, dígitos e underscores
		$identifier = preg_replace('/[^A-Za-z0-9_]+/', '', $identifier);
		// reduzir duplos espaços em um só
		$identifier = preg_replace('/_+/', '_', $identifier);
		// se primeiro char é dígito, prefixa com _
		if (is_numeric(substr($identifier, 0, 1))) {
			$identifier = '_' . $identifier;
		}
		//
		return $identifier;
	}

	/**
	 * Tells if the argument is not really empty.
	 * Returns false even for a string like '  ' (spaces)
	 *
	 * @param mixed $string
	 * @return bool
	 */
	private static function isNotEmpty($string)
	{
		if (is_string($string)) {
			$string = trim(str_replace("\xA0",' ',$string));
		}
		//
		return !empty($string);
	}
	
}
