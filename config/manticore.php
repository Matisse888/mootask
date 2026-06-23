<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Manticore Search Configuration
    |--------------------------------------------------------------------------
    |
    | Manticore is a fast, open-source search engine that we use for
    | searching projects, tasks, files, etc.
    |
    */

    'host' => env('MANTICORE_HOST', '127.0.0.1'),
    'port' => env('MANTICORE_PORT', 9312),

    'indexes' => [
        'projects' => 'projects_index',
        'tasks' => 'tasks_index',
        'files' => 'files_index',
        'dialogs' => 'dialogs_index',
    ],
];
