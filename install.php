<?php

require_once __DIR__ . '/lib/common.php';

if (file_exists(__DIR__ . "/.install_bypass")) {
	drawErrorPageAndDie("delete .install_bypass and comer back");
}

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);



$status = null;
$messages = [];
$errors = [];

require_once __DIR__ . '/lib/postMagic.php';
$conf = require __DIR__ . '/baseConfig.php';

function checkRequirements(): array
{
	$errors = [];

	// PHP extensions
	if (!extension_loaded('mysqli')) {
		$errors[] = 'Missing PHP extension: mysqli (enable in php.ini)';
	}

	if (!extension_loaded('gd')) {
		$errors[] = 'Missing PHP extension: gd (enable in php.ini)';
	}

	if (!extension_loaded('exif')) {
		$errors[] = 'Missing PHP extension: exif (enable in php.ini)';
	}

	# needed for ffmpeg, video screen shot
	#if (!function_exists('shell_exec')) {
	#	$errors[] = 'shell_exec is disabled';
	#}

	# fix this
	#if (!file_exists('/usr/bin/ffmpeg')) {
	#	$errors[] = 'ffmpeg not found at <code>/usr/bin/ffmpeg</code> (move it in the chroot if on openbsd)';
	#}

	#if (!file_exists('/usr/bin/composer')) {
	#	$errors[] = 'composer not found at <code>/usr/bin/composer</code> (move it in the chroot if on openbsd)';
	#}

	return $errors;
}

$errors = checkRequirements();

function createDB($conn)
{
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
	global $messages;
	global $status;
	global $errors;

	// SQL to create tables
	$sqlCommands = [
		"CREATE TABLE IF NOT EXISTS boards (
			boardID INT AUTO_INCREMENT PRIMARY KEY,
			boardNameID VARCHAR(64) NOT NULL,
			lastPostID INT DEFAULT 0,
			config JSON NOT NULL,
			updatedAt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
		) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
		",

		"CREATE TABLE IF NOT EXISTS posts (
				UID INT AUTO_INCREMENT PRIMARY KEY,
				postID INT NOT NULL,
				boardID INT NOT NULL,
				threadID INT NULL,
				password VARCHAR(16) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
				name VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
				email VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
				subject VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
				comment TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
				ip VARCHAR(45) NOT NULL,
				postTime BIGINT NOT NULL,
				special TEXT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
				FOREIGN KEY (boardID) REFERENCES boards(boardID) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

		"CREATE TABLE IF NOT EXISTS threads (
				threadID INT AUTO_INCREMENT PRIMARY KEY,
				boardID INT NOT NULL,
				lastTimePosted BIGINT NOT NULL,
				opPostID INT,
                status VARCHAR(10) DEFAULT 'active',
				FOREIGN KEY (boardID) REFERENCES boards(boardID) ON DELETE CASCADE ON UPDATE CASCADE
			) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

		"CREATE TABLE IF NOT EXISTS files (
                fileID INT AUTO_INCREMENT PRIMARY KEY,
                postID INT NOT NULL,
                threadID INT NOT NULL,
                boardID INT NOT NULL,
                fileName VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                filePath VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                md5 CHAR(32) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
                FOREIGN KEY (threadID) REFERENCES threads(threadID) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (boardID) REFERENCES boards(boardID) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

		"CREATE TABLE IF NOT EXISTS ipBans (
                banID INT AUTO_INCREMENT PRIMARY KEY,
                ipAddress VARCHAR(45),
                boardID INT NULL,
                reason TEXT,
                category VARCHAR(20),
                isPublic BOOLEAN DEFAULT 0,
                isGlobal BOOLEAN DEFAULT 0,
                createdAt BIGINT NOT NULL,
                expiresAt BIGINT NULL,
                INDEX idx_ip (ipAddress),
                INDEX idx_board (boardID)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

		"CREATE TABLE IF NOT EXISTS fileBans (
                banID INT AUTO_INCREMENT PRIMARY KEY,
                fileHash VARCHAR(64),
                isPerceptual BOOLEAN,
                reason TEXT,
                category VARCHAR(20),
                boardID INT NULL,
                isPublic BOOLEAN DEFAULT 0,
                isGlobal BOOLEAN DEFAULT 0,
                createdAt BIGINT NOT NULL,
                INDEX idx_file_hash (fileHash)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",

		"CREATE TABLE IF NOT EXISTS stringBans (
                banID INT AUTO_INCREMENT PRIMARY KEY,
                bannedString TEXT,
                reason TEXT,
                boardID INT NULL,
                category VARCHAR(20),
                isPublic BOOLEAN DEFAULT 0,
                isGlobal BOOLEAN DEFAULT 0,
                createdAt BIGINT NOT NULL,
                INDEX idx_banned_string (bannedString(255))
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
	];
	// Execute each SQL command
	foreach ($sqlCommands as $sql) {
		if ($conn->query($sql) === TRUE) {
			$messages[] = "Table created successfully<br>";
		} else {
			$status = "error";
			$messages[] = "Error creating table: " . $conn->error;
			throw new Exception("Error creating table: " . $conn->error);
		}
	}
}

function detectAndCreateLogDir(): string
{
	global $errors;

	$baseDir = realpath(__DIR__ . '/../');
	$logDir = $baseDir . '/bbslog/';

	if (!file_exists($logDir)) {
		if (!@mkdir($logDir, 0755, true)) {
			$errors[] = "Failed to create log directory at <code>$logDir</code>. Check permissions.";
			return '';
		}
	}

	if (!is_writable($logDir)) {
		$errors[] = "Log directory <code>$logDir</code> is not writable.";
		return '';
	}

	return $logDir;
}

