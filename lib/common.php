<?php
require_once __DIR__ . '/../classes/repos/repoBoard.php';
/*
 *  this lib has things basicly used all over to get the boards to work. 
 *  reteaving data. page redirects. 
 */

// // idk how to get this working for openbsd...
// function postWebHook($boardID, $threadID, $postID=""){
//     global $globalConf;

//     $url = 'https://'.DOMAIN.WEBPATH.boardIDToName($boardID).'/thread/'.$threadID.'/#p'.$postID;

//     $stream = stream_context_create([
//         'http' => [
//             'method' => 'POST',
//             'header' => 'Content-Type: application/x-www-form-urlencoded',
//             'content' => http_build_query([
//                 'content' => "new post <$url>", 
//             ]),
//         ]
//     ]);

//     file_get_contents($globalConf['webhook'], false, $stream);
// }

function bytesToHumanReadable($size)
{
    if ($size == 0) {
        $format = "";
    } elseif ($size <= 1024) {
        $format = $size . " B";
    } elseif ($size <= (1024 * 1024)) {
        $format = sprintf("%d KB", ($size / 1024));
    } elseif ($size <= (1000 * 1024 * 1024)) {
        $format = sprintf("%.2f MB", ($size / (1024 * 1024)));
    } elseif ($size <= (1000 * 1024 * 1024 * 1024)) {
        $format = sprintf("%.2f GB", ($size / (1024 * 1024 * 1024)));
    } elseif ($size <= (1000 * 1024 * 1024 * 1024 * 1024) || $size >= (1000 * 1024 * 1024 * 1024 * 1024)) {
        $format = sprintf("%.2f TB", ($size / (1024 * 1024 * 1024 * 1024)));
    } else {
        $format = $size . "B";
    }

    return $format;
}
function nameIDToBoardID($nameID)
{
    $repo = BoardRepoClass::getInstance();
    $board = $repo->loadBoardByNameID($nameID);
    return $board ? $board->getBoardID() : '';
}

function boardIDToName(int $boardID): ?string
{
    $BOARDREPO = BoardRepoClass::getInstance();
    $board = $BOARDREPO->loadBoardByID($boardID);
    return $board?->getConf()['boardNameID'] ?? null;
}
function getBoardListing(bool $getUnlisted = false): array
{
    $db = DatabaseConnection::getInstance();
    $listing = [];

    $query = "SELECT boardNameID, config FROM boards";
    $result = $db->query($query);

    while ($row = $result->fetch_assoc()) {
        $config = json_decode($row['config'], true);
        $isUnlisted = $config['unlisted'] ?? false;

        if ($isUnlisted === $getUnlisted) {
            $listing[$row['boardNameID']] = '/' . $row['boardNameID'] . '/';
        }
    }

    return $listing;
}

function getAllBoardConfs(): array
{
    $db = DatabaseConnection::getInstance();
    $boards = [];

    $query = "SELECT boardID, config FROM boards";
    $result = $db->query($query);

    while ($row = $result->fetch_assoc()) {
        $row['config'] = json_decode($row['config'], true) ?? [];
        $boards[] = $row;
    }

    return $boards;
}


function getBoardCount()
{
    $db = DatabaseConnection::getInstance();
    $res = $db->query("SELECT COUNT(*) as count FROM boards");
    return $res->fetch_assoc()['count'] ?? 0;
}

function getBoardConfByID($id): array
{
    $repo = BoardRepoClass::getInstance();
    $board = $repo->loadBoardByID($id);
    if ($board === null) {
        return [];
    }
    return $board->getConf();
}

