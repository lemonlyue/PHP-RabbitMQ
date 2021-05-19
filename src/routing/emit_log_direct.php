<?php

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

$config = require '../config/config.php';
require_once $config['vendor'] . '/autoload.php';

$config = $config['rabbitmq'];

$connection = new AMQPStreamConnection($config['localhost'], $config['port'], $config['username'], $config['password']);
$channel = $connection->channel();

$channel->exchange_declare('direct_logs', 'direct', false, false, false);

$severity = isset($argv[1]) && !empty($argv[1]) ? $argv[1] : 'info';

$data = implode(' ', array_slice($argv, 2));
if (empty($data)) $data = "Hello World!";

$msg = new AMQPMessage($data);

$channel->basic_publish($msg, 'direct_logs', $severity);

echo " [x] Sent ", $severity, ':', $data, " \n";

$channel->close();
$connection->close();
