<?php
/*
 *
 * this file might look weird in your editor. i am using vscode and the logic lines up with the html.
 * this is to help make it more understandable where the logic is being apply to.
 * 
 * sorry you have to hunt down the functions to change peices. luckly most have comments in html of witch function was used to make peices.
 * 
 */

require_once __DIR__ . '/hook.php';
require_once __DIR__ . '/repos/repoThread.php';
require_once __DIR__ . '/repos/repoBan.php';
require_once __DIR__ . '/../lib/common.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../lib/logging.php';

$HOOK = HookClass::getInstance();
$THREADREPO = ThreadRepoClass::getInstance();
$BANREPO = BanRepoClass::getInstance();
$AUTH = AuthClass::getInstance();

class htmlclass
{
    private string $html = "";
    private array $conf;
    private boardClass $board;
    public function __construct(array $conf, boardClass $board)
    {
        $this->conf = $conf;
        $this->board = $board;
    }
    private function drawHead()
    {
        global $AUTH;
        $this->html .= '
        <!--drawHead() Hello!! If you are looking to modify this webapge. please check out DenpaBBS github and look in /classes/html.php-->
        <head>
            <!--tell browsers to use UTF-8-->
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
            <!--always get newest content-->
            <meta http-equiv="cache-control" content="max-age=0">
            <meta http-equiv="cache-control" content="no-cache">
            <meta http-equiv="expires" content="0">
            <meta http-equiv="expires" content="Tue, 01 Jan 1980 1:00:00 GMT">
            <meta http-equiv="pragma" content="no-cache">
            <!--mobile view-->
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <!--tell bots its ok to scrape the whole site. disallowing this wont stop bots FYI-->
            <meta name="robots" content="follow,archive">
            <!--board specific stuff-->
            <title>' . $this->conf['boardTitle'] . '</title>
            <link rel="stylesheet" type="text/css" href="' . $this->conf['staticPath'] . 'css/base.css">
            <link rel="stylesheet" type="text/css" href="' . $this->conf['style'] . '" title="boardcss">
            <link rel="shortcut icon" href="' . $this->conf['iconPack'] . '/favicon.png">';

        if ($this->conf['allowRuffle'] && $this->conf['allowJS']) {
            $this->html .= '<script src="https://unpkg.com/@ruffle-rs/ruffle"></script>';
        }
        if ($this->conf['allowJS']) {
            $this->html .=
                '<script src="' . $this->conf['staticPath'] . 'js/system/onClickEmbedFile.js" defer></script>
                <script src="' . $this->conf['staticPath'] . 'js/system/postidToForm.js" defer></script>
                <script src="' . $this->conf['staticPath'] . 'js/system/autoFillCookies.js" defer></script>
                <script src="' . $this->conf['staticPath'] . 'js/system/highlight.js" defer></script>';
        }
        $this->html .=

            //'<link rel="alternate" type="application/rss+xml" title="RSS 2.0 Feed" href="//nashikouen.net/main/koko.php?mode=module&amp;load=mod_rss">
            '</head>';
    }
    /*drawNavGroup expects a key,value pair. where key is displayname and value is url*/
    private function drawNavGroup($URLPair)
    {
        //this is what i mean by grouping [ webpage1 / webpage2 / webpage3 / etc.. ]
        if (empty($URLPair)) {
            return;
        }
        $pairs = '<span class="nowrap">[';
        foreach ($URLPair as $key => $value) {
            $pairs .= '&nbsp;<a class="navLink" href="' . $value . '">' . $key . '</a>&nbsp;/';
        }
        $pairs = substr($pairs, 0, -1); // Remove the last "/"

        $pairs .= ']</span>';
        $this->html .= $pairs;
    }
    private function drawNavBar()
    {
        global $HOOK;

        $conf = $this->conf;

        $this->html .= '
        <!--drawNavBar()-->
        <div class="navBar">
        <span class="navLeft">';
        $this->drawNavGroup(getBoardListing());
        $this->drawNavGroup($conf['navLinksLeft']);
        $res = $HOOK->executeHook("onDrawNavLeft");// HOOK drawing to left side of nav
        foreach ($res as $urlGroup) {
            $this->drawNavGroup($urlGroup);
        }
        $this->html .= '
        </span>
        <span class="navRight">';
        $res = $HOOK->executeHook("onDrawNavRight");// HOOK drawing to right side of nav
        foreach ($res as $urlGroup) {
            $this->drawNavGroup($urlGroup);
        }
        $this->drawNavGroup($conf['navLinksRight']);
        $this->drawNavGroup(['admin' => WEBPATH . $conf['boardNameID'] . '/admin/']);
        $this->html .= '
        </span>
        </div>';
    }
    private function drawBoardTitle()
    {
        $conf = $this->conf;
        $conf['boardTitle'];
        $conf['boardSubTitle'];
        $conf['boardLogoPath'];
        $this->html .= '
        <!--drawBoardTitle()-->
        <div class="boardTitle">';
        if ($conf['boardLogoPath'] != "") {
            $this->html .= '<img class="logo" loading="lazy" src="' . $conf['boardLogoPath'] . '">';
        }
        $this->html .= '<h1 class="title">' . $conf['boardTitle'] . '</h1>';
        $this->html .= '<h5 class="subtitle">' . $conf['boardSubTitle'] . '</h5>
        </div>';
    }
    private function drawFooter()
    {
        $this->html .= '<br><br><br><center>- you are running <a rel="nofollow noreferrer license" href="https://github.com/shokkaku-net/denpaBBS" target="_blank">DenpaBBS</a>. a clear and easy to read image board software -</center>';
    }
    private function postManagerWraper($drawFunc, $parameter)
    {
        $this->html .= '
        <!--postManagerWraper($drawFunc, $parameter)-->
        <form name="managePost" id="managePost" action="' . WEBPATH . $this->conf['boardNameID'] . '" method="post">';
        call_user_func_array($drawFunc, $parameter);
        $this->html .= '
            <!--make dropdown with other options-->
            <table align="right">
            <tr>
            <td align="">
			<input type="hidden" name="action" value="deletePosts">
            <input type="hidden" name="boardID" value="' . $this->conf['boardID'] . '">

                Delete Post: [<label><input type="checkbox" name="fileOnly" id="fileOnly" value="on">File only</label>]<br>
                Password: <input type="password" name="password" size="16" maxlength="' . MAX_INPUT_LENGTH_PASSWORD . '">
                <input type="submit" value="Submit">
            </td>
            </tr>
            </table>
        </form>';
    }
    private function drawMainFormBody($buttonText, $isThread = false)
    {
        global $AUTH;
        global $board;
        $this->html .= '
        <!--drawFormNewThread($buttonText)-->
        <table>
        <tr>
            <td class="accent"><label for="name">Name</label></td>
            <td><input type="text" id="name" name="name" autocomplete="off" maxlength="' . MAX_INPUT_LENGTH . '" ';
        if ($this->conf['requireName']) {
            $this->html .= 'required';
        }
        $this->html .=
            '></td>
        </tr>
        <tr>
            <td class="accent"><label for="email">Email</label></td>
            <td>
                <input type="text" id="email" name="email" autocomplete="off" maxlength="' . MAX_INPUT_LENGTH . '" ';
        if ($this->conf['requireEmail']) {
            $this->html .= 'required';
        }
        $this->html .=
            '">
            </td>
        </tr>
        <tr>
            <td class="accent"><label for="subject">Subject</label></td>
	        <td><input type="text" id="subject" name="subject" maxlength="' . MAX_INPUT_LENGTH . '" ';
        if ($this->conf['requireSubject']) {
            $this->html .= 'required';
        }
        $this->html .=
            '>
	            <button type="submit">' . $buttonText . '</button>
	        </td>
        </tr>
        <tr>
            <td class="accent"><label for="comment">Comment</label></td>
            <td><textarea type="text" id="comment" name="comment" cols="48" rows="4" maxlength="' . $this->conf['maxCommentSize'] . '" ';
        if ($this->conf['requireComment']) {
            $this->html .= 'required';
        }
        $this->html .=
            '></textarea></td>
        </tr>';
        if ($this->conf['fileConf']['maxFiles'] >= 1) {
            $this->html .= '
        <tr>
            <td class="accent"><label for="files">Files</label></td>
            <td><input type="file" id="files" name="upfile[]" multiple=""';
            if ($this->conf['requireFile']) {
                $this->html .= 'required';
            } elseif ($isThread && $this->conf['opMustHaveFile']) {
                $this->html .= 'required';
            }
            $this->html .=
                '></td>
        </tr>';
        }
        $this->html .= '
        <tr>
            <td class="accent"><label for="password">Password</label></td>
            <td><input type="password" id="password" name="password" maxlength="' . MAX_INPUT_LENGTH_PASSWORD . '"></td>
        </tr>';
        if ($AUTH->isAuth($board->getBoardID()) && !$AUTH->isJanitor($board->getBoardID())) {
            $this->html .= '
            <tr>
                <td class="accent"><label for="embedingHTML">embeding HTML</label></td>
                <td><input type="checkbox" id="embedingHTML" name="embedingHTML"></td>
            </tr>';
        }
        $this->html .= '
        </table>';
        $this->drawFormSumbitionRules();
        $this->html .= '<script src="' . $this->conf['staticPath'] . '/js/system/kao.js" defer></script>';
    }
    private function drawFormSumbitionRules()
    {
        global $AUTH;
        global $board;
        $this->html .= '
            <details class="submitRules"><summary>submition rules</summary>';
        if ($this->conf['fileConf']['maxFiles'] >= 1) {
            $listing = '<li>Allowed file types are: ';
            foreach ($this->conf['fileConf']['allowedMimeTypes'] as $mimeType) {
                $listing .= ' ' . getExtensionByMimeType($mimeType) . ',';
            }
            $listing = substr($listing, 0, -1); // Remove the last ","
            $listing .= '</li>';

            $this->html .= $listing;

            $this->html .= '<li>maximum files: ' . $this->conf['fileConf']['maxFiles'] . '</li>';
            $this->html .= '<li>maximum upload size: ' . bytesToHumanReadable($this->conf['fileConf']['maxFileSize']) . '</li>';
            if ($this->conf['fileConf']['allowDuplicateFiles'] == false) {
                $this->html .= '<li>duplicate files are not allowed</li>';
            }
        }
        $this->html .= '<li>posts will be saved in ' . $this->conf['timeZone'] . ' time</li>';
        $this->html .= '<li>max comment size is ' . $this->conf['maxCommentSize'] . ' characters</li>';

        if ($this->conf['canTripcode']) {
            $this->html .= '<li>tripcodes are enabled</li>';
        }
        if ($this->conf['canFortune']) {
            $this->html .= '<li>fortunes are enabled</li>';
        }
        if ($this->conf['allowBBcode']) {
            $this->html .= '<li>BBCode is enabled</li>';
        }

        $this->html .= '
            </details>';
    }
    private function drawFormNewThread()
    {
        $this->html .= '
        <!--drawFormNewThread()-->
        [<a href="' . WEBPATH . $this->conf['boardNameID'] . '/catalog/">Catalog</a>]
        <center id="mainForm">
            <form id="formThread" action="' . WEBPATH . $this->conf['boardNameID'] . '" method="post" enctype="multipart/form-data">
            <input type="hidden" name="action" value="postNewThread">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">';
        $this->drawMainFormBody("New Thread", true);
        $this->html .= '
            </form>
        </center>';
    }
    private function drawFormNewPost($threadID)
    {
        $this->html .= '
        <!--drawFormNewPost($threadID)-->
        [<a href="' . WEBPATH . $this->conf['boardNameID'] . '/">Return</a>]
        [<a href="#bottom">bottom</a>]
        [<a href="' . WEBPATH . $this->conf['boardNameID'] . '/catalog/">Catalog</a>]
        <center class="theading"><b>Posting mode: Reply</b></center>
        <center id="mainForm">
            <form id="formPost" action="' . WEBPATH . $this->conf['boardNameID'] . '" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="action" value="postToThread">
            <input type="hidden" name="threadID" value="' . $threadID . '">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">';
        $this->drawMainFormBody("New Post");
        $this->html .= '
            </form>
        </center>';
    }
    private function drawFiles($files, $threadID, $strippedDown = false)
    {
        $this->html .= '
        <!--drawFiles($files, $threadID)-->
        <div class="files">';
        $fileNameS = "";
        $filesS = "";
        $count = 0;
        $len = count($files);
        $float = "float";
        $inline = "";
        if ($len > 1) {
            $float = "nofloat";
            $inline = "inLine";
        }
        if (empty($files) && $strippedDown) {
            // When there are no files and strippedDown is true, use the noThumb.png
            $noThumb = $this->conf['iconPack'] . "/noThumb.png";
            $filesS .= '
            <div class="file ' . $inline . '" id="f0">
                <img class="' . $float . ' media" loading="lazy" src="' . $noThumb . '">
            </div>';
        }
        // Loop through each file per post and get the name and file elements and attach them
        foreach ($files as $file) {
            $count = $count + 1;
            $webLocation = WEBPATH . 'threads/' . $threadID . '/';   // Location where a user can make a GET request to the file.
            //drawErrorPageAndDie($file->getWebPath());
            $fileOnWeb = $file->getWebPath();  // File's location on the server.
            $thumbnail = $file->getWebPathThumb();

            $SWFThumb = $this->conf['iconPack'] . "/flash.png";
            $unknownFileThumb = $this->conf['iconPack'] . "/unknownFile.png";
            $missingFileThumb = $this->conf['iconPack'] . "/missingFile.png";

            if ($file->hasThumbnail() == false) {
                $thumbnail = $this->conf['iconPack'] . "/noThumb.png";
            }
            if ($file->isSpoiler()) {
                $thumbnail = $this->conf['iconPack'] . "/spoiler.png";
            }

            if (!$strippedDown) {
                $fileNameS .= '
                <div class="fileName" id="f' . $count . '">
                    [<a href="' . $fileOnWeb . '" download="' . $file->getFileName() . '">
                        download
                    </a>]
                    <small>' . $file->getSizeFormated() . '</small>
                    <a href="' . $fileOnWeb . '" target="_blank" rel="nofollow"> 
                        ' . $file->getFileName() . '
                    </a> 
                </div>';
            }

            $filesS .= '
            <div class="file ' . $inline . '" id="f' . $count . '">';
            if ($file->isMissing()) {
                $filesS .= '
                <img class="' . $float . ' media" loading="lazy" src="' . $missingFileThumb . '">';
            } elseif (in_array($file->getFileExtention(), IMAGE_EXTENTIONS)) {
                if ($strippedDown) {
                    $filesS .= '
                    <img class="' . $float . ' media" src="' . $thumbnail . '" loading="lazy" title="' . $file->getStoredName() . '">';
                } else {
                    $filesS .= '
                    <a href="' . $fileOnWeb . '" class="image" target="_blank" rel="nofollow">
                        <img class="' . $float . ' media" src="' . $thumbnail . '" loading="lazy" title="' . $file->getStoredName() . '">
                    </a>';
                }
            } elseif (in_array($file->getFileExtention(), VIDEO_EXTENTIONS)) {
                if ($strippedDown) {
                    $filesS .= '
                    <img class="' . $float . ' media" src="' . $thumbnail . '" loading="lazy" title="' . $file->getStoredName() . '">';
                } else {
                    $filesS .= '
                    <a href="' . $fileOnWeb . '" class="video" target="_blank" rel="nofollow">
                        <img class="' . $float . ' media" src="' . $thumbnail . '" loading="lazy" title="' . $file->getStoredName() . '">
                    </a>';
                }
            } elseif ($file->getFileExtention() == "swf") {
                if ($strippedDown) {
                    $filesS .= '
                    <img class="' . $float . ' media" src="' . $SWFThumb . '" loading="lazy" title="' . $file->getStoredName() . '">';
                } else {
                    $filesS .= '
                    <a href="' . $fileOnWeb . '" class="swf" target="_blank" rel="nofollow">
                        <img class="' . $float . ' media" src="' . $SWFThumb . '" loading="lazy" title="' . $file->getStoredName() . '">
                    </a>';
                }
            } elseif (in_array($file->getFileExtention(), AUDIO_EXTENTIONS)) {
                if (!$strippedDown) {
                    $filesS .= '<audio loading="lazy" class="media" controls=""><source src="' . $fileOnWeb . '" type="audio/mpeg"></audio>';
                }
            } else {
                if ($strippedDown) {
                    $filesS .= '
                    <img class="' . $float . ' media" src="' . $unknownFileThumb . '" loading="lazy" title="' . $file->getStoredName() . '">';
                } else {
                    $filesS .= '
                    <a href="' . $fileOnWeb . '" target="_blank" rel="nofollow">
                        <img class="' . $float . ' media" src="' . $unknownFileThumb . '" loading="lazy" title="' . $file->getStoredName() . '">
                    </a>';
                }
            }
            $filesS .= '
            </div>';
        }

        $this->html .=
            $fileNameS .
            $filesS .
            '</div>';
    }
    private function drawPost($post, $isOP = false, $isListingMode = false, $omitedPosts = 0)
    {
        global $AUTH;

        $type = "reply";
        if ($isOP) {
            $type = "op";
        }
        $postID = $post->getPostID();
        $threadID = $post->getThreadID();
        $email = $post->getEmail();

        $this->html .= '
            <div class="post ' . $type . '" id="p' . $postID . '">';
        if ($isOP) {
            $this->drawFiles($post->getFiles(), $threadID);
            $this->html .= '<br>';
        }
        $this->html .= '
                <div class="postinfo">
                    <input type="checkbox" name="postIDs[]" value="' . $postID . '">
                    <span class="bigger"><b class="subject">' . $post->getSubject() . '</b></span>
                    <span class="name">';
        if ($email != "") {
            $this->html .= '<a href="mailto:' . $email . '"><b>' . $post->getName() . '</b></a>';
        } else {
            $this->html .= '<b>' . $post->getName() . '</b>';
        }
        $this->html .= '
                    </span>
                    <span class="time">' . date('Y-m-d H:i:s', $post->getUnixTime()) . '</span>
                    <span class="postnum">
                        <a href="' . WEBPATH . $this->conf['boardNameID'] . '/thread/' . $threadID . '/#p' . $postID . '" class="no">No.</a>&nbsp;<a href="' . WEBPATH . $this->conf['boardNameID'] . '/thread/' . $threadID . '/#formPost" title="Quote">' . $postID . '</a>
                    </span>';
        if ($isOP && $isListingMode) {
            $this->html .= '<span>[&nbsp;<a href="' . WEBPATH . $this->conf['boardNameID'] . '/thread/' . $threadID . '/" class="no">Reply</a>&nbsp;]</span>';
        }
        $this->html .= '
                </div>';
        if ($AUTH->isAuth($post->getBoardID())) {
            $this->drawAdminViewPost($post);
        }
        if ($isOP == false) {
            $this->drawFiles($post->getFiles(), $threadID);
        }
        $this->html .= '
                <blockquote class="comment">' . $post->getComment() . '</blockquote>';
        if ($isOP && $isListingMode && $omitedPosts > 0) {
            $this->html .= '<span class="omittedposts">' . $omitedPosts . ' posts omitted. Click Reply to view.</span>';
        }
        $this->html .= '
            </div><br>';
    }
    private function drawPosts($thread, $posts, $isListingMode = false, $omitedPosts = 0)
    {
        global $AUTH;
        $this->html .= '
        <!--drawPosts($thread, $posts, $isListingMode=false ,$omitedPosts=0)-->';
        if (empty($posts)) {
            logError($this->board, "drawPosts() empty array for posts. treadID: " . $thread->getThreadID() . " boardID: " . $thread->getBoardID());
            return;
        }
        foreach ($posts as $post) {
            $isOP = $post->getPostID() == $thread->getOPPostID();
            $this->drawPost($post, $isOP, $isListingMode, $omitedPosts);
        }
    }
    private function drawThread($thread)
    {
        $posts = $thread->getPosts();
        sortPostsByTimeDesending($posts);

        $this->html .= '
        <!--drawThread($thread)-->
        <div id="t' . $thread->getThreadID() . '" class="thread">';
        if ($thread->getStatus() == "archived") {
            $this->html .= '<div class="archived">this thread is archived</div>';
        }
        $this->drawPosts($thread, $posts);
        $this->html .= '
        </div>';
        $this->html .= '[<a href="#top">top</a>]';
    }
    private function drawThreadListing($threads)
    {
        $this->html .= '
        <!--drawThreadListing($threads)-->';
        foreach ($threads as $thread) {
            //TODO NEEDS FIXING.
            // if op post and child has same time. there is a chance they will swap positions on thread listing and 2nd post will just be OP post.
            // maybe make a paramitr for just OP and remove op from the drawing list if it exist?
            $posts = $thread->getLastNPost($this->conf['postPerThreadListing']);
            sortPostsByTimeDesending($posts);
            $posts[0] = $thread->getPostByID($thread->getOPPostID());
            $omitedPost = $thread->getPostCount() - sizeof($posts);

            $this->html .= '
            <div id="t' . $thread->getThreadID() . '" class="thread">';
            $this->drawPosts($thread, $posts, true, $omitedPost);
            $this->html .= '
            </div>';
        }
    }
    private function drawPageNumbers($curentPage)
    {
        global $THREADREPO;

        $maxThreadsPerPage = $this->conf['threadsPerPage'];
        $threadCount = $THREADREPO->getThreadCount($this->conf);

        if ($threadCount >= $this->conf['maxActiveThreads']) {
            $threadCount = $this->conf['maxActiveThreads'];
        }

        $pages = ceil($threadCount / $maxThreadsPerPage);

        $this->html .= '
        <!--drawPageNumbers($curentPage)-->
        <div class="pages">';
        if ($curentPage > 1) {
            $this->html .= '
            <a href="/' . $this->conf['boardNameID'] . '/' . 1 . '">&lt;&lt;</a>
            [&nbsp;<a href="/' . $this->conf['boardNameID'] . '/' . $curentPage - 1 . '">back</a>&nbsp;]';
        }

        for ($i = 1; $i <= $pages; $i++) {
            if ($curentPage == $i) {
                $this->html .= '
                [&nbsp;<b>' . $i . '</b>&nbsp;]';
            } else {
                $this->html .= '
                [&nbsp;<a href="/' . $this->conf['boardNameID'] . '/' . $i . '">' . $i . '</a>&nbsp;]';
            }
        }

        if ($curentPage < $pages) {
            $this->html .= '
            [&nbsp;<a href="/' . $this->conf['boardNameID'] . '/' . $curentPage + 1 . '">next</a>&nbsp;]
            <a href="/' . $this->conf['boardNameID'] . '/' . $pages . '">&gt;&gt;</a>';
        }
        $this->html .= '</div>';
    }

