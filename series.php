<?php
require_once 'backend/config.php';
$parentId = $_GET['id'];

$url = $CONFIG['jellyfin_url'] . "/Users/" . $CONFIG['user_id'] . "/Items" .
       "?api_key=" . $CONFIG['api_key'] .
       "&ParentId=" . $parentId .
       "&Recursive=true" . 
       "&IncludeItemTypes=Episode" .
       "&SortBy=SortName";

$data = json_decode(file_get_contents($url), true);
$episodes = $data['Items'];
?>
<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="ctr-header">EPISODES</div>
    
    <div class="ctr-list">
        <?php foreach ($episodes as $ep): ?>
            <a href="watch.php?id=<?= $ep['Id'] ?>" class="ctr-item">
                <span class="type-tag">E</span> 
                S<?= $ep['ParentIndexNumber'] ?>:E<?= $ep['IndexNumber'] ?> - <?= htmlspecialchars($ep['Name']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <a href="index.php" class="ctr-btn">BACK</a>
</body>
</html>