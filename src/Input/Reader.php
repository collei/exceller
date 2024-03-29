<?php
namespace Collei\Exceller\Input;

use PhpOffice\PhpSpreadsheet\IOFactory as PhpSpreadsheetIOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Collei\Exceller\Input\HeadingRow\Formatter;
use Closure;
use DateTime;
use InvalidArgumentException;

/**
 * Basic input engine for spreadsheet row readers;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
abstract class Reader
{
	/**
	 * @var string
	 */
	protected $fileName;

	/**
	 * @var string
	 */
	protected $currentSheet = null;

	/**
	 * @var \PhpOffice\PhpSpreadsheet\Spreadsheet
	 */
	protected $spreadsheet;

	/**
	 * @var array
	 */
	protected $reports = [];

	/**
	 * Initialize an instance for $fileName
	 *
	 * @param string $fileName
	 * @param string|int $whichSheet = null
	 * @return void
	 */
	public function __construct(string $fileName, $whichSheet = null)
	{
		$this->fileName = $fileName;
		$this->currentSheet = $whichSheet;

		// Identify the type of $fileName
		$fileType = PhpSpreadsheetIOFactory::identify($this->fileName);
		// Create a new Reader of the type that has been identified
		$reader = PhpSpreadsheetIOFactory::createReader($fileType);
		// Load $fileName to a Spreadsheet Object
		$this->spreadsheet = $reader->load($fileName);
	}

	/**
	 * Select which sheet should be read from file.
	 *
	 * @param string|int $whichSheet
	 * @return $this
	 */
	public function selectSheet($whichSheet)
	{
		if (! empty($whichSheet)) {
			$this->currentSheet = $whichSheet;
		}

		return $this;
	}
	
	/**
	 * Check if the given sheet name or index exists.
	 *
	 * @param int|string $whichSheet
	 * @return bool
	 * @throws InvalidArgumentException
	 */
	public function hasSheet($whichSheet)
	{
		if (is_int($whichSheet)) {
			return ($whichSheet >= 0) && ($whichSheet < $this->spreadsheet->getSheetCount());
		}

		if (is_string($whichSheet)) {
			return $this->spreadsheet->sheetNameExists($whichSheet);
		}

		throw new InvalidArgumentException(
			sprintf('Argument should be int or string, but given %s', gettype($whichSheet))
		);
	}
	
	/**
	 * Get a list of the available sheet names.
	 *
	 * @return array
	 */
	public function getSheetNames()
	{
		return $this->spreadsheet->getSheetNames();
	}

	/**
	 * Adds a report to the report list.
	 *
	 * @param array $info
	 * @return $this
	 */
	protected function addReport(array $info)
	{
		$timestamp = new DateTime();

		$this->reports[] = $info + compact('timestamp');

		return $this;
	}
	
	/**
	 * Open a worksheet from the spreadsheet
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	protected function openSheet($which = null)
	{
		// If no sheet is given, assume the selected one
		$which = $which ?: $this->currentSheet;

		if (! empty($which)) {
			// Get the sheet named $which
			if (is_string($which)) {
				return $this->spreadsheet->getSheetByName($which);
			}

			// Get the sheet with index $which
			if (is_int($which)) {
				return $this->spreadsheet->getSheet($which);
			}
		}

		// Get the active sheet (usually the first)
		return $this->spreadsheet->getActiveSheet();
	}

	/* internal *\
	\* mechanic */

	/**
	 * Process sheet lines, one by one, bringing them to the Closure.
	 *
	 * @static
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Closure $callback
	 * @param int $startRow = 1
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param bool|array $withHeading = false
	 * @param bool $groupedHeadings = false
	 * @return array
	 * @throws \RuntimeException if $startRow is lesser then 1
	 */
	protected static function processSheet(
		Worksheet $sheet,
		Closure $callback,
		int $startRow = 1,
		int $endRow = null,
		string $endColumn = null,
		$withHeading = null,
		bool $groupedHeadings = false
	) {
		if ($startRow < 1) {
			throw new RuntimeException('First line must not be lesser than 1');
		}

		// Data limits
		$endRow = $endRow ?: $sheet->getHighestRow();
		$endColumn = $endColumn ?: $sheet->getHighestColumn();
		$lines = range($startRow, $endRow);

		// data extraction
		$emptyLinesCount = 0;
		$emptyLinesLimit = 10;

		// header guide
		$hasHeading = false !== $withHeading;
		$hasNoHeading = false === $hasHeading;
		$headerLine = $hasHeading ? null : range('A', $endColumn);

		// custom headings (column names)
		$customHeadings = is_array($withHeading) ? $withHeading : null;

		foreach ($lines as $lineNumber) {
			// define row range (the columns whose data must get captured)
			$rangeStr = str_replace(['_','?'], [$lineNumber, $endColumn], 'A_:?_');
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
			if ($headerLine || $hasNoHeading) {
				// capture data from existing title row columns only
				if ($groupedHeadings) {
					// allow for multiple columns with same name
					// to be captured as an array of data
					foreach ($headerLine as $k => $v) {
						$colName = $v['sanitized'];
						//
						if (isset($trimmedLine[$colName])) {
							// if two or more, turn it in an array
							// so all values can be accomodated
							if (is_array($trimmedLine[$colName])) {
								$trimmedLine[$colName][] = $row[$k];
							} else {
								$trimmedLine[$colName] = [$trimmedLine[$colName], $row[$k]];
							}
						} else {
							// if just one, keep it scalar
							$trimmedLine[$colName] = $row[$k];
						}
					}
				} else {
					// captures only the first column of each name
					// when there are two or more with same name
					foreach ($headerLine as $k => $v) {
						$colName = $hasHeading ? $v['sanitized'] : $k;
						//
						// avoid content override (preserves the first non-empty)
						if (empty($trimmedLine[$colName])) {
							$trimmedLine[$colName] = $row[$k];
						}
					}
				}
				// send the row via the callback
				$callback($trimmedLine);
			} else {
				// filter empty cells from the title row
				$headerLine = $originalTitleLine = array_filter($row, [self::class, 'isNotEmpty']);

				// if $customHeadings is given, use them 
				if (is_array($customHeadings)) {
					$columnCount = count($headerLine);

					// if size doesn't fit, numeric arrays will be issued
					if (empty($customHeadings) || count($customHeadings) !== $columnCount) {
						$customHeadings = range(0, $columnCount);
					}

					// appropriate the custom headings
					foreach ($headerLine as $key => $value) {
						$headerLine[$key] = array_shift($customHeadings);
					}
				}

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
		return $headerLine;
	}

	/**
	 * Sanitizes title contents to make them become identifier.
	 *
	 * @static
	 * @param string $identifier
	 * @param string $using = 'ascii'
	 * @return string
	 */
	private static function sanitizeIdentifier(
		string $identifier, string $using = 'ascii'
	) {
		// permite usar customizado se solicitado
		if ('ascii' !== $using) {
			return Formatter::apply($identifier, 0, $using);
		}

		// remover acentos de letras, troca espaços para underscore
		$identifier = Formatter::apply($identifier, 0, 'ascii');
		// tudo em minúsculas
		$identifier = strtolower(trim($identifier));
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
		return trim($identifier, '_');
	}

	/**
	 * Tells if the argument is not really empty.
	 * Returns false even for a string like '  ' (spaces)
	 *
	 * @static
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
