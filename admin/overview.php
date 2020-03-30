<?php

namespace modules\seo\admin;

use m\functions;
use m\module;
use m\registry;
use m\view;
use m\i18n;
use m\config;
use modules\admin\admin\overview_data;
use modules\pages\models\pages;

class overview extends module {

    public function _init()
    {
        config::set('per_page', 2000);

        $fields = [
            'address' => 'URL-' . i18n::get('address'),
            'title' => 'SEO-' . i18n::get('title'),
            'noindex' => 'noindex,nofollow',
        ];

        $conditions = [
            [['site' => (int)$this->site->id], ['site' => null]],
            [['language' => registry::get('language_id')], ['language' => null]],
        ];

        $this->js = ['/js/onchange_update.js'];

        view::set('content', overview_data::items(
            'modules\seo\models\seo',
            $fields,
            $conditions,
            $this->view->overview,
            $this->view->overview_item,
            [

            ]
        ));
    }
}
