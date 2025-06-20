<?php

namespace Clue\React\Ssdp;

use Clue\React\Multicast\Factory as MulticastFactory;
use React\EventLoop\Loop;
use React\EventLoop\LoopInterface;
use React\Promise\Deferred;
use RuntimeException;

class Client
{
    const ADDRESS = '239.255.255.250:1900';

    /** @var LoopInterface */
    private $loop;

    /** @var MulticastFactory */
    private $multicast;

    public function __construct($loop = null, $multicast = null)
    {
        if ($loop !== null && !$loop instanceof LoopInterface) {
            throw new \InvalidArgumentException('Argument #1 ($loop) expected null|React\EventLoop\LoopInterface');
        }
        if ($multicast !== null && !$multicast instanceof MulticastFactory) {
            throw new \InvalidArgumentException('Argument #2 ($multicast) expected null|Clue\React\Multicast\Factory');
        }

        $this->loop = $loop ?: Loop::get();
        $this->multicast = $multicast ?: new MulticastFactory($this->loop);
    }

    public function search($searchTarget = 'ssdp:all', $mx = 2)
    {
        $data  = "M-SEARCH * HTTP/1.1\r\n";
        $data .= "HOST: " . self::ADDRESS . "\r\n";
        $data .= "MAN: \"ssdp:discover\"\r\n";
        $data .= "MX: $mx\r\n";
        $data .= "ST: $searchTarget\r\n";
        $data .= "\r\n";

        $socket = $this->multicast->createSender();
        // TODO: The TTL for the IP packet SHOULD default to 2 and SHOULD be configurable.

        $messages = array();

        $timer = $this->loop->addTimer($mx, function() use ($socket, &$deferred, &$messages) {
            // resolve promise with all collected messages
            $deferred->resolve($messages);
            $socket->close();
        });

            $loop = $this->loop;
            $deferred = new Deferred(function () use ($socket, &$timer, $loop) {
                // canceling resulting promise cancels timer and closes socket
                $loop->cancelTimer($timer);
                $socket->close();
                throw new RuntimeException('Cancelled');
            });

                $that = $this;
                $socket->on('message', function ($data, $remote) use ($that, &$messages) {
                    $message = $that->parseMessage($data, $remote);

                    $messages[] = $message;
                });

                    $socket->send($data, self::ADDRESS);

                    return $deferred->promise();
    }

    /** @internal */
    public function parseMessage($message, $remote)
    {
        // TODO: parse message into message model
        $message = array(
            'data' => $message,
            '_sender' => $remote
        );
        return $message;
    }
}