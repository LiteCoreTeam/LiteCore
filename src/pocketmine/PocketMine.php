<?php

/*
 *
 *  _____            _               _____           
 * / ____|          (_)             |  __ \          
 *| |  __  ___ _ __  _ ___ _   _ ___| |__) | __ ___  
 *| | |_ |/ _ \ '_ \| / __| | | / __|  ___/ '__/ _ \ 
 *| |__| |  __/ | | | \__ \ |_| \__ \ |   | | | (_) |
 * \_____|\___|_| |_|_|___/\__, |___/_|   |_|  \___/ 
 *                         __/ |                    
 *                        |___/                     
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * @author GenisysPro
 * @link https://github.com/GenisysPro/GenisysPro
 *
 *
*/

namespace {
	const INT32_MIN = -0x80000000;
	const INT32_MAX = 0x7fffffff;

	function safe_var_dump(){
		static $cnt = 0;
		foreach(func_get_args() as $var){
			switch(true){
				case is_array($var):
					echo str_repeat("  ", $cnt) . "array(" . count($var) . ") {" . PHP_EOL;
					foreach($var as $key => $value){
						echo str_repeat("  ", $cnt + 1) . "[" . (is_int($key) ? $key : '"' . $key . '"') . "]=>" . PHP_EOL;
						++$cnt;
						safe_var_dump($value);
						--$cnt;
					}
					echo str_repeat("  ", $cnt) . "}" . PHP_EOL;
					break;
				case is_int($var):
					echo str_repeat("  ", $cnt) . "int(" . $var . ")" . PHP_EOL;
					break;
				case is_float($var):
					echo str_repeat("  ", $cnt) . "float(" . $var . ")" . PHP_EOL;
					break;
				case is_bool($var):
					echo str_repeat("  ", $cnt) . "bool(" . ($var === true ? "true" : "false") . ")" . PHP_EOL;
					break;
				case is_string($var):
					echo str_repeat("  ", $cnt) . "string(" . strlen($var) . ") \"$var\"" . PHP_EOL;
					break;
				case is_resource($var):
					echo str_repeat("  ", $cnt) . "resource() of type (" . get_resource_type($var) . ")" . PHP_EOL;
					break;
				case is_object($var):
					echo str_repeat("  ", $cnt) . "object(" . get_class($var) . ")" . PHP_EOL;
					break;
				case is_null($var):
					echo str_repeat("  ", $cnt) . "NULL" . PHP_EOL;
					break;
			}
		}
	}

	function dummy(){

	}
}

namespace pocketmine {

	use pocketmine\utils\Binary;
	use pocketmine\utils\MainLogger;
	use pocketmine\utils\ServerKiller;
	use pocketmine\utils\Terminal;
	use pocketmine\utils\Timezone;
	use pocketmine\utils\Utils;
	use pocketmine\wizard\Installer;

	const VERSION = "1.1.X";
	const API_VERSION = "3.0.1";
	const CODENAME = "vk.com/litecore_team";
	const GENISYS_API_VERSION = '2.0.0';
	const CORE_VERSION = '1.0.1-public';

	/*
	 * Startup code. Do not look at it, it may harm you.
	 * Most of them are hacks to fix date-related bugs, or basic functions used after this
	 * This is the only non-class based file on this project.
	 * Enjoy it as much as I did writing it. I don't want to do it again.
	 */

	if(\Phar::running(true) !== ""){
		@define('pocketmine\PATH', \Phar::running(true) . "/");
	}else{
		@define('pocketmine\PATH', \getcwd() . DIRECTORY_SEPARATOR);
	}

	if(!extension_loaded("pthreads")){
		echo "[CRITICAL] Unable to find the pthreads extension." . PHP_EOL;
		echo "[CRITICAL] Please use the installer provided on the homepage." . PHP_EOL;
		exit(1);
	}

	if(!class_exists("ClassLoader", false)){
		require_once(\pocketmine\PATH . "src/spl/ClassLoader.php");
		require_once(\pocketmine\PATH . "src/spl/BaseClassLoader.php");
	}

	$autoloader = new \BaseClassLoader();
	$autoloader->addPath(\pocketmine\PATH . "src");
	$autoloader->addPath(\pocketmine\PATH . "src" . DIRECTORY_SEPARATOR . "spl");
	$autoloader->register(true);

	error_reporting(-1);

	set_error_handler([Utils::class, 'errorExceptionHandler']);

	ini_set("allow_url_fopen", '1');
	ini_set("display_errors", '1');
	ini_set("display_startup_errors", '1');
	ini_set("default_charset", "utf-8");

	ini_set("memory_limit", '-1');

	$opts = getopt("", ["data:", "plugins:", "no-wizard", "enable-profiler"]);

	define('pocketmine\DATA', isset($opts["data"]) ? $opts["data"] . DIRECTORY_SEPARATOR : \realpath(\getcwd()) . DIRECTORY_SEPARATOR);
	define('pocketmine\PLUGIN_PATH', isset($opts["plugins"]) ? $opts["plugins"] . DIRECTORY_SEPARATOR : \realpath(\getcwd()) . DIRECTORY_SEPARATOR . "plugins" . DIRECTORY_SEPARATOR);

