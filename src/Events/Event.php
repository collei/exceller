<?php
namespace Collei\Exceller\Events;

/**
 * Allows skipping missing sheets.
 */
abstract class Event
{
	/**
	 * @var object
	 */
	protected $import;

	/**
	 * Instantiate.
	 *
	 * @return void
	 */
	public function __construct($import)
	{
		$this->import = $import;
	}

	/**
	 * The assigned handling class.
	 *
	 * @return object
	 */
	public function getImport()
	{
		return $this->import;
	}

	/**
	 * Check if the assigned handling class relates to.
	 *
	 * @param  string  $concern
	 * @return bool
	 */
	public function appliesToConcern(string $concern)
	{
		return $this->getImport() instanceof $concern;
	}
}
