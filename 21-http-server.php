<?php
// https://github.com/reactphp/event-loop/blob/v1.1.1/examples/21-http-server.php

require __DIR__ . '/vendor/autoload.php';

$loop = React\EventLoop\Factory::create();

// start TCP/IP server on localhost:8080
// for illustration purposes only, should use react/socket instead
$server = stream_socket_server('tcp://127.0.0.1:8080');
if (!$server) {
    exit(1);
}
stream_set_blocking($server, false);

// wait for incoming connections on server socket
$loop->addReadStream($server, function ($server) use ($loop) {
    $conn = stream_socket_accept($server);
    $date = new DateTime();
    $now = $date->format('Y-m-d H:i:s');
    $md5 = md5($now);
    $content = ">begin\nnow ".$now."\nmd5 ".$md5."\nend<";
    $data = "HTTP/1.1 200 OK\r\nContent-Length: ".strlen($content)."\r\n\r\n".$content."\n";
    $loop->addWriteStream($conn, function ($conn) use (&$data, $loop) {
        $written = fwrite($conn, $data);
        if ($written === strlen($data)) {
            fclose($conn);
            $loop->removeWriteStream($conn);
        } else {
            $data = substr($data, $written);
        }
    });
});

$loop->addPeriodicTimer(5, function () {
    $memory = memory_get_usage() / 1024;
    $formatted = number_format($memory, 3).'K';
    echo "Current memory usage: {$formatted}\n";
});

$loop->run();
