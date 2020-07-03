<?php
declare(strict_types=1);

namespace unrealization\PHPClassCollection\HTTPConnection;

use unrealization\PHPClassCollection\HTTPConnection\HTTPResponse\Header;

class HTTPResponse
{
	private $rawResponse	= '';
	private $header			= null;
	private $body			= '';

	public function __construct(string $response, bool $autoUnchunk = true)
	{
		$this->rawResponse = $response;
		$matches = array();

		if (preg_match('@^.*((?|\r)?\n)@Um', $response, $matches))
		{
			$lineBreak = $matches[1];
		}
		else
		{
			throw new \Exception('Unable to find line break.');
		}

		if (preg_match('@^((.+)(?|'.$lineBreak.'){2})(.+)?$@sU', $response, $matches))
		{
			$header = $matches[2].$lineBreak;

			try
			{
				$this->header = new Header($header);
			}
			catch (\Exception $e)
			{
				throw new \Exception('Unable to decode the header.', 0, $e);
			}

			if (!empty($matches[3]))
			{
				$body = $matches[3];

				if (($this->header->getContentInfo()->getTransferEncoding() === 'chunked') && ($autoUnchunk === true))
				{
					$chunkedBody = $body;
					$body = '';

					/**
					 * TODO: Check !==
					 */
					while ((!empty($chunkedBody)) && (preg_match('@^([\dA-Fa-f]+)'.$lineBreak.'@', $chunkedBody, $matches) != false))
					{
						$chunkLength = hexdec($matches[1]);
						$chunkedBody = preg_replace('@^'.$matches[1].$lineBreak.'@', '', $chunkedBody, 1);
						$body .= substr($chunkedBody, 0, $chunkLength);
						$chunkedBody = substr($chunkedBody, $chunkLength + strlen($lineBreak));
					}
				}

				$this->body = $body;
			}
		}
		else
		{
			throw new \Exception('Unable to split header and body.');
		}
	}

	public function getRawResponse(): string
	{
		return $this->rawResponse;
	}

	public function getHeader(): Header
	{
		return $this->header;
	}

	public function getBody(): string
	{
		return $this->body;
	}
}