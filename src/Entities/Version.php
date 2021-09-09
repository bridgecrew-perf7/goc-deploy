<?php

namespace Marcth\GocDeploy\Entities;

class Version extends Entity
{
    protected $attributes = [
        'major' => null,
        'minor' => null,
        'patch' => null,
        'type' => null,
        'descriptor' => null,
        'revision' => 0,
    ];
}
