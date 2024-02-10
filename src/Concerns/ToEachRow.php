<?php
namespace Collei\Exceller\Concerns;

/**
 * Allows handle rows, one by one.
 */
interface ToEachRow
{
	/**
	 * Allows handle rows, one by one.
	 *
	 * @return void
	 */
	public function row(array $row);
}
