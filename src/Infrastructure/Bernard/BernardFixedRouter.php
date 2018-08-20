<?php

declare(strict_types=1);

namespace Building\Infrastructure\Bernard;

use Bernard\Consumer;
use Bernard\Envelope;
use Bernard\Exception\ReceiverNotFoundException;
use Bernard\Queue;
use Bernard\Receiver;
use Bernard\Router;
use Prooph\Common\Messaging\Message;
use Prooph\ServiceBus\Exception\InvalidArgumentException;
use Interop\Container\ContainerInterface;
use Prooph\ServiceBus\CommandBus;
use Prooph\ServiceBus\EventBus;
use Prooph\ServiceBus\Message\Bernard\BernardMessage;
use Prooph\ServiceBus\Message\Bernard\BernardRouter;
use Symfony\Component\EventDispatcher\EventDispatcher;

class BernardFixedRouter implements Router
{
    /**
     * @var CommandBus
     */
    private $commandBus;

    /**
     * @var EventBus
     */
    private $eventBus;

    public function __construct(CommandBus $commandBus, EventBus $eventBus)
    {
        $this->commandBus = $commandBus;
        $this->eventBus = $eventBus;
    }

    /**
     * Returns the right Receiver (callable) based on the Envelope.
     *
     * @param  Envelope $envelope
     * @throws InvalidArgumentException
     * @return array
     */
    public function map(Envelope $envelope)
    {
        $message = $envelope->getMessage();

        if (! $message instanceof BernardMessage) {
            throw new InvalidArgumentException(sprintf(
                "Routing the message %s failed due to wrong message type",
                $envelope->getName()
            ));
        }

        return [$this, "routeMessage"];
    }

    /**
     * @param BernardMessage $message
     */
    public function routeMessage(BernardMessage $message)
    {
        $proophMessage = $message->getProophMessage();

        if ($proophMessage->messageType() === Message::TYPE_COMMAND) {
            $this->commandBus->dispatch($proophMessage);
        } else {
            $this->eventBus->dispatch($proophMessage);
        }
    }

    /**
     * Returns the right Receiver based on the Envelope.
     *
     * @param Envelope $envelope
     *
     * @return Receiver
     *
     * @throws ReceiverNotFoundException
     */
    public function route(Envelope $envelope)
    {
        return $this->routeMessage($envelope->getMessage());
    }
}
