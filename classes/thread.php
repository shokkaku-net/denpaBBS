<?php
require_once __DIR__ . '/post.php';
require_once __DIR__ . '/hook.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/fileHandler.php';
require_once __DIR__ . '/repos/repoPost.php';

class threadClass
{
    private $conf;
    private $posts = [];
    private $status = 'active';
    private $lastPosts = []; //flipped array. last post is first index
    private $threadID;
    private $lastBumpTime;
    private $OPPostID;
    private $postCount;
    private $isPostsFullyLoaded = false;
    private $isPostCountFullyLoaded = false;
    private $postRepo;
    public function __construct($conf, $lastBumpTime, $threadID = -1, $OPPostID = -1, $status = 'active')
    {
        $this->conf = $conf;
        $this->threadID = $threadID;
        $this->lastBumpTime = $lastBumpTime;
        $this->OPPostID = $OPPostID;
        $this->status = $status;
        $this->postRepo = PostRepoClass::getInstance();
    }
    public function bump()
    {
        if ($this->getPostCount() >= $this->conf['postUntilCantBump']) {
            return;
        } elseif ($this->getOPPost()->getUnixTime() - time() > $this->conf['timeUntilCantBump']) {
            return;
        } else {
            $this->lastBumpTime = time();
        }
    }
    public function getLastBumpTime()
    {
        return $this->lastBumpTime;
    }
    public function getThreadID()
    {
        return $this->threadID;
    }
    public function getStatus()
    {
        return $this->status;
    }
    public function getBoardID()
    {
        return $this->conf['boardID'];
    }
    public function getBoardNameID()
    {
        return $this->conf['boardNameID'];
    }
    public function getPostCount()
    {
        if ($this->isPostCountFullyLoaded != true) {
            $this->postCount = $this->postRepo->getPostCount($this->conf, $this->threadID);
            $this->isPostCountFullyLoaded = true;
        }
        return $this->postCount;
    }
    public function getOPPostID()
    {
        return $this->OPPostID;
    }
    public function getConf()
    {
        return $this->conf;
    }
    /* build postObj from postrequest -> validate postObj -> save postObj to database */ // -> redraw pages -> redirect user */
    public function getPosts()
    {
        if ($this->isPostsFullyLoaded != true) {
            $this->posts = $this->postRepo->loadPostsByThreadID($this->conf, $this->threadID);
            $this->isPostsFullyLoaded = true;
        }
        return $this->posts;
    }
    public function getPostByID($postID)
    {
        if (!$this->isPostsFullyLoaded && !isset($this->posts[$postID])) {
            $this->posts[$postID] = $this->postRepo->loadPostByThreadID($this->conf, $this->threadID, $postID);
        }
        return $this->posts[$postID];
    }
    public function getOPPost()
    {
        return $this->getPostByID($this->OPPostID);
    }
    public function getLastNPost($num)
    {
        if ($this->isPostsFullyLoaded != true || !count($this->lastPosts) >= $num) {
            $this->lastPosts = $this->postRepo->loadNPostByThreadID($this->conf, $this->threadID, $num);
        }
        return array_reverse($this->lastPosts);
    }
    public function setOPPostID($postID)
    {
        $this->OPPostID = $postID;
    }
    public function setThreadID($threadID)
    {
        $this->threadID = $threadID;
    }
    public function setPostCount($postCount)
    {
        $this->postCount = $postCount;
    }
    public function getDrawData($postPreviewCount = -1)
    {
        $data = [
            'id' => $this->threadID
        ];
        if ($this->getPostCount() > $postPreviewCount && $postPreviewCount != -1) {
            $data = array_merge($data, [
                'omitedPosts' => $this->getPostCount() - $postPreviewCount
            ]);
        }
        if ($postPreviewCount == -1) {
            $posts = $this->getPosts();
        } else {
            $posts = $this->getLastNPost($postPreviewCount);
        }
        $op = $this->getOPPost();

        $pdata = $op->getDrawData(true);
        $pdata['postLocation'] = WEBPATH . $this->getBoardNameID() . "/thread/" . $this->threadID;
        $data['posts'][] = $pdata;

        foreach ($posts as $post) {
            if ($post->getPostID() == $op->getPostID()) {
                continue;
            }
            $pdata = $post->getDrawData();
            $pdata['postLocation'] = WEBPATH . $this->getBoardNameID() . "/thread/" . $this->threadID;

            $data['posts'][] = $pdata;

        }

        return $data;
    }

}


/*new thread 
$repoP = postRepoClass::getInstance();
$repoT = ThreadRepoClass::getInstance();

$t = new threadClass([],4);
$p = new PostDataClass([],0,0,0,0,0,0,0,$t->getThreadID());
$repoP->createPost([], $p);
$repoT->createThread([], $t,$p->getPostID());
$repoP->updatePost([], $p);
*/
