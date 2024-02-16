<?php
namespace Collei\Exceller\Concerns;

/**
 * Allows capture data in blocks.
 */
interface ToArrayBlocks
{
	/**
	 * Allows capture data in blocks.
	 *
	 * @return void
	 */
	public function block(array $array);

	/**
	 * Determine the block size.
	 *
	 * @return int
	 */
	public function size();
}
