<?php
require_once __DIR__ . '/DBConnection.php';
require_once __DIR__ . '/../board.php';
require_once __DIR__ . '/../../lib/common.php';

class BoardRepoClass
{
    private static $instance = null;
    private $db;
    private $loadedBoards = [];

    private function __construct()
    {
        $this->db = DatabaseConnection::getInstance(); // must be PDO
    }

    private function __clone()
    {
    }
    public function __wakeup()
    {
        throw new Exception("Unserialization is not allowed.");
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new BoardRepoClass();
        }
        return self::$instance;
    }

    public function createBoard(boardClass $board): bool
    {
        $conf = $board->getConf();
        $boardNameID = $conf['boardNameID'] ?? 'unknown';
        $jsonConf = json_encode($conf, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lastPostID = $board->getLastPostID();

        $stmt = $this->db->prepare("INSERT INTO boards (boardNameID, lastPostID, config) VALUES (?, ?, ?)");
        $stmt->execute([$boardNameID, $lastPostID, $jsonConf]);

        $boardID = (int) $this->db->insert_id;
        $board->setBoardID($boardID); // set ID on the object

        // Optional: inject boardID into conf array
        $conf['boardID'] = $boardID;
        $board->setConf($conf);

        $this->loadedBoards[$boardID] = $board;
        return true;
    }

    public function updateBoard(boardClass $board): bool
    {
        $conf = $board->getConf();
        $boardID = $board->getId();
        $boardNameID = $conf['boardNameID'] ?? 'unknown';
        $jsonConf = json_encode($conf, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        $lastPostID = $board->getLastPostID();

        $stmt = $this->db->prepare("UPDATE boards SET boardNameID = ?, lastPostID = ?, config = ? WHERE boardID = ?");
        return $stmt->execute([$boardNameID, $lastPostID, $jsonConf, $boardID]);
    }

    public function deleteBoardByID(int $boardID): bool
    {
        $stmt = $this->db->prepare("DELETE FROM boards WHERE boardID = ?");
        $stmt->execute([$boardID]);
        unset($this->loadedBoards[$boardID]);
        return true;
    }

    public function loadBoardByID(int $boardID): ?boardClass
    {
        if (isset($this->loadedBoards[$boardID])) {
            return $this->loadedBoards[$boardID];
        }

        $stmt = $this->db->prepare("SELECT * FROM boards WHERE boardID = ?");
        $stmt->bind_param("i", $boardID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $conf = json_decode($row['config'], true);
        $board = new boardClass($row['boardID'], (int) $row['lastPostID']);
        $board->setConf($conf);
        $this->loadedBoards[$boardID] = $board;

        return $board;
    }
    public function loadBoardByNameID(string $boardNameID): ?boardClass
    {
        $stmt = $this->db->prepare("SELECT boardID, boardNameID, lastPostID, config FROM boards WHERE boardNameID = ?");
        $stmt->bind_param("s", $boardNameID);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        if (!$row) {
            return null;
        }

        $conf = json_decode($row['config'], true);
        $board = new boardClass((int) $row['boardID'], (int) $row['lastPostID']);
        $board->setConf($conf);
        $board->boardNameID = $row['boardNameID'];
        $this->loadedBoards[$board->getId()] = $board;

        return $board;
    }


    public function loadBoards(): array
    {
        $boards = [];

        $stmt = $this->db->query("SELECT boardID, boardNameID, lastPostID, config FROM boards");
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $conf = json_decode($row['config'], true);
            $board = new boardClass($row['boardID'], (int) $row['lastPostID']);
            $board->setConf($conf);
            $boards[] = $board;
            $this->loadedBoards[$board->getId()] = $board;
        }

        return $boards;
    }
}