    /* these functions below belong to admin useage */
    private function drawAdminBar()
    {
        // this bar is the bard you see that will be at the top when you are logged in.
        global $AUTH;
        $this->html .= '
        <!--drawAdminBar()-->';
        $this->html .= '<center class="theading3"><b>Logged in as a: ' . $AUTH . '</b></center>';
        $this->html .= '
        <div class="adminbar">';
        if ($AUTH->isSuper()) {
            $this->html .= '
                <span class="unlistedBoards">
                UNLISTED : ';
            $this->drawNavGroup(getBoardListing(true));
            $this->html .= '
                </span>';
        }
        $this->html .= '
            ACTIONS :&nbsp;';
        $this->drawLogOutForm();
        $this->html .= '[<a href="' . WEBPATH . boardIDToName($this->board->getBoardID()) . '/admin/postListing" >admin post view</a>]
        </div>';
    }
    private function drawPostIP($post)
    {
        global $AUTH;
        $ip = $post->getIP();

        if (!$AUTH->isAdmin($post->getBoardID())) {
            $ipParts = explode('.', $ip);

            if (count($ipParts) == 4) {
                $ip = $ipParts[0] . '.' . $ipParts[1] . '.***.***';
            } else {
                $ip = 'Invalid IP';
            }
        }
        if ($AUTH->isModerator($post->getBoardID()) && $this->conf['allowModsToSeeIPs']) {
            $ip = $post->getIP();
        }
        $this->html .=
            '<span>
            [<a class="postByIP" href="' . WEBPATH . $post->getConf()['boardNameID'] . '/admin/postListing/byIP/' . $post->getPostID() . '">' . $ip . '</a>]
        </span>';
    }
    public function drawCustomButton($text, $class, $href)
    {
        $this->html .=
            '<span>
            [<a class="' . $class . '" href="' . $href . '">' . $text . '</a>]
        </span>';
    }
    private function drawBanButton($post)
    {
        $this->drawCustomButton("ban", "banButton", WEBPATH . $post->getConf()['boardNameID'] . '/admin/ban/' . $post->getPostID());
    }
    private function drawEditButton($post)
    {
        $this->drawCustomButton("edit", "editButton", WEBPATH . $post->getConf()['boardNameID'] . '/admin/edit/' . $post->getPostID());
    }
    private function drawAdminViewPost($post)
    {
        // this is what gets attached to every post when you are logged in
        $this->html .= '
        <!--drawAdminViewPost($post)-->
        <div class="adminView">';
        $this->drawPostIP($post);
        //$this->drawEditButton($post);
        $this->drawBanButton($post);
        $this->html .= '</div><br>';
    }
    private function drawLoginForm()
    {
        $this->html .= '
        <!--drawLoginForm()-->
        <center class="loginForm">
        <form method="POST" action="' . WEBPATH . 'admin" enctype="multipart/form-data">
            <input type="hidden" name="action" value="login">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            <input type="password" id="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        </center>';
    }
    private function drawLogOutForm()
    {
        $this->html .= '
        <!--drawLogOutForm()-->
        <form method="post" action="' . WEBPATH . $this->conf['boardNameID'] . '/admin" enctype="multipart/form-data">
            <input type="hidden" name="action" value="logout">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            [<button type="submit" class="hyperButton">Logout</button>]
        </form>';
    }

