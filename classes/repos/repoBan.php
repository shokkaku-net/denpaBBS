<?php
require_once  __DIR__ .'/DBConnection.php';
require_once __DIR__ .'/../../lib/common.php';

class BanRepoClass{
    private function __clone() {}
    public function __wakeup() { throw new Exception("Unserialization of AuthClass instances is not allowed.");}
    private $db;
    private static $instance = null;

    private function __construct() {
        $this->db = DatabaseConnection::getInstance();
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new BanRepoClass();
        }
        return self::$instance;
    }


    public function banIP($boardID, $ip, $reason, $expireTime, $rangeBaned="none", $isGlobal=false, $isPublic=false, $category="none"){
        global $globalConf;

        $time = time();
        
        if($rangeBaned == "range1"){
            $ipParts = explode('.', $ip);
            $ip = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] . '.*';
        }elseif($rangeBaned == "range2"){
            $ipParts = explode('.', $ip);
            $ip = $ipParts[0] . '.' . $ipParts[1] . '.*.*';
        }

        $insertQuery = "INSERT INTO ipBans (boardID, ipAddress, reason, category, createdAt, expiresAt, isGlobal, isPublic) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->bind_param("isssiiii", $boardID, $ip, $reason, $category, $time, $expireTime, $isGlobal, $isPublic);
        $stmt->execute();

        return true;
    }
    public function banFile($boardID, $fileHash, $reason, $isPreceptual=false, $isGlobal=false, $isPublic=false, $category="none"){
        global $globalConf;

        $time = time();

        $insertQuery = "INSERT INTO fileBans (boardID, fileHash, isPerceptual, reason, category, isGlobal, isPublic, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->bind_param("isissiii", $boardID, $fileHash, $isPreceptual, $reason, $category, $isGlobal, $isPublic, $time);
        $stmt->execute();

        return true;
    }
    public function banDomain($boardID, $domain, $reason, $isGlobal=false, $isPublic=false, $category="none"){
        global $globalConf;

        $time = time();

        $insertQuery = "INSERT INTO stringBans (boardID, bannedString, reason, category, isGlobal, isPublic, createdAt) VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->db->prepare($insertQuery);
        $stmt->bind_param("isssiii", $boardID, $domain, $reason, $category, $isGlobal, $isPublic, $time);
        $stmt->execute();

        return true;
    }

    public function isIpBanned($boardID, $ip) {
        $ipParts = explode('.', $ip);
        $range = $ipParts[0] . '.' . $ipParts[1] . '.' . $ipParts[2] .'.*';
        $range2 = $ipParts[0] . '.' . $ipParts[1] . '.*.*';

        $query = "SELECT * FROM ipBans WHERE (boardID = ? OR isGlobal = 1) AND (ipAddress = ? OR ipAddress = ? or ipAddress = ?)";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isss", $boardID, $ip, $range, $range2);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            if ($this->checkExpiration($row['expiresAt'])) {
                return true;
            }
        }

        return false;
    }
    public function isFileBanned($boardID, $fileHash, $isPreceptual=false) {
        $query = "SELECT * FROM fileBans WHERE (boardID = ? OR isGlobal = 1) AND fileHash = ? AND isPerceptual = ?";
        $stmt = $this->db->prepare($query);
        $stmt->bind_param("isi", $boardID, $fileHash, $isPreceptual);
        $stmt->execute();
        $result = $stmt->get_result();


        if ($result->num_rows > 0) {
            $stmt->close();
            return true;
        }

        $stmt->close();

        return false;
    }
    public function isDomainBanned($boardID, $domain, $globalCheck = false) {
        if ($globalCheck) {
            // Query to check for a global ban
            $query = "SELECT * FROM stringBans WHERE bannedString = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("s", $domain);
        } else {
            // Query to check for a specific board or global ban
            $query = "SELECT * FROM stringBans WHERE (boardID = ? OR isGlobal = 1) AND bannedString = ?";
            $stmt = $this->db->prepare($query);
            $stmt->bind_param("is", $boardID, $domain);
        }
    
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $stmt->close();
            return true;
        }
    
        $stmt->close();
        return false;
    }
    public function loadCategories() {  
        $query = "SELECT DISTINCT category FROM ipBans";
        $result = $this->db->query($query);
    
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row['category'];
        }
    
        return $categories;
    }
    private function checkExpiration($expiresAt) {
        if ($expiresAt === null) {
            return true;
        }
        return time() < $expiresAt;
    }
    public function getAllPerceptualHashes() {
        $query = "SELECT fileHash FROM fileBans WHERE isPerceptual = 1";
        $stmt = $this->db->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();
        $hashes = [];
        
        while ($row = $result->fetch_assoc()) {
            $hashes[] = $row['fileHash'];
        }
        
        $stmt->close();
        return $hashes;
    }
}