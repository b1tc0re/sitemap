<?php

namespace DeftCMS\Components\b1tc0re\Sitemap;

use DeftCMS\Components\b1tc0re\Sitemap\Models\LocationCollection;
use DeftCMS\Components\b1tc0re\Sitemap\Models\UrlModel;

/**
 * Генератор карты сайта.
 *
 *
 * @author	    b1tc0re
 * @copyright   2020-2021 DeftCMS (https://deftcms.ru/)
 *
 * @since	    Version 0.0.9a
 */
class Sitemap
{
    /**
     * Путь к файлу карты сайта.
     *
     * @var string
     */
    protected $filePath;

    /**
     * @var LocationCollection
     */
    protected $collection;

    /**
     * Использовать сжатие.
     *
     * @var bool
     */
    protected $useGzipCompress = false;

    /**
     * Корень сайта.
     *
     * @var null|string
     */
    protected $documentRoot = null;

    /**
     * Указать пространство имен XHTML.
     *
     * @var bool
     *
     * @see https://support.google.com/webmasters/answer/2620865?hl=en
     */
    protected $useXhtmlNs = false;

    /**
     * Максимально допустимое количество URL-адресов в одном файле.
     *
     * @var int
     */
    private $maxUrls = 50000;

    /**
     * Максимально допустимое количество байтов в одном файле.
     *
     * @var int
     */
    private $maxBytes = 10485760;

    /**
     * Конструктор
     *
     * @param string $filePath     путь к файлу карты
     * @param bool   $useGzip      пользовать сжатие
     * @param string $documentRoot Путь к корню сайта
     * @param bool   $read  Прочитать карту сайта если она существует
     */
    public function __construct($filePath, $useGzip = false, $documentRoot = null, $read = true)
    {
        $this->filePath = $filePath;
        $this->useGzipCompress = $useGzip;
        $this->collection = new LocationCollection();

        $this->setDocumentRoot($documentRoot);
        $read && file_exists($this->filePath) && $this->fillCollection($this->getFilePath());
    }

    /**
     * Устоновить максимальное количество адресов.
     *
     * @param int $maxUrls
     *
     * @return $this
     */
    public function setMaxUrls(int $maxUrls)
    {
        $this->maxUrls = $maxUrls;

        return $this;
    }

    /**
     * Устоновить путь к корню сайта.
     *
     * @param string|null $documentRoot
     *
     * @return $this
     */
    public function setDocumentRoot($documentRoot)
    {
        if (null === $documentRoot && array_key_exists('DOCUMENT_ROOT', $_SERVER)) {
            $documentRoot = $_SERVER['DOCUMENT_ROOT'];
        }

        $this->documentRoot = $documentRoot;

        return $this;
    }

    /**
     * Добавить URL-адрес страницы.
     *
     * Если первый аргумет будет массивом генерироватся карта сайта для локализованных страниц
     *
     * @param string|array $location   - URL-адрес страницы
     * @param null         $lastMod    - Дата последнего изменения файла.
     * @param null         $changeFreq - Вероятная частота изменения этой страницы
     * @param null         $priority   - Приоритетность URL относительно других URL на Вашем сайте.
     */
    public function addItem($location, $lastMod = null, $changeFreq = null, $priority = null)
    {
        $model = new UrlModel([
            'lastModified'      => $lastMod,
            'changeFrequency'   => $changeFreq,
            'priority'          => $priority,
        ]);

        if (is_array($location)) {
            $this->useXhtmlNs = true;

            $urlLocation = current($location);
            $model->setLocation($urlLocation);
            $model->setAlternates($location);
        } else {
            $model->setLocation($location);
        }

        $this->collection->addNotExist($model);
    }

    /**
     * Количество адрессов.
     *
     * @return int
     */
    public function countItems()
    {
        return $this->collection->count();
    }

    /**
     * Получить путь к файлу для записи.
     *
     * @return string
     */
    public function getFilePath()
    {
        return $this->filePath;
    }