function updateConf()
{
	global $conf, $status, $errors;

	// set DB config from POST
	$conf['mysqlDB']['host'] = $_POST['host'];
	$conf['mysqlDB']['port'] = $_POST['port'];
	$conf['mysqlDB']['username'] = $_POST['username'];
	$conf['mysqlDB']['password'] = $_POST['password'];
	$conf['mysqlDB']['databaseName'] = $_POST['databaseName'];

	$detectedDomain = $_SERVER['HTTP_HOST'] ?? 'localhost';
	$conf['domain'] = $_POST['domain'] ?? $detectedDomain;

	$trip = genTripcode($_POST['adminPassword'], $conf['tripcodeSalt']);
	$conf['adminHashes'][] = [$trip['hash'], 'admin'];



	// set full path to ./threads
	$threadsDir = realpath(__DIR__ . '/threads');
	if (!$threadsDir) {
		$threadsDir = __DIR__ . '/threads';
		if (!is_dir($threadsDir)) {
			mkdir($threadsDir, 0755, true);
		}
	}
	$conf['threadsDir'] = rtrim($threadsDir, '/') . '/';


	// attempt to create and assign logDir
	$logDir = detectAndCreateLogDir();
	if ($logDir !== '') {
		$conf['logDir'] = rtrim($logDir, '/') . '/';
	}

	$newConf = '<?php return ' . var_export($conf, true) . ';';
	if (file_put_contents('conf.php', $newConf) === false) {
		$status = "error";
		$errors[] = "Failed to write configuration.";
	}
}
function validateInstallInputs(): array
{
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$requiredFields = ['host', 'port', 'username', 'password', 'databaseName', 'domain', 'adminPassword'];
		$missing = [];

		foreach ($requiredFields as $field) {
			if (empty($_POST[$field])) {
				$missing[] = $field;
			}
		}
		return $missing;
	}
	return [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($errors)) {
	$status = "error";
	$messages[] = "Cannot install — missing required system dependencies.";
} elseif (file_exists(__DIR__ . '/conf.php')) {
	$status = "error";
	$messages[] = "config install detected. please remove install.php or clean up config files";
} elseif (!empty($list = validateInstallInputs())) {
	if (!empty($missing)) {
		$status = "error";
		foreach ($missing as $field) {
			$messages[] = "Missing required field: $field";
		}
	}
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {

	updateConf();

	try {
		//try connect and create db
		$connection = new mysqli($_POST['host'], $_POST['username'], $_POST['password'], $_POST['databaseName']);
		createDB($connection);
		$connection->close();
		$messages[] = "Database successfully set up!";

		//create post and thread
		require_once __DIR__ . '/lib/adminControl.php';
		require_once __DIR__ . '/classes/repos/repoPost.php';
		require_once __DIR__ . '/classes/repos/repoThread.php';

		$POSTREPO = PostRepoClass::getInstance();
		$THREADREPO = ThreadRepoClass::getInstance();

		$time = time();
		$board = createBoard("my first board", "a place to test stuff", "intro", false);

		$thread = new threadClass($board->getConf(), $time);
		$post = new PostDataClass(
			$board->getConf(),
			"System",
			"",
			"HelloWorld!",
			'Thank you for installing DenpaBBS!!<br> I have put in a lot of work to make this software be as fluent as possible.<br>Please consider following and leaving a star on my <a href="https://github.com/shokkaku-net/denpaBBS">repo</a>. It means a lot.',
			"",
			$time,
			"127.0.0.1",
			1
		);

		$POSTREPO->createPost($board->getConf(), $post);
		$THREADREPO->createThread($board->getConf(), $thread, $post);

		// create the custom route file 
		$customRoutePath = __DIR__ . '/customRoute.php';
		if (!file_exists($customRoutePath)) {
			$customRouteTemplate = <<<'PHP'
		<?php
		// This is for the site admin to define optional custom routes.
		// If a match is found, handle it and call exit; otherwise the default routing will continue.

		$requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
		$path = trim($requestPath, '/');
		$customPhpPath = BASEDIR . '/main/custom/';

		if (preg_match('#^connection/([0-9_]+)/?$#', $path, $m)) {
			$_GET['wave'] = $m[1];
			require $customPhpPath . "wire.php";
			exit;

		}

		// If nothing is matched, do nothing — the default router will continue.
				
		PHP;

			file_put_contents($customRoutePath, $customRouteTemplate);
		}

		// create the custom route file 
		$customPhpPath = __DIR__ . '/main/custom/wire.php';
		if (!file_exists($customPhpPath)) {
			$customPhpTemplate = <<<'PHP'
		<?php

		$wave = isset($_GET['wave']) ? (int) $_GET['wave'] : 0;
		$limit = min($wave, 100);

		for ($i = 0; $i < $limit; $i++) {
			echo "<h1>電波がビンビン来てる感じ。</h1>";
		}
		
		PHP;

			file_put_contents($customPhpPath, $customPhpTemplate);
		}

		$messages[] = "Intro board successfully created!";
		$status = "success";
	} catch (Exception $e) {
		$status = "error";
		$messages[] = "Installation failed: " . $e->getMessage();

	}
}

require 'vendor/autoload.php';

use duncan3dc\Laravel\BladeInstance;
$blade = new BladeInstance(__DIR__ . "/views", __DIR__ . "/cache");

echo $blade->render("install", [
	"conf" => $conf,
	"status" => $status,
	"messages" => $messages,
	"errors" => $errors,
]);
