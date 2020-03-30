<?php

namespace modules\seo\admin;

use m\module;
use m\core;
use modules\seo\models\seo;

class delete extends module {

    public function _init()
    {
        $seo = new seo(!empty($this->get->delete) ? $this->get->delete : null);

        if (!empty($seo->id) && $this->user->is_admin() && $seo->destroy()) {
            core::redirect($this->config->previous);
        }
    }
}