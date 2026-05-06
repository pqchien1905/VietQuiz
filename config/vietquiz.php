<?php

return [
    'anti_cheat' => [
        'max_violations' => max(1, (int) env('ANTI_CHEAT_MAX_VIOLATIONS', 3)),
    ],
];
