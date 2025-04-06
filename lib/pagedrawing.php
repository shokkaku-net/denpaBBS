<?php
use eftec\bladeone\BladeOne;

function drawBoardThreadListing($board, $page = 1)
{
    $blade = new BladeOne(BASEDIR . '/views', BASEDIR . '/cache', BladeOne::MODE_DEBUG);
    $nameID = $board->getBoardNameID();


    $drawData = [];

    $threadData = $board->getThreadsByPage($page);

    foreach ($threadData as $thread) {
        $drawData['threads'][] = $thread->getDrawData($board->getPostPreviwCount());
    }
    $drawData = array_merge($drawData, [
        'boardbuttons' => [
            [
                'type' => 'button',
                'name' => 'Bottom',
                'location' => "#bottom",
            ],
            [
                'type' => 'button',
                'name' => 'Catalog',
                'location' => WEBPATH . $nameID . "/catalog",
            ],
        ],
        'mode' => 'listing',
        'mainform' => [
            'formID' => 'formThread',
            'endpoint' => WEBPATH . $nameID,
            'method' => 'POST',
            'formAction' => 'postNewThread',
            'nameID' => $nameID,
            'inputs' => [
                [
                    'input' => 'text',
                    'id' => 'name',
                    'lable' => 'Name',
                    'name' => 'name',
                    'properties' => 'autocomplete="off" maxlength="127"'
                ],
                [
                    'input' => 'text',
                    'id' => 'email',
                    'lable' => 'Email',
                    'name' => 'email',
                    'properties' => 'autocomplete="off" maxlength="127"'
                ],
                [
                    'input' => 'text',
                    'id' => 'subject',
                    'lable' => 'Subject',
                    'name' => 'subject',
                    'properties' => 'autocomplete="off" maxlength="127"',
                    'submit' => 'New Thread'
                ],
                [
                    'input' => 'textarea',
                    'id' => 'comment',
                    'lable' => 'Comment',
                    'name' => 'comment',
                    'properties' => 'required cols="48" rows="4" maxlength="2048"'
                ],
                [
                    'input' => 'file',
                    'id' => 'file',
                    'lable' => 'Files',
                    'name' => 'upfile[]',
                    'properties' => 'required cols="48" rows="4" maxlength="2048" require=""'
                ],
                [
                    'input' => 'password',
                    'id' => 'password',
                    'lable' => 'Password',
                    'name' => 'password',
                    'properties' => 'autocomplete="off" maxlength="8"',
                ],
            ],
        ],
    ]);
    $drawData['paging'] = $board->buildPageData($page);
    $drawData['navLeft'][] = getDrawnBoardListing();


    $boardData = $board->getDrawData();
    $drawData = array_merge($drawData, array_diff_key($boardData, ['navLeft' => 1, 'navRight' => 1]));

    // merge navLeft properly
    $drawData['navLeft'] = array_merge(
        $drawData['navLeft'] ?? [],
        $boardData['navLeft'] ?? []
    );

    // merge navRight properly
    $drawData['navRight'] = array_merge(
        $drawData['navRight'] ?? [],
        $boardData['navRight'] ?? []
    );

    $drawData['navRight'][] = [
        [
            'name' => 'admin',
            'url' => WEBPATH . $nameID . '/admin'
        ],
    ];

    echo $blade->run("pages.board", $drawData);
    /*
    $adminBar = [
        'adminbar' => [
            [
                'lable' => 'unlisted',
                'buttons' => [
                    [
                        'type' => 'hyperbutton',
                        'name' => 'logout',
                        'endpoint' => '/intro/admin',
                        'action' => 'logout'
                    ],
                    [
                        'type' => 'hyperbutton',
                        'name' => 'logout',
                        'endpoint' => '/intro/admin',
                        'action' => 'logout'
                    ],
                ]
            ],
            [
                'lable' => 'actions',
                'buttons' => [
                    [
                        'type' => 'hyperbutton',
                        'name' => 'logout',
                        'endpoint' => WEBPATH . $nameID . '/admin',
                        'action' => 'logout'
                    ],
                ]
            ]

        ]
    ];
    */
    exit;

}

function drawBoardThread($board, $threadID)
{
    $blade = new BladeOne(BASEDIR . '/views', BASEDIR . '/cache', BladeOne::MODE_DEBUG);
    $nameID = $board->getBoardNameID();

    $drawData = [];

    $threadData = $board->getThreadByID($threadID);
    $drawData['threads'][] = $threadData->getDrawData();

    $drawData = array_merge($drawData, [
        'boardbuttons' => [
            [
                'type' => 'button',
                'name' => 'Bottom',
                'location' => "#bottom",
            ],
            [
                'type' => 'button',
                'name' => 'Catalog',
                'location' => WEBPATH . $nameID . "/catalog",
            ],
        ],
        'mode' => 'reply',
        'mainform' => [
            'formID' => 'postToThread',
            'endpoint' => WEBPATH . $nameID,
            'method' => 'POST',
            'formAction' => 'postToThread',
            'nameID' => $nameID,
            'hidden' => [
                [
                    'name' => 'threadID',
                    'value' => $threadID,
                ],
            ],
            'inputs' => [
                [
                    'input' => 'text',
                    'id' => 'name',
                    'lable' => 'Name',
                    'name' => 'name',
                    'properties' => 'autocomplete="off" maxlength="127"'
                ],
                [
                    'input' => 'text',
                    'id' => 'email',
                    'lable' => 'Email',
                    'name' => 'email',
                    'properties' => 'autocomplete="off" maxlength="127"'
                ],
                [
                    'input' => 'text',
                    'id' => 'subject',
                    'lable' => 'Subject',
                    'name' => 'subject',
                    'properties' => 'autocomplete="off" maxlength="127"',
                    'submit' => 'New Post'
                ],
                [
                    'input' => 'textarea',
                    'id' => 'comment',
                    'lable' => 'Comment',
                    'name' => 'comment',
                    'properties' => 'required cols="48" rows="4" maxlength="2048"'
                ],
                [
                    'input' => 'file',
                    'id' => 'file',
                    'lable' => 'Files',
                    'name' => 'upfile[]',
                    'properties' => 'cols="48" rows="4" maxlength="2048" require=""'
                ],
                [
                    'input' => 'password',
                    'id' => 'password',
                    'lable' => 'Password',
                    'name' => 'password',
                    'properties' => 'autocomplete="off" maxlength="8"',
                ],
            ],
        ],
    ]);
    $drawData['navLeft'][] = getDrawnBoardListing();


    $boardData = $board->getDrawData();
    $drawData = array_merge($drawData, array_diff_key($boardData, ['navLeft' => 1, 'navRight' => 1]));

    // merge navLeft properly
    $drawData['navLeft'] = array_merge(
        $drawData['navLeft'] ?? [],
        $boardData['navLeft'] ?? []
    );

    // merge navRight properly
    $drawData['navRight'] = array_merge(
        $drawData['navRight'] ?? [],
        $boardData['navRight'] ?? []
    );

    $drawData['navRight'][] = [
        [
            'name' => 'admin',
            'url' => WEBPATH . $nameID . '/admin'
        ],
    ];

    echo $blade->run("pages.board", $drawData);
    exit;

}