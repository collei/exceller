<?php
namespace Collei\Exceller\Concerns;

/**
 * Defines the row limit (row count) for the data to be read.
 */
interface WithLimit
{
	/**
	 * Must return an integer greater than WithStartRow::start().
	 *
	 * @return int
	 */
	public function limit();
}