    /**
     * Записать данные в карту.
     */
    public function write()
    {
        $collections = $this->collection->chunk($this->maxUrls, $chunks);

        $chunks > 1 && $indexMap = new Index($this->getFilePath(), $this->useGzipCompress);

        foreach ($collections as $index => $collection) {
            $writer = new \XMLWriter();
            $writer->openMemory();
            $writer->startDocument('1.0', 'UTF-8');
            $writer->setIndent(true);

            $writer->startElement('urlset');
            $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

            if (true === $this->useXhtmlNs) {
                $writer->writeAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
            }

            $writer->text(PHP_EOL);

            /**
             * @var UrlModel $item
             */
            foreach ($collection as $item) {
                $writer->startElement('url');
                $writer->writeElement('loc', $item->getLocation());
                $writer->writeElement('lastmod', $item->getLastModified());
                $writer->writeElement('changefreq', $item->getChangeFrequency());
                $writer->writeElement('priority', $item->getPriority());

                foreach ($item->getAlternates() as $lang => $url) {
                    $writer->startElement('xhtml:link');
                    $writer->startAttribute('rel');
                    $writer->text('alternate');
                    $writer->endAttribute();

                    $writer->startAttribute('hreflang');
                    $writer->text($lang);
                    $writer->endAttribute();

                    $writer->startAttribute('href');
                    $writer->text($url);
                    $writer->endAttribute();
                    $writer->endElement();
                }

                $writer->endElement();
            }

            $writer->endElement();
            $writer->endDocument();

            $chunks > 1 ? $path = $this->getFilePathForIndexMap($index) : $path = $this->getFilePath();
            $this->writeToDisk($writer, $path);
            $chunks > 1 && $indexMap->addSitemap($this->getUrlForSitemap($path));
        }

        $chunks > 1 && $indexMap->write();
    }

    /**
     * Записать данные.
     *
     * @param \XMLWriter $writer - Класс XMLWriter
     * @param string     $path   - Путь к файлу для записи
     */
    protected function writeToDisk(\XMLWriter $writer, $path)
    {
        if ($this->useGzipCompress) {
            $path = 'compress.zlib://'.$path;
        }

        file_put_contents($path, $writer->flush());
    }

    /**
     * Получить новый путь в карте сайта.
     *
     * @param int $index
     *
     * @return string
     */
    protected function getFilePathForIndexMap($index)
    {
        $parts = pathinfo($this->getFilePath());
        $parts['filename'] = sprintf('%s_%s', $index, $parts['filename']);

        return $parts['dirname'].DIRECTORY_SEPARATOR.$parts['filename'].'.'.$parts['extension'];
    }

    /**
     * Сгенерировать URL-адрес карте сайта.
     *
     * @param string $path - Путь к карте сайта
     *
     * @return string
     */
    protected function getUrlForSitemap($path)
    {
        $path = str_replace($this->documentRoot, '/', $path);
        $url = parse_url($this->collection->first()->getLocation());
        $path = preg_replace('#(^|[^:])//+#', '\\1/', $url['host'].DIRECTORY_SEPARATOR.$path);

        return sprintf('%s://%s', $url['scheme'], $path);
    }

    /**
     * Сгенерировать путь карте сайта.
     *
     * @param string $url - Путь к карте сайта
     *
     * @return string
     */
    protected function getPathToSitemap(string $url)
    {
        $path = $this->documentRoot.DIRECTORY_SEPARATOR.parse_url($url, PHP_URL_PATH);

        return preg_replace('#(^|[^:])//+#', '\\1/', $path);
    }

    /**
     * Прочитать фаил и заполнить колекцию (прочитать карту сайта).
     *
     * @param string $path - Путь к файлу читения
     *
     * @return void
     */
    protected function fillCollection(string $path)
    {
        $content = file_get_contents($path);

        if (true === $this->useGzipCompress && $gzcontent = gzinflate(substr($content, 10, -8))) {
            $content = $gzcontent;
        }

        /**
         * @var \SimpleXMLElement $reader
         */
        $reader = @simplexml_load_string($content);

        if ($reader) {
            if (property_exists($reader, 'sitemap')) {
                foreach ($reader->sitemap as $element) {
                    if (property_exists($element, 'loc')) {
                        $this->fillCollection($this->getPathToSitemap((string) $element->loc));
                    }
                }
            }

            //@todo Не нравится получение атрибутов
            if (property_exists($reader, 'url')) {
                foreach ($reader as $url) {
                    $attributes = $url->children('xhtml', true);
                    $alternates = [];

                    if (0 !== $attributes->count() && 0 !== $attributes->attributes()->count()) {
                        foreach ($attributes as $item) {
                            $alternates[(string) $item->attributes()->hreflang] = (string) $item->attributes()->href;
                        }
                    }

                    $this->addItem(
                        count($alternates) ? $alternates : (string) $url->loc,
                        strtotime((string) $url->lastmod),
                        (string) $url->changefreq,
                        (string) $url->priority
                    );
                }
            }
        }
    }
}
