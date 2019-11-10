<?php
namespace App\Transport;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class SemaphoreEnvelope
{
	/**
	 * @var int $type
	 */
	private $type;
	
	/**
	 * @var string $body
	 */
	private $body;
	
	public function __construct(int $type, string $body)
	{
		$this->type = $type;
		$this->body = $body;
	}
	
	public function getType() :int
	{
		return $this->type;
	}
	
	public function getBody() :string {
		return $this->body;
	}
}
