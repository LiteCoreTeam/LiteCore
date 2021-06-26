[CmdletBinding(PositionalBinding=$false)]
param (
    [string]$php = "",
	[switch]$Loop = $false,
	[string]$file = "",
	[string][Parameter(ValueFromRemainingArguments)]$extraPocketMineArgs
)

if($php -ne ""){
	$binary = $php
}elseif(Test-Path "bin\php\php.exe"){
	$env:PHPRC = ""
	$binary = "bin\php\php.exe"
}else{
	$binary = "php"
}

if($file -eq ""){
	if(Test-Path "PocketMine-MP.phar"){
	    $file = "PocketMine-MP.phar"
	}elseif(Test-Path "src\pocketmine\PocketMine.php"){
	    $file = "src\pocketmine\PocketMine.php"
	}else{
	    echo "Ядро установлено некорректно!"
	    pause
	    exit 1
	}
}

function StartServer{
	$command = "powershell -NoProfile " + $binary + " " + $file + " " + $extraPocketMineArgs
	iex $command
}

$loops = 0

StartServer

while($Loop){
	if($loops -ne 0){
		echo ("Restarted " + $loops + " times")
	}
	$loops++
	echo "Чтобы выйти из цикла, нажмите CTRL + C сейчас. В ином случае подождите 5 секунд, пока сервер не перезагрузится."
	echo ""
	Start-Sleep 5
	StartServer
}
