<?php
include __DIR__ .'/includes.php';

require_once __DIR__ .'/classes/board.php';
require_once __DIR__ .'/classes/thread.php';
require_once __DIR__ .'/classes/post.php';
require_once __DIR__ .'/classes/file.php';

require_once __DIR__ .'/classes/fileHandler.php';

require_once __DIR__ .'/classes/repos/repoBoard.php';
require_once __DIR__ .'/classes/repos/repoThread.php';
require_once __DIR__ .'/classes/repos/repoPost.php';
//require_once __DIR__ .'/classes/repos/repoFile.php';

require_once __DIR__ .'/lib/common.php';
require_once __DIR__ .'/lib/adminControl.php';

$POSTREPO = PostRepoClass::getInstance();
$THREADREPO = ThreadRepoClass::getInstance();
//$FILEREPO = FileRepoClass::getInstance();
$BOARDREPO = BoardRepoClass::getInstance();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['log_file'])) {
    $board = getBoardFromRequest();
    $conf = $board->getConf();
    $fileName = $_FILES['log_file']['tmp_name'];
    if ($_FILES['log_file']['size'] > 0) {
        // Decompress the gzipped file
        $data = '';
        $gp = gzopen($fileName, 'r');
        while (!gzeof($gp)) {
            $data .= gzread($gp, 4096);
        }
        gzclose($gp);

        $lines = explode("\n", $data);
        $lines = array_reverse($lines);

        $threads = [];
        $posts = [[]];

        // this loop will create all the object and populate these arrays above based on thread id. used for after this loop
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            $row = explode(',', $line);

            // thread junk
            $threadID = $row[1];
            $lastBump = strtotime($row[2]);

            //post junk
            $postID = $row[0];

            $date_string = preg_replace('/\([A-Za-z]+\)/', '', $row[14]);
            $postTime = strtotime($date_string);

            $password = $row[13];
            $name = $row[15];
            $email = $row[16];
            $subject = $row[17];
            $comment = $row[18];
            $ip = $row[19];
            $special = $row[21];

            // file junk
            $filenameOnDisk = $row[5];
            $fileExtension = $row[7];
            $fileName = $row[6];
            $md5chksum = $row[3];
            
            $post = new PostDataClass($conf, $name, $email, $subject, $comment, $password, $postTime, $ip, -1, $postID, $special);

            if($threadID == 0){ // in koko, threadID of 0 means new thread, when its not a new thread. it is the post id of the thread
                $thread = new  threadClass($conf, $lastBump, -1, $postID);
                $threads[$postID] = $thread;
                $posts[$postID][$postID] = $post;
            }else{
                $posts[$threadID][$postID] = $post;
            }

            if(empty($md5chksum) == false){
                $filePath = __DIR__ . '/src/' . $filenameOnDisk . $fileExtension;
                $fName = $fileName . $fileExtension;
                $file = new FileDataClass($conf, $filePath ,$fName, $md5chksum);

                // bc koko, you can pick your poison. why cant it be prefixed...
                if(file_exists(__DIR__ . '/src/' . $filenameOnDisk .'s.jpg')){
                    $file->setThumnailPath('src/' . $filenameOnDisk .'s.jpg');
                }elseif(file_exists(__DIR__ . '/src/' . $filenameOnDisk .'s.png')){
                    $file->setThumnailPath('src/' . $filenameOnDisk .'s.png');
                }
                $post->addFile($file);
            }
        }

        $biggestPost = 0;
        //loop thu each thread and create the thread.
        foreach ($threads as $threadID => $thread) {
            $opPost = $posts[$threadID][$threadID];

            $THREADREPO->createThread($board->getConf(), $thread, $opPost);

            $opPost->setThreadID($thread->getThreadID());
            $POSTREPO->createPostImport($board->getConf(), $opPost);

            $threadDir = __DIR__ . "/threads/" . $thread->getThreadID();
            mkdir($threadDir);

            $opPost->moveFilesToDir($threadDir, true);
            $opPost->addFilesToRepo();
            if($threadID > $biggestPost){
                $biggestPost = $threadID;
            }
            unset($posts[$threadID][$threadID]);
            
            foreach ($posts[$threadID] as $postID => $post) {
                $post->setThreadID($thread->getThreadID());
                $POSTREPO->createPostImport($board->getConf(), $post);
                $post->moveFilesToDir($threadDir, true);
                $post->addFilesToRepo();
                if($postID > $biggestPost){
                    $biggestPost = $postID;
                }
            }
        }
        $board->setLastPostID($biggestPost);
        $BOARDREPO->updateBoard($board);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>import koko</title>
</head>
<body>
    <h1>import koko</h1>
    <form action="importTest.php" method="post" enctype="multipart/form-data">
        <input type="file" name="log_file" accept=".gz" required>
        <input type="hidden" name="boardID" value="16">

        <input type="submit" value="Upload Log File">
    </form>
</body>
</html>
