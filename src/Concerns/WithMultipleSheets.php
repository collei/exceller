<?php
namespace Collei\Exceller\Concerns;

/**
 * Defines the column limit for the data to be read.
 */
interface WithMultipleSheets
{
	/**
	 * Must return an associative array with sheet names as keys
	 * and object instances as values.
	 *
	 * @return array
	 */
	public function sheets();
}
