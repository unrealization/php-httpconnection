<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection;

class HTTPRequest
{
	private $uri			= '';
	private $getParameters	= array();
	private $postParameters	= array();
	private $cookies		= array();
	private $files			= array();
	private $authUser		= '';
	private $authPassword	= '';
	private $extraHeaders	= array();

	public function __construct(string $uri)
	{
		$this->uri = $uri;
	}

	public function getUri(): string
	{
		return $this->uri;
	}

	public function addGetParameter(string $name, string $value): HTTPRequest
	{
		$this->getParameters[$name] = $value;
		return $this;
	}

	public function getGetParameters(): array
	{
		return $this->getParameters;
	}

	public function addPostParameter(string $name, string $value): HTTPRequest
	{
		$this->postParameters[$name] = $value;
		return $this;
	}

	public function getPostParameters(): array
	{
		return $this->postParameters;
	}

	public function addCookie(string $name, string $value): HTTPRequest
	{
		$this->cookies[$name] = $value;
		return $this;
	}

	public function getCookies(): array
	{
		return $this->cookies;
	}

	public function addFile(string $name, string $fileName, string $mimeType = ''): HTTPRequest
	{
		$this->files[$name] = array(
			'fileName'	=> $fileName,
			'mimeType'	=> $mimeType
		);
		return $this;
	}

	public function getFiles(): array
	{
		return $this->files;
	}

	public function authenticate(string $user, string $password): HTTPRequest
	{
		$this->authUser = $user;
		$this->authPassword = $password;
		return $this;
	}

	public function getAuthenticationUser(): string
	{
		return $this->authUser;
	}

	public function getAuthenticationPassword(): string
	{
		return $this->authPassword;
	}

	public function addExtraHeader(string $name, string $value): HTTPRequest
	{
		$this->extraHeaders[$name] = $value;
		return $this;
	}

	public function getExtraHeaders(): array
	{
		return $this->extraHeaders;
	}
}