<?php

declare(strict_types=1);

return [
    'routes' => [
        ['name' => 'zoom#settings', 'url' => '/zoom/settings', 'verb' => 'GET'],
        ['name' => 'zoom#save', 'url' => '/zoom/options', 'verb' => 'POST'],
        ['name' => 'watermark#resolve', 'url' => '/watermark/resolve', 'verb' => 'GET'],
        ['name' => 'watermark#contextImage', 'url' => '/watermark/context-image', 'verb' => 'GET'],
        ['name' => 'watermark#folderOwners', 'url' => '/watermark/folder/owners', 'verb' => 'GET'],
        ['name' => 'watermark#browseFolders', 'url' => '/watermark/folder/browse', 'verb' => 'GET'],
        ['name' => 'watermark#assignFolder', 'url' => '/watermark/folder/assign', 'verb' => 'POST'],
        ['name' => 'watermark#unassignFolder', 'url' => '/watermark/folder/unassign', 'verb' => 'POST'],
        ['name' => 'watermark#get', 'url' => '/watermark', 'verb' => 'GET'],
        ['name' => 'watermark#settings', 'url' => '/watermark/settings', 'verb' => 'GET'],
        ['name' => 'watermark#preview', 'url' => '/watermark/preview', 'verb' => 'GET'],
        ['name' => 'watermark#listImages', 'url' => '/watermark/library', 'verb' => 'GET'],
        ['name' => 'watermark#upload', 'url' => '/watermark/upload', 'verb' => 'POST'],
        ['name' => 'watermark#restoreDefault', 'url' => '/watermark/restore-default', 'verb' => 'POST'],
        ['name' => 'watermark#reset', 'url' => '/watermark/reset', 'verb' => 'POST'],
        ['name' => 'watermark#select', 'url' => '/watermark/select', 'verb' => 'POST'],
        ['name' => 'watermark#delete', 'url' => '/watermark/delete', 'verb' => 'POST'],
        ['name' => 'watermark#saveOptions', 'url' => '/watermark/options', 'verb' => 'POST'],
    ],
];
