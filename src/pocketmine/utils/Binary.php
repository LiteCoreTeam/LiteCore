<?php

/*
 *
 *  ____            _        _   __  __ _                  __  __ ____
 * |  _ \ ___   ___| | _____| |_|  \/  (_)_ __   ___      |  \/  |  _ \
 * | |_) / _ \ / __| |/ / _ \ __| |\/| | | '_ \ / _ \_____| |\/| | |_) |
 * |  __/ (_) | (__|   <  __/ |_| |  | | | | | |  __/_____| |  | |  __/
 * |_|   \___/ \___|_|\_\___|\__|_|  |_|_|_| |_|\___|     |_|  |_|_|
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author PocketMine Team
 * @link http://www.pocketmine.net/
 *
 *
*/

/**
 * Methods for working with binary strings
 */
namespace pocketmine\utils;

use InvalidArgumentException;
use function chr;
use function ord;
use function pack;
use function preg_replace;
use function round;
use function sprintf;
use function substr;
use function unpack;
use const PHP_INT_MAX;

class Binary{
	public static function signByte(int $value) : int{
		return $value << 56 >> 56;
	}

	public static function unsignByte(int $value) : int{
		return $value & 0xff;
	}

	public static function signShort(int $value) : int{
		return $value << 48 >> 48;
	}

	public static function unsignShort(int $value) : int{
		return $value & 0xffff;
	}

	public static function signInt(int $value) : int{
		return $value << 32 >> 32;
	}

	public static function unsignInt(int $value) : int{
		return $value & 0xffffffff;
	}

	public static function flipShortEndianness(int $value) : int{
		return self::readLShort(self::writeShort($value));
	}

	public static function flipIntEndianness(int $value) : int{
		return self::readLInt(self::writeInt($value));
	}

	public static function flipLongEndianness(int $value) : int{
		return self::readLLong(self::writeLong($value));
	}

	/**
	 * @return mixed[]
	 */
	private static function safeUnpack(string $formatCode, string $bytes) : array{
		//unpack SUCKS SO BADLY. We really need an extension to replace this garbage :(
		$result = unpack($formatCode, $bytes);
		if($result === false){
			//assume the formatting code is valid, since we provided it
			throw new BinaryDataException("Invalid input data (not enough?)");
		}
		return $result;
	}

	/**
	 * Reads a byte boolean
	 *
	 * @param string $b
	 *
	 * @return bool
	 */
	public static function readBool(string $b) : bool{
		return $b !== "\x00";
	}

	/**
	 * Writes a byte boolean
	 *
	 * @param bool $b
	 *
	 * @return string
	 */
	public static function writeBool(bool $b) : string{
		return $b ? "\x01" : "\x00";
	}

	/**
	 * Reads an unsigned byte (0 - 255)
	 *
	 * @param string $c
	 *
	 * @return int
	 */
	public static function readByte(string $c){
		return ord($c[0]);
	}

	/**
	 * Reads a signed byte (-128 - 127)
	 * 
	 * @param string $c
	 *
	 * @return int
	 */
	public static function readSignedByte(string $c) : int{
		return self::signByte(ord($c[0]));
	}

	/**
	 * Writes an unsigned/signed byte
	 *
	 * @param int $c
	 *
	 * @return string
	 */
	public static function writeByte(int $c) : string{
		return chr($c);
	}

	/**
	 * Reads a 16-bit unsigned big-endian number
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	public static function readShort($str){
		return self::safeUnpack("n", $str)[1];
	}

	/**
	 * Reads a 16-bit signed big-endian number
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	public static function readSignedShort($str){
        return self::signShort(self::safeUnpack("n", $str)[1]);
	}

	/**
	 * Writes a 16-bit signed/unsigned big-endian number
	 *
	 * @param int $value
	 *
	 * @return string
	 */
	public static function writeShort($value){
		return pack("n", $value);
	}

	/**
	 * Reads a 16-bit unsigned little-endian number
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	public static function readLShort($str){
		return self::safeUnpack("v", $str)[1];
	}

	/**
	 * Reads a 16-bit signed little-endian number
	 *
	 * @param string $str
	 *
	 * @return int
	 */
	public static function readSignedLShort($str){
		return self::signShort(self::safeUnpack("v", $str)[1]);
	}

