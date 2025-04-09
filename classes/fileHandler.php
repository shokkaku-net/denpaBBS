<?php

require_once __DIR__ . '/repos/repoFile.php';
require_once __DIR__ . '/repos/repoBan.php';


class fileHandlerClass
{
    private $config;
    private $disableProssesing;
    public function __construct($config)
    {
        $this->config = $config;
    }

    public function getFilesFromPostRequest(?int $maxFiles = null): array
    {
        $fileConf = $this->config['fileConf'];
        $filesGotten = [];
        $processedFilesCount = 0;
        $fileLimit = $maxFiles ?? $fileConf['maxFiles'];
        $BANREPO = BanRepoClass::getInstance();



        if (!isset($_FILES['upfile'])) {
            return $filesGotten;
        }

        // Loop through each file
        foreach ($_FILES['upfile']['error'] as $key => $error) {
            if ($error != UPLOAD_ERR_OK) {
                continue;
            }
            // Stop processing if the maximum number of files has been reached
            if ($processedFilesCount >= $fileLimit) {
                break;
            }
            $tmpName = $_FILES['upfile']['tmp_name'][$key];
            $fileName = $_FILES['upfile']['name'][$key];
            $fileSize = $_FILES['upfile']['size'][$key];

            // Validate file size.
            if ($fileSize > $fileConf['maxFileSize']) {
                echo "Error: File {$fileName} is too large. Maximum file size is " . $fileConf['maxFileSize'];
                continue;
            }

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $realMimeType = $finfo->file($tmpName);

            // Validate file type
            if (!in_array($realMimeType, $fileConf['allowedMimeTypes'])) {
                echo "Error: File {$fileName} type is not allowed.";
                continue;
            }

            $md5 = md5_file($tmpName);
            if ($BANREPO->isFileBanned(getBoardFromRequest()->getId(), $md5, false)) {
                drawErrorPageAndDie("banned file uploaded");
            }

            $filesGotten[] = new fileDataClass($this->config, $tmpName, $fileName, $md5);
            $processedFilesCount++;
        }

        foreach ($filesGotten as $file) {
            $tmpName = $file->getFilePath();

            $finfo = new finfo(FILEINFO_MIME_TYPE);
            $realMimeType = $finfo->file($tmpName);

            $fileExtention = getExtensionByMimeType($realMimeType);
            $fileNameOnDisk = uniqid() . $fileExtention;
            $newFilePath = __DIR__ . "/../threads/staging/" . $fileNameOnDisk;
            move_uploaded_file($tmpName, $newFilePath);
            $file->setFilePath($newFilePath);
        }

        return $filesGotten;
    }

