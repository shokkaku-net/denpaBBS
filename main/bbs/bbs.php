<?php

//include BASEDIR . '/includes.php';

require_once BASEDIR . '/classes/board.php';
require_once BASEDIR . '/classes/thread.php';
require_once BASEDIR . '/classes/post.php';
//require_once BASEDIR . '/classes/file.php';

require_once BASEDIR . '/classes/hook.php';
require_once BASEDIR . '/classes/auth.php';
require_once BASEDIR . '/classes/media.php';
//require_once BASEDIR . '/classes/fileHandler.php';
require_once BASEDIR . '/classes/html.php';

require_once BASEDIR . '/classes/repos/repoBoard.php';
require_once BASEDIR . '/classes/repos/repoThread.php';
require_once BASEDIR . '/classes/repos/repoPost.php';
require_once BASEDIR . '/classes/repos/repoBan.php';
//require_once __DIR__ .'/classes/repos/repoFile.php';

require_once BASEDIR . '/lib/common.php';
require_once BASEDIR . '/lib/requestHelper.php';
require_once BASEDIR . '/lib/pagedrawing.php';
require_once BASEDIR . '/lib/adminControl.php';

$AUTH = AuthClass::getInstance();
$HOOK = HookClass::getInstance();
$POSTREPO = PostRepoClass::getInstance();
$THREADREPO = ThreadRepoClass::getInstance();
$BOARDREPO = BoardRepoClass::getInstance();
$BANREPO = BanRepoClass::getInstance();

