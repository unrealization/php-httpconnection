<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class HTTPStatus
{
	private $version		= '';
	private $responseCode	= null;
	private $status			= '';

	public function __construct(string $version, int $responseCode, string $status)
	{
		$this->version = $version;
		$this->responseCode = $responseCode;
		$this->status = $status;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	public function getResponseCode(): int
	{
		return $this->responseCode;
	}

	public function getStatus(): string
	{
		return $this->status;
	}
}