	/**
	 * Writes a 16-bit signed/unsigned little-endian number
	 *
	 * @param int $value
	 *
	 * @return string
	 */
	public static function writeLShort($value){
		return pack("v", $value);
	}

	/**
	 * Reads a 3-byte big-endian number
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readTriad(string $str) : int{
		return self::safeUnpack("N", "\x00" . $str)[1];
	}

	/**
	 * Writes a 3-byte big-endian number
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeTriad(int $value) : string{
		return substr(pack("N", $value), 1);
	}

	/**
	 * Reads a 3-byte little-endian number
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readLTriad(string $str) : int{
		return self::safeUnpack("V", $str . "\x00")[1];
	}

	/**
	 * Writes a 3-byte little-endian number
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeLTriad(int $value) : string{
		return substr(pack("V", $value), 0, -1);
	}

	/**
	 * Reads a 4-byte signed integer
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readInt(string $str) : int{
		return self::signInt(self::safeUnpack("N", $str)[1]);
	}

	/**
	 * Writes a 4-byte integer
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeInt(int $value) : string{
		return pack("N", $value);
	}

	/**
	 * Reads a 4-byte signed little-endian integer
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readLInt($str){
		return self::signInt(self::safeUnpack("V", $str)[1]);
	}

	/**
	 * Writes a 4-byte signed little-endian integer
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeLInt($value){
		return pack("V", $value);
	}

	/**
	 * Reads a 4-byte floating-point number
	 *
	 * @param string $str
	 * 
	 * @return float
	 */
	public static function readFloat($str){
		return self::safeUnpack("G", $str)[1];
	}

	/**
	 * Reads a 4-byte floating-point number, rounded to the specified number of decimal places.
	 *
	 * @param string $str
	 * @param int $accuracy
	 *
	 * @return float
	 */
	public static function readRoundedFloat(string $str, int $accuracy) : float{
		return round(self::readFloat($str), $accuracy);
	}

	/**
	 * Writes a 4-byte floating-point number.
	 *
	 * @param float $value
	 * 
	 * @return string
	 */
	public static function writeFloat($value){
		return pack("G", $value);
	}

	/**
	 * Reads a 4-byte little-endian floating-point number.
	 *
	 * @param string $str
	 * 
	 * @return float
	 */
	public static function readLFloat($str){
		return self::safeUnpack("g", $str)[1];
	}

	/**
	 * Reads a 4-byte little-endian floating-point number rounded to the specified number of decimal places.
	 *
	 * @param string $str
	 * @param int $accuracy
	 *
	 * @return float
	 */
	public static function readRoundedLFloat(string $str, int $accuracy) : float{
		return round(self::readLFloat($str), $accuracy);
	}

	/**
	 * Writes a 4-byte little-endian floating-point number.
	 *
	 * @param float $value
	 * 
	 * @return string
	 */
	public static function writeLFloat($value){
		return pack("g", $value);
	}

	/**
	 * Returns a printable floating-point number.
	 *
	 * @param float $value
	 * 
	 * @return string
	 */
	public static function printFloat($value) : string{
		return preg_replace("/(\\.\\d+?)0+$/", "$1", sprintf("%F", $value));
	}

	/**
	 * Reads an 8-byte floating-point number.
	 *
	 * @param string $str
	 * 
	 * @return float
	 */
	public static function readDouble($str){
		return self::safeUnpack("E", $str)[1];
	}

	/**
	 * Writes an 8-byte floating-point number.
	 *
	 * @param float $value
	 * 
	 * @return string
	 */
	public static function writeDouble($value){
		return pack("E", $value);
	}

	/**
	 * Reads an 8-byte little-endian floating-point number.
	 *
	 * @param string $str
	 * 
	 * @return float
	 */
	public static function readLDouble($str){
		return self::safeUnpack("e", $str)[1];
	}

	/**
	 * Writes an 8-byte floating-point little-endian number.
	 * 
	 * @param float $value
	 * 
	 * @return string
	 */
	public static function writeLDouble($value){
		return pack("e", $value);
	}

	/**
	 * Reads an 8-byte integer.
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readLong($str){
		return self::safeUnpack("J", $str)[1];
	}

	/**
	 * Writes an 8-byte integer.
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeLong($value){
		return pack("J", $value);
	}

	/**
	 * Reads an 8-byte little-endian integer.
	 *
	 * @param string $str
	 * 
	 * @return int
	 */
	public static function readLLong($str){
		return self::safeUnpack("P", $str)[1];
	}

