<?php

return [
    // 本番ではfalse、必要に応じて .env で ATTACHMENTS_DEBUG_LOG=true にする
    'debug_log' => env('ATTACHMENTS_DEBUG_LOG', false),
];

