<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class ContentInfo
{
	private $length		= 0;
	private $type		= '';
	private $charset	= '';
	private $encoding	= '';

	public function __construct(int $length, string $type, string $charset, string $encoding)
	{
		$this->length = $length;
		$this->type = $type;
		$this->charset = $charset;
		$this->encoding = $encoding;
	}

	public function getContentLength(): int
	{
		return $this->length;
	}

	public function getContentType(): string
	{
		return $this->type;
	}

	public function getCharset(): string
	{
		return $this->charset;
	}

	public function getTransferEncoding(): string
	{
		return $this->encoding;
	}
}