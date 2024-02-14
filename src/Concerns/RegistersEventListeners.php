<?php

namespace Maatwebsite\Excel\Concerns;

use Maatwebsite\Excel\Events\AfterImport;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Events\BeforeImport;
use Maatwebsite\Excel\Events\BeforeSheet;
use Maatwebsite\Excel\Events\ImportFailed;

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