// just assume it all works...
function registerNewData($boardID, $thread, $post): void
{
    global $POSTREPO;
    global $THREADREPO;

    $POSTREPO->createPost($boardID, $post);
    if ($thread->getId() == -1) {
        $THREADREPO->createThread($boardID, $thread, $post);
    } else {
        $THREADREPO->updateThread($boardID, $thread);
    }
    $post->setThreadID($thread->getId());
    $POSTREPO->updatePost($boardID, $post);
    $post->addFilesToRepo();
}
function applyPostFilters($post, $conf)
{
    global $HOOK;
    global $BANREPO;
    $post->stripHtml();

    $domains = extractUniqueDomainsFromComment($post->getComment());

    foreach ($domains as $domain) {
        if ($BANREPO->isDomainBanned($conf['boardID'], $domain, true)) {
            drawErrorPageAndDie("domain is not allowed on this site");
        }
    }

    // if the board allows embeding of links.
    if ($conf['autoEmbedLinks']) {
        $post->embedLinks();
    }

    // if board allows post to link to other post.
    if ($conf['allowPostLinking']) {
        $post->applyPostLinks();
    }

    // if board allows quoting of text.
    if ($conf['allowQuoteing']) {
        $post->applyQuoteUser();
    }

    // if board allows BBcode.
    if ($conf['allowBBcode']) {
        $post->applyBBCode();
    }

    if ($conf['visableSage']) {
        if ($post->isSage()) {
            $post->setName($post->getName() . ' <b><font color="#F00">SAGE!</font></b>');
        }
    }

    //new lines get converted to <br>
    $post->addLineBreaks();

    // stuff like bb code, emotes, capcode, ID, should all be handled in moduels.
    $HOOK->executeHook("filtersAppliedToPost", $post);// HOOK post with html fully loaded
}
function genFilesFromRequest(array $filesData): array
{
    $files = [];

    foreach ($filesData['tmp_name'] as $i => $tmpPath) {
        if (!is_uploaded_file($tmpPath) || $filesData['error'][$i] !== UPLOAD_ERR_OK) {
            continue;
        }

        $mime = mime_content_type($tmpPath);
        $md5 = md5_file($tmpPath);
        $size = $filesData['size'][$i];

        $media = new MediaDataClass($md5, $tmpPath, $mime, $size);
        $file = new FileDataClass($filesData['name'][$i], $media);

        $files[] = $file;
    }

    return $files;
}
function genUserPostFromRequest($conf, $boardID, $isOp = false): PostDataClass
{
    global $AUTH;
    global $HOOK;
    global $globalConf;

    /*
     * below is the exact same thing as this
     * 
     * $name;
     * if(!empty($_POST['name']))){
     *     $name = $_POST['name'];
     * }elseif (!$conf['requireName']){
     *     $name = $conf['defaultName'];
     * }else{
     *     drawErrorPageAndDie("name is a required feild");
     * }
     */
    $name = !empty($_POST['name']) ? $_POST['name'] : (!$conf['requireName'] ? $conf['defaultName'] : drawErrorPageAndDie("your Name is required."));
    $email = !empty($_POST['email']) ? $_POST['email'] : (!$conf['requireEmail'] ? $conf['defaultEmail'] : drawErrorPageAndDie("your Email is required."));
    $subject = !empty($_POST['subject']) ? $_POST['subject'] : (!$conf['requireSubject'] ? $conf['defaultSubject'] : drawErrorPageAndDie("a Subject is required."));
    $comment = !empty($_POST['comment']) ? $_POST['comment'] : (!$conf['requireComment'] ? $conf['defaultComment'] : drawErrorPageAndDie("a comment is required."));
    $password = !empty($_POST['password']) ? $_POST['password'] : (isset($_COOKIE['password']) ? $_COOKIE["password"] : null);

    //gen post password if none is provided
    if ($password == null) {
        // op's first post, he gets cookie, clicks on ip logger. now evil has an ip + time...
        $hasinput = $_SERVER['REMOTE_ADDR'] . time() . $globalConf['tripcodeSalt'];
        $hash = hash('sha256', $hasinput);
        $password = substr($hash, -MAX_INPUT_LENGTH_PASSWORD);
    }

    //drawErrorPageAndDie("Name: $name, Email: $email, Password: $password");
    //cookies!!!!
    setrawcookie('name', rawurlencode($name), time() + $conf['cookieExpireTime']);
    setrawcookie('email', rawurlencode($email), time() + $conf['cookieExpireTime']);
    setrawcookie('password', rawurlencode($password), time() + $conf['cookieExpireTime']);

    $post = new PostDataClass(
        $boardID,
        $name,
        $email,
        $subject,
        $comment,
        $password,
        time(),
        $_SERVER['REMOTE_ADDR'],
    );
    // make sure stuff dose not blow over the db limits
    $post->validate($conf);


    /* 
     * file upload stuff 
     */
    $files = [];
    $fileCount = countRequestedFiles($_FILES['upfile']);
    if (
        $conf['postMustHaveFileOrComment'] &&
        ($post->getComment() == $conf['defaultComment'] || $post->getComment() == "") &&
        $fileCount == 0
    ) {
        drawErrorPageAndDie("you must have a file or comment");
    }
    if ((($isOp && $conf['opMustHaveFile']) || $conf['requireFile']) && $fileCount == 0) {
        drawErrorPageAndDie("you must have a file");
    }
    if ($fileCount > 0) {

        if ($fileCount > $conf['fileConf']['maxFiles']) {
            drawErrorPageAndDie("to many files uploaded");
        }
        if (getTotalRequestedFileSize($_FILES['upfile']) > MAX_TOTAL_FILE_SIZES) {
            drawErrorPageAndDie("total file size is to big, software wide!");
        }

        $files = genFilesFromRequest($_FILES['upfile']);
        //drawErrorPageAndDie($files[0]);

        if (count($files) == 0) {
            drawErrorPageAndDie("files are required. 0 where provided");
        }

        $passed = [];
        foreach ($files as $file) {
            if ($file->validate($conf['fileConf'])) {
                $passed[] = $file;
            }
        }
        $files = $passed;
    }
    $post->setFiles($files);

    //if the board lets you tripcode, apply tripcode to name.
    if ($conf['canTripcode']) {
        $post->applyTripcode();
    } else {
        $post->stripTripcodePass();
    }

    $HOOK->executeHook("postDataLoaded", $post); // HOOK post with html fully loaded

    /* 
     *  if we are admin or mod and we decide to not strip html and post raw. then dont strip html and procsses.
     *  if we are not striping html then continue as a normal users
     */
    $skipEmbedding = false;

    if ($AUTH->isAdmin($boardID) || $AUTH->isModerator($boardID)) {
        if (isset($_POST['embedingHTML'])) {
            $skipEmbedding = true;
        }
    }

    // word filters. aka a bunch of SED
    // moduels might have there own filters to add too.
    if ($skipEmbedding == false) {
        applyPostFilters($post, $conf);
    }

    return $post;
}
function userPostNewPostToThread($board)
{
    $conf = $board->getConf();

    // load existing thread
    $thread = $board->getThreadByID($_POST['threadID']);
    if (is_null($thread)) {
        drawErrorPageAndDie("the thread you are replying to dose not exist");
    }
    if ($thread->getStatus() == 'archived') {
        drawErrorPageAndDie("you cant reply to a archived thread");
    }

    // create post to thread
    $post = genUserPostFromRequest($conf, $board->getId());

    if ($post->isBumpingThread()) {
        $thread->bump($conf);
    }

    registerNewData($board->getId(), $thread, $post);

    $board->prune();

    return $post;
}
function userPostNewThread($board)
{
    $conf = $board->getConf();

    // make a new thread
    $thread = new threadClass($conf, time());

    // create post with thread
    $post = genUserPostFromRequest($conf, $board->getId(), true);

    registerNewData($board->getId(), $thread, $post);

    $board->prune();

    return $thread;
}
function userDeletedPost($board, $post, $password)
{
    global $AUTH;
    if ($password == "" && isset($_COOKIE['password'])) {
        $password = $_COOKIE['password'];
    }
    // the passwords dont match and user dose not have power.
    if ($password != $post->getPassword() && !$AUTH->isAuth($board->getId())) {
        return;
    }
    if ($AUTH->isAuth($board->getId())) {
        logAudit($board, $AUTH->getName() . " has deleted post " . $post->getPostID());
    }
    /*    if (isset($_POST['fileOnly'])) {
            foreach ($post->getFiles() as $file) {
                deleteFile($file);
            }
        } else {
            deletePost($post);
        }
    */
}