    public function createThumbnail($file, $isOp)
    {
        // Quality and dimensions settings
        $imgConf = $this->config['fileConf'];

        $quality = $imgConf['compressQuality'];
        $maxWidth = $imgConf['thumNailWidth'];
        $maxHeight = $imgConf['thumNailHight'];
        $backgroundRGB = sscanf($imgConf['backgroundColor'], "#%02x%02x%02x");
        if ($isOp == false) {
            $maxWidth = (int) $maxWidth / 2;
            $maxHeight = (int) $maxHeight / 2;
        }

        $filePath = $file->getFilePath();

        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $fileType = $finfo->file($filePath);

        if (strpos($fileType, 'image/') === 0) {
            $image = imagecreatefromstring(file_get_contents($filePath));

            if (function_exists('exif_read_data')) {
                $exif = exif_read_data($filePath);
                if (!empty($exif['Orientation'])) {
                    switch ($exif['Orientation']) {
                        case 3:
                            $image = imagerotate($image, 180, 0);
                            break;
                        case 6:
                            $image = imagerotate($image, -90, 0);
                            break;
                        case 8:
                            $image = imagerotate($image, 90, 0);
                            break;
                    }
                }
            }

            $width = imagesx($image);
            $height = imagesy($image);

            $newWidth = $width;
            $newHeight = $height;


            if ($width > $maxWidth || $height > $maxHeight) {
                $aspectRatio = $width / $height;

                if ($width > $height) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $aspectRatio;
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $aspectRatio;
                }
            }

            // Create a new image
            $thumbnail = imagecreatetruecolor((int) $newWidth, (int) $newHeight);
            $backgroundColor = imagecolorallocate($thumbnail, $backgroundRGB[0], $backgroundRGB[1], $backgroundRGB[2]);
            imagefill($thumbnail, 0, 0, $backgroundColor);

            // Resize the image to the new dimensions
            imagecopyresampled($thumbnail, $image, 0, 0, 0, 0, (int) $newWidth, (int) $newHeight, $width, $height);

            // Save the thumbnail to a temporary file
            $thumbnailPath = tempnam(sys_get_temp_dir(), 'thumbnail');
            imagejpeg($thumbnail, $thumbnailPath, $quality);
            chmod($thumbnailPath, 0644);  // Make it web-accessible

            // Free up memory
            imagedestroy($image);
            imagedestroy($thumbnail);

            return $thumbnailPath;
        } elseif (strpos($fileType, 'video/') === 0) {
            global $globalConf;
            $thumbnailPath = tempnam(sys_get_temp_dir(), 'thumbnail') . ".jpg";
            $safeFilePath = escapeshellarg($filePath);

            if ($globalConf['isOpenBSD']) {
                $videoInfo = shell_exec("LD_LIBRARY_PATH=/usr/local/lib:/usr/X11R6/lib /usr/local/bin/ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 {$safeFilePath}");
            } else {
                $videoInfo = shell_exec("ffprobe -v error -select_streams v:0 -show_entries stream=width,height -of csv=s=x:p=0 {$safeFilePath}");
            }

            // Get video dimensions
            list($width, $height) = explode('x', $videoInfo);

            // Calculate new dimensions while maintaining aspect ratio
            $newWidth = $width;
            $newHeight = $height;

            if ($width > $maxWidth || $height > $maxHeight) {
                $aspectRatio = $width / $height;

                if ($width > $height) {
                    $newWidth = $maxWidth;
                    $newHeight = $maxWidth / $aspectRatio;
                } else {
                    $newHeight = $maxHeight;
                    $newWidth = $maxHeight * $aspectRatio;
                }
            }

            if ($globalConf['isOpenBSD']) {
                $ffmpegCommand = "LD_LIBRARY_PATH=/usr/local/lib:/usr/X11R6/lib /usr/local/bin/ffmpeg -i {$safeFilePath} -vframes 1 -vf scale={$newWidth}:{$newHeight} -q:v 2 -y {$thumbnailPath} 2>&1";
            } else {
                $ffmpegCommand = "ffmpeg -i {$safeFilePath} -vframes 1 -vf scale={$newWidth}:{$newHeight} -q:v 2 -y {$thumbnailPath} 2>&1";
            }
            exec($ffmpegCommand);

            return $thumbnailPath;
        }

        // Unsupported file type
        return null;
    }
    public function procssesFiles($files, $isOp)
    {
        $fileConf = $this->config['fileConf'];
        $FILEREPO = FileRepoClass::getInstance();
        $BANREPO = BanRepoClass::getInstance();
        $filesProcssed = [];

        foreach ($files as $file) {

            $md5Hash = $file->getMD5();

            // a good moduel would be deduplication. 
            if ($fileConf['allowDuplicateFiles'] == false) {
                if ($FILEREPO->isDuplicateFile($this->config, $md5Hash)) {
                    drawErrorPageAndDie("duplicate file detected: " . $file->getFileName());
                    continue;
                }
            }

            $thumbnailPath = $this->createThumbnail($file, $isOp);
            $file->setThumnailPath($thumbnailPath);
            $filesProcssed[] = $file;
        }
        return $filesProcssed;
    }

}