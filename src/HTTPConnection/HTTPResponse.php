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
		$encoding = mb_detect_encoding($response, mb_list_encodings());
		mb_regex_encoding($encoding);
		mb_regex_set_options('d');

		$longBreak = null;
		mb_ereg_search_init($response);

		if (mb_ereg_search('\r\n'))
		{
			$longBreak = mb_ereg_search_getpos();
		}

		$shortBreak = null;
		mb_ereg_search_init($response);

		if (mb_ereg_search("\n"))
		{
			$shortBreak = mb_ereg_search_getpos();
		}

		if ((!is_null($longBreak)) && (!is_null($shortBreak)))
		{
			if ($longBreak <= $shortBreak)
			{
				$lineBreak = "\r\n";
			}
			else
			{
				$lineBreak = "\n";
			}
		}
		elseif (!is_null($longBreak))
		{
			$lineBreak = "\r\n";
		}
		elseif (!is_null($shortBreak))
		{
			$lineBreak = "\n";
		}
		else
		{
			throw new \Exception('Unable to find line break.');
		}

		$matches = array();
		mb_regex_set_options('md');
		/*mb_ereg_search_init($response);

		if (mb_ereg_search('('.$lineBreak.'){2}'))
		{
			error_log((string)mb_ereg_search_getpos());
		}*/

		if (mb_ereg('^((.+)('.$lineBreak.'){2})(.+)?$', $response, $matches))
		{
			$header = $matches[2].$lineBreak;
			$this->header = new Header($header);

			if (!empty($matches[4]))
			{
				$body = $matches[4];

				if (($this->header->getContentInfo()->getTransferEncoding() === 'chunked') && ($autoUnchunk === true))
				{
					$chunkedBody = $body;
					$body = '';

					/**
					 * TODO: Check !==
					 */
					while ((!empty($chunkedBody)) && (mb_ereg('^([\dA-Fa-f]+)'.$lineBreak, $chunkedBody, $matches)))
					{
						$chunkLength = hexdec($matches[1]);
						$chunkedBody = mb_ereg_replace('^'.$matches[1].$lineBreak, '', $chunkedBody);
						$body .= mb_substr($chunkedBody, 0, $chunkLength);
						$chunkedBody = mb_substr($chunkedBody, $chunkLength + mb_strlen($lineBreak));
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