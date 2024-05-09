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

namespace pocketmine\plugin;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Server;
use pocketmine\utils\Config;

abstract class PluginBase implements Plugin
{

	/** @var PluginLoader */
	private $loader;

	/** @var \pocketmine\Server */
	private $server;

	/** @var bool */
	private $isEnabled = false;

	/** @var bool */
	private $initialized = false;

	/** @var PluginDescription */
	private $description;

	/** @var string */
	private $dataFolder;
	private $config;
	/** @var string */
	private $configFile;
	private $file;

	/** @var PluginLogger */
	private $logger;

	/**
	 * Called when the plugin is loaded, before calling onEnable()
	 */
	public function onLoad()
	{

	}

	public function onEnable()
	{

	}

	public function onDisable()
	{

	}

	/**
	 * @return bool
	 */
	public final function isEnabled()
	{
		return $this->isEnabled === true;
	}

	/**
	 * @param bool $boolean
	 */
	public final function setEnabled($boolean = true): void
	{
		if ($this->isEnabled !== $boolean) {
			$this->isEnabled = $boolean;
			if ($this->isEnabled === true) {
				$this->onEnable();
			} else {
				$this->onDisable();
			}
		}
	}

	/**
	 * @return bool
	 */
	public final function isDisabled(): bool
	{
		return $this->isEnabled === false;
	}

	/**
	 * @return string
	 */
	public final function getDataFolder(): string
	{
		return $this->dataFolder;
	}

	/**
	 * @return PluginDescription
	 */
	public final function getDescription(): PluginDescription
	{
		return $this->description;
	}

	/**
	 * @param PluginLoader      $loader
	 * @param Server            $server
	 * @param PluginDescription $description
	 * @param                   $dataFolder
	 * @param                   $file
	 */
	public final function init(PluginLoader $loader, Server $server, PluginDescription $description, $dataFolder, $file): void
	{
		if ($this->initialized === false) {
			$this->initialized = true;
			$this->loader = $loader;
			$this->server = $server;
			$this->description = $description;
			$this->dataFolder = rtrim($dataFolder, "/" . DIRECTORY_SEPARATOR) . "/";
			$this->file = rtrim($file, "/" . DIRECTORY_SEPARATOR) . "/";
			$this->configFile = $this->dataFolder . "config.yml";
			$this->logger = new PluginLogger($this);
		}
	}

	/**
	 * @return PluginLogger
	 */
	public function getLogger(): PluginLogger
	{
		return $this->logger;
	}

	/**
	 * @return bool
	 */
	public final function isInitialized(): bool
	{
		return $this->initialized;
	}

	/**
	 * @param string $name
	 *
	 * @return Command|PluginIdentifiableCommand
	 */
	/**
	 * @param string $name
	 *
	 * @return Command|PluginIdentifiableCommand|null
	 */
	public function getCommand($name): ?Command
	{
		$server = $this->getServer();
		$pluginName = strtolower($this->description->getName());

		$command = $server->getPluginCommand($name);
		if ($command instanceof PluginIdentifiableCommand && $command->getPlugin() === $this) {
			return $command;
		}

		$command = $server->getPluginCommand($pluginName . ":" . $name);
		if ($command instanceof PluginIdentifiableCommand && $command->getPlugin() === $this) {
			return $command;
		}

		return null;
	}

	/**
	 * @param CommandSender $sender
	 * @param Command       $command
	 * @param string        $label
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function onCommand(CommandSender $sender, Command $command, $label, array $args): bool
	{
		return false;
	}

	/**
	 * @return bool
	 */
	protected function isPhar(): bool
	{
		return substr($this->file, 0, 7) === "phar://";
	}

	/**
	 * Gets an embedded resource on the plugin file.
	 * WARNING: You must close the resource given using fclose()
	 *
	 * @param string $filename
	 *
	 * @return resource Resource data, or null
	 */
	public function getResource($filename)
	{
		$resourcePath = rtrim(str_replace(DIRECTORY_SEPARATOR, "/", $filename), "/");
		$fullPath = $this->file . "resources/" . $resourcePath;

		return file_exists($fullPath) ? fopen($fullPath, "rb") : null;
	}

	/**
	 * @param string $filename
	 * @param bool   $replace
	 *
	 * @return bool
	 */
	public function saveResource($filename, $replace = false): bool
	{
		if (trim($filename) === "") {
			return false;
		}

		$resource = $this->getResource($filename);
		if ($resource === null) {
			return false;
		}

		$out = $this->dataFolder . $filename;
		$outDir = dirname($out);

		if (!is_dir($outDir) && !@mkdir($outDir, 0755, true)) {
			fclose($resource);
			return false;
		}

		if (file_exists($out) && !$replace) {
			fclose($resource);
			return false;
		}

		$fp = fopen($out, "wb");
		$ret = stream_copy_to_stream($resource, $fp) > 0;
		fclose($fp);
		fclose($resource);

		return $ret;
	}

	/**
	 * Returns all the resources packaged with the plugin
	 *
	 * @return string[]
	 */
	public function getResources(): array
	{
		$resources = [];
		$resourcesDir = $this->file . "resources/";

		if (is_dir($resourcesDir)) {
			$iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($resourcesDir));
			$resources = array_filter(iterator_to_array($iterator), function ($file) {
				return $file->isFile();
			});
		}

		return $resources;
	}

	/**
	 * @return Config
	 */
	public function getConfig(): Config
	{
		if (!isset($this->config)) {
			$this->reloadConfig();
		}

		return $this->config;
	}

	public function saveConfig(): void
	{
		if ($this->getConfig()->save() === false) {
			$this->getLogger()->critical("Could not save config to " . $this->configFile);
		}
	}

	public function saveDefaultConfig(): void
	{
		if (!file_exists($this->configFile)) {
			$this->saveResource("config.yml", false);
		}
	}

	public function reloadConfig(): void
	{
		if (!$this->saveDefaultConfig()) {
			@mkdir($this->dataFolder);
		}
		$this->config = new Config($this->configFile);
	}

	/**
	 * @return Server
	 */
	public final function getServer(): Server
	{
		return $this->server;
	}

	/**
	 * @return string
	 */
	public final function getName(): string
	{
		return $this->description->getName();
	}

	/**
	 * @return string
	 */
	public final function getFullName(): string
	{
		return $this->description->getFullName();
	}

	/**
	 * @return mixed
	 */
	protected function getFile()
	{
		return $this->file;
	}

	/**
	 * @return PluginLoader
	 */
	public function getPluginLoader(): PluginLoader
	{
		return $this->loader;
	}

}
