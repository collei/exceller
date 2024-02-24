<?php
namespace Collei\Exceller\Concerns;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

trait ReferencesCurrentSheet
{
	/**
	 * @var \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	protected $sheet;

	/**
	 * Set the reference.
	 *
	 * @param \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet
	 * @return void
	 */
	public function setSheet(Worksheet $sheet)
	{
		$this->sheet = $sheet;
	}

	/**
	 * Return a reference to the sheet.
	 *
	 * @return \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet
	 */
	public function getSheet()
	{
		return $this->sheet;
	}

	/**
	 * Return a reference to the sheet.
	 *
	 * @return string|null
	 */
	public function getSheetName()
	{
		if ($this->sheet instanceof Worksheet) {
			return $this->sheet->getTitle();
		}

		return null;
	}
}
