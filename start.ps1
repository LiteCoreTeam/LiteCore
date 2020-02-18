param (
	[switch]$Loop = $false
)

if(Test-Path "bin\php\php.exe"){
	$env:PHPRC = ""
	$binary = "bin\php\php.exe"
}else{
	$binary = "php"
}

if(Test-Path "LiteCore*.phar"){
    foreach($filename in Get-ChildItem LiteCore*.phar -Name){
        $file = "'$filename'"
        break
    }
}elseif(Test-Path "LiteCore.phar"){
	$file = "LiteCore.phar"
}elseif(Test-Path "PocketMine-MP.phar"){
	$file = "PocketMine-MP.phar"
}elseif(Test-Path "src\pocketmine\PocketMine.php"){
	$file = "src\pocketmine\PocketMine.php"
}else{
	echo "Не удалось найти правильную установку LiteCore."
	pause
	exit 1
}

function StartServer{
	$command = $binary + " " + $file + " --enable-ansi"
	chcp 65001
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
