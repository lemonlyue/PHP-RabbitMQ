<?php


use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class FibRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;

    public function __construct() {
        $config = require '../config/config.php';
        require_once $config['vendor'] . '/autoload.php';
        $config = $config['rabbitmq'];
        $this->connection = new AMQPStreamConnection($config['localhost'], $config['port'], $config['username'], $config['password']);
        $this->channel = $this->connection->channel();
        list($this->callback_queue, ,) = $this->channel->queue_declare(
            "", false, false, true, false);
        $this->channel->basic_consume(
            $this->callback_queue, '', false, false, false, false,
            array($this, 'on_response'));
    }

    public function on_response($rep) {
        if($rep->get('correlation_id') === $this->corr_id) {
            $this->response = $rep->body;
        }
    }

    public function call($n) {
        $this->response = null;
        $this->corr_id = uniqid('', true);

        $msg = new AMQPMessage(
            (string) $n,
            array('correlation_id' => $this->corr_id,
                'reply_to' => $this->callback_queue)
        );
        $this->channel->basic_publish($msg, '', 'rpc_queue');
        while(!$this->response) {
            $this->channel->wait();
        }
        return (int)$this->response;
    }
}

$fibonacci_rpc = new FibRpcClient();
$response = $fibonacci_rpc->call($argv[1]);
echo " [.] Got ", $response, "\n";