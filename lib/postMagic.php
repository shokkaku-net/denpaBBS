<?php
require_once __DIR__ . '/../classes/repos/repoPost.php';
require_once __DIR__ . '/common.php';

/*
 *  post magic has things that are needed by post to get up and working. tripcodes.
 *  post sorting.
 */

// can return null if none is found
function getExtensionByMimeType($mimeType): string
{
    $mimeMap = [
        // Images
        'image/jpeg' => '.jpg',
        'image/png' => '.png',
        'image/gif' => '.gif',
        'image/webp' => '.webp',
        'image/svg+xml' => '.svg',
        'image/tiff' => '.tiff',
        'image/bmp' => '.bmp',
        'image/vnd.microsoft.icon' => '.ico',
        'image/heic' => '.heic',

        // Video
        'video/mp4' => '.mp4',
        'video/x-msvideo' => '.avi',
        'video/x-ms-wmv' => '.wmv',
        'video/mpeg' => '.mpeg',
        'video/quicktime' => '.mov',
        'video/webm' => '.webm',
        'video/ogg' => '.ogv',
        'video/x-flv' => '.flv',

        // Audio
        'audio/mpeg' => '.mp3',
        'audio/ogg' => '.ogg',
        'audio/wav' => '.wav',
        'audio/x-aac' => '.aac',
        'audio/x-ms-wma' => '.wma',
        'audio/flac' => '.flac',
        'audio/x-midi' => '.midi',

        // Documents
        'application/pdf' => '.pdf',
        'application/msword' => '.doc',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
        'application/vnd.ms-excel' => '.xls',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
        'application/vnd.ms-powerpoint' => '.ppt',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
        'text/plain' => '.txt',
        'text/csv' => '.csv',
        'text/html' => '.html',
        'application/rtf' => '.rtf',
        'application/xml' => '.xml',
        'application/json' => '.json',

        // Archives
        'application/zip' => '.zip',
        'application/x-rar-compressed' => '.rar',
        'application/x-7z-compressed' => '.7z',
        'application/x-tar' => '.tar',
        'application/x-bzip' => '.bz',
        'application/x-bzip2' => '.bz2',
        'application/x-gzip' => '.gz',

        // Fonts
        'font/otf' => '.otf',
        'font/ttf' => '.ttf',
        'font/woff' => '.woff',
        'font/woff2' => '.woff2',

        // Others
        'application/vnd.adobe.flash.movie' => '.swf',
        'application/x-shockwave-flash' => '.swf',
        'application/vnd.android.package-archive' => '.apk',
        'application/x-apple-diskimage' => '.dmg',

        // Images
        'image/x-canon-cr2' => '.cr2',
        'image/x-canon-crw' => '.crw',
        'image/x-epson-erf' => '.erf',
        'image/x-fuji-raf' => '.raf',
        'image/x-nikon-nef' => '.nef',
        'image/x-olympus-orf' => '.orf',
        'image/x-panasonic-raw' => '.raw',
        'image/x-sony-arw' => '.arw',

        // eBooks
        'application/epub+zip' => '.epub',
        'application/x-mobipocket-ebook' => '.mobi',
        'application/x-ms-reader' => '.lit',

        // 3D Models
        'model/stl' => '.stl',
        'model/obj' => '.obj',
        'model/gltf-binary' => '.glb',
        'model/gltf+json' => '.gltf',

        // Scripts
        'application/javascript' => '.js',
        'application/x-python-code' => '.py',
        'application/x-ruby' => '.rb',
        'text/x-c' => '.c',
        'text/x-csharp' => '.cs',
        'text/x-c++' => '.cpp',
        'text/x-java-source' => '.java',
        'text/x-php' => '.php',
        'application/x-perl' => '.pl',
        'application/x-shellscript' => '.sh',

        // Markup/Stylesheets
        'text/css' => '.css',
        'text/markdown' => '.md',
        'application/xhtml+xml' => '.xhtml',
        'text/xml' => '.xml',

        // Fonts
        'application/x-font-ttf' => '.ttf',
        'application/x-font-otf' => '.otf',
        'application/font-woff' => '.woff',
        'application/font-woff2' => '.woff2',

        // Executables
        'application/x-msdownload' => '.exe',
        'application/x-ms-installer' => '.msi',

        // Add more MIME types as needed
    ];

    return $mimeMap[$mimeType] ?? null; // Return false if MIME type is not found
}

