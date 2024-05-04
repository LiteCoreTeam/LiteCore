<?php

/*
 *
 *  _        _                ______ 
 * | |      (_) _            / _____) 
 * | |       _ | |_    ____ | /        ___    ____   ____ 
 * | |      | ||  _)  / _  )| |       / _ \  / ___) / _  ) 
 * | |_____ | || |__ ( (/ / | \_____ | |_| || |    ( (/ / 
 * |_______)|_| \___) \____) \______) \___/ |_|     \____) 
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author LiteTeam
 * @link https://github.com/LiteCoreTeam/LiteCore
 *
 *
 */

namespace pocketmine\math;

use pocketmine\utils\Random;
use function abs;
use function ceil;
use function floor;
use function max;
use function round;
use function sqrt;
use const PHP_ROUND_HALF_UP;

class Vector3{

	const SIDE_DOWN = 0;
	const SIDE_UP = 1;
	const SIDE_NORTH = 2;
	const SIDE_SOUTH = 3;
	const SIDE_WEST = 4;
	const SIDE_EAST = 5;

	public $x;
	public $y;
	public $z;

	/**
	 * Vector3 constructor.
	 *
	 * @param int $x
	 * @param int $y
	 * @param int $z
	 */
	public function __construct($x = 0, $y = 0, $z = 0){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
	}

