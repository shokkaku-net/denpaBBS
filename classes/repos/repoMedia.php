<?php

class MediaRepoClass
{
    private function __clone()
    {
    }
    public function __wakeup()
    {
        throw new Exception("Unserialization not allowed.");
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
            self::$instance = new MediaRepoClass();
        }
        return self::$instance;
    }

    public function findByMD5(string $md5): ?MediaDataClass
    {
        $stmt = $this->db->prepare("SELECT * FROM media WHERE md5 = ? LIMIT 1");
        $stmt->bind_param("s", $md5);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();
        $media = new MediaDataClass(
            $row['md5'],
            $row['storedFileName'],
            $row['mime'],
            $row['size'],
            $row['sizeFormated'],
            (bool) $row['hasThumbnail'],
            $row['thumbnailExt'] ?? '',
            $row['status'],
            (int) $row['mediaID']
        );

        return $media;
    }

    public function create($media): int
    {
        $md5 = $media->getMd5();
        $storedFileName = $media->getStoredFileName();
        $mime = $media->getMime();
        $size = $media->getSize();
        $sizeFormated = $media->getSizeFormated();
        $hasThumb = $media->hasThumb();
        $thumbExt = $media->getThumbnailExtention();
        $status = $media->getStatus();
        $stmt = $this->db->prepare("INSERT INTO media (md5, storedFileName, mime, size, sizeFormated, hasThumbnail, thumbnailExt, status) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssissss", $md5, $storedFileName, $mime, $size, $sizeFormated, $hasThumb, $thumbExt, $status);
        $stmt->execute();
        $id = $this->db->insert_id;
        $media->setID($id);

        $stmt->close();
        return $id;
    }

    public function getByID(int $mediaID): ?MediaDataClass
    {
        $stmt = $this->db->prepare("SELECT * FROM media WHERE mediaID = ?");
        $stmt->bind_param("i", $mediaID);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        $stmt->close();

        $media = new MediaDataClass(
            $row['md5'],
            $row['storedFileName'],
            $row['mime'],
            $row['size'],
            $row['sizeFormated'],
            (bool) $row['hasThumbnail'],
            $row['thumbnailExt'] ?? '',
            $row['status'],
            (int) $row['mediaID']
        );

        return $media;
    }
}
