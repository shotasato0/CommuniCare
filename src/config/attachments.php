<?php

return [
    // 本番ではfalse、必要に応じて .env で ATTACHMENTS_DEBUG_LOG=true にする
    'debug_log' => env('ATTACHMENTS_DEBUG_LOG', false),

    // 表示時の自己修復（tenant FSに無ければ中央FSから1回だけコピーして配信）
    // 本番では方針に応じて有効化を判断（既定: false）
    'self_heal' => env('ATTACHMENTS_SELF_HEAL', false),
];
