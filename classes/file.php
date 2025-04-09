<?php

class FileDataClass
{
    private int $fileID = -1;
    private int $postUID = -1;
    private string $originalName;
    private MediaDataClass $media;

    public function __construct(string $originalName, ?MediaDataClass $media = null, int $postUID = -1, int $fileID = -1)
    {
        $this->originalName = $originalName;
        $this->media = $media;
        $this->postUID = $postUID;
        $this->fileID = $fileID;
    }

    public function __toString()
    {
        $str = "";

        $str .= "orginal name : $this->originalName\n";
        if ($this->media) {
            $str .= "$this->media\n";
        }
        return nl2br((string) $str);
    }
    /* getters */
    public function getOriginalName(): string
    {
        return $this->originalName;
    }

    public function getMedia(): MediaDataClass
    {
        return $this->media;
    }

    public function getPostUID(): int
    {
        return $this->postUID;
    }

    /* setters */
    public function setPostUID(int $uid): void
    {
        $this->postUID = $uid;
    }

    public function getFileID(): int
    {
        return $this->fileID;
    }

    public function setFileID(int $id): void
    {
        $this->fileID = $id;
    }

    /******/
    public function Validate($conf): bool
    {
        // do some hook stuff here.
        if (is_null($this->media) || $this->media->getSize() > $conf['maxFileSize']) {
            return false;
        }
        return true;
    }
}
