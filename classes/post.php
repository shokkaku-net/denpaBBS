<?php
require_once __DIR__ . '/file.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/postMagic.php';
require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/repos/repoFile.php';

class PostDataClass
{
    private int $postID;//postID 
    private int $boardID;
    private int $uid;
    private int $threadID;
    private array $files = [];//file objects
    private string $password;//post password
    private string $name;//name
    private string $email;//email
    private string $subject;//subject
    private string $comment;//comment
    private int $unixTime;//time posted
    private string $IP;//poster's ip
    private string $special;//special things like. auto sage, locked, animated gif, etc. used by moduels.
    private $board;
    private $isSaging = false;

    private $fileRepo;
    private $isFilesFullyLoaded;
    public function __construct(
        int $boardID,
        string $name,
        string $email,
        string $subject,
        string $comment,
        string $password,
        int $unixTime,
        string $IP,
        int $threadID = -1,
        int $postID = -1,
        string $special = '',
        int $uid = -1,
    ) {

        $this->boardID = $boardID;
        $this->name = $name;
        $this->email = $email;
        $this->subject = $subject;
        $this->comment = $comment;
        $this->password = $password;

        $this->unixTime = $unixTime;
        $this->IP = $IP;
        $this->postID = $postID;
        $this->uid = $uid;
        $this->threadID = $threadID;
        $this->special = $special;
        $this->fileRepo = FileRepoClass::getInstance();

    }
    public function __toString()
    {
        return
            "BoardID: {$this->config['boardID']}\n" .
            "Name: {$this->name}\n" .
            "Email: {$this->email}\n" .
            "Subject: {$this->subject}\n" .
            "Comment: {$this->comment}\n" .
            "Password: {$this->password}\n" .
            "Unix Time: {$this->unixTime}\n" .
            "IP Address: {$this->IP}\n" .
            "Post UID: {$this->uid}\n" .
            "Post ID: {$this->postID}\n" .
            "Thread ID: {$this->threadID}\n" .
            "Special Info: {$this->special}";
    }
    public function validate($conf)
    {
        if (mb_strlen($this->name, 'UTF-8') > MAX_INPUT_LENGTH) {
            drawErrorPageAndDie("your post's name is invalid. max size: " . MAX_INPUT_LENGTH);
        }
        if (mb_strlen($this->email, 'UTF-8') > MAX_INPUT_LENGTH) {
            drawErrorPageAndDie("your post's email is invalid. max size : " . MAX_INPUT_LENGTH);

        }
        if (mb_strlen($this->subject, 'UTF-8') > MAX_INPUT_LENGTH) {
            drawErrorPageAndDie("your post's subject is invalid. max size: " . MAX_INPUT_LENGTH);
        }
        if (mb_strlen($this->comment, 'UTF-8') > $conf['maxCommentSize']) {
            drawErrorPageAndDie("your post's comment is invalid. max size: " . $conf['maxCommentSize']);
        }
        if (mb_strlen($this->password, 'UTF-8') > MAX_INPUT_LENGTH_PASSWORD) {
            drawErrorPageAndDie("your post's password is invalid. max size: " . MAX_INPUT_LENGTH_PASSWORD);
        }
    }
    public function stripHtml()
    {
        $this->name = htmlspecialchars($this->name, ENT_QUOTES, 'UTF-8');
        $this->email = htmlspecialchars($this->email, ENT_QUOTES, 'UTF-8');
        $this->subject = htmlspecialchars($this->subject, ENT_QUOTES, 'UTF-8');
        $this->comment = htmlspecialchars($this->comment, ENT_QUOTES, 'UTF-8');
    }
    public function embedLinks()
    {
        $regexUrl = '/(https?:\/\/[^\s]+)/';
        $this->comment = preg_replace($regexUrl, '<a href="$1" target="_blank">$1</a>', $this->comment);
    }
    public function addLineBreaks()
    {
        $this->comment = nl2br($this->comment);
    }
    public function applyTripcode()
    {
        $nameXpass = splitTextAtTripcodePass($this->name);
        $gc = require __DIR__ . '/../conf.php';
        $tripcodeArray = genTripcode($nameXpass[1], $gc['tripcodeSalt']);

        if (!isset($tripcodeArray['tripcode'])) {
            return;
        }
        $this->name = $nameXpass[0] . $tripcodeArray['tripcode'];


        $special = $this->getSpecial();
        $special = array_merge($special, $tripcodeArray);
        $this->setSpecial($special);
    }
    public function stripTripcodePass()
    {
        $nameXpass = splitTextAtTripcodePass($this->name);
        $this->name = $nameXpass[0];
    }
    public function applyQuoteUser()
    {
        $patternQuote = '/(^|\n)&gt;(?!&gt;)([^\n]*)/';
        $replacementQuote = '$1<div class="quote">&gt;$2</div>';
        $this->comment = preg_replace($patternQuote, $replacementQuote, $this->comment);
    }
    public function applyPostLinks()
    {
        // Convert post numbers starting with '&gt;&gt;'
        $patternPost = '/&gt;&gt;(\d+)/';
        $replacementPost = function ($matches) {
            $postID = $matches[1];
            $url = postResolve($this->getConf(), $postID);
            return '<a href="' . $url . '">&gt;&gt;' . $postID . '</a>';
        };
        $this->comment = preg_replace_callback($patternPost, $replacementPost, $this->comment);
    }
    public function applyBBCode()
    {
        // bold
        $this->comment = preg_replace('#\[b\](.*?)\[/b\]#si', '<b>\1</b>', $this->comment);
        // spoiler
        $this->comment = preg_replace('#\[spoiler\](.*?)\[/spoiler\]#si', '<span class="spoiler">\1</span>', $this->comment);
        // code
        $this->comment = preg_replace('#\[code\](.*?)\[/code\]#si', '<div class="code">\1</div>', $this->comment);
        // italics
        $this->comment = preg_replace('#\[i\](.*?)\[/i\]#si', '<i>\1</i>', $this->comment);
        // underlined
        $this->comment = preg_replace('#\[u\](.*?)\[/u\]#si', '<u>\1</u>', $this->comment);
        // paragraph
        $this->comment = preg_replace('#\[p\](.*?)\[/p\]#si', '<p>\1</p>', $this->comment);
        // color
        $this->comment = preg_replace('#\[color=(\S+?)\](.*?)\[/color\]#si', '<font color="\1">\2</font>', $this->comment);
        // size
        $this->comment = preg_replace('#\[s([1-7])\](.*?)\[/s([1-7])\]#si', '<font size="\1">\2</font>', $this->comment);
        // strike though
        $this->comment = preg_replace('#\[del\](.*?)\[/del\]#si', '<del>\1</del>', $this->comment);
        // preserve content
        $this->comment = preg_replace('#\[pre\](.*?)\[/pre\]#si', '<pre>\1</pre>', $this->comment);
        // block quote
        $this->comment = preg_replace('#\[quote\](.*?)\[/quote\]#si', '<blockquote>\1</blockquote>', $this->comment);
        // scroll
        $this->comment = preg_replace('#\[scroll\](.*?)\[/scroll\]#si', '<div class="scroll">\1</div>', $this->comment);
        // email
        $this->comment = preg_replace('#\[email\](\S+?@\S+?\\.\S+?)\[/email\]#si', '<a href="mailto:\1">\1</a>', $this->comment);

        // ruby/furigana
        $this->comment = preg_replace('#\[ruby\](.*?)\[/ruby\]#si', '<ruby>\1</ruby>', $this->comment);
        $this->comment = preg_replace('#\[rt\](.*?)\[/rt\]#si', '<rt>\1</rt>', $this->comment);
        $this->comment = preg_replace('#\[rp\](.*?)\[/rp\]#si', '<rp>\1</rp>', $this->comment);
    }
    public function isSage()
    {
        if (stripos($this->getEmail(), "sage") !== false) {
            if ($this->getConf()['visableSage'] == false) {
                $this->isSaging = true;
            }
            return true;
        } elseif ($this->isSaging == true) {
            return true;
        } else {
            return false;
        }
    }
    public function isBumpingThread()
    {
        if ($this->isSage()) {
            return false;
        } else {
            return true;
        }
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }
    public function addFile(FileDataClass $file)
    {
        $this->files[] = $file;
    }
    public function addFilesToRepo()
    {
        foreach ($this->files as $file) {
            $file->setPostID($this->postID);
            $file->setThreadID($this->threadID);
            $file->setConf($this->config);

            $FILEREPO = FileRepoClass::getInstance();
            $FILEREPO->createFile($this->config, $file);
        }
    }
    public function moveFilesToDir($dir, $isImport = false)
    {
        foreach ($this->files as $file) {
            $file->moveToDir($dir, $isImport);
        }
    }
    public function getFiles()
    {
        if ($this->isFilesFullyLoaded != true) {
            $this->files = $this->fileRepo->loadFilesByPostID($this->config, $this->postID);
            $this->isFilesFullyLoaded = true;
        }
        return $this->files;
    }
    public function appendText($text)
    {
        $this->comment .= $text;
    }
    public function getPostID()
    {
        return $this->postID;
    }
    public function getThreadID()
    {
        return $this->threadID;
    }

