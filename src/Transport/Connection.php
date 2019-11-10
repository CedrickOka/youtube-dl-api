<?php
namespace App\Transport;

use App\Transport\Exception\SemaphoreException;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class Connection
{
	/**
	 * @var array $queueConfiguration
	 */
	private $queueConfiguration;
	
	/**
	 * @var resource $queue
	 */
	private $queue;
	
	public function __construct(array $queueConfiguration)
	{
		$this->queueConfiguration = $queueConfiguration;
	}
	
	/**
	 * Create Connection from Semaphore DSN
	 * 
	 * @param string $dsn
	 * @param array $options
	 * @throws InvalidArgumentException
	 * @return self
	 */
	public static function fromDsn(string $dsn, array $options = []): self
	{
		if (false === $parsedUrl = parse_url($dsn)) {
			throw new InvalidArgumentException(sprintf('The given Semaphore DSN "%s" is invalid.', $dsn));
		}
		
		$queueOptions = array_replace_recursive([
				'path' => $parsedUrl['path'] ?? __FILE__,
				'project' => 'M'
		], $options);
		
		if (true === isset($parsedUrl['query'])) {
			$parsedQuery = [];
			parse_str($parsedUrl['query'], $parsedQuery);
			
			$queueOptions = array_replace_recursive($queueOptions, $parsedQuery);
		}
		
		return new self($queueOptions);
	}
	
	public function getQueueConfiguration() :array
	{
		return $this->queueConfiguration;
	}
	
	/**
	 * Send message on semaphore queue
	 * 
	 * @param string $body
	 * @param SemaphoreStamp $semaphoreStamp
	 * @throws SemaphoreException
	 */
	public function send(string $body, SemaphoreStamp $semaphoreStamp = null): void
	{
		$messageType = null === $semaphoreStamp ? $this->queueConfiguration['message_type'] ?? 1 : $semaphoreStamp->getType();
		
		if (false === msg_send($this->queue(), $messageType, $body, false, false, $errorCode)) {
			throw new SemaphoreException(sprintf('Semaphore sending message failed with error code : "%s".', $errorCode));
		}
	}
	
	/**
	 * Waits and gets a message from the configured semaphore queue.
	 *
	 * @throws SemaphoreException
	 */
	public function receive() :?SemaphoreEnvelope
	{
		if (true === msg_receive($this->queue(), $this->queueConfiguration['message_type'] ?? 0, $messageType, 131072, $message, false, null, $errorCode)) {
			return new SemaphoreEnvelope($messageType, $message);
		}
		
		if (MSG_ENOMSG !== $errorCode) {
			throw new SemaphoreException(sprintf('Semaphore receiving message failed with error code : "%s".', $errorCode));
		}
		
		return null;
	}
	
	/**
	 * @return resource
	 */
	private function queue() 
	{
		if (null === $this->queue) {
			$key = ftok($this->queueConfiguration['path'], 'Y');
			$this->queue = msg_get_queue($key);
		}
		
		return $this->queue;
	}
}