    private function drawFormCreateCatagory()
    {
        global $BANREPO;
        $categories = $BANREPO->loadCategories();
        $this->html .= '
        <!--drawFormCreateBoard()-->
        <center class="adminForm">
        <h3><b>Create Ban Catagories</b></h3>
        <form method="post" action="' . WEBPATH . $this->conf['boardNameID'] . '/admin" enctype="multipart/form-data">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            <input type="hidden" name="action" value="createCategory">
            <table>
            <tr>
                <td class="accent"><label for="newCategoryName">New Category Name:</label></td>
                <td><input type="text" id="newCategoryName" name="newCategoryName" required></td>
                <td><button type="submit">Create Category</button></td>
            </tr>
            </table>
        </form>
        <details><summary>Current Categories</summary>';
        foreach ($categories as $category) {
            $this->html .= '<div>' . htmlspecialchars($category) . '</div>';
        }
        $this->html .= '
        </details>
        </center>';

    }

    private function drawFormCreateBoard()
    {
        $this->html .= '
        <!--drawFormCreateBoard()-->
        <center class="adminForm">
        <h3><b>Create board form</b></h3>
        <form method="post" action="' . WEBPATH . $this->conf['boardNameID'] . '/admin" enctype="multipart/form-data">
            <input type="hidden" name="action" value="createBoard">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            <table>
            <tr>
                <td class="accent"><label for="boardURLName">BOARD URL NAME</label></td>
                <td><input type="text" id="boardURLName" name="boardURLName" maxlength="' . MAX_INPUT_LENGTH . '"></td>
            </tr>
            <tr>
                <td class="accent"><label for="boardTitle">BOARD TITLE</label></td>
                <td><input type="text" id="boardTitle" name="boardTitle" maxlength="' . MAX_INPUT_LENGTH . '">
                    <button type="submit">Create Board</button>
                </td>
            </tr>
            <tr>
                <td class="accent"><label for="boardDescription">BOARD DESCRIPTION</label></td>
                <td><textarea type="text" id="boardDescription" name="boardDescription" cols="48" rows="4" maxlength="' . MAX_INPUT_LENGTH . '"></textarea></td>
            </tr>
            <tr>
                <td class="accent"><label for="boardUnlisted">IS UNLISTED</label></td>
                <td><input type="checkbox" id="boardUnlisted" name="boardUnlisted" checked></td>
            </tr>
            </table>
        </form>
        </center>';
    }
    private function drawFormDeleteBoard()
    {
        $boardConfs = getAllBoardConfs(); // Now from DB
        $currentBoardID = $this->board->getBoardID();

        $this->html .= '
        <!--drawFormDeleteBoard()-->
        <center class="adminForm">
        <h3><b>Delete board form</b></h3>
        <form method="post" action="' . WEBPATH . $this->conf['boardNameID'] . '/admin">
            <input type="hidden" name="action" value="deleteBoard">
            <input type="hidden" name="boardID" value="' . $currentBoardID . '">
            <table>
            <tr>
                <td class="accent"><label for="boardList">SELECT BOARD TO DELETE</label></td>
                <td>
                    <select id="boardList" name="boardList">';
        foreach ($boardConfs as $conf) {
            $boardID = $conf['boardID'];
            $boardNameID = $conf['config']['boardNameID'] ?? 'unknown';
            $selected = ($boardID == $currentBoardID) ? ' selected' : '';
            $this->html .= '<option value="' . $boardID . '"' . $selected . '>' . $boardNameID . '</option>';
        }
        $this->html .= '
                    </select>
                </td>
                <td><details><summary>show delete button</summary><button type="submit">Delete Board</button></td></details>
            </tr>
            </table>
        </form>
        </center>';
    }
    private function drawFormBanPost($post)
    {
        global $AUTH;
        global $BANREPO;
        $categories = $BANREPO->loadCategories();
        $banMessage = htmlspecialchars('<br><br><b class="warning">' . $this->conf['banMessage'] . '</b><img style="vertical-align: baseline;" loading="lazy" src="' . $this->conf['iconPack'] . '/banhammer.png">');
        $this->html .= '
        <!--drawFormBanPost($post)-->
        <script src="' . $this->conf['staticPath'] . '/js/system/adminForm.js"></script>
        <div class=banForm>
        <form method="post" action="' . WEBPATH . 'admin.php" enctype="multipart/form-data">
            <input type="hidden" name="action" value="banPost">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            <input type="hidden" name="postID" value="' . $post->getPostID() . '">';
        $this->html .= '
            <table>
            <tr>
                <td class="accent"><label for="banForever">BAN FOREVER?</label></td>
                <td><input type="checkbox" id="banForever" name="banForever"></td>
            </tr>
            <tr>
                <td class="accent"><label for="banFile">BAN FILES?</label></td>
                <td><input type="checkbox" id="banFile" name="banFile"></td>
            </tr>
            <tr>
                <td class="accent"><label for="domainString">BAN DOMAIN?</label></td>
                <td><input type="checkbox" id="banDomain" name="bandomain">
                    <input type="text" id="domainString" name="domainString">(using js? click link to add)</td>
            </tr>
            <tr>
                <td class="accent"><label for="banIP">BAN IP?</label></td>
                <td><input type="checkbox" id="banIP" name="banIP" checked></td>
            </tr>
            <tr>
                <td class="accent"><label for="rangeBan">RANGE BAN</label></td>
                <td>
                    <select id="rangeBan" name="rangeBan">
                        <option value="none" selected>No Range Ban</option>
                        <option value="range1">0.0.0.*</option>
                        <option value="range2">0.0.*</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="accent"><label for="deletePost">DELETE POST?</label></td>
                <td><input type="checkbox" id="deletePost" name="deletePost"></td>
            </tr>';
        if ($AUTH->isSuper()) {
            $this->html .= '
                <tr>
                    <td class="accent"><label for="isGlobal">BAN GLOBALY?</label></td>
                    <td><input type="checkbox" id="isGlobal" name="isGlobal"></td>
                </tr>';
        }

        $this->html .= '
            <tr>
                <td class="accent"><label for="banTime">BANED TIME</label></td>
                <td><input type="text" id="banTime" name="banTime" value="' . $this->conf['defaultBanTime'] . '">(ex. 1w 2d 3h 4min, 0 for warning)</td>
            </tr>
            <tr>
                <td class="accent"><label for="banReason">BAN REASON</label></td>
                <td><textarea type="text" id="banReason" name="banReason" cols="28" rows="2">No reason given.</textarea></td>
            </tr>
            <tr>
                <td class="accent"><label for="publicMessage">BAN MESSAGE</label></td>
                <td><textarea type="text" id="publicMessage" name="publicMessage" cols="48" rows="4">' . $banMessage . '</textarea></td>
            </tr>
            <tr>
                <td class="accent"><label for="isPublic">MAKE IP PUBLIC?</label></td>
                <td><input type="checkbox" id="isPublic" name="isPublic"></td>
            </tr>
            <tr>
                <td class="accent"><label for="category">CATEGORY</label></td>
                <td>
                    <select id="category" name="category" required>
                        <option value="" disabled selected>Select a category</option>';
        foreach ($categories as $category) {
            if (is_string($category)) {
                $this->html .= '<option value="' . htmlspecialchars($category) . '">' . htmlspecialchars($category) . '</option>';
            }
        }
        $this->html .= '
                    </select>
                </td>
            </tr>
            <tr>
                <td class="accent"><label for="isPreseptual">BAN PRESEPTUALY?</label></td>
                <td><input type="checkbox" id="isPreseptual" name="isPreseptual"></td>
            </tr>
            </table>
            <button type="submit" class="bigRedButton">BAN!</button>
        </form>
        </div>';
    }

