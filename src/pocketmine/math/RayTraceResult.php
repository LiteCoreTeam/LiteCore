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

/**
 * Class representing a ray trace collision with an AxisAlignedBB
 */
class RayTraceResult{

	/**
	 * @var AxisAlignedBB
	 */
	public $bb;
	/**
	 * @var int
	 */
	public $hitFace;
	/**
	 * @var Vector3
	 */
	public $hitVector;

	/**
	 * @param int           $hitFace one of the Vector3::SIDE_* constants
	 */
	public function __construct(AxisAlignedBB $bb, int $hitFace, Vector3 $hitVector){
		$this->bb = $bb;
		$this->hitFace = $hitFace;
		$this->hitVector = $hitVector;
	}

	public function getBoundingBox() : AxisAlignedBB{
		return $this->bb;
	}

	public function getHitFace() : int{
		return $this->hitFace;
	}

	public function getHitVector() : Vector3{
		return $this->hitVector;
	}
}