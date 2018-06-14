<?php

namespace Api\Message;

class Objects extends \Core\Objectify
{
    public $id;
    public $preview;
    public $time;
    public $users;
    public $thread_name;

    public function __construct($object)
    {

        $map = [
            'id'          => (int)$object['thread_id'],
            'preview'     => (isset($object['preview']) ? $object['preview'] : null),
            'time'        => (int)$object['time_stamp'],
            'thread_name' => $object['thread_name'],
            'users'       => [],
        ];

        foreach ($object['users'] as $user) {
            $map['users'][] = $this->_build($user);
        }

        parent::__construct($map);

        if (!isset($object['preview'])) {
            unset($this->preview);
        }
    }
}