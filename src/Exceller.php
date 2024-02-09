<?php
namespace Collei\Exceller;

class Exceller
{
	protected $filename;

	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}
	
}
