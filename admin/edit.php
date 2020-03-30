<?php

namespace modules\seo\admin;

use m\module;
use m\i18n;
use m\registry;
use m\view;
use m\config;
use m\model;
use m\form;
use m\core;
use modules\pages\models\pages;
use modules\seo\models\seo;
use modules\users\models\users;
use modules\users\models\users_info;

class edit extends module {

    public function _init()
    {
        if (!isset($this->view->{'seo_' . $this->name . '_form'})) { // used `seo_edit_form` or `seo_add_form`
            return false;
        }

        $this->css = ['/css/seo_edit.css'];

        $conditions = [];

        if (!empty($this->get->edit)) {
            $conditions['id'] = $this->get->edit;
        }
//core::out($this->get);
        if (!empty($this->get->address)) {
            $conditions['address'] = $this->get->address;
            $conditions['site'] = $this->site->id;
        }

//        $seo = new seo(!empty($this->get->edit) ? $this->get->edit : null);
        $seo = empty($conditions) ? new seo : seo::call_static()->s([], $conditions)->obj();

        if (!empty($seo->id)) {
            view::set('page_title', '<h1><i class="fa fa-file-text-o"></i> ' . i18n::get('Edit SEO-settings') . '</h1>');

            registry::set('title', '*To edit page SEO settings*');

            registry::set('breadcrumbs', [
                '/' . config::get('admin_panel_alias') . '/seo' => '*SEO-settings*',
                '/' . config::get('admin_panel_alias') . '/seo/edit/' . $this->get->edit => '*Edit SEO-settings*',
            ]);
        }

        if (empty($seo->id) && !empty($this->get->address)) {
            $seo->address = $this->get->address;
        }

        if (empty($seo->site)) {
            $seo->site = $this->site->id;
        }
        if (empty($seo->id) && empty($seo->language)) {
            $seo->language = $this->language_id;
        }
        if (empty($seo->id) && empty($seo->og_type)) {
            $seo->og_type = 'article';
        }
        if (empty($seo->id) && empty($seo->twitter_site)) {
            $seo->twitter_site = config::get('twitter_site');
        }
        if (empty($seo->id) && empty($seo->twitter_creator)) {
            $seo->twitter_creator = config::get('twitter_creator');
        }

        new form(
            $seo,
            [
                'address' => [
                    'type' => 'varchar',
                    'field_name' => 'Address',
                    'required' => true,
                ],
                'title' => [
                    'type' => 'varchar',
                    'field_name' => 'Title',
                ],
                'description' => [
                    'type' => 'textarea',
                    'field_name' => 'Description',
                ],
                'name' => [
                    'type' => 'varchar',
                    'field_name' => 'META name',
                ],
                'image' => [
                    'type' => 'file_path',
                    'field_name' => 'META image',
                ],

                'hr1' => [
                    'type' => 'hr',
                ],

                'og_title' => [
                    'type' => 'varchar',
                    'field_name' => 'og:title',
                ],
                'og_type' => [
                    'type' => 'varchar',
                    'field_name' => 'og:type',
                ],
                'og_image' => [
                    'type' => 'file_path',
                    'field_name' => 'og:image',
                ],
                'og_description' => [
                    'type' => 'textarea',
                    'field_name' => 'og:description',
                ],
                'og_site_name' => [
                    'type' => 'varchar',
                    'field_name' => 'og:site_name',
                ],
                'og_price_amount' => [
                    'type' => 'varchar',
                    'field_name' => 'og:price_amount',
                ],
                'og_price_currency' => [
                    'type' => 'varchar',
                    'field_name' => 'og:price_currency', // TODO: existed currencies drop-down
                ],

                'hr2' => [
                    'type' => 'hr',
                ],

                'twitter_card' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:card',
                ],
                'twitter_site' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:site',
                ],
                'twitter_title' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:title',
                ],
                'twitter_description' => [
                    'type' => 'textarea',
                    'field_name' => 'twitter:description',
                ],
                'twitter_creator' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:creator',
                ],
                'twitter_image' => [
                    'type' => 'file_path',
                    'field_name' => 'twitter:image',
                ],
                'twitter_data1' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:data1',
                ],
                'twitter_label1' => [
                    'type' => 'varchar',
                    'field_name' => 'twitter:label1',
                ],

                'hr3' => [
                    'type' => 'hr',
                ],

                'canonical' => [
                    'type' => 'varchar',
                    'field_name' => 'Canonical URL',
                ],
                'redirect_301' => [
                    'type' => 'varchar',
                    'field_name' => '301-' . i18n::get('redirect'),
                ],
                'sitemap' => [
                    'type' => 'tinyint',
                    'field_name' => i18n::get('Show in sitemap'),
                ],
                'status_code' => [
                    'type' => 'int',
                    'field_name' => i18n::get('Special status code'),
                ],
                'noindex' => [
                    'type' => 'tinyint',
                    'field_name' => 'noindex,nofollow',
                ],
                'text' => [
                    'type' => 'text',
                    'field_name' => 'SEO-' . i18n::get('text'),
                ],
                'site' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
                'language' => [
                    'type' => 'hidden',
                    'field_name' => '',
                ],
            ],
            [
                'form' => $this->view->{'seo_' . $this->name . '_form'},
                'varchar' => $this->view->edit_row_varchar,
                'text' => $this->view->edit_row_text,
                'textarea' => $this->view->edit_row_textarea,
                'tinyint' => $this->view->edit_row_tinyint,
                'int' => $this->view->edit_row_int,
                'hidden' => $this->view->edit_row_hidden,
                'hr' => $this->view->edit_row_hr,
                'file_path' => $this->view->edit_row_file_path,
                'saved' => $this->view->edit_row_saved,
                'error' => $this->view->edit_row_error,
            ]
        );
    }
}