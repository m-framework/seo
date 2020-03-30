<?php

namespace modules\seo\admin;

use m\module;
use m\i18n;
use m\core;
use modules\articles\models\articles;
use modules\seo\models\seo;

class duplicate extends module {

    public function _init()
    {
        $seo = new seo(!empty($this->get->duplicate) ? $this->get->duplicate : null);

        if (!empty($seo->id) && $this->user->is_admin()) {

            $new_seo = new seo();
            $new_seo->import(array_merge(get_object_vars($seo), [
                'id' => null,
                'language' => (string)$this->language_id,
                'title' => $seo->title,
            ]));
            $new_seo->save();

            if (!$seo->error()) {
                core::redirect('/' . $this->conf->admin_panel_alias . '/seo/edit/' . $new_seo->id);
            }

            core::redirect('/' . $this->conf->admin_panel_alias . '/seo');
        }
    }
}