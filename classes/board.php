<?php

class boardClass
{
	public int $boardID = 0;
	public int $lastPostID = 0;
	public string $boardNameID = '';
	public array $conf = [];
	private array $threads = [];
	private bool $threadsFullyLoaded = false;
	private $repo;

	public function __construct(int $boardID = 0, int $lastPostID = 0, array $conf = [])
	{
		$this->boardID = $boardID;
		$this->lastPostID = $lastPostID;

		// Load base defaults if nothing passed
		$default = require __DIR__ . '/../baseBoardConfig.php';
		$this->conf = array_replace_recursive($default, $conf);

		if (isset($this->conf['boardNameID'])) {
			$this->boardNameID = $this->conf['boardNameID'];
		}

		$this->repo = ThreadRepoClass::getInstance();

		// Optional: set timezone if config provides one
		if (isset($this->conf['timeZone'])) {
			date_default_timezone_set($this->conf['timeZone']);
		}
	}
	public function getThreads()
	{
		if ($this->threadsFullyLoaded === false) {
			$this->threads = $this->repo->loadThreads($this->boardID);
			$this->threadsFullyLoaded = true;
		}
		return $this->threads;
	}

	public function getThreadByID($threadID)
	{
		if (!isset($this->threads[$threadID])) {
			$this->threads[$threadID] = $this->repo->loadThreadByID($this->boardID, $threadID);
		}
		return $this->threads[$threadID];
	}


	public function getId(): int
	{
		return $this->boardID;
	}

	public function setBoardID(int $id): void
	{
		$this->boardID = $id;
	}

	public function getLastPostID(): int
	{
		return $this->lastPostID;
	}

	public function setLastPostID(int $id): void
	{
		$this->lastPostID = $id;
	}

	public function getNameID(): string
	{
		return $this->boardNameID;
	}

	public function getConf(): array
	{
		return $this->conf;
	}

	public function setConf(array $conf): void
	{
		$this->conf = $conf;
		$this->conf['boardID'] = $this->boardID;
		$this->boardNameID = $conf['boardNameID'];
	}

	public function prune()
	{
		$maxActiveThreads = $this->conf['maxActiveThreads'] ?? 150;
		$maxArchivedThreads = $this->conf['maxArchivedThreads'] ?? 150;
		$totalAllowedThreads = $maxActiveThreads + $maxArchivedThreads;

		$THREADREPO = ThreadRepoClass::getInstance();
		$count = $THREADREPO->getThreadCount($this->boardID);

		if ($count > $totalAllowedThreads) {
			$threadIDs = $THREADREPO->fetchThreadIDsForDeletion($this->boardID, $totalAllowedThreads);
			$THREADREPO->deleteThreadByID($this->boardID, $threadIDs);

			foreach ($threadIDs as $threadID) {
				deleteFilesInThreadByID($threadID);
			}
		}

		$THREADREPO->archiveOldThreads($this->boardID, $maxActiveThreads);
	}
	public function getDrawData()
	{
		$data = [
			'title' => [
				'logo' => $this->getLogoPath(),
				'title' => $this->getTitle(),
				'subtitle' => $this->getSubtitle(),
			],
			'style' => $this->getStyle(),
			'iconpack' => $this->getIconpack(),
			'pagetitle' => $this->getPageTitle(),
		];
		$data['navLeft'][] = $this->conf['navLinksLeft'];
		$data['navRight'][] = $this->conf['navLinksRight'];
		return $data;

	}
	public function getThreadsByPage($page)
	{
		return $this->repo->loadThreadsByPage($this->boardID, $page);
	}
	public function getPostPreviwCount()
	{
		return $this->conf['postPerThreadListing'];
	}
	public function buildPageData($page)
	{

		$maxThreadsPerPage = $this->conf['threadsPerPage'];
		$threadCount = $this->repo->getThreadCount($this->boardID);

		if ($threadCount >= $this->conf['maxActiveThreads']) {
			$threadCount = $this->conf['maxActiveThreads'];
		}

		$pages = (int) ceil($threadCount / $maxThreadsPerPage);
		$pageData = [];

		$pageData['baseurl'] = WEBPATH . $this->conf['boardNameID'] . '/';
		$pageData['curPage'] = $page;

		if ($page > 1) {
			$pageData['prevPage'] = $page - 1;
			$pageData['startPage'] = 1;
		} else {
			$pageData['startPage'] = 1;
		}

		if ($page < $pages) {
			$pageData['nextPage'] = $page + 1;
		}

		$pageData['endPage'] = $pages;

		return $pageData;
	}

	public function getLogoPath()
	{
		return $this->conf['boardLogoPath'];
	}
	public function getTitle()
	{
		return $this->conf['boardTitle'];

	}
	public function getSubtitle()
	{
		return $this->conf['boardSubTitle'];

	}
	public function getStyle()
	{
		return $this->conf['style'];

	}
	public function getIconpack()
	{
		return $this->conf['iconPack'];

	}
	public function getPageTitle()
	{
		return $this->conf['boardNameID'];

	}
}
