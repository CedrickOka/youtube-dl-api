<?php
namespace App\Transport;

use App\Transport\Exception\SemaphoreException;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Exception\InvalidArgumentException;
use Symfony\Component\Messenger\Exception\MessageDecodingFailedException;
use Symfony\Component\Messenger\Exception\TransportException;
use Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface;
use Symfony\Component\Messenger\Transport\Serialization\PhpSerializer;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 * Symfony Messenger receiver to get messages from Semaphore.
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class SemaphoreReceiver implements ReceiverInterface
{
	/**
	 * @var Connection $connection
	 */
	private $connection;
	
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
	 * @see \Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface::get()
	 */
	public function get() :iterable
	{
		try {
			$semaphoreEnvelope = $this->connection->receive();
		} catch (SemaphoreException $exception) {
			throw new TransportException($exception->getMessage(), 0, $exception);
		}
		
		if (null === $semaphoreEnvelope) {
			return;
		}
		
		try {
			$envelope = $this->serializer->decode(['body' => $semaphoreEnvelope->getBody()]);
		} catch (MessageDecodingFailedException $exception) {
			// TODO: [Researh] Implements nack strategy for semaphore
			
			throw $exception;
		}
		
		yield $envelope->with(new SemaphoreStamp($semaphoreEnvelope->getType()));
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface::ack()
	 */
	public function ack(Envelope $envelope) :void
	{
// 		throw new InvalidArgumentException('You cannot call ack() on the Messenger SemaphoreReceiver.');
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Messenger\Transport\Receiver\ReceiverInterface::reject()
	 */
	public function reject(Envelope $envelope) :void
	{
// 		throw new InvalidArgumentException('You cannot call reject() on the Messenger SemaphoreReceiver.');
	}
}
