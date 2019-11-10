<?php
namespace App\Transport;

use Symfony\Component\Messenger\Stamp\NonSendableStampInterface;

/**
 * 
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class SemaphoreStamp implements NonSendableStampInterface
{
	/**
	 * @var int $type
	 */
	private $type;
	
	public function __construct(int $type)
	{
		$this->type = $type;
	}
	
	public function getType() :int
	{
		return $this->type;
	}
}
