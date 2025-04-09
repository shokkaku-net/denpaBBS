<?php
require __DIR__ . '/internal/pagedrawing.php';

use eftec\bladeone\BladeOne;
use internal\pagedrawing;
use function internal\pagedrawing\makeBoardData;

function drawBoardThreadListing($board, $page = 1)
{
    $data = makeBoardData($board);
    $nameID = $board->getNameID();

    $data['mode'] = 'listing';
    $data['mainform'] = [
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
    ];
    $data['subform'] = [
        'formID' => 'postManage',
        'endpoint' => WEBPATH . $nameID,
        'method' => 'POST',
        'formAction' => 'deletePosts',
        'nameID' => $nameID,
        'inputs' => [
            [
                'input' => 'lable',
                'lable' => 'Delete Post',
            ],
            [
                'input' => 'checkbox',
                'name' => 'fileOnly',
                'value' => 'on',
                'lable' => 'File only'
            ],
            [
                'input' => 'br'
            ],
            [
                'input' => 'lable',
                'lable' => 'Password',
            ],
            [
                'input' => 'password',
                'submit' => 'delete',
            ]
        ]
    ];

    $threadData = $board->getThreadsByPage($page);

    foreach ($threadData as $thread) {
        $data['threads'][] = $thread->getDrawData($board->getPostPreviwCount());
    }

    $data['paging'] = $board->buildPageData($page);

    $blade = new BladeOne(BASEDIR . '/views', BASEDIR . '/cache', BladeOne::MODE_DEBUG);
    echo $blade->run("pages.board", $data);
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
    $data = makeBoardData($board);
    $nameID = $board->getNameID();

    $data['mode'] = 'reply';
    $data['mainform'] = [
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
    ];


    $data['subform'] = [
        'formID' => 'postManage',
        'endpoint' => WEBPATH . $nameID,
        'method' => 'POST',
        'formAction' => 'deletePosts',
        'nameID' => $nameID,
        'inputs' => [
            [
                'input' => 'lable',
                'lable' => 'Delete Post',
            ],
            [
                'input' => 'checkbox',
                'name' => 'fileOnly',
                'value' => 'on',
                'lable' => 'File only'
            ],
            [
                'input' => 'br'
            ],
            [
                'input' => 'lable',
                'lable' => 'Password',
            ],
            [
                'input' => 'password',
                'submit' => 'delete',
            ]
        ]
    ];

    $threadData = $board->getThreadByID($threadID);
    $data['threads'][] = $threadData->getDrawData();

    $blade = new BladeOne(BASEDIR . '/views', BASEDIR . '/cache', BladeOne::MODE_DEBUG);
    echo $blade->run("pages.board", $data);
    exit;

}