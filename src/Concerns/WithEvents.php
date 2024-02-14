<?php
namespace Collei\Exceller\Concerns;

/**
 * Defines which events (if any) should be taken in account.
 */
interface WithEvents
{
	/**
	 * Must return an array.
	 *
	 * @return array
	 */
	public function registerEvents();
}
