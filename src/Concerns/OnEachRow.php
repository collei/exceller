<?php
namespace Collei\Exceller\Concerns;

/**
 * Allows handle rows, one by one.
 */
interface OnEachRow
{
	/**
	 * Allows handle rows, one by one.
	 *
	 * @return void
	 */
	public function onRow(array $row);
}