    public function getName()
    {
        return $this->name;
    }
    public function getEmail()
    {
        return $this->email;
    }
    public function getSubject()
    {
        return $this->subject;
    }
    public function getComment()
    {
        return $this->comment;
    }
    public function getPassword()
    {
        return $this->password;
    }
    public function getUnixTime()
    {
        return $this->unixTime;
    }
    public function getIP()
    {
        return $this->IP;
    }
    /* special should only be used by moduels */
    public function getSpecial()
    {
        if (!isset($this->special) || empty($this->special)) {
            return [];
        }

        // Explode the special string into an associative array
        $pairs = explode('|', $this->special);
        $assocArray = [];

        foreach ($pairs as $pair) {
            list($key, $value) = explode(':', $pair);
            // Properly unescape the keys and values
            $key = str_replace(['\\:', '\\|'], [':', '|'], $key);
            $value = str_replace(['\\:', '\\|'], [':', '|'], $value);
            $assocArray[$key] = $value;
        }

        return $assocArray;
    }
    public function getRawSpecial()
    {
        return $this->special;
    }

    public function getUID()
    {
        return $this->uid;
    }
    public function setUID(int $uid)
    {
        $this->uid = $uid;
    }
    public function getBoardId()
    {
        return $this->boardID;
    }

    public function setPostID($id)
    {
        $this->postID = $id;
    }
    public function setThreadID($id)
    {
        $this->threadID = $id;
    }
    public function setName($name)
    {
        $this->name = $name;
    }
    public function setEmail($email)
    {
        $this->email = $email;
    }
    public function setSubject($subject)
    {
        $this->subject = $subject;
    }
    public function setComment($comment)
    {
        $this->comment = $comment;
    }
    public function setPassword($password)
    {
        $this->password = $password;
    }
    public function setUnixTime($unixTime)
    {
        $this->unixTime = $unixTime;
    }
    public function setIP($IP)
    {
        $this->IP = $IP;
    }
    public function updateSpecial($key, $value)
    {
        $currentSpecial = $this->getSpecial();
        $currentSpecial[$key] = $value;
        $this->setSpecial($currentSpecial);
    }
    public function setSpecial($associativeTable)
    {
        // Ensure it's an associative array
        if (!is_array($associativeTable)) {
            throw new InvalidArgumentException('Expected an associative array.');
        }

        $pairs = [];

        foreach ($associativeTable as $key => $value) {
            $key = str_replace([':', '|'], ['\\:', '\\|'], $key);
            $value = str_replace([':', '|'], ['\\:', '\\|'], $value);
            $pairs[] = $key . ':' . $value;
        }

        // Implode the associative array into a special string
        $this->special = implode('|', $pairs);
    }
    public function getSpecialValue($key)
    {
        $currentSpecial = $this->getSpecial();

        if (array_key_exists($key, $currentSpecial)) {
            return $currentSpecial[$key];
        }

        return null;
    }
    public function getDrawData($includeFiles = true, $opID = -1)
    {
        $data = [
            'id' => $this->postID,
            'subject' => $this->subject,
            'name' => $this->name,
            'time' => date('Y-m-d H:i:s', $this->unixTime),
            'comment' => $this->comment,
            'email' => $this->email,
        ];
        if ($this->postID == $opID) {
            $data['isOp'] = true;
        }
        if ($includeFiles) {
            /*
            $files = $this->getFiles();
            foreach ($files as $file) {
                $data['files'][] = $file->getDrawData();
            }
            */
        }
        return $data;
    }
}