    private function drawAdminFileListing($files, $threadID)
    {
        if (empty($files)) {
            return;
        }
        $this->html .= '<details><summary>preview</summary>';
        $this->drawFiles($files, $threadID);
        $this->html .= '</details>';
    }
    private function drawPostsAdminListing($posts)
    {
        $this->html .= '<!-- drawPostsAdminListing($posts)-->
        <hr><table class="adminTable" width="100%" style="font-size:10pt;">
        <tr>
            <td width="3%"><tt><b>PID</b></tt></td>
            <td width="5%"><tt><b>IP</b></tt></td>
            <td width="8%"><tt><b>Actions</b></tt></td>
            <td width="8%"><tt><b>Name</b></tt></td>
            <td width="30%"><tt><b>Comment</b></tt></td>
            <td width="30%"><tt><b>Files</b></tt></td>
            <td width="6%"><tt><b>Subject</b></tt></td>
            <td width="4%"><tt><b>Email</b></tt></td>
            <td width="4%"><tt><b>Board</b></tt></td>
            <td width="2%"><tt><b>TID</b></tt></td>
        </tr>';
        foreach ($posts as $post) {
            $this->html .=
                '<tr>
                <td><font size="2">' . $post->getPostID() . '</font></td>
                <td><font size="2">';
            $this->drawPostIP($post);
            $this->html .= '</font></td>
                <td>';
            $this->drawBanButton($post);
            //$this->drawEditButton($post);
            $this->html .=
                '</td>
                <td><font size="2">' . $post->getName() . '</font></td>
                <td>
                    <div class="comment">
                        <div class="comment-box">' . $post->getComment() . '</div>
                    </div>
                </td>
                <td>';
            $this->drawAdminFileListing($post->getFiles(), $post->getThreadID());
            $this->html .= '
                </td>
                <td><font size="2">' . $post->getSubject() . '</font></td>
                <td><font size="2">' . $post->getEmail() . '</font></td>
                <td><font size="2">' . boardIDToName($post->getBoardID()) . '</font></td>
                <td><font size="2">' . $post->getThreadID() . '</font></td>
            </tr>';
        }
        $this->html .= '</table><hr>';
    }
    private function drawFormCatalog($sort, $keyword, $caseSensitive)
    {
        $this->html .= '<!--drawFormCatalog()-->
        <form method="post" action="' . WEBPATH . $this->conf['boardNameID'] . '">
            <input type="hidden" name="action" value="catalog">
            <input type="hidden" name="boardID" value="' . $this->board->getBoardID() . '">
            <span>Sort by:</span>
            <select name="sort" style="display: inline-block">';
        if ($sort == 'dateCreated') {
            $this->html .= '
                <option value="bump">Bump order</option>
                <option selected="" value="dateCreated">Creation date</option>';

        } else {
            $this->html .= '
                <option selected="" value="bump">Bump order</option>
                <option value="dateCreated">Creation date</option>';
        }
        $this->html .=
            '</select>
            <button type="submit">Apply</button>
            <br>';
        if ($caseSensitive == 1) {
            $this->html .= '[<label><input name="case" type="checkbox" value="1" checked>Case sensitive</label>]';

        } else {
            $this->html .= '[<label><input name="case" type="checkbox" value="1">Case sensitive</label>]';

        }

        if ($keyword == '') {
            $this->html .= '<input name="keyword" type="search" id="keywordSerch" placeholder="Search" >';

        } else {
            $this->html .= '<input name="keyword" type="search" id="keywordSerch" placeholder="Search" value="' . $keyword . '">';

        }
        $this->html .= '</form>';
    }
    private function drawCatalog($threads)
    {
        $this->html .= '<!--drawCatalogPage($threads)-->';
        $this->html .= '[<a href="' . WEBPATH . $this->conf['boardNameID'] . '/">Return</a>]';
        $this->html .= '[<a href="#bottom">bottom</a>]';
        $this->html .= '<center class="theading2"><b>Catalog</b></center>';
        $this->html .= '<center id=catalog>';

        foreach ($threads as $thread) {
            $post = $thread->getOPPost();
            $threadID = $thread->getThreadID();
            $sub = $post->getSubject();
            if ($sub == '') {
                $sub = "no subject";
            }
            $this->html .= '<div class=catalogItem>
            <a href="' . WEBPATH . $this->conf['boardNameID'] . '/thread/' . $threadID . '/" class="no">';
            $this->drawFiles($post->getFiles(), $threadID, true);
            $this->html .= '
            </a>
            <small><b class="title">' . $sub . '</b> Replies:
            <span title="Replies">' . $thread->getPostCount() . '</span></small><br>
	        <small class=catComment>' . $post->getComment() . '</small>';
            $this->html .= '</div>';
        }
        $this->html .= '</center>';
        $this->html .= '[<a href="#top">top</a>]';
    }

