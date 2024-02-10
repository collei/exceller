<?php
namespace Collei\Exceller\Concerns;

/**
 * Defines the column limit for the data to be read.
 */
interface WithColumnLimit
{
	/**
	 * Must return a string that is an Excel column header (e.g., 'G').
	 *
	 * @return string
	 */
	public function endColumn();
}
