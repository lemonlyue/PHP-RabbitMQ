<?php

$config = require '../config/config.php';
require_once $config['vendor'] . '/autoload.php';

$config = $config['rabbitmq'];

use PhpAmqpLib\Connection\AMQPStreamConnection;

$connection = new AMQPStreamConnection($config['localhost'], $config['port'], $config['username'], $config['password']);
$channel = $connection->channel();

$channel->queue_declare('task_queue', false, false, false, false);

echo ' [*] Waiting for messages. To exit press CTRL+C', "\n";

$callback = function ($msg) {
    echo " [x] Received ", $msg->body, "\n";
    sleep(substr_count($msg->body, '.'));
    echo '[x] Done' . PHP_EOL;
    $msg->delivery_info['channel']->basic_ack($msg->delivery_info['delivery_tag']);
};
// RabbitMQ提供了一种qos（服务质量保证）功能, 即在非自动确认消息的前提下, 如果一定数目的消息（通过基于consume或者channel设置Qos的值）未被确认前, 不进行消费新的消息
// prefetchSize：0
// prefetchCount：会告诉RabbitMQ不要同时给一个消费者推送多于N个消息，即一旦有N个消息还没有ack，则该consumer将block掉，直到有消息ack
// global：true\false 是否将上面设置应用于channel，简单点说，就是上面限制是channel级别的还是consumer级别
$channel->basic_qos(null, 1, null);
$channel->basic_consume('task_queue', '', false, false, false, false, $callback);

while (count($channel->callbacks)) {
    $channel->wait();
}