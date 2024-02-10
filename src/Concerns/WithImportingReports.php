<?php
namespace Collei\Exceller\Concerns;

/**
 * Allows receiving notification about importing completion.
 */
interface WithImportingReports
{
	/**
	 * Receive notification about importing completion.
	 *
	 * @return void
	 */
	public function report($info);
}
