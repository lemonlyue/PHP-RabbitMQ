<?php

$config = require '../config/config.php';
require_once $config['vendor'] . '/autoload.php';

$config = $config['rabbitmq'];

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$connection = new AMQPStreamConnection($config['localhost'], $config['port'], $config['username'], $config['password']);
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);
$data = implode(' ', array_slice($argv, 1));
$msg = new AMQPMessage($data, [
    'delivery_mode' => AMQPMessage::DELIVERY_MODE_PERSISTENT
]);
foreach (range(1, 10) as $item) {
    $channel->basic_publish($msg, '', 'task_queue');
}

echo '[x] Sent ' . $data;

$channel->close();
$connection->close();
