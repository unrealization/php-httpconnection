<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class LocationInfo
{
	private $protocol	= '';
	private $host		= '';
	private $port		= null;
	private $ssl		= false;
	private $uri		= '';
	private $parameters	= '';

	public function __construct(string $protocol, string $host, ?int $port, bool $ssl, string $uri, string $parameters)
	{
		$this->protocol = $protocol;
		$this->host = $host;
		$this->port = $port;
		$this->ssl = $ssl;
		$this->uri = $uri;
		$this->parameters = $parameters;
	}

	public function getProtocol(): string
	{
		return $this->protocol;
	}

	public function getHost(): string
	{
		return $this->host;
	}

	public function getPort(): ?int
	{
		return $this->port;
	}

	public function getSsl(): bool
	{
		return $this->ssl;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function getParameters(): string
	{
		return $this->parameters;
	}
}