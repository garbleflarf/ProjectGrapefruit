<?php
require_once 'backend/config.php';

$page = isset($_GET['page']) ? (int)$_GET['page'] : 0;
$limit = 10; 
$startIndex = $page * $limit;

$url = $CONFIG['jellyfin_url'] . "/Users/" . $CONFIG['user_id'] . "/Items" .
       "?api_key=" . $CONFIG['api_key'] .
       "&StartIndex=" . $startIndex .
       "&Limit=" . $limit .
       "&Recursive=true" .
       "&IncludeItemTypes=Movie,Series,Episode" .
       "&SortBy=SortName" .
       "&Fields=Type" .
       "&EnableImageTypes=Primary";

// Suppress errors with @ so we can handle them manually
$response = @file_get_contents($url);

$errorMsg = null;
$statusCode = null;

if ($response === false) {
    // Grab the status code from the magic header variable
    if (isset($http_response_header)) {
        $statusCode = $http_response_header[0]; // e.g., "HTTP/1.1 401 Unauthorized"
    } else {
        $statusCode = "Connection Failed (DNS or Firewall?)";
    }
    $errorMsg = "Jellyfin is unreachable or mad at you.";
}

$data = json_decode($response, true);
$items = isset($data['Items']) ? $data['Items'] : [];
?>
<!DOCTYPE html>
<html>
<head>
    <title>Grapefruit CTR</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="ctr-header">
        <strong>GRAPEFRUIT</strong> | Library
    </div>

    <div class="ctr-list">
        <?php if ($errorMsg): ?>
            <div class="status-box" style="border-left: 6px solid #ce181e; color: #ce181e;">
                <strong>ERROR:</strong> <?= htmlspecialchars($errorMsg) ?><br>
                <small><?= htmlspecialchars($statusCode) ?></small>
            </div>
            <div class="ctr-item">Check your config.php and server logs.</div>
        <?php elseif (empty($items)): ?>
            <div class="ctr-item">Nothing found. Your library is empty.</div>
        <?php else: ?>
            <?php foreach ($items as $item): ?>
                <?php $view = ($item['Type'] === 'Movie' || $item['Type'] === 'Episode') ? 'watch.php' : 'series.php'; ?>
                <a href="<?= $view ?>?id=<?= $item['Id'] ?>" class="ctr-item">
                    <span class="type-tag tag-<?= strtolower($item['Type']) ?>"><?= strtoupper($item['Type'][0]) ?></span>
                    <?= htmlspecialchars($item['Name']) ?>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="ctr-nav">
        <?php if ($page > 0): ?>
            <a href="?page=<?= $page - 1 ?>" class="ctr-btn">BACK</a>
        <?php endif; ?>
        <a href="?page=<?= $page + 1 ?>" class="ctr-btn">NEXT</a>
    </div>
</body>
</html>