	Terminal::init();

	define('pocketmine\ANSI', Terminal::hasFormattingCodes());

	if(!file_exists(\pocketmine\DATA)){
		mkdir(\pocketmine\DATA, 0777, true);
	}

	//Logger has a dependency on timezone
	$tzError = Timezone::init();

    $logger = new MainLogger(\pocketmine\DATA . "server.log");
    $logger->registerStatic();

	foreach($tzError as $e){
		$logger->warning($e);
	}
	unset($tzError);

	if(isset($opts["enable-profiler"])){
		if(function_exists("profiler_enable")){
			\profiler_enable();
			$logger->notice("Execution is being profiled");
		}else{
			$logger->notice("No profiler found. Please install https://github.com/krakjoe/profiler");
		}
	}

	$errors = 0;

	if(php_sapi_name() !== "cli"){
		$logger->critical("You must run LiteCore using the CLI.");
		++$errors;
	}

	if(!extension_loaded("sockets")){
		$logger->critical("Unable to find the Socket extension.");
		++$errors;
	}

	$pthreads_version = phpversion("pthreads");
	if(substr_count($pthreads_version, ".") < 2){
		$pthreads_version = "0.$pthreads_version";
	}
	if(version_compare($pthreads_version, "3.1.5") < 0){
		$logger->critical("pthreads >= 3.1.5 is required, while you have $pthreads_version.");
		++$errors;
	}

	if(!extension_loaded("uopz")){
		//$logger->notice("Couldn't find the uopz extension. Some functions may be limited");
	}

	if(extension_loaded("pocketmine")){
		if(version_compare(phpversion("pocketmine"), "0.0.1") < 0){
			$logger->critical("You have the native LiteCore extension, but your version is lower than 0.0.1.");
			++$errors;
		}elseif(version_compare(phpversion("pocketmine"), "0.0.4") > 0){
			$logger->critical("You have the native LiteCore extension, but your version is higher than 0.0.4.");
			++$errors;
		}
	}

	if(extension_loaded("xdebug")){
		$logger->warning("You are running LiteCore with Xdebug enabled. This has a major impact on performance.");
	}

	if(!extension_loaded("curl")){
		$logger->critical("Unable to find the cURL extension.");
		++$errors;
	}

	if(!extension_loaded("yaml")){
		$logger->critical("Unable to find the YAML extension.");
		++$errors;
	}

	if(!extension_loaded("zlib")){
		$logger->critical("Unable to find the Zlib extension.");
		++$errors;
	}

	if($errors > 0){
		$logger->critical("Please update or recompile PHP.");
		$logger->shutdown();
		$logger->join();
		exit(1); //Exit with error
	}

	if(file_exists(\pocketmine\PATH . ".git/HEAD")){ //Found Git information!
		$ref = trim(file_get_contents(\pocketmine\PATH . ".git/HEAD"));
		if(preg_match('/^[0-9a-f]{40}$/i', $ref)){
			define('pocketmine\GIT_COMMIT', strtolower($ref));
		}elseif(substr($ref, 0, 5) === "ref: "){
			$refFile = \pocketmine\PATH . ".git/" . substr(trim(file_get_contents(\pocketmine\PATH . ".git/HEAD")), 5);
			if(is_file($refFile)){
				define('pocketmine\GIT_COMMIT', strtolower(trim(file_get_contents($refFile))));
			}
		}
	}
	if(!defined('pocketmine\GIT_COMMIT')){
		define('pocketmine\GIT_COMMIT', "0000000000000000000000000000000000000000");
	}

	@define("ENDIANNESS", (pack("d", 1) === "\77\360\0\0\0\0\0\0" ? Binary::BIG_ENDIAN : Binary::LITTLE_ENDIAN));
	@define("INT32_MASK", is_int(0xffffffff) ? 0xffffffff : -1);
	@ini_set("opcache.mmap_base", bin2hex(random_bytes(8))); //Fix OPCache address errors

	if(!file_exists(\pocketmine\DATA . "server.properties") and !isset($opts["no-wizard"])){
		$installer = new Installer();
		if(!$installer->run()){
			$logger->shutdown();
			$logger->join();
			exit(-1);
		}
	}

	//TODO: move this to a Server field
	define('pocketmine\START_TIME', microtime(true));
	ThreadManager::init();
	new Server($autoloader, $logger, \pocketmine\PATH, \pocketmine\DATA, \pocketmine\PLUGIN_PATH);

	$logger->info("Stopping other threads");

	$killer = new ServerKiller(8);
	$killer->start(PTHREADS_INHERIT_CONSTANTS);
	usleep(10000); //Fixes ServerKiller not being able to start on single-core machines

	$logger->shutdown();
	$logger->join();

	echo Terminal::$FORMAT_RESET . PHP_EOL;

	if(ThreadManager::getInstance()->stopAll() > 0){
		$logger->debug("Some threads could not be stopped, performing a force-kill");
		Utils::kill(getmypid());
	}else{
		exit(0);
	}

}