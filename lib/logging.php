<?php
/*
 *  name as implies. this logs things to a text file.
 */

$globalConf = require __DIR__ . "/../conf.php";
$loggingDir = $globalConf['logDir'];

$auditLogPath = $loggingDir . "audit.log";
$errorLogPath = $loggingDir . "error.log";

// Check if logging directory exists
if (!file_exists($loggingDir)) {
    if (!@mkdir($loggingDir, 0750, true)) {
        // directory creation failed — show error and exit
        http_response_code(500);
        echo "<h1>Logging Error</h1>";
        echo "<p>Failed to create logging directory: <code>$loggingDir</code></p>";
        echo "<p>Please change the conf.php <code>logDir</code> or make the parent directory writable by the web server.</p>";
        exit;
    }
}

// Check if it's writable
if (!is_writable($loggingDir)) {
    http_response_code(500);
    echo "<h1>Logging Error</h1>";
    echo "<p>Logging directory exists but is not writable: <code>$loggingDir</code></p>";
    echo "<p>Check permissions and ensure the web server can write to it.</p>";
    exit;
}

foreach ([$auditLogPath, $errorLogPath] as $file) {
    if (!file_exists($file)) {
        touch($file);
    }
}

function logError($board, $errorMessage)
{
    global $errorLogPath;
    $logs = fopen($errorLogPath, 'a');
    fwrite($logs, "[" . date('Y-m-d H:i:s', time()) . "] board: " . boardIDToName($board->getId()) . " boardID: " . $board->getId() . " >> " . $errorMessage . "\n");
    fclose($logs);
}
function logAudit($board, $auditMessage)
{
    global $auditLogPath;
    $logs = fopen($auditLogPath, 'a');
    fwrite($logs, "[" . date('Y-m-d H:i:s', time()) . "] board: " . boardIDToName($board->getId()) . " boardID: " . $board->getId() . " >> " . $auditMessage . "\n");
    fclose($logs);
}
