<?php

namespace X\LaravelSwoolePool\Capsule;

use X\LaravelSwoolePool\DatabaseManager;
use Illuminate\Database\Capsule\Manager as BaseManager;
use Illuminate\Database\Connectors\ConnectionFactory;
use Swoole\Event;

class Manager extends BaseManager
{
    public function getAvailableConnection()
    {
        foreach($this->getDatabaseManager()->getConnections() as $connection)
        {
            if(!$connection->isActive())
            {
                $con = $this->manager->connection($connection->getName());

                return $con;
            }
        }
    }

    protected function setupManager()
    {
        $factory = new ConnectionFactory($this->container);

        $this->manager = new DatabaseManager($this->container, $factory);
    }

    public function addConnections(string $name, int $amount, array $config)
    {
        for($i = 0; $i < $amount; $i++)
        {
            $config['name'] = $name . '-' . ($i + 1);

            $this->addConnection($config, $name . '-' . ($i + 1));

            if($i == 0)
            {
                $this->getDatabaseManager()->setDefaultConnection($name . '-' . ($i + 1));
            }

            // todo - refactor
            go(function() use($name, $i) {
               $this->getConnection($name . '-' . ($i + 1)) ;
            });
        }

        Event::wait();
    }
}
