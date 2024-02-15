<?php
namespace Collei\Exceller\Concerns;

use Collei\Exceller\Events\AfterImport;
use Collei\Exceller\Events\AfterSheet;
use Collei\Exceller\Events\BeforeImport;
use Collei\Exceller\Events\BeforeSheet;
use Collei\Exceller\Events\ImportFailed;

trait RegistersEventListeners
{
	/**
	 * Register listeners if already present.
	 *
	 * @return array
	 */
	public function registerEvents()
	{
		$listenersClasses = [
			BeforeImport::class	=> 'beforeImport',
			AfterImport::class	=> 'afterImport',
			ImportFailed::class	=> 'importFailed',
			BeforeSheet::class	=> 'beforeSheet',
			AfterSheet::class	=> 'afterSheet',
		];
		$listeners = [];

		foreach ($listenersClasses as $class => $name) {
			// Method names are case insensitive in php
			if (method_exists($this, $name)) {
				// Allow methods to not be static
				$listeners[$class] = [$this, $name];
			}
		}

		return $listeners;
	}
}
