<?php
/** @noinspection PhpIncludeInspection */

declare(strict_types=1);

$outputDirectory = __DIR__.DIRECTORY_SEPARATOR."out".DIRECTORY_SEPARATOR;
if(!is_dir($outputDirectory)){
    mkdir($outputDirectory);
}

$texturesDirectory = $outputDirectory."textures";
if(!is_dir($texturesDirectory)){
    exec("git clone https://github.com/Yexeed/mc-icons.git ".$texturesDirectory);
}

$iterator = new FilesystemIterator(
    $texturesDirectory.DIRECTORY_SEPARATOR."pics"
    , FilesystemIterator::CURRENT_AS_FILEINFO
);

/** @var SplFileInfo $file */
$colorPalette = [];
foreach($iterator as $file) {
    if($file->getExtension() === "png"){
        $filename = getFileWithoutExtension($file->getFilename());

        if($filename !== "0-0"){
            $colorPalette[str_replace("-", ":", $filename)] = getAverage($file->getRealPath());
        }
    }
}
file_put_contents($outputDirectory."colors.json", json_encode($colorPalette, JSON_PRETTY_PRINT));

function getFileWithoutExtension(string $filename) : string{
    $exploded = explode(".", $filename);
    array_pop($exploded);

    return implode(".", $exploded);
}

function getAverage(string $filename) : array{
    $image = imagecreatefrompng($filename);

    $colors = [];

    $size = getimagesize($filename);
    for($x = 0; $x < $size[0]; ++$x){
        for($y = 0; $y < $size[1]; ++$y){
            $color = imagecolorat($image, $x, $y);

            if(isset($colors[$color])){
                $colors[$color] += 1;
            }else{
                $colors[$color] = 1;
            }
        }
    }

    do{
        if(count($colors) > 1){
            $maxKey = array_search(max($colors), $colors);
            $rgb = imagecolorsforindex($image, $maxKey);
            unset($colors[$maxKey]);
        }else{
            $rgb = imagecolorsforindex($image, array_shift($colors));

            break;
        }
    }while($rgb["red"] === 0 && $rgb["green"] === 0 && $rgb["blue"] === 0);

    $rgb["alpha"] = ord(~chr($rgb["alpha"]));

    imagedestroy($image);

    return $rgb;
}