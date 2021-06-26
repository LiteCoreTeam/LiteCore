<?php

/*
 * RakLib network library
 *
 *
 * This project is not affiliated with Jenkins Software LLC nor RakNet.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 */

declare(strict_types=1);

namespace raklib\server;

use function socket_bind;
use function socket_close;
use function socket_create;
use function socket_last_error;
use function socket_recvfrom;
use function socket_sendto;
use function socket_set_nonblock;
use function socket_set_option;
use function socket_strerror;
use function strlen;
use function trim;
use const AF_INET;
use const SO_RCVBUF;
use const SO_SNDBUF;
use const SOCK_DGRAM;
use const SOCKET_EADDRINUSE;
use const SOL_SOCKET;
use const SOL_UDP;

class UDPServerSocket{
	/**
	 * @var resource
	 * @phpstan-var PhpSocket
	 */
	protected $socket;

	public function __construct(int $port = 19132, string $interface = "0.0.0.0"){
		$socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if($socket === false){
			throw new \RuntimeException("Failed to create socket: " . trim(socket_strerror(socket_last_error())));
		}
		$this->socket = $socket;

		if(@socket_bind($this->socket, $interface, $port) === true){
			$this->setSendBuffer(1024 * 1024 * 8)->setRecvBuffer(1024 * 1024 * 8);
		}else{
			$error = socket_last_error($this->socket);
			if($error === SOCKET_EADDRINUSE){ //platform error messages aren't consistent
				throw new \RuntimeException("Failed to bind socket: Something else is already running on $interface:$port");
			}
			throw new \RuntimeException("Failed to bind to " . $port . ": " . trim(socket_strerror(socket_last_error($this->socket))));
		}
		socket_set_nonblock($this->socket);
	}

	/**
	 * @return resource
	 * @phpstan-return PhpSocket
	 */
	public function getSocket(){
		return $this->socket;
	}

	public function close() : void{
		socket_close($this->socket);
	}

	public function getLastError() : int{
		return socket_last_error($this->socket);
	}

	/**
	 * @param string $buffer reference parameter
	 * @param string $source reference parameter
	 * @param int    $port reference parameter
	 *
	 * @return int|bool
	 */
	public function readPacket(?string &$buffer, ?string &$source, ?int &$port){
		return @socket_recvfrom($this->socket, $buffer, 65535, 0, $source, $port);
	}

	/**
	 * @return int|bool
	 */
	public function writePacket(string $buffer, string $dest, int $port){
		return socket_sendto($this->socket, $buffer, strlen($buffer), 0, $dest, $port);
	}

	/**
	 * @return $this
	 */
	public function setSendBuffer(int $size){
		@socket_set_option($this->socket, SOL_SOCKET, SO_SNDBUF, $size);

		return $this;
	}

	/**
	 * @return $this
	 */
	public function setRecvBuffer(int $size){
		@socket_set_option($this->socket, SOL_SOCKET, SO_RCVBUF, $size);

		return $this;
	}

}