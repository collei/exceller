<?php
namespace Collei\Exceller\Input;

use Collei\Exceller\Concerns\WithColumnLimit;
use Collei\Exceller\Concerns\WithHeadingRow;
use Collei\Exceller\Concerns\WithGroupedHeadingRow;
use Collei\Exceller\Concerns\WithLimit;
use Collei\Exceller\Concerns\WithMultipleSheets;
use Collei\Exceller\Concerns\WithStartRow;
use Collei\Exceller\Concerns\WithEvents;
use Collei\Exceller\Concerns\OnEachRow;
use Collei\Exceller\Concerns\ToEachRow;
use Collei\Exceller\Concerns\ToArray;
use Collei\Exceller\Concerns\ToArrayBlocks;
use Collei\Exceller\Concerns\WithImportingReports;
use Collei\Exceller\Concerns\SkipsUnknownSheets;
use Collei\Exceller\Events\Event;
use Collei\Exceller\Events\AfterImport;
use Collei\Exceller\Events\AfterSheet;
use Collei\Exceller\Events\BeforeImport;
use Collei\Exceller\Events\BeforeSheet;
use Collei\Exceller\Events\ImportFailed;
use Collei\Exceller\Exceptions\SheetNotFoundException;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;


/**
 * Input rows from a Spreadsheet one by one using a Closure;
 *
 * @author Alarido Su <alarido.su@gmail.com>
 */
class Importer extends Reader
{
	/**
	 * @var bool
	 */
	protected $throwOnError = true;

	/**
	 * @var array
	 */
	protected $listeners = [];

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

		if ($importer instanceof WithEvents) {
			$this->listeners = $importer->registerEvents();
		}