    public function drawFormBoardConfigEditor()
    {
        $boardNameID = htmlspecialchars($this->board->getBoardNameID());

        $this->html .= <<<HTML
        <!-- drawFormBoardConfigEditor() -->
        <center class="adminForm">
        <h3><b>Edit Board Config</b></h3>
        <form method="get" action="/{$boardNameID}/admin/edit">
            <button type="submit">Open Config Editor</button>
        </form>
        </center>
        HTML;
    }

    private function drawFormExportDatabase()
    {

    }
    private function drawFormPremoteUser()
    {

    }
    private function drawFormDemoteUser()
    {

    }
    private function drawFormChangeBoardSettings()
    {

    }
    private function drawFormManageBans()
    {

    }



    /* drawBase is the defualt templet that all pages will be built from unless specifies else wize */
    private function drawBase(array $functions)
    {
        global $AUTH;
        global $board;
        $this->html .= '
        <!DOCTYPE html>
        <html lang="en-US">';
        $this->drawHead();
        $this->html .= '<body><div id="top"></div>';
        $this->drawNavBar();
        if ($AUTH->isAuth($board->getBoardID())) {
            $this->drawAdminBar();
        }
        $this->drawBoardTitle();

        foreach ($functions as $func) {
            if (isset($func['function']) && isset($func['params'])) {
                call_user_func_array($func['function'], $func['params']);
            }
        }

        $this->html .= '</body><div id="bottom"></div>';
        if ($this->conf['drawFooter']) {
            $this->drawFooter();
        }
    }

