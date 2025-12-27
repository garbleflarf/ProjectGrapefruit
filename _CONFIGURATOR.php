<?php

$BackendPath = 'backend/config.php';
if (file_exists($BackendPath)) {
    include $BackendPath; // for old stuff
} else {
    $JELLYFIN_URL = "";
    $API_KEY = "";
    $USER_ID = "";
    $ffmpeg = "";
}

if (isset($_POST['subm'])) {
    $NEW_API_KEY = $_POST['api_key'];
    $NEW_USID = $_POST['user_id'];
    $NEW_JELLYFIN_URL = $_POST['jellyfin_url'];
    $NEW_FFPATH = $_POST['ffmpeg_path'] ?? ""; // Get ffmpeg path from form
    
    $config_content = "<?php\n\n\$JELLYFIN_URL = \"" . $NEW_JELLYFIN_URL . "\"; \n\$API_KEY = \"" . $NEW_API_KEY . "\";\n\$USER_ID = \"" . $NEW_USID . "\";\n\$ffmpeg = \"" . $NEW_FFPATH . "\";\n\n?>";
    file_put_contents($BackendPath, $config_content);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="ctr-header">
        <strong>GRAPEFRUIT</strong> | Configurator
    </div>

    <div class="config-container">
        <form method="POST" action="_CONFIGURATOR.php">
            <label for="jellyfin_url">Jellyfin URL:</label>
            <input type="text" id="jellyfin_url" name="jellyfin_url" value="<?php echo isset($JELLYFIN_URL) ? htmlspecialchars($JELLYFIN_URL) : ''; ?>" required>

            <label for="api_key">API Key:</label>
            <input type="text" id="api_key" name="api_key" value="<?php echo isset($API_KEY) ? htmlspecialchars($API_KEY) : ''; ?>" required>

            <label for="user_id">User ID:</label>
            <input type="text" id="user_id" name="user_id" value="<?php echo isset($USER_ID) ? htmlspecialchars($USER_ID) : ''; ?>" required>

            <label for="ffmpeg_path">FFmpeg Path</label>
            <input type="text" id="ffmpeg_path" name="ffmpeg_path" value="<?php echo isset($ffmpeg) ? htmlspecialchars($ffmpeg) : ''; ?>" required>
            <button type="submit" name="subm" class="ctr-btn">SAVE</button>
        </form>

        <hr>
        
        <div>
            <h1>How to get everything</h1>
            <h2>Jellyfin URL</h2>
                Navigate to your Jellyfin server in a browser and copy the domain and port.
            <h2>API Key</h2>
                <ol>
                    <li>Log in to your Jellyfin server as an administrative account</li>
                    <li>Go to Dashboard > Advanced > API Keys</li>
                    <li>Click "New API Key"</li>
                    <li>Enter a name (e.g., "cappu_frontend")</li>
                    <li>Click "Add" and copy the generated key</li>
                </ol>
            <h2>User ID</h2>
                <ol>
                    <li>Log in to your Jellyfin server as an administrative account</li>
                    <li>Go to Dashboard > Server > Users</li>
                    <li>Click on your user - or create a new one</li>
                    <li>The User ID is displayed in the address bar in the userId parameter.</li>
                </ol>
            <h2>FFmpeg Path</h2>
                <ol>
                    
                    <li>On Linux, open a terminal and type <code>which ffmpeg</code><ul>
                    <li>If no path is returned, FFmpeg is not installed. Install it using your package manager:
                        <ul>
                            <li>Ubuntu/Debian: <code>sudo apt install ffmpeg</code></li>
                            <li>Fedora: <code>sudo dnf install ffmpeg</code></li>
                            <li>Arch: <code>sudo pacman -S ffmpeg</code></li>
                        </ul>
                    </li>
                    <li>After installation, run <code>which ffmpeg</code> again to get the full path</li>
                    </ul></li>
            </ol>
    </div>
</body>
</html>
    