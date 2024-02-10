<?php
namespace Collei\Exceller;

class Exceller
{
	protected $filename;

	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}

	public function doug(...$things)
	{
		echo sprintf('<fieldset><legend>There are %s things to show:</legend> <pre>%s</pre> </fieldset>', count($things), print_r(compact('things'), true));
	}

}