	/**
	 * Writes an 8-byte little-endian integer.
	 *
	 * @param int $value
	 * 
	 * @return string
	 */
	public static function writeLLong($value){
		return pack("P", $value);
	}

	/**
	 * @param $stream
	 *
	 * @return int
	 */
	public static function readVarInt(string $buffer, int &$offset) : int{
		$raw = self::readUnsignedVarInt($buffer, $offset);
		$temp = ((($raw << 63) >> 63) ^ $raw) >> 1;
		return $temp ^ ($raw & (1 << 63));
	}

	/**
	 * @param $stream
	 *
	 * @return int
	 */
	public static function readUnsignedVarInt(string $buffer, int &$offset) : int{
		$value = 0;
		for($i = 0; $i <= 28; $i += 7){
			if(!isset($buffer[$offset])){
				throw new BinaryDataException("No bytes left in buffer");
			}
			$b = ord($buffer[$offset++]);
			$value |= (($b & 0x7f) << $i);

			if(($b & 0x80) === 0){
				return $value;
			}
		}

		throw new BinaryDataException("VarInt did not terminate after 5 bytes!");
	}

	/**
	 * @param int $v
	 * 
	 * @return string
	 */
	public static function writeVarInt($v){
		$v = ($v << 32 >> 32);
		return self::writeUnsignedVarInt(($v << 1) ^ ($v >> 31));
	}

	/**
	 * @param int $value
	 *
	 * @return string
	 */
	public static function writeUnsignedVarInt($value){
		$buf = "";
		$value &= 0xffffffff;
		for($i = 0; $i < 5; ++$i){
			if(($value >> 7) !== 0){
				$buf .= chr($value | 0x80);
			}else{
				$buf .= chr($value & 0x7f);

				return $buf;
			}
			$value = (($value >> 7) & (PHP_INT_MAX >> 6)); //PHP really needs a logical right-shift operator
		}
		throw new InvalidArgumentException("Value too large to be encoded as a VarInt");
	}

	/**
	 * Reads a 64-bit zigzag-encoded variable-length integer.
	 * 
	 * @param string $buffer
	 * @param int    &$offset
	 * 
	 * @return int
	 */
	public static function readVarLong(string $buffer, int &$offset){
		$raw = self::readUnsignedVarLong($buffer, $offset);
		$temp = ((($raw << 63) >> 63) ^ $raw) >> 1;
		return $temp ^ ($raw & (1 << 63));
	}

	/**
	 * Reads a 64-bit unsigned variable-length integer.
	 *
	 * @param string $buffer
	 * @param int    &$offset
	 *
	 * @return int
	 */
	public static function readUnsignedVarLong(string $buffer, int &$offset){
		$value = 0;
		for($i = 0; $i <= 63; $i += 7){
			if(!isset($buffer[$offset])){
				throw new BinaryDataException("No bytes left in buffer");
			}
			$b = ord($buffer[$offset++]);
			$value |= (($b & 0x7f) << $i);

			if(($b & 0x80) === 0){
				return $value;
			}
		}

		throw new BinaryDataException("VarLong did not terminate after 10 bytes!");
	}

	/**
	 * Writes a 64-bit integer as a zigzag-encoded variable-length long.
	 * 
	 * @param int $v
	 *
	 * @return string
	 */
	public static function writeVarLong($v){
		return self::writeUnsignedVarLong(($v << 1) ^ ($v >> 63));
	}

	/**
	 * Writes a 64-bit unsigned integer as a variable-length long.
	 * 
	 * @param int $value
	 *
	 * @return string
	 */
	public static function writeUnsignedVarLong($value) : string{
		$buf = "";
		for($i = 0; $i < 10; ++$i){
			if(($value >> 7) !== 0){
				$buf .= chr($value | 0x80); //Let chr() take the last byte of this, it's faster than adding another & 0x7f.
			}else{
				$buf .= chr($value & 0x7f);

				return $buf;
			}
			$value = (($value >> 7) & (PHP_INT_MAX >> 6)); //PHP really needs a logical right-shift operator
		}
		throw new InvalidArgumentException("Value too large to be encoded as a VarLong");
	}
}