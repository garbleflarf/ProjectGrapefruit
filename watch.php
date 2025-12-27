<?php
require_once 'backend/config.php';
$vidID = $_GET['id'];
// We need the item details to get the title
$itemUrl = $CONFIG['jellyfin_url'] . "/Users/" . $CONFIG['user_id'] . "/Items/" . $vidID . "?api_key=" . $CONFIG['api_key'];
$item = json_decode(file_get_contents($itemUrl), true);
$ITEM_ID = $item['Id'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="ctr-header">NOW WATCHING</div>
    
    <div style="padding: 15px; text-align: center;">
        <h2 style="font-size: 16px;"><?= htmlspecialchars($item['Name']) ?></h2>
        
        <div class="status-box">
            3DS Transcoding usually takes a hot minute to finish. Patience.
        </div>

        <div id="player-area"></div>

        <div style="margin-top: 20px;">
            <a href="index.php" class="ctr-btn" style="width: 80%;">CANCEL</a>
        </div>
    </div>

    <script>
        var pollTimeout = null;
        var done = false;

        function checkStatus() {
            if (done) return; // HARD STOP

            var xhr = new XMLHttpRequest();

            xhr.open(
                'GET',
                'backend/stream_proxy.php?vidID=<?php echo $vidID; ?>&check=true',
                true
            );

            xhr.onreadystatechange = function () {
                if (xhr.readyState !== 4 || done) return;

                if (xhr.status === 200) {
                    done = true;

                    if (pollTimeout !== null) {
                        clearTimeout(pollTimeout);
                        pollTimeout = null;
                    }

                    document.getElementById('player-area').innerHTML = 
                        '<a href="backend/stream_proxy.php?vidID=<?php echo $vidID; ?>" class="ctr-btn" style="background: #28a745;">START</a>' +
                        '<p style="font-size:10px; margin-top:5px;">Clicking will open the media player</p>';
                } else {
                    pollTimeout = setTimeout(checkStatus, 5000);
                }
            };

            xhr.send(null);
        }

        checkStatus();
    </script>
</body>
</html>