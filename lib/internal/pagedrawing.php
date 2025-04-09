<?php
namespace internal\pagedrawing;

use boardClass;

function makeBoardData(boardClass $board)
{
    $nameID = $board->getNameID();
    $data = [];

    $data['boardbuttons'] = [
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
    ];
    $data['navLeft'][] = getDrawnBoardListing();
    $boardData = $board->getDrawData();
    $data = array_merge($data, array_diff_key($boardData, ['navLeft' => 1, 'navRight' => 1]));

    // merge navLeft properly
    $data['navLeft'] = array_merge(
        $data['navLeft'] ?? [],
        $boardData['navLeft'] ?? []
    );

    // merge navRight properly
    $data['navRight'] = array_merge(
        $data['navRight'] ?? [],
        $boardData['navRight'] ?? []
    );

    $data['navRight'][] = [
        [
            'name' => 'admin',
            'url' => WEBPATH . $nameID . '/admin'
        ],
    ];
    return $data;
}
