<?php
namespace Collei\Exceller\Events;

use Throwable;

/**
 * Right before start importing.
 */
class ImportFailed extends Event
{
	/**
	 * @var \Throwable
	 */
	protected $e;

	/**
	 * Instantiate.
	 *
	 * @return void
	 */
	public function __construct($import, Throwable $e)
	{
		$this->e = $e;

		parent::__construct($import);
	}

	/**
	 * Retrieves the error.
	 *
	 * @return \Throwable
	 */
	public function getError()
	{
		return $this->e;
	}
}