		$this->import($importer);
	}

	/**
	 * Import rows from spreadsheet by using a custom importer object instance.
	 *
	 * @param object $importer
	 * @return void
	 */
	protected function import(object $importer)
	{
		// before start importing
		$this->notifyEvent(new BeforeImport($importer));

		// the importing
		if ($importer instanceof WithMultipleSheets) {
			$this->importMultiple($importer, $this->throwOnError);
		} else {
			$this->importSingle(null, $importer, $this->throwOnError);
		}

		// after finish importing
		$this->notifyEvent(new AfterImport($importer));
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
		// read sheet counter
		$readSheet = 0;
		// info about failed sheets
		$errorSheetNames = [];

		// iterate
		foreach ($sheetImporters as $name => $importer) {
			// check if the given sheet exists
			$sheetExists = false
				|| (is_int($name) && ($name >= 0) && ($name < $sheetCount))
				|| (is_string($name) && $this->spreadsheet->sheetNameExists($name));

			// sheet by index
			if ($sheetExists) {
				// before sheet
				$this->notifyEvent(new BeforeSheet($multipleImporter, $name));

				// call the importer
				if ($this->importSingle($name, $importer)) {
					++$readSheet;
				} else {
					$errorSheetNames[] = $name;
				}
				//
				// after sheet
				$this->notifyEvent(new AfterSheet($multipleImporter, $name));

				// go next sheet
				continue;
			}

			if ($importer instanceof SkipsUnknownSheets) {
				// notify about the missing sheet
				$importer->onUnknownSheet($name);
				//
			} elseif ($multipleImporter instanceof SkipsUnknownSheets) {
				// notify about the missing sheet
				$multipleImporter->onUnknownSheet($name);
				//
			} else {
				// throws it
				throw new SheetNotFoundException(
					sprintf('Sheet %s not found in the file', $name)
				);
			}
		}

		// obtain sheet names
		$sheetNames = array_keys($sheetImporters);
		// prepare the package
		$info = compact('sheetCount','readSheet','sheetNames','errorSheetNames');

		// add report to reader instance
		$this->addReport($info);

		// report to importer class if requested
		if ($multipleImporter instanceof WithImportingReports) {
			$multipleImporter->report((object) $info);
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

		// if $hasDataHeader === true, leave it null.
		// Otherwise (if custom headings given, then obtain them, else leave it empty)
		$customHeadings = $hasDataHeader
			? null
			: ($importer instanceof WithHeadings ? $importer->headings() : []);

		// pick the sheet reference
		$sheet = empty($sheetName) ? $this->openSheet() : $this->openSheet($sheetName);

		if ($importer instanceof OnEachRow) {
			$boolResult = $this->doImportOnEachRow(
				$sheet, $importer, $startRow, $endRow, $endColumn, $customHeadings, $hasDataHeader, $throwOnError
			);
		}
		elseif ($importer instanceof ToEachRow) {
			$boolResult = $this->doImportToEachRow(
				$sheet, $importer, $startRow, $endRow, $endColumn, $customHeadings, $hasDataHeader, $throwOnError
			);
		}
		elseif ($importer instanceof ToArray) {
			$boolResult = $this->doImportToArray(
				$sheet, $importer, $startRow, $endRow, $endColumn, $customHeadings, $hasDataHeader, $throwOnError
			);
		}
		elseif ($importer instanceof ToArrayBlocks) {
			$boolResult = $this->doImportToArrayBlocks(
				$sheet, $importer, $startRow, $endRow, $endColumn, $customHeadings, $hasDataHeader, $throwOnError
			);
		}

		if (! $boolResult) {
			// on import fail
			$this->notifyEvent(new ImportFailed($importer, $sheetName));
		}

		return $boolResult;
	}

	/**
	 * Notify about events.
	 *
	 * @param \Collei\Exceller\Events\Event $event
	 */
	protected function notifyEvent(Event $event)
	{
		$class = get_class($event);

		if ($listener = $this->listeners[$class] ?? null) {
			call_user_func($listener, $event);
		}
	}

	/**
	 * Import rows from spreadsheet by using an OnEachRow instance.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Collei\Exceller\Concerns\OnEachRow $importer
	 * @param int $startRow
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param array $customHeadings = null,
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
		array $customHeadings = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// initialize line control
		$firstLine = $hasDataHeader;
		// configure if headings should be grouped
		$groupedHeadings = $importer instanceof WithGroupedHeadingRow;
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
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
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
	 * @param array $customHeadings = null,
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
		array $customHeadings = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// initialize line control
		$firstLine = $hasDataHeader;
		// configure if headings should be grouped
		$groupedHeadings = $importer instanceof WithGroupedHeadingRow;
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
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
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
	 * @param array $customHeadings = null,
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
		array $customHeadings = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// data lines here
		$array = [];
		// initialize line control
		$firstLine = $hasDataHeader;
		// configure if headings should be grouped
		$groupedHeadings = $importer instanceof WithGroupedHeadingRow;
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
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
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

	/**
	 * Import rows from spreadsheet by using a ToArrayBlocks instance.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @param \Collei\Exceller\Concerns\ToArrayBlocks $importer
	 * @param int $startRow
	 * @param int $endRow = null
	 * @param string $endColumn = null
	 * @param array $customHeadings = null,
	 * @param bool $hasDataHeader = true
	 * @param bool $throwOnError = true
	 * @throws \Collei\Exceller\Exceptions\ExcellerException
	 */
	protected function doImportToArrayBlocks(
		Worksheet $sheet,
		ToArrayBlocks $importer,
		int $startRow,
		int $endRow = null,
		string $endColumn = null,
		array $customHeadings = null,
		bool $hasDataHeader = true,
		bool $throwOnError = true
	) {
		// data lines here
		$bInfo = (object) [
			'count' => 0,
			'maxSize' => $importer->size(),
			'lines' => [],
		];
		// initialize line control
		$firstLine = $hasDataHeader;
		// configure if headings should be grouped
		$groupedHeadings = $importer instanceof WithGroupedHeadingRow;
		// importer lambda function
		$rowImporterFunction = function($row) use ($importer, $bInfo, &$firstLine) {
			// if heading must be discarded
			if ($firstLine) {
				$firstLine = false;
				return;
			}

			if ($bInfo->count === $bInfo->maxSize) {
				// send the block
				$importer->block($bInfo->lines);
				// reset counters
				$bInfo->lines = [$row];
				$bInfo->count = 1;
			} else {
				// save row in the current block
				$bInfo->lines[] = $row;
				// and update counter
				$bInfo->count++;
			}
		};

		// if $throwOnError == true, let the exceptions show themselves
		if ($throwOnError) {
			$lineCount = static::processSheet(
				$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
			);
		}
		// otherwise, let us confine them for a graceful fail 
		else {
			try {
				$lineCount = static::processSheet(
					$sheet, $rowImporterFunction, $startRow, $endRow, $endColumn, $customHeadings, $groupedHeadings
				);
			}
			catch (Exception $e) {
				return false;
			}
		}

		// send the last block
		if (0 !== $bInfo->count) {
			$importer->block($bInfo->lines);
		}

		// data extracted successfully
		return true;
	}
}
