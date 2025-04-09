<?php
require_once __DIR__ . '/DBConnection.php';
require_once __DIR__ . '/../post.php';
require_once __DIR__ . '/../../lib/common.php';

class FileRepoClass
{
    private function __clone()
    {
    }
    public function __wakeup()
    {
        throw new Exception("Unserialization of AuthClass instances is not allowed.");
    }

    private $db;
    private static $instance = null;

    private function __construct()
    {
        $this->db = DatabaseConnection::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new FileRepoClass();
        }
        return self::$instance;
    }
    public function isDuplicateFile($boardConf, $md5)
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM files WHERE md5 = ? AND boardID = ? LIMIT 1");
        $stmt->bind_param("si", $md5, $boardConf['boardID']);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $count = $row['count'];
        $stmt->close();

        return $count > 0;
    }
    public function createFile($boardConf, $file)
    {
        global $globalConf;

        $threadID = $file->getThreadID();
        $boardID = $boardConf['boardID'];
        $postID = $file->getPostID();
        $filePath = str_replace($globalConf['threadsDir'], '', $file->getFilePath());
        $fileName = $file->getFileName();
        $md5 = $file->getMD5();
        //drawErrorPageAndDie($boardID .' '. $threadID.' '. $postID.' '. $filePath.' '. $fileName.' '. $md5);
        $insertQuery = "INSERT INTO files (boardID, threadID, postID, filePath, fileName, md5) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->bind_param("iiisss", $boardID, $threadID, $postID, $filePath, $fileName, $md5);
        $stmt->execute();

        $file->setFileID($this->db->insert_id);
        $stmt->close();
        return true;
    }
    public function loadFilesByPostID($boardConf, $postID)
    {
        global $globalConf;

        $query = "SELECT * FROM files WHERE boardID = ? and postID = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $boardConf['boardID'], $postID);
        $stmt->execute();
        $result = $stmt->get_result();
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $file = new FileDataClass($boardConf, $globalConf['threadsDir'] . $row['filePath'], $row['fileName'], $row['md5'], $row['fileID'], $row['postID'], $row['threadID']);
            $files[] = $file;
        }
        $stmt->close();
        return $files;
    }
    public function loadFilesByThreadID($boardConf, $threadID)
    {
        global $globalConf;

        $query = "SELECT * FROM files WHERE boardID = ? and threadID = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("ii", $boardConf['boardID'], $threadID);
        $stmt->execute();
        $result = $stmt->get_result();
        $files = [];
        while ($row = $result->fetch_assoc()) {
            $file = new FileDataClass($boardConf, $globalConf['threadsDir'] . $row['filePath'], $row['fileName'], $row['md5'], $row['fileID'], $row['postID'], $row['threadID']);
            $files[] = $file;
        }
        $stmt->close();
        return $files;
    }
    public function updateFile($boardConf, $file)
    {
        global $globalConf;

        // why is sqli like this...
        $fileID = $file->getFileID();
        $postID = $file->getFileID();
        $threadID = $file->getFileID();
        $filePath = str_replace($globalConf['threadsDir'], '', $file->getFilePath());
        $fileName = $file->getFileID();
        $md5 = $file->getFileID();
        $query = "UPDATE files SET      boardID = ?, threadID = ?, postID = ?, filePath = ?,
                                        fileName = ?, md5 = ?
                                    WHERE fileID = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param(
            "iiisssi",
            $boardConf['boardID'],
            $threadID,
            $postID,
            $filePath,
            $fileName,
            $md5,
            $fileID
        );
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
    public function deleteFileByID($fileID)
    {
        $query = "DELETE FROM files WHERE fileID = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("i", $fileID);
        $success = $stmt->execute();
        $stmt->close();
        return $success;
    }
}