/*-------------------------------------------------------MAIN ENTRY-------------------------------------------------------*/

/*
 * this file is the main request handler of the board after it is already installed.
 * below handels the routing.
 * 
 * you should not try and hack anything into this file unless you know what you are doing.
 * ./moduels/ is where your hacks should be put. and then enable them through the admin pannel.
 */

$board = getBoardFromRequest();
$boardHtml = new htmlclass($board->getConf(), $board);

$modules = loadModules();

foreach ($board->getConf()['enabledModules'] as $moduleName) {
    foreach ($modules as $module) {
        if ($module->getName() === $moduleName) {
            $module->init();
        }
    }
}


/*----------get action recived----------*/
if (isset($_GET['thread'])) {

    $threadID = $_GET['thread'];
    if (!is_numeric($threadID)) {
        drawErrorPageAndDie("thread must be a number");
    }

    $thread = $board->getThreadByID($threadID);
    if (is_null($thread)) {
        drawErrorPageAndDie("thread dose not exist");
    }
    drawBoardThread($board, $threadID);
    //$boardHtml->drawThreadPage($thread);
    return;
} elseif (isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'catalog':
            $sort = $_GET['sort'] ?? 'bump';
            $keyword = $_GET['keyword'] ?? '';
            $case = isset($_GET['case']) && $_GET['case'] == '1';
            $boardHtml->drawCatalogPage($sort, $keyword, $case);
            return;
    }
}
/*----------post action recived----------*/ elseif (isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'postToThread':
            $post = userPostNewPostToThread($board);
            $thread = $board->getThreadByID($_POST['threadID']);
            //postWebHook($board->getBoardID(), $thread->getThreadID(), $post->getPostID());
            redirectToPost($post);
            break;
        case 'postNewThread':
            $thread = userPostNewThread($board);
            //postWebHook($board->getBoardID(), $thread->getThreadID(), $thread->getOPPost()->getPostID());
            redirectToThread($thread);
            break;
        case 'deletePosts':
            if (empty($_POST['postIDs'])) {
                drawErrorPageAndDie("no posts where selected.");
            }
            $postIDs = $_POST['postIDs'];
            foreach ($postIDs as $postId) {
                if (!is_numeric($postId)) {
                    continue;
                }
                $post = $POSTREPO->loadPostByID($board->getId(), $postId);
                userDeletedPost($board, $post, $_POST['password']);
            }
            redirectToBoard($board);
            break;
        case 'catalog':
            $sort = $_POST['sort'];
            $keyword = $_POST['keyword'];
            $case = isset($_POST['case']) && $_POST['case'] == '1';

            redirectToCatalog($board, $sort, $keyword, $case);
            break;
        default:
            $stripedInput = htmlspecialchars($_POST['action'], ENT_QUOTES, 'UTF-8');
            drawErrorPageAndDie("invalid action: " . $stripedInput);
            break;
    }
}
/*----------no action recived----------*/ else {
    $page = $_GET['page'] ?? $_POST['page'] ?? 1;
    if (!is_numeric($page)) {
        drawErrorPageAndDie("invalid page");
    }
    $page = abs(intval($page));
    drawBoardThreadListing($board, $page);
    //$boardHtml->drawThreadListingPage($page);
}