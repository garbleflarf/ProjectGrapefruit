<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/config.php';

$vidID = $_GET['vidID'] ?? '';
$check = !empty($_GET['check']);

if ($vidID === '' && strtolower(PHP_SAPI) != 'cli') {
    header("HTTP/1.1 400 Bad Request");
    die("Missing vidID");
}

$isWin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN');

$ua = $_SERVER['HTTP_USER_AGENT'];
$deviceSuffix = "";

if (strpos($ua, 'Nintendo 3DS') !== false) {
    $deviceSuffix = ".3ds";
} elseif (strpos($ua, 'Wii U') !== false) {
    $deviceSuffix = "";
}

// Setup Paths
$cacheDirBase = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
$cacheDir  = $cacheDirBase . 'vid' . DIRECTORY_SEPARATOR;
if (!is_dir($cacheDirBase)) {
    mkdir($cacheDirBase, 0777, true);
}
if (!is_dir($cacheDir)) {
    mkdir($cacheDir, 0777, true);
}

if (strtolower(PHP_SAPI) === 'cli') {
    $ttl = 7200; // 1 hour
    $count = 0;

    foreach (glob($cacheDir . '*.mp4') as $mp4) {
        // Skip files still being processed (check for .mp4.lock)
        if (file_exists($mp4 . '.lock')) {
            continue;
        }

        // Check if the MP4 itself is old
        if ((time() - filemtime($mp4)) > $ttl) {
            echo "$mp4 is old\n";
            $basePath = substr($mp4, 0, -4); // path/to/videoID
            
            // Explicitly delete the family of files
            @unlink($mp4);           // .mp4
            @unlink($mp4 . '.lock'); // .mp4.lock
            @unlink($mp4 . '.log');  // .mp4.log
            @unlink($mp4 . '.bat');  // .mp4.bat
            @unlink($mp4 . '.sh');   // .mp4.sh
            
            $count++;
        } else {
            echo "$mp4 is not old\n";
        }
    }
    echo "it is done\n"; 
    echo "deleted $count sets of files\n";
    exit(0); // Use exit in CLI, not die with a message for better scripting
}

$cacheFile = $cacheDir . $vidID . $deviceSuffix . '.mp4';
$lockFile  = $cacheFile . '.lock';
$scriptFile = $cacheFile . ($isWin ? '.bat' : '.sh');
$logFile   = $cacheFile . '.log';

// Serve if ready
if (file_exists($cacheFile) && !file_exists($lockFile)) {
    if ($check) die("Ready");
    
    // Update the timestamp so the cleanup script doesn't eat it
    touch($cacheFile); 

    // Construct the URL to the file
    // If your proxy is in /backend/ and cache is in /cache/
    $redirectUrl = "../cache/vid/" . basename($cacheFile);
    
    header("Location: " . $redirectUrl);
    exit;
}

if ($check && file_exists($lockFile)) {
    header("HTTP/1.1 425 Too Early");
    die("Still processing");
}

/* --- TRANSCODE START --- */
if (!file_exists($lockFile)) {
    file_put_contents($lockFile, 'running');

    $sourceUrl = $CONFIG['jellyfin_url'] . "/Videos/{$vidID}/stream?static=true&api_key=" . $CONFIG['api_key'];
    $ffmpeg = $CONFIG['ffmpeg'];

    // Device Detection (Put this before the script builder)
    $ua = $_SERVER['HTTP_USER_AGENT'];
    $deviceSuffix = "";
    $vf = "scale=1280:720"; // Default / Wii U
    $bitrate = "-maxrate 2500k -bufsize 5000k";

    if (strpos($ua, 'Nintendo 3DS') !== false) {
        $deviceSuffix = ".3ds";
        $vf = "scale=854:480"; // 3DS Sweet spot
        $bitrate = "-maxrate 1000k -bufsize 2000k -r 30"; // 30fps cap is life or death
    }

    $cacheFile = $cacheDir . $vidID . $deviceSuffix . '.mp4';
    $lockFile  = $cacheFile . '.lock';
    $scriptFile = $cacheFile . ($isWin ? '.bat' : '.sh');
    $logFile   = $cacheFile . '.log';

    // Build the Content
    if (strpos($ua, 'Nintendo 3DS') !== false) {
        $ffmpegCmd = "\"$ffmpeg\" -i \"$sourceUrl\" -vf \"$vf\" -c:v libx264 -x264-params \"ref=1\" -profile:v baseline -level 3.0 -preset fast -crf 28 $bitrate -sn -c:a aac -ac 2 -ar 44100 -b:a 128k \"$cacheFile\" > \"$logFile\" 2>&1";
    } else {
        $ffmpegCmd = "\"$ffmpeg\" -i \"$sourceUrl\" -vf \"$vf\" -c:v libx264 -x264-params \"ref=1\" -profile:v baseline -level 3.0 -preset fast -crf 28 $bitrate -sn -c:a aac -ac 2 -ar 44100 -b:a 128k -movflags +faststart \"$cacheFile\" > \"$logFile\" 2>&1";

    }

    if ($isWin) {
        $content = "@echo off\r\n" .
                $ffmpegCmd . "\r\n" .
                "del \"$lockFile\"\r\n" .
                "del \"$scriptFile\"";
    } else {
        $content = "#!/bin/bash\n" .
                $ffmpegCmd . "\n" .
                "rm \"$lockFile\"\n" .
                "rm \"$scriptFile\"";
    }

    if (file_put_contents($scriptFile, $content) === false) {
        unlink($lockFile);
        die("Permission denied: Cannot write to $cacheDir");
    }

    // Execute background process
    if ($isWin) {
        // The "Poor Man's" background launch for Windows
        pclose(popen("start /B cmd /C \"$scriptFile\" >NUL 2>&1", "r"));
    } else {
        // Linux background launch
        chmod($scriptFile, 0755);
        exec("bash \"$scriptFile\" > /dev/null 2>&1 &");
    }
}

header("HTTP/1.1 202 Accepted");
die("Caching in progress");