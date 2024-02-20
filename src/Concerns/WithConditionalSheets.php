<?php
namespace Collei\Exceller\Concerns;

trait WithConditionalSheets
{
	/**
	 * @var array
	 */
	protected $conditionallySelectedSheets = [];

	/**
	 * Adds sheets to be imported.
	 *
	 * @param string|array $sheets
	 * @return $this
	 */
	public function onlySheets($sheets)
	{
		$this->conditionallySelectedSheets = is_array($sheets) ? $sheets : func_get_args();
	}

	/**
	 * Implements the WithMultipleSheets interface
	 *
	 * @return array
	 */
	public function sheets()
	{
		return \array_filter($this->conditionalSheets(), function ($name) {
			return \in_array($name, $this->conditionallySelectedSheets, false);
		}, ARRAY_FILTER_USE_KEY);
	}

	/**
	 * Must be implemented by the class.
	 *
	 * @return array
	 */
	abstract public function conditionalSheets();
}
