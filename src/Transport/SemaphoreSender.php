<?php
namespace App\Transport;

use App\Transport\Exception\SemaphoreException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Sender\SenderInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Symfony Messenger sender to send messages to Semaphore.
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class SemaphoreSender implements SenderInterface
{
	/**
	 * @var SerializerInterface $serializer
	 */
	private $serializer;
	
	public function __construct(Connection $connection, SerializerInterface $serializer = null)
	{
		$this->connection = $connection;
		$this->serializer = $serializer ?? new PhpSerializer();
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Messenger\Transport\Sender\SenderInterface::send()
	 */
	public function send(Envelope $envelope) :Envelope
	{
		$encodedMessage = $this->serializer->encode($envelope);
		
		try {
			$this->connection->send($encodedMessage['body'], $envelope->last(SemaphoreStamp::class));
		} catch (SemaphoreException $exception) {
			throw new TransportException($exception->getMessage(), 0, $exception);
		}
		
		return $envelope;
	}
}
