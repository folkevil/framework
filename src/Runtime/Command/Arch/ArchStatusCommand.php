<?php

namespace Kraken\Runtime\Command\Arch;

use Kraken\Channel\ChannelBaseInterface;
use Kraken\Channel\Extra\Request;
use Kraken\Command\CommandInterface;
use Kraken\Promise\Promise;
use Kraken\Runtime\Command\Command;
use Kraken\Runtime\RuntimeCommand;

class ArchStatusCommand extends Command implements CommandInterface
{
    /**
     * ChannelBaseInterface
     */
    protected $channel;

    /**
     *
     */
    protected function construct()
    {
        $this->channel = $this->runtime->getCore()->make('Kraken\Runtime\Channel\ChannelInterface');
    }

    /**
     *
     */
    protected function destruct()
    {
        unset($this->channel);
    }

    /**
     * @param mixed[] $params
     * @return mixed
     */
    protected function command($params = [])
    {
        $runtime = $this->runtime;
        $channel = $this->channel;
        $promise = Promise::doResolve();

        return $promise
            ->then(
                function() use($runtime) {
                    return $runtime->manager()->getRuntimes();
                }
            )
            ->then(
                function($children) use($channel) {
                    $promises = [];

                    foreach ($children as $childAlias)
                    {
                        $req = $this->createRequest(
                            $channel,
                            $childAlias,
                            new RuntimeCommand('arch:status')
                        );

                        $promises[] = $req->call();
                    }

                    return Promise::all($promises);
                }
            )
            ->then(
                function($childrenData) use($runtime) {
                    return [
                        'parent'   => $runtime->parent(),
                        'alias'    => $runtime->alias(),
                        'name'     => $runtime->name(),
                        'state'    => $runtime->state(),
                        'children' => $childrenData
                    ];
                }
            )
        ;
    }

    /**
     * Create Request.
     *
     * @param ChannelBaseInterface $channel
     * @param string $receiver
     * @param string $command
     * @return Request
     */
    protected function createRequest(ChannelBaseInterface $channel, $receiver, $command)
    {
        return new Request($channel, $receiver, $command);
    }
}
