<?php
namespace Collei\Exceller\Events\Traits;

/**
 * Right before start importing.
 */
trait SheetEventTrait
{
	/**
	 * @var mixed
	 */
	public $sheet;

	/**
	 * Returns the sheet reference.
	 *
	 * @return void
	 */
	protected function setSheet($sheet)
	{
		$this->sheet = $sheet;
	}

	/**
	 * Returns the sheet reference.
	 *
	 * @return mixed
	 */
	public function getSheet()
	{
		return $this->sheet;
	}

	/**
	 * Check if the event is for the given reference.
	 *
	 * @param mixed $another
	 * @return bool
	 */
	public function isSheet($another)
	{
		return ($another) === $this->sheet;
	}
}
