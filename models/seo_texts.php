<?php

namespace modules\seo\models;

use libraries\helper\url;
use m\config;
use m\core;
use m\functions;
use m\model;
use m\registry;

class seo_texts extends model
{
    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'language' => 'int',
        'address' => 'varchar',
        'text' => 'text',
    ];

    public function _override_text()
    {
        return $this->text = stripslashes(htmlspecialchars_decode($this->text));
    }
}
