<?php
namespace Collei\Exceller\Events;

/**
 * Right before start importing.
 */
class AfterSheet extends Event
{
	use Traits\SheetEventTrait;

	/**
	 * Instantiate.
	 *
	 * @return void
	 */
	public function __construct($import, $sheet)
	{
		$this->setSheet($sheet);

		parent::__construct($import);
	}
}
