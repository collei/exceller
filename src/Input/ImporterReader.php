<?php
namespace Collei\Exceller\Input;

use Collei\Exceller\Concerns\WithColumnLimit;
use Collei\Exceller\Concerns\WithHeadingRow;
use Collei\Exceller\Concerns\WithLimit;
use Collei\Exceller\Concerns\WithMultipleSheets;
use Collei\Exceller\Concerns\WithStartRow;
use Collei\Exceller\Concerns\OnEachRow;
use Collei\Exceller\Concerns\ToEachRow;
use Collei\Exceller\Concerns\ToArray;
use Collei\Exceller\Concerns\WithImportingReports;
use Collei\Exceller\Concerns\SkipsUnknownSheets;
use Collei\Exceller\Exceptions\SheetNotFoundException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


/**
 * Input rows from a Spreadsheet one by one using a Closure;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
class ImporterReader extends Reader
{
	/**
	 * @var bool
	 */
	protected $throwOnError = true;

	/**
	 * Initialize an instance for $fileName
	 *
	 * @param string $fileName
	 * @param string|int $whichSheet = null
	 * @return void
	 */
	public function __construct(string $fileName, object $importer, bool $throwOnError = true)
	{
		parent::__construct($fileName, null);

		$this->throwOnError = $throwOnError;

		$this->startImporting($importer);
	}

	/**
	 * Import rows from spreadsheet by using a custom importer object instance.
	 *
	 * @param object $importer
	 * @return void
	 */
	protected function startImporting(object $importer)
	{
		if ($importer instanceof WithMultipleSheets) {
			$this->importMultiple($importer, $this->throwOnError);

			return;
		}

		$this->importSingle(null, $importer, $this->throwOnError);
	}

	/**
	 * Import rows from spreadsheet by using a custom importer object instance.
	 *
	 * @param WithMultipleSheets $multipleImporter
	 * @return int
	 */
	protected function importMultiple(WithMultipleSheets $multipleImporter, bool $throwOnError = true)
	{
		// acquire the sheets to be imported
		$sheetImporters = $multipleImporter->sheets();
		// get the sheet count
		$sheetCount = $this->spreadsheet->getSheetCount();
		// find the last index
		$lastSheet = $sheetCount - 1;
		// read sheet counter
		$readSheet = 0;
		// info about failed sheets
		$errorSheetNames = [];

		// iterate
		foreach ($sheetImporters as $name => $importer) {
			// sheet by index
			if (is_int($name) && ($name >= 0) && ($name <= $lastSheet)) {
				// select sheet
				$sheet = $this->spreadsheet->getSheet($name);
				// call the importer
				if ($this->importSingle($sheet, $importer)) {
					++$readSheet;
				} else {
					$errorSheetNames[] = $name;
				}
			}
			// sheet by name
			elseif (is_string($name) && ($sheet = $this->spreadsheet->getSheetByName($name))) {
				// call the importer
				if ($this->importSingle($sheet, $importer)) {
					++$readSheet;
				} else {
					$errorSheetNames[] = $name;
				}
			}
			//
			if ($multipleImporter instanceof SkipsUnknownSheets) {
				// notify about the missing sheet
				$multipleImporter->onUnknownSheet($name);
			}
			//
			throw new SheetNotFoundException(
				sprintf('Sheet %s not found in the file', $name)
			);
		}

		// report if requested
		if ($multipleImporter instanceof WithImportingReports) {
			// obtain sheet names
			$sheetNames = array_keys($sheetImporters);
			// prepare the package
			$info = (object) compact('sheetCount','readCount','sheetNames','errorSheetNames');
			// report it
			$multipleImporter->report($info);
		}

		// return if all sheets were read successfully
		return $readSheet === $sheetCount;
	}

	/**
	 * Import rows from spreadsheet by using a custom importer object instance.
	 *
	 * @param int|string|null $sheetName
	 * @param object $importer
	 * @param bool $throwOnError = true
	 * @return bool
	 * @throws \Collei\Exceller\Exceptions\ExcellerException
	 */
	protected function importSingle($sheetName, object $importer, bool $throwOnError = true)
	{
		// Default parameters
		$startRow = 1;
		$endRow = null;
		$endColumn = null;

		// Does have a heading row?
		$hasDataHeader = $importer instanceof WithHeadingRow;

		// Does have a different starting row?
		if ($importer instanceof WithStartRow) {
			$startRow = $importer->start();
		}

		// Does have a different ending row?
		if ($importer instanceof WithLimit) {
			$endRow = $importer->limit();
		}

		// Does have a different ending column?
		if ($importer instanceof WithColumnLimit) {
			$endColumn = $importer->endColumn();
		}

		// pick the sheet reference
		$sheet = empty($sheetName) ? $this->openSheet() : $this->openSheet($sheetName);


		if ($importer instanceof OnEachRow) {
			return $this->doImportOnEachRow(
				$sheet, $importer, $startRow, $endRow, $endColumn, $hasDataHeader, $throwOnError
			);
		}
		elseif ($importer instanceof ToEachRow) {
			return $this->doImportToEachRow(
				$sheet, $importer, $startRow, $endRow, $endColumn, $hasDataHeader, $throwOnError
			);
		}
		elseif ($importer instanceof ToArray) {
			return $this->doImportToArray(
				$sheet, $importer, $startRow, $endRow, $endColumn, $hasDataHeader, $throwOnError
			);
		}

		return false;
	}

	/**
	 * Import rows from spreadsheet by using an OnEachRow instance.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Collei\Exceller\Concerns\OnEachRow $importer
	 * @param int $startRow
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param bool $hasDataHeader = true
	 * @param bool $throwOnError = true
	 * @throws \Collei\Exceller\Exceptions\ExcellerException
	 */
	protected function doImportOnEachRow(
		Worksheet $sheet,
		OnEachRow $importer,
		int $startRow,
		int $endRow = null,
		string $endColumn = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// initialize line control
		$firstLine = $hasDataHeader;
		// importer lambda function
		$rowImporterFunction = function($row) use ($importer, &$firstLine) {
			if ($firstLine) {
				$firstLine = false;
			} else {
				$importer->onRow($row);
			}
		};

		// if $throwOnError == true, let the exceptions show themselves
		if ($throwOnError) {
			$lineCount = static::processSheet(
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
				);
			}
			catch (Exception $e) {
				return false;
			}
		}

		// data extracted successfully
		return true;
	}

	/**
	 * Import rows from spreadsheet by using a ToEachRow instance.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Collei\Exceller\Concerns\ToEachRow $importer
	 * @param int $startRow
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param bool $hasDataHeader = true
	 * @param bool $throwOnError = true
	 * @throws \Collei\Exceller\Exceptions\ExcellerException
	 */
	protected function doImportToEachRow(
		Worksheet $sheet,
		ToEachRow $importer,
		int $startRow,
		int $endRow = null,
		string $endColumn = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// initialize line control
		$firstLine = $hasDataHeader;
		// importer lambda function
		$rowImporterFunction = function($row) use ($importer, &$firstLine) {
			if ($firstLine) {
				$firstLine = false;
			} else {
				$importer->row($row);
			}
		};

		// if $throwOnError == true, let the exceptions show themselves
		if ($throwOnError) {
			$lineCount = static::processSheet(
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
				);
			}
			catch (Exception $e) {
				return false;
			}
		}

		// data extracted successfully
		return true;
	}

	/**
	 * Import rows from spreadsheet by using a ToArray instance.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Collei\Exceller\Concerns\ToArray $importer
	 * @param int $startRow
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param bool $hasDataHeader = true
	 * @param bool $throwOnError = true
	 * @throws \Collei\Exceller\Exceptions\ExcellerException
	 */
	protected function doImportToArray(
		Worksheet $sheet,
		ToArray $importer,
		int $startRow,
		int $endRow = null,
		string $endColumn = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// data lines here
		$array = [];
		// initialize line control
		$firstLine = $hasDataHeader;
		// importer lambda function
		$rowImporterFunction = function($row) use (&$array, &$firstLine) {
			if ($firstLine) {
				$firstLine = false;
			} else {
				$array[] = $row;
			}
		};

		// if $throwOnError == true, let the exceptions show themselves
		if ($throwOnError) {
			$lineCount = static::processSheet(
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn
				);
			}
			catch (Exception $e) {
				return false;
			}
		}

		// appropriate collected data
		$importer->array($array);

		// data extracted successfully
		return true;
	}

}
