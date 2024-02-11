<?php
namespace Collei\Exceller\Concerns;

/**
 * Defines the first data row to be read.
 */
interface WithStartRow
{
	/**
	 * Must return an integer greater than zero.
	 *
	 * @return int
	 */
	public function start();
}