function isIPBanned($ip): bool
{
    return false;
}

//tripcode put this in it own lib file.
/*
 *    return [
 *       'hash' => $fullHash,
 *       'tripcode' => $tripcode
 *    ];
 */
function genTripcode(string $password, string $salt = ''): array
{
    if (empty($password)) {
        return [];
    }

    // Determine tripcode type
    $hashType = '';
    if (substr($password, 0, 2) === '##') {
        $hashType = 'secure';
        $password = substr($password, 2);
        if ($password == '') {
            return ['tripcode' => '##'];
        }
    } elseif (substr($password, 0, 1) === '#') {
        $hashType = 'regular';
        $password = substr($password, 1);
        if ($password == '') {
            return ['tripcode' => '#'];
        }
    }

    // Traditional tripcodes use Shift_JIS
    $password = mb_convert_encoding($password, 'Shift_JIS', 'UTF-8');

    // Set to Futaba-type salt if regular
    if ($hashType === 'regular') {
        $salt = substr($password . 'H.', 1, 2);
    }

    $salt = preg_replace('/[^\.-z]/', '.', $salt);  // Clean up the salt
    $salt = strtr($salt, ':;<=>?@[\\]^_`', 'ABCDEFGabcdef');  // Adjust the salt

    // Generate the tripcode
    if ($hashType === 'regular') {
        $fullHash = crypt($password, $salt);
    } else {
        $fullHash = hash('sha256', $password . $salt);
    }

    // Determine tripcode display
    $tripcode = substr($fullHash, -10);
    if ($hashType === 'regular') {
        $tripcode = '◆' . $tripcode;
    } elseif ($hashType === 'secure') {
        $encodedHash = strtr(base64_encode(hex2bin($fullHash)), '+/', '.-');
        $tripcode = '★' . substr($encodedHash, 0, 10); // Use the first 10 characters for the tripcode
    }

    return [
        'hash' => $fullHash,
        'hashType' => $hashType,
        'tripcode' => $tripcode
    ];
}

function splitTextAtTripcodePass(string $text): array
{
    //replace reserved characters.
    $text = str_replace(["◆", "★"], ["◇", "☆"], $text);
    $pos = strpos($text, '#');

    if ($pos !== false) {
        $name = substr($text, 0, $pos);
        $tripcodePassword = substr($text, $pos);
        return [$name, $tripcodePassword];
    } else {
        return [$text, ''];
    }
}

// get the tripcode that is after a name "anon◆extractThisPart"
function extractTripCode(string $text): string
{
    return "";
}

function postResolve($conf, $postID)
{
    $POSTREPO = PostRepoClass::getInstance();
    $boardName = boardIDToName($conf['boardID']);
    $post = $POSTREPO->loadPostByID($conf, $postID);
    if (is_null($post)) {
        return "#";
    }
    $threadID = $post->getThreadID();
    if (is_null($threadID)) {
        return "#";
    }

    return WEBPATH . $boardName . '/thread/' . $threadID . '/#p' . $postID;
}

function sortPostsByTimeDesending(&$posts)
{
    usort($posts, function ($a, $b) {
        return $a->getUnixTime() - $b->getUnixTime();
    });
}

function sortPostsByTimeAesending(&$posts)
{
    usort($posts, function ($a, $b) {
        return $b->getUnixTime() - $a->getUnixTime();
    });
}

function sortThreadByBump(&$threads)
{
    usort($threads, function ($a, $b) {
        return $b->getLastBumpTime() - $a->getLastBumpTime();
    });
}
function sortThreadByDateCreated(&$threads)
{
    usort($threads, function ($a, $b) {
        return $b->getOPPost()->getUnixTime() - $a->getOPPost()->getUnixTime();
    });
}

function filterThreadsByKeyword(array $threads, string $keyword, bool $caseSensitive): array
{
    return array_filter($threads, function ($thread) use ($keyword, $caseSensitive) {
        $comment = $thread->getOPPost()->getComment();
        if ($caseSensitive) {
            return strpos($comment, $keyword) !== false;
        } else {
            return stripos($comment, $keyword) !== false;
        }
    });
}