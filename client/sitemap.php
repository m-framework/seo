<?php

namespace modules\seo\client;

use libraries\helper\url;
use m\config;
use m\core;
use m\functions;
use m\module;
use m\cache;
use m\registry;
use modules\articles\models\articles;
use modules\board\models\board_adverts;
use modules\board\models\board_operations;
use modules\board\models\board_types;
use modules\geo\models\geo_cities;
use modules\pages\models\pages;
use modules\seo\models\seo;
use modules\shop\models\shop_categories;
use modules\shop\models\shop_products;

class sitemap extends module {

    public static $_name = '*Sitemap*';

    public function _init()
    {
        $sitemap_name = 'sitemap.' . $this->language . '.xml';
        if (cache::has($sitemap_name, 86400 * 30)) {
            return cache::show($sitemap_name, 86400 * 30);
        }

        $sitemap_arr = [];
        $urls = [];
        $disabled_addresses = [];

        $disabled = seo::call_static()
            ->s(['address'], [[['hide_in_sitemap' => 1], ['noindex' => 1]], [['site' => $this->site->id], ['site' => null]], [['language' => $this->language_id], ['language' => null]]], [100000])
            ->all();

        if (!empty($disabled)) {
            foreach ($disabled as $disabled_address) {
                $disabled_addresses[] = $disabled_address['address'];
            }
        }
            
        /**
         * Start sitemap.xml
         */
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        if (class_exists('\modules\seo\models\seo')) {
            $seo_friendly = seo::call_static()
                ->s(['address'], ['hide_in_sitemap' => null, 'noindex' => null, 'sitemap' => 1, [['site' => $this->site->id], ['site' => null]], [['language' => $this->language_id], ['language' => null]]], [100000])
                ->all();

            if (!empty($seo_friendly)) {
                foreach ($seo_friendly as $seo_friendly_record) {
                    if (!in_array($seo_friendly_record['address'], $disabled_addresses) && empty($urls[$seo_friendly_record['address']])) {
                        $sitemap_arr[] = '<url><loc>' . url::to($seo_friendly_record['address'], null, true) . '</loc><lastmod>' . date('Y-m-d')
                            . '</lastmod><changefreq>daily</changefreq><priority>1</priority></url>';
                        $urls[$seo_friendly_record['address']] = 1;
                    }
                }
            }
        }

        /**
         * Pages
         */
        if (class_exists('\modules\pages\models\pages')) {
            $pages = pages::call_static()
                ->s(['parent', 'address'], [[['site' => $this->site->id], ['site' => null]]], [100000])
                ->all('object');

            if (!empty($pages)) {
                foreach ($pages as $page) {
                    $address = $page->get_path();
                    $priority = $address == '/' ? '1' : '0.9';

                    if (!in_array($address, $disabled_addresses) && empty($urls[$address])) {
                        $sitemap_arr[] = '<url><loc>' . url::to($address, null, true) . '</loc><lastmod>' . date('Y-m-d')
                            . '</lastmod><changefreq>daily</changefreq><priority>' . $priority . '</priority></url>';
                        $urls[$address] = 1;
                    }
                }
            }
        }

        /**
         * Articles and news
         */
        if (class_exists('\modules\articles\models\articles')) {
            $articles = articles::call_static()
                ->s(
                    ['page', 'alias'],
                    [[['site' => $this->site->id], ['site' => null]], 'language' => $this->language_id, 'published' => 1],
                    [100000]
                )
                ->all('object');

            if (!empty($articles)) {
                foreach ($articles as $article) {
                    $address = $article->path;

                    if (!in_array($address, $disabled_addresses) && empty($urls[$address])) {
                        $sitemap_arr[] = '<url><loc>' . url::to($address, null, true) . '</loc><lastmod>' . date('Y-m-d') .
                            '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';
                        $urls[$address] = 1;
                    }
                }
            }
        }

        /**
         * Shop products
         */
        if (class_exists('\modules\shop\models\shop_products')) {
            $products = shop_products::call_static()
                ->join_type('LEFT')
                ->select(
                    [
                        '`shop_products`.`category`',
                        '`shop_brands`.`address` AS `brand_address`',
                        '`shop_models`.`address` AS `model_address`',
                        'alias',
                    ],
                    [
                        'shop_brands' => ['id' => 'brand'],
                        'shop_models' => ['id' => 'model'],
                    ],
                    ['`shop_products`.`site`' => $this->site->id, '`shop_products`.`active`' => 1],
                    [],
                    ['`shop_products`.`sequence`' => 'ASC', '`shop_products`.`id`' => 'DESC'],
                    [200000]
                )
                ->all();

            if (!empty($products)) {

                $categories = [];

                foreach ($products as $product) {

                    if (empty($product['alias']) || empty($product['category'])) {
                        continue;
                    }

                    if (!empty($categories[$product['category']])) {
                        $category_address = $categories[$product['category']];
                    } else {
                        $category = new shop_categories($product['category']);
                        $categories[$product['category']] = $category_address = $category->get_path();
                    }

                    $address = $category_address . $product['brand_address'] . $product['model_address'] . '/' . $product['alias'];

                    if (!in_array($address, $disabled_addresses) && empty($urls[$address])) {
                        $sitemap_arr[] = '<url><loc>' . url::to($address, null, true) . '</loc><lastmod>' . date('Y-m-d') .
                            '</lastmod><changefreq>daily</changefreq><priority>0.8</priority></url>';
                        $urls[$address] = 1;
                    }
                }
            }
        }

        /**
         * Adverts board categories
         */
        if (class_exists('\modules\board\models\board_adverts')) {
            $adverts_cities_cond = ['site' => $this->site->id, 'active' => 1];

            $adverts_cities = board_adverts::call_static()
                ->select(['city'], [], $adverts_cities_cond, ['city'], [], [])
                ->all();

            $cities = [];

            if (!empty($adverts_cities)) {
                foreach ($adverts_cities as $advert_city) {
                    $cities[] = (int)$advert_city['city'];
                }
            }

            if (!empty($cities)) {
                $cities = geo_cities::call_static()->s([], ['id' => $cities], [1000])->all('object');

                $operations = board_operations::call_static()->s([], ['site' => $this->site->id, 'active' => 1], [100])->all('object');
                $types = board_types::call_static()->s([], ['site' => $this->site->id, 'active' => 1], [100])->all('object');

                if (!empty($operations) && !empty($types)) {
                    foreach ($cities as $city) {

                        $address = '/s/' . (empty($city->alias) ? $city->id : $city->alias);

                        if (!in_array($address, $disabled_addresses) && empty($urls[$address])) {
                            $sitemap_arr[] = '<url><loc>' . url::to($address, null, true) . '</loc><lastmod>' . date('Y-m-d') .
                                '</lastmod><changefreq>daily</changefreq><priority>1</priority></url>';
                            $urls[$address] = 1;
                        }

                        foreach ($operations as $operation) {
                            foreach ($types as $type) {

                                $count_active = board_adverts::call_static()->count([
                                    'site' => $this->site->id,
                                    'operation' => $operation->id,
                                    'type' => $type->id,
                                    'city' => $city->id,
                                    'active' => 1,
                                ]);

                                if (empty($count_active)) {
                                    continue;
                                }

                                $address = '/s/' . (empty($city->alias) ? $city->id : $city->alias) . '/' . $operation->alias . '/' . $type->alias;

                                if (!in_array($address, $disabled_addresses) && empty($urls[$address])) {
                                    $sitemap_arr[] = '<url><loc>' . url::to($address, null, true) . '</loc><lastmod>' . date('Y-m-d') .
                                        '</lastmod><changefreq>daily</changefreq><priority>0.9</priority></url>';
                                    $urls[$address] = 1;
                                }
                            }
                        }
                    }
                }
            }
        }


        /**
         * End sitemap.xml
         */
        //ksort($sitemap_arr);
        $sitemap_arr = array_unique($sitemap_arr);
        $sitemap .= implode('', $sitemap_arr);
        $sitemap .= '</urlset>';

        cache::set($sitemap_name, $sitemap);

        header("Content-Type: application/xml; charset=utf-8");
        exit($sitemap);
    }
}