    public function draw404($text)
    {
        header("HTTP/1.1 404 Not Found");

        $functions = [
            [
                'function' => function () use ($text) {
                    $this->html .= "
                <center><h1>404 page not found</h1>
                <h4>$text</h4></center>";
                },
                'params' => []
            ],

            ['function' => [$this, 'drawPageNumbers'], 'params' => [-1]]
        ];
        $this->drawBase($functions);

        echo $this->html;

    }
    public function drawThreadListingPage($pageNumber = 1)
    {
        global $THREADREPO;

        $maxPage = ceil($this->conf['maxActiveThreads'] / $this->conf['threadsPerPage']);
        if ($pageNumber - 1 >= $maxPage || $pageNumber == 0) {
            echo $this->draw404("invalid page number");
            return;
        }

        // as the threads start at 0 but drawing starts at page 1
        $threads = $THREADREPO->loadThreadsByPage($this->conf, $pageNumber - 1);

        $drawThreadListingWraped = function ($threads) {
            $this->postManagerWraper([$this, 'drawThreadListing'], [$threads]);
        };

        $functions = [
            ['function' => [$this, 'drawFormNewThread'], 'params' => []],
            ['function' => $drawThreadListingWraped, 'params' => [$threads]],
            ['function' => [$this, 'drawPageNumbers'], 'params' => [$pageNumber]]
        ];
        $this->drawBase($functions);

        echo $this->html;
    }
    public function drawThreadPage($thread)
    {
        $drawThreadWraped = function ($thread) {
            $this->postManagerWraper([$this, 'drawThread'], [$thread]);
        };

        $functions = [
            ['function' => [$this, 'drawFormNewPost'], 'params' => [$thread->getThreadID()]],
            ['function' => $drawThreadWraped, 'params' => [$thread]]
        ];
        $this->drawBase($functions);

        echo $this->html;
    }
    public function drawLoginPage()
    {
        $functions = [
            ['function' => [$this, 'drawLoginForm'], 'params' => []]
        ];
        $this->drawBase($functions);

        echo $this->html;
    }


