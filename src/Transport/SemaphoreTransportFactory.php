<?php
namespace App\Transport;

use Symfony\Component\Messenger\Transport\TransportFactoryInterface;
use Symfony\Component\Messenger\Transport\TransportInterface;
use Symfony\Component\Messenger\Transport\Serialization\SerializerInterface;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class SemaphoreTransportFactory implements TransportFactoryInterface
{
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Messenger\Transport\TransportFactoryInterface::createTransport()
	 */
	public function createTransport(string $dsn, array $options, SerializerInterface $serializer) :TransportInterface
	{
		return new SemaphoreTransport(Connection::fromDsn($dsn, $options), $serializer);
	}
	
	/**
	 * {@inheritDoc}
	 * @see \Symfony\Component\Messenger\Transport\TransportFactoryInterface::supports()
	 */
	public function supports($dsn, array $options) :bool
	{
		return 0 === strpos($dsn, 'semaphore://');
	}
}
