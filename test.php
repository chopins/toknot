<?php

// Create a new socket
$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

// An example list of IP addresses owned by the computer
$sourceips['kevin']    = '127.0.0.1';
$sourceips['madcoder'] = '127.0.0.2';

// Bind the source address
socket_bind($sock, '0.0.0.0', 32902);

// Connect to destination address
socket_connect($sock, '127.0.0.1', 80);

// Write
$request = 'GET / HTTP/1.1' . "\r\n" .
           'Host: example.com' . "\r\n\r\n";
socket_write($sock, $request);
sleep(10);
// Close
socket_close($sock);