    public function drawBanUserPage($post)
    {
        $functions = [
            ['function' => [$this, 'drawFormBanPost'], 'params' => [$post]],
            ['function' => [$this, 'drawPost'], 'params' => [$post]],
            //['function' => [$this, 'drawFormBanPost'], 'params' => [$post]]

        ];
        $this->drawBase($functions);

        echo $this->html;
    }
    public function drawEditPostPage($post)
    {

    }
    public function drawAdminPostListingPage($posts)
    {
        $functions = [
            ['function' => [$this, 'drawPostsAdminListing'], 'params' => [$posts]],
            //['function' => [$this, 'drawFormBanPost'], 'params' => [$post]]

        ];
        $this->drawBase($functions);

        echo $this->html;
    }
    public function drawCatalogPage($sort = 'bump', $keyword = '', $caseSensitive = false)
    {
        $threads = $this->board->getThreads();
        switch ($sort) {
            case 'bump':
                sortThreadByBump($threads);
                break;
            case 'dateCreated':
                sortThreadByDateCreated($threads);
                break;
            default:
                sortThreadByBump($threads);
                break;
        }
        $fThreads = filterThreadsByKeyword($threads, $keyword, $caseSensitive);
        $functions = [
            ['function' => [$this, 'drawFormCatalog'], 'params' => [$sort, $keyword, $caseSensitive]],
            ['function' => [$this, 'drawCatalog'], 'params' => [$fThreads]],
        ];
        $this->drawBase($functions);

        echo $this->html;
    }

