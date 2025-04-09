<?php
/*
 * this lib is a set of tools of things to help control your board.
 * delete post. edit configs. edit post. move threads across boards. list users
 */
require_once __DIR__ . '/../classes/board.php';
require_once __DIR__ . '/../classes/repos/repoBoard.php';
require_once __DIR__ . '/../classes/repos/repoThread.php';
require_once __DIR__ . '/../classes/repos/repoPost.php';
require_once __DIR__ . '/../classes/repos/repoFile.php';

require_once __DIR__ . '/common.php';

$globalConf = require __DIR__ . "/../conf.php";

function getFirstValidBoard()
{
    $BOARDREPO = BoardRepoClass::getInstance();

    $files = glob(__DIR__ . '/../boardConfigs/*.php');

    foreach ($files as $file) {
        $conf = include($file);
        if ($conf['boardID'] == -1) {
            continue;
        }
        return $BOARDREPO->loadBoardByID($conf['boardID']);
    }
    return null;
}
function createBoard($name, $desc, $smallName, $isUnlisted = true)
{
    $BOARDREPO = BoardRepoClass::getInstance();

    // Check if the board already exists
    $existing = $BOARDREPO->loadBoardByNameID($smallName);
    if ($existing !== null) {
        drawErrorPageAndDie("There is already a board using this boardNameID");
    }

    $conf = [
        'boardNameID' => $smallName,
        'boardTitle' => $name,
        'boardSubTitle' => $desc,
        'unlisted' => $isUnlisted,
        // optionally preload other defaults
    ];

    $board = new boardClass(0, 0, $conf);

    if (!$BOARDREPO->createBoard($board)) {
        drawErrorPageAndDie("Failed to create a new board");
    }

    return $board;
}


function deleteFilesInThreadByID($id)
{
    $dir = __DIR__ . "/../threads/" . $id;

    $files = scandir($dir);
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filePath = $dir . '/' . $file;

            if (!is_dir($filePath)) {
                unlink($filePath);
            }
        }
    }
    rmdir($dir);
}
function deleteBoardByID($boardID)
{
    if (getBoardCount() <= 1) {
        drawErrorPageAndDie("can not delete. you must have one active board at any time.");
    }
    $BOARDREPO = BoardRepoClass::getInstance();
    $board = $BOARDREPO->loadBoardByID($boardID);

    $threads = $board->getThreads();
    foreach ($threads as $thread) {
        deleteFilesInThreadByID($thread->getThreadID());
    }
    $BOARDREPO->deleteBoardByID($boardID);
}
function deleteThread($thread)
{
    global $globalConf;
    $THREADREPO = ThreadRepoClass::getInstance();
    deleteFilesInThreadByID($thread->getThreadID());
    foreach ($thread->getPosts() as $post) {
        deletePost($post, true);
    }
    $THREADREPO->deleteThreadByID($thread->getConf()['boardID'], $thread->getThreadID());
}
function deletePost($post, $isDeletingThread = false)
{
    $THREADREPO = ThreadRepoClass::getInstance();
    $thread = $THREADREPO->loadThreadByID($post->getBoardID(), $post->getThreadID());
    if ($post->getPostID() == $thread->getOPPostID() && $isDeletingThread == false) {
        deleteThread($thread);
        return;
    } else {
        foreach ($post->getFiles() as $file) {
            deleteFile($file);
        }
        $POSTREPO = PostRepoClass::getInstance();
        $POSTREPO->deletePostByID($post->getBoardID(), $post->getPostID());
    }
}
function updatePost($post)
{
    $POSTREPO = PostRepoClass::getInstance();
    $POSTREPO->updatePost($post->getId(), $post);
}
function editPost($post, $newComment)
{
    $POSTREPO = PostRepoClass::getInstance();

    $post->setComment($newComment);
    $POSTREPO->updatePost($post->getBoardID(), $post);
}
function deleteFile($file)
{
    unlink($file->getThumbnailPath());
    unlink($file->getFilePath());
}

function deleteFileHard($file)
{
    $FILEREPO = FileRepoClass::getInstance();
    deleteFile($file);
    $FILEREPO->deleteFileByID($file->getFileID());
}

function moveTreadToNewBoard($thread, $newBoardID)
{
    $BOARDREPO = BoardRepoClass::getInstance();
    $POSTREPO = PostRepoClass::getInstance();
    $THREADREPO = ThreadRepoClass::getInstance();

    $destBoard = $BOARDREPO->loadBoardByID($newBoardID);

    $srcPosts = $thread->getPosts();
    foreach ($srcPosts as $post) {
        // we are using the dest board for configs. this has board id in it.
        $POSTREPO->createPost($destBoard->getId(), $post);
        //make sure to add files to new posts.
    }
    //new board means new post ids for posts.
    $thread->setOPPostID($srcPosts[0]->getPostID);
    //thread's uniqe id is the same just need to update its board id.
    $THREADREPO->updateThread($destBoard->getConf()['boardID'], $thread);

    // files should still be same link :P

    return;
}
function changeBoardConf($boardID, $key, $value)
{

}