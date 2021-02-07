# DeftCMS Sitemap generator package

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![PHP tests](https://github.com/b1tc0re/sitemap/workflows/PHP%20Tests/badge.svg?branch=master)](https://github.com/b1tc0re/sitemap/actions?query=workflow%3A%22PHP+Tests%22)
[![Maintainability](https://api.codeclimate.com/v1/badges/348bf888bd974a826107/maintainability)](https://codeclimate.com/github/b1tc0re/sitemap/maintainability)
[![Style Status](https://github.styleci.io/repos/336636040/shield?style=normal&branch=master)](https://github.styleci.io/repos/336636040)
[![Latest Stable Version](https://poser.pugx.org/b1tc0re/sitemap/v/stable)](https://packagist.org/packages/b1tc0re/sitemap) 
[![Total Downloads](https://poser.pugx.org/b1tc0re/sitemap/downloads)](https://packagist.org/packages/b1tc0re/sitemap)

Этот пакет может генерировать карту сайта.

Для установки используйте команду:

```bash
composer require b1tc0re/sitemap
```

### Создание простой карты сайта:
```php 
$sitemap = new Sitemap('sitemap.xml'); 
```

Если количество ссылок будет превышать ```$sitemap->maxUrls``` будет 
создана [SiteMapIndex](https://www.sitemaps.org/ru/protocol.html#index) с указанном 
названием в конструкторе класса (```sitemap.xml```). Ссылки будут разбиты по ```$sitemap->maxUrls```
и созданы карты сайта с наванием 0_sitemap.xml эти карты будут добавлены в ```sitemap.xml```

```php
$sitemap = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap('sitemap.xml');
$sitemap->addItem('http://example.com/1', time());
$sitemap->addItem('http://example.com/2');
$sitemap->write();
```