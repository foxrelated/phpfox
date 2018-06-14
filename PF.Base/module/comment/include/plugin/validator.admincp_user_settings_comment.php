<?php

$aValidation['points_comment'] = [
    'def' => 'int:required',
    'min' => '0',
    'title' => _p('validate_points_comment'),
];

$aValidation['comment_post_flood_control'] = [
    'def' => 'int:required',
    'min' => '0',
    'title' => _p('validate_comment_post_flood_control'),
];