    public function drawAdminPage()
    {
        global $AUTH;
        $this->html .= '
        <!--drawAdminPage()-->';

        $functions = [];

        $id = $this->board->getBoardID();
        $isAdmin = $AUTH->isAdmin($id);
        $isMod = $AUTH->isModerator($id);
        $isSuper = $AUTH->isSuper();

        if ($isAdmin) {
            if ($isSuper) {
                $functions[] = ['function' => [$this, 'drawFormCreateBoard'], 'params' => []];
                $functions[] = ['function' => [$this, 'drawFormDeleteBoard'], 'params' => []];
                $functions[] = ['function' => [$this, 'drawFormExportDatabase'], 'params' => []];
            }
            $functions[] = ['function' => [$this, 'drawFormBoardConfigEditor'], 'params' => []];
            $functions[] = ['function' => [$this, 'drawFormPremoteUser'], 'params' => []];
            $functions[] = ['function' => [$this, 'drawFormDemoteUser'], 'params' => []];
            $functions[] = ['function' => [$this, 'drawFormChangeBoardSettings'], 'params' => []];
        }
        if ($isMod or $isAdmin) {
            if ($isSuper) {
                $functions[] = ['function' => [$this, 'drawFormCreateCatagory'], 'params' => []];
            }
        }

        $this->drawBase($functions);

        echo $this->html;
    }
}