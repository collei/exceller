<?php
namespace Collei\Exceller\Concerns;

/**
 * Allows skipping missing sheets.
 */
interface SkipsUnknownSheets
{
	/**
	 * Called when $sheetName does not exist.
	 *
	 * @return void
	 */
	public function onUnknownSheet($sheetName);
}
