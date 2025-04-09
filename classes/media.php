<?php
require_once __DIR__ . '/file.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/postMagic.php';
require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/repos/repoFile.php';

class MediaDataClass
{
    private string $md5;
    private string $storedFileName;
    private string $mime;
    private int $size;
    private string $sizeFormated;
    private bool $hasThumb;
    private string $thumbExt;
    private string $status;
    private int $id;

    public function __construct(string $md5, string $storedFileName, string $mime, int $size, string $sizeFormated = "0B ?x?", bool $hasThumbnail = false, string $thumbnailExt = "x_x", string $status = "error", int $uid = -1)
    {
        $this->md5 = $md5;
        $this->storedFileName = $storedFileName;
        $this->mime = $mime;
        $this->size = $size;
        $this->hasThumb = $hasThumbnail;
        $this->thumbExt = $thumbnailExt;
        $this->status = $status;
        $this->id = $uid;
    }

    public function __tostring()
    {
        $str = "";
        $str .= "md5: " . $this->md5 . "\n";
        $str .= "storedName: " . $this->storedFileName . "\n";
        $str .= "mime: " . $this->mime . "\n";
        $str .= "size: " . $this->size . "\n";

        return nl2br((string) $str);
    }

    /* getters */
    public function getMD5(): string
    {
        return $this->md5;
    }

    public function getStoredFileName(): string
    {
        return $this->storedFileName;
    }

    public function getMime(): string
    {
        return $this->mime;
    }
    public function getSize(): int
    {
        return $this->size;
    }
    public function getSizeFormated(): string
    {
        return $this->sizeFormated;
    }
    public function hasThumbnail(): bool
    {
        return $this->hasThumb;
    }

    public function getThumbnailExt(): string
    {
        return $this->thumbExt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function getID(): int
    {
        return $this->id;
    }

    /* setters */
    public function setID(int $id): void
    {
        $this->id = $id;
    }

    public function setStoredFileName($name): void
    {
        $this->storedFileName = $name;
    }
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function setSize(int $s): void
    {
        $this->size = $s;
    }
    public function setSizeFormated(string $s): void
    {
        $this->sizeFormated = $s;
    }

    public function setThumbnail(bool $hasThumb): void
    {
        $this->hasThumb = $hasThumb;
    }

    public function setThumbnailExt(string $ext): void
    {
        $this->thumbExt = $ext;
    }
    /*******/
}
