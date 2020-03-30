<?php

namespace modules\seo\models;

use libraries\helper\url;
use m\config;
use m\core;
use m\functions;
use m\model;
use m\registry;

class seo extends model
{
    protected $fields = [
        'id' => 'int',
        'site' => 'int',
        'language' => 'int',
        'address' => 'varchar',
        'title' => 'text',
        'name' => 'text',
        'description' => 'text',
        'image' => 'varchar',
        'canonical' => 'varchar',

        'og_title' => 'text',
        'og_type' => 'varchar',
        'og_image' => 'varchar',
        'og_description' => 'text',
        'og_site_name' => 'varchar',
        'og_price_amount' => 'varchar',
        'og_price_currency' => 'varchar',

        'twitter_card' => 'varchar',
        'twitter_site' => 'varchar',
        'twitter_title' => 'varchar',
        'twitter_description' => 'text',
        'twitter_creator' => 'varchar',
        'twitter_image' => 'varchar',
        'twitter_data1' => 'varchar',
        'twitter_label1' => 'varchar',

        'redirect_301' => 'varchar',
        'sitemap' => 'tinyint',
        'status_code' => 'int',
        'hide_in_sitemap' => 'tinyint',
        'noindex' => 'tinyint',
    ];

    public function _after_save()
    {
        $vars = get_object_vars($this);

        if (isset($vars['text'])) {
            $this->seo_text->text = $vars['text'];
            $this->seo_text->save();
        }

        return true;
    }

    public function _autoload_path()
    {
        $this->path = $this->address;
    }

    public function _autoload_seo_text()
    {
        $cond = ['site' => $this->site, 'language' => $this->language, 'address' => $this->address];

        $this->seo_text = seo_texts::call_static()->s([], $cond)->obj();

        if (empty($this->seo_text->id) && !empty($this->address)) {
            $this->seo_text = new seo_texts();
            $this->seo_text->import($cond);
            $this->seo_text->save();
        }

        return $this->seo_text;
    }

    public function _autoload_text()
    {
        return $this->text = $this->seo_text->text;
    }

    public function _autoload_sitemap_checked()
    {
        $this->sitemap_checked = empty($this->sitemap) ? '' : 'checked';
    }

    public function _autoload_noindex_checked()
    {
        $this->noindex_checked = empty($this->noindex) ? '' : 'checked';
    }

    public function _override_redirect_301()
    {
        if (empty($this->redirect_301)) {
            return null;
        }

        $this->redirect_301 = url::to($this->redirect_301);
    }
}
