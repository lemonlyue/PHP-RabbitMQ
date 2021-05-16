<?php

$config = require '../config/config.php';
require_once $config['vendor'] . '/autoload.php';

use PhpAmqpLib\Connection\AMQPStreamConnection;

$config = $config['rabbitmq'];

$connection = new AMQPStreamConnection($config['localhost'], $config['port'], $config['username'], $config['password']);
$channel = $connection->channel();

$channel->exchange_declare('logs', 'fanout', false, false, false);

list($queue_name, ,) = $channel->queue_declare("", false, false, true, false);

$channel->queue_bind($queue_name, 'logs');

echo ' [*] Waiting for logs. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    echo ' [x] ', $msg->body, "\n";
};

$channel->basic_consume($queue_name, '', false, true, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}

$channel->close();
$connection->close();