function getBoardByID($boardID)
{
    $BOARDREPO = BoardRepoClass::getInstance();
    return $BOARDREPO->loadBoardByID($boardID);
}
function redirectToPost($post)
{
    $name = boardIDToName($post->getBoardID());
    $threadID = $post->getThreadID();
    $postID = $post->getPostID();

    $url = WEBPATH . "$name/thread/$threadID/#p$postID";

    header("Location: $url");
    exit;
}
function redirectToThread($thread)
{
    $name = boardIDToName($thread->getBoardID());
    $threadID = $thread->getThreadID();

    $url = WEBPATH . "$name/thread/$threadID/";

    header("Location: $url");
    exit;
}
function redirectToBoard($board)
{
    $name = boardIDToName($board->getBoardID());
    $url = WEBPATH . "$name";

    header("Location: $url");
    exit;
}
function redirectToCatalog($board, $sort, $keyword, $case)
{
    $name = boardIDToName($board->getBoardID());
    $url = WEBPATH . "$name/catalog/";

    $queryParams = http_build_query(['sort' => $sort, 'keyword' => $keyword, 'case' => $case]);
    $url .= '?' . $queryParams;

    header("Location: $url");
    exit;
}
function redirectToAdmin($board)
{
    $name = boardIDToName($board->getBoardID());
    $url = WEBPATH . "$name/admin";

    header("Location: $url");
    exit;
}
function redirectToHome()
{
    $url = WEBPATH;

    header("Location: $url");
    exit;
}
function drawErrorPageAndDie($txt)
{
    $html = '
	<!DOCTYPE html>
	<html lang="en">
	<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Error recived</title>
	<style>
		body {
			background-color: #d0f0c0;
			font-family: Arial, sans-serif;
		}
		.postblock {
			padding: 20px;
			background-color: #ffcccc;
			border: 2px solid #ff0000;
			margin: 10px 0;
			text-align: center;
		}
	</style>
	</head>
	<body>

	<div class="postblock">
		<p>';
    if (is_array($txt)) {
        print_r($txt);
    } else {
        echo ($txt);
    }
    $html = '</p>
	</div>

	</body>
	</html>';
    echo $html;
    die();
}
function getBoardFromRequest($allowNull = false)
{
    $BOARDREPO = BoardRepoClass::getInstance();

    // Try POST first
    if (isset($_POST['boardID']) && is_numeric($_POST['boardID'])) {
        $boardID = (int) $_POST['boardID'];
    } elseif (isset($_GET['boardNameID'])) {
        // Lookup boardID by boardNameID
        $boardNameID = $_GET['boardNameID'];
        $board = $BOARDREPO->loadBoardByNameID($boardNameID);
        if ($board) {
            return $board;
        }
        $boardID = null;
    } else {
        $boardID = null;
    }

    if ($boardID === null) {
        if (!$allowNull) {
            drawErrorPageAndDie("you must have a boardID or boardNameID");
        }
        return null;
    }

    $board = $BOARDREPO->loadBoardByID($boardID);
    if (!$board && !$allowNull) {
        drawErrorPageAndDie("board with the boardID of \"$boardID\" does not exist");
    }

    return $board;
}
function extractUniqueDomainsFromComment($comment)
{
    $regexUrl = '/(https?:\/\/[^\s]+)/';
    preg_match_all($regexUrl, $comment, $matches);
    $urls = $matches[0];
    $domains = [];

    foreach ($urls as $url) {
        $domains[] = parse_url($url, PHP_URL_HOST);
    }

    // Remove duplicate domains
    $uniqueDomains = array_unique($domains);
    return $uniqueDomains;
}
function durationToUnixTime($duration)
{
    $starttime = $_SERVER['REQUEST_TIME'];

    $durationWeeks = preg_match("/(\d+)w/", $duration, $matchWeeks) ? (int) $matchWeeks[1] : 0;
    $durationDays = preg_match("/(\d+)d/", $duration, $matchDays) ? (int) $matchDays[1] : 0;
    $durationHours = preg_match("/(\d+)h/", $duration, $matchHours) ? (int) $matchHours[1] : 0;
    $durationMinutes = preg_match("/(\d+)min/", $duration, $matchMinutes) ? (int) $matchMinutes[1] : 0;

    return $starttime + ($durationWeeks * 604800) + ($durationDays * 86400) + ($durationHours * 3600) + ($durationMinutes * 60);
}