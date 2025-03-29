<?php

require_once BASEDIR . '/lib/common.php';
require_once BASEDIR . '/classes/auth.php';
require_once BASEDIR . '/classes/repos/repoThread.php';

$AUTH = AuthClass::getInstance();
$board = getBoardFromRequest();

if (!$board) {
    http_response_code(404);
    echo "404 Not Found: Board could not be loaded.";
    exit;
}

if (!$AUTH->isAdmin($board->getBoardID())) {
    http_response_code(403);
    echo "403 Forbidden: You are not an admin of this board.";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rawJson = $_POST['configJson'] ?? '';

    $decoded = json_decode($rawJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
        echo '<div class="postblock" style="color:red;">Invalid JSON: ' . htmlspecialchars(json_last_error_msg()) . '</div>';
        echo '<meta http-equiv="refresh" content="3;url=' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">';
        exit;
    }

    $board->setConf($decoded);
    BoardRepoClass::getInstance()->updateBoard($board);

    echo '<div class="postblock">Config updated successfully. Redirecting back to board...</div>';
    echo '<meta http-equiv="refresh" content="2;url=/' . htmlspecialchars($board->getBoardNameID()) . '/">';
    exit;
}


$currentJson = json_encode($board->getConf(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Board Config</title>
    <link rel="stylesheet" href="/static/css/futaclone.css">
</head>

<body>
    <div class="postblock">
        <h2>Edit Config for /<?= htmlspecialchars($board->getBoardNameID()) ?>/</h2>
        <form method="post">
            <textarea name="configJson" rows="30" cols="100"><?= htmlspecialchars($currentJson) ?></textarea><br><br>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>

</html>