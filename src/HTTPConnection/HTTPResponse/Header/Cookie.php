<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class Cookie
{
	private $name = '';
	private $value = '';
	private $parameters = array();

	public function __construct(string $name, string $value, array $parameters)
	{
		$this->name = $name;
		$this->value = $value;
		$this->parameters = $parameters;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getValue(): string
	{
		return $this->value;
	}

	public function getParameters(): array
	{
		return $this->parameters;
	}
}