	/**
	 * @return int
	 */
	public function getX(){
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getY(){
		return $this->y;
	}

	/**
	 * @return int
	 */
	public function getZ(){
		return $this->z;
	}

	/**
	 * @return int
	 */
	public function getFloorX(){
		return (int) floor($this->x);
	}

	/**
	 * @return int
	 */
	public function getFloorY(){
		return (int) floor($this->y);
	}

	/**
	 * @return int
	 */
	public function getFloorZ(){
		return (int) floor($this->z);
	}

	/**
	 * @return int
	 */
	public function getRight(){
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getUp(){
		return $this->y;
	}

	/**
	 * @return int
	 */
	public function getForward(){
		return $this->z;
	}

	/**
	 * @return int
	 */
	public function getSouth(){
		return $this->x;
	}

	/**
	 * @return int
	 */
	public function getWest(){
		return $this->z;
	}

	/**
	 * @param Vector3|int $x
	 * @param int         $y
	 * @param int         $z
	 *
	 * @return Vector3
	 */
	public function add($x, $y = 0, $z = 0){
		if($x instanceof Vector3){
			return new Vector3($this->x + $x->x, $this->y + $x->y, $this->z + $x->z);
		}else{
			return new Vector3($this->x + $x, $this->y + $y, $this->z + $z);
		}
	}

	/**
	 * @param Vector3|float $x
	 * @param float         $y
	 * @param float         $z
	 *
	 * @return Vector3
	 */
	public function subtract($x, $y = 0, $z = 0){
		if($x instanceof Vector3){
			return $this->add(-$x->x, -$x->y, -$x->z);
		}else{
			return $this->add(-$x, -$y, -$z);
		}
	}

	/**
	 * @param $number
	 *
	 * @return Vector3
	 */
	public function multiply($number){
		return new Vector3($this->x * $number, $this->y * $number, $this->z * $number);
	}

	/**
	 * @param $number
	 *
	 * @return Vector3
	 */
	public function divide($number){
		return new Vector3($this->x / $number, $this->y / $number, $this->z / $number);
	}

	/**
	 * @return Vector3
	 */
	public function ceil(){
		return new Vector3((int) ceil($this->x), (int) ceil($this->y), (int) ceil($this->z));
	}

	/**
	 * @return Vector3
	 */
	public function floor(){
		return new Vector3((int) floor($this->x), (int) floor($this->y), (int) floor($this->z));
	}

	public function round(int $precision = 0, int $mode = PHP_ROUND_HALF_UP){
		return $precision > 0 ?
			new Vector3(round($this->x, $precision, $mode), round($this->y, $precision, $mode), round($this->z, $precision, $mode)) :
			new Vector3((int) round($this->x, $precision, $mode), (int) round($this->y, $precision, $mode), (int) round($this->z, $precision, $mode));
	}

	/**
	 * @return Vector3
	 */
	public function abs(){
		return new Vector3(abs($this->x), abs($this->y), abs($this->z));
	}

	/**
	 * @param     $side
	 * @param int $step
	 *
	 * @return $this|Vector3
	 */
	public function getSide(int $side, int $step = 1){
		switch((int) $side){
			case Vector3::SIDE_DOWN:
				return new Vector3($this->x, $this->y - $step, $this->z);
			case Vector3::SIDE_UP:
				return new Vector3($this->x, $this->y + $step, $this->z);
			case Vector3::SIDE_NORTH:
				return new Vector3($this->x, $this->y, $this->z - $step);
			case Vector3::SIDE_SOUTH:
				return new Vector3($this->x, $this->y, $this->z + $step);
			case Vector3::SIDE_WEST:
				return new Vector3($this->x - $step, $this->y, $this->z);
			case Vector3::SIDE_EAST:
				return new Vector3($this->x + $step, $this->y, $this->z);
			default:
				return $this;
		}
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function down(int $step = 1){
		return $this->getSide(self::SIDE_DOWN, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function up(int $step = 1){
		return $this->getSide(self::SIDE_UP, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function north(int $step = 1){
		return $this->getSide(self::SIDE_NORTH, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function south(int $step = 1){
		return $this->getSide(self::SIDE_SOUTH, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function west(int $step = 1){
		return $this->getSide(self::SIDE_WEST, $step);
	}

	/**
	 * @param int $step
	 *
	 * @return Vector3
	 */
	public function east(int $step = 1){
		return $this->getSide(self::SIDE_EAST, $step);
	}

	/**
	 * Return a Vector3 instance
	 * 
	 * @return Vector3
	 */
	public function asVector3() : Vector3{
		return new Vector3($this->x, $this->y, $this->z);
	}

	/**
	 * Returns the Vector3 side number opposite the specified one
	 *
	 * @param int $side 0-5 one of the Vector3::SIDE_* constants
	 *
	 * @return int
	 *
	 * @throws \InvalidArgumentException if an invalid side is supplied
	 */
	public static function getOppositeSide($side){
 		switch((int) $side){
 			case Vector3::SIDE_DOWN:
 				return Vector3::SIDE_UP;
 			case Vector3::SIDE_UP:
 				return Vector3::SIDE_DOWN;
 			case Vector3::SIDE_NORTH:
 				return Vector3::SIDE_SOUTH;
 			case Vector3::SIDE_SOUTH:
 				return Vector3::SIDE_NORTH;
 			case Vector3::SIDE_WEST:
 				return Vector3::SIDE_EAST;
 			case Vector3::SIDE_EAST:
 				return Vector3::SIDE_WEST;
 			default:
 				return -1;
		}
 	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return float
	 */
	public function distance(Vector3 $pos){
		return sqrt($this->distanceSquared($pos));
	}

	/**
	 * @param Vector3 $pos
	 *
	 * @return number
	 */
	public function distanceSquared(Vector3 $pos){
		return (($this->x - $pos->x) ** 2) + (($this->y - $pos->y) ** 2) + (($this->z - $pos->z) ** 2);
	}

	/**
	 * @param int $x
	 * @param int $z
	 *
	 * @return mixed
	 */
	public function maxPlainDistance($x, $z = 0) : float{
		if($x instanceof Vector3){
			return $this->maxPlainDistance($x->x, $x->z);
		}elseif($x instanceof Vector2){
			return $this->maxPlainDistance($x->x, $x->y);
		}else{
			return max(abs($this->x - $x), abs($this->z - $z));
		}
	}

	/**
	 * @return float
	 */
	public function length(){
		return sqrt($this->lengthSquared());
	}

	/**
	 * @return int
	 */
	public function lengthSquared(){
		return $this->x * $this->x + $this->y * $this->y + $this->z * $this->z;
	}

	/**
	 * @return Vector3
	 */
	public function normalize(){
		$len = $this->lengthSquared();
		if($len > 0){
			return $this->divide(sqrt($len));
		}

		return new Vector3(0, 0, 0);
	}

	public function dot(Vector3 $v) : float{
		return $this->x * $v->x + $this->y * $v->y + $this->z * $v->z;
	}

	/**
	 * @param Vector3 $v
	 *
	 * @return Vector3
	 */
	public function cross(Vector3 $v){
		return new Vector3(
			$this->y * $v->z - $this->z * $v->y,
			$this->z * $v->x - $this->x * $v->z,
			$this->x * $v->y - $this->y * $v->x
		);
	}

	/**
	 * @param Vector3 $v
	 *
	 * @return bool
	 */
	public function equals(Vector3 $v){
		return $this->x == $v->x and $this->y == $v->y and $this->z == $v->z;
	}

	/**
	 * Returns a new vector with x value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3 $v
	 * @param float   $x
	 *
	 * @return Vector3
	 */
	public function getIntermediateWithXValue(Vector3 $v, $x){
		$xDiff = $v->x - $this->x;

		if(($xDiff * $xDiff) < 0.0000001){
			return null;
		}

		$f = ($x - $this->x) / $xDiff;

		if($f < 0 or $f > 1){
			return null;
		}else{
			return new Vector3($x, $this->y + ($v->y - $this->y) * $f, $this->z + ($v->z - $this->z) * $f);
		}
	}

	/**
	 * Returns a new vector with y value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3 $v
	 * @param float   $y
	 *
	 * @return Vector3
	 */
	public function getIntermediateWithYValue(Vector3 $v, $y){
		$yDiff = $v->y - $this->y;

		if(($yDiff * $yDiff) < 0.0000001){
			return null;
		}

		$f = ($y - $this->y) / $yDiff;

		if($f < 0 or $f > 1){
			return null;
		}else{
			return new Vector3($this->x + ($v->x - $this->x) * $f, $y, $this->z + ($v->z - $this->z) * $f);
		}
	}

	/**
	 * Returns a new vector with z value equal to the second parameter, along the line between this vector and the
	 * passed in vector, or null if not possible.
	 *
	 * @param Vector3 $v
	 * @param float   $z
	 *
	 * @return Vector3
	 */
	public function getIntermediateWithZValue(Vector3 $v, $z){
		$zDiff = $v->z - $this->z;

		if(($zDiff * $zDiff) < 0.0000001){
			return null;
		}

		$f = ($z - $this->z) / $zDiff;

		if($f < 0 or $f > 1){
			return null;
		}else{
			return new Vector3($this->x + ($v->x - $this->x) * $f, $this->y + ($v->y - $this->y) * $f, $z);
		}
	}

	/**
	 * @param $x
	 * @param $y
	 * @param $z
	 *
	 * @return Vector3
	 */
	public function setComponents($x, $y, $z){
		$this->x = $x;
		$this->y = $y;
		$this->z = $z;
		return $this;
	}

	/**
	 * @param Vector3 $pos
	 * @param         $x
	 * @param         $y
	 * @param         $z
	 *
	 * @return $this
	 */
	public function fromObjectAdd(Vector3 $pos, $x, $y, $z){
		$this->x = $pos->x + $x;
		$this->y = $pos->y + $y;
		$this->z = $pos->z + $z;
		return $this;
	}

	/**
	 * @return string
	 */
	public function __toString(){
		return "Vector3(x=" . $this->x . ",y=" . $this->y . ",z=" . $this->z . ")";
	}

	/**
	 * @param Random $random
	 *
	 * @return Vector3
	 */
	public static function createRandomDirection(Random $random){
		return VectorMath::getDirection3D($random->nextFloat() * 2 * pi(), $random->nextFloat() * 2 * pi());
	}
}
