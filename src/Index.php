<?php

namespace DeftCMS\Components\b1tc0re\Sitemap;

use DeftCMS\Components\b1tc0re\Sitemap\Models\LocationCollection;
use DeftCMS\Components\b1tc0re\Sitemap\Models\UrlModel;

/**
 * Класс для генерации SitemapIndex.
 *
 * @author	    b1tc0re
 * @copyright   2020-2021 DeftCMS (https://deftcms.ru/)
 *
 * @since	    Version 0.0.9a
 */
class Index
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
     * Конструктор
     *
     * @param string $filePath путь к файлу карты сайта
     * @param bool   $useGzip  Использовать сжатие
     * @param bool   $read     Прочитать карту сайта если она существует
     */
    public function __construct($filePath, bool $useGzip = false, $read = true)
    {
        $this->useGzipCompress  = $useGzip;
        $this->filePath         = $this->normalizeFilePath($filePath);
        $this->collection       = new LocationCollection();

        $read && file_exists($this->filePath) && $this->fillCollection();
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
     * Добавить Sitemap.
     *
     * @param string $location     - Указывает местоположение файла Sitemap
     * @param int    $lastModified - Указывает время изменения соответствующего файла Sitemap.
     *
     * @return $this
     */
    public function addSitemap($location, $lastModified = null)
    {
        $this->collection->addUpdateExist(new Models\UrlModel([
            'location'      => $location,
            'lastModified'  => $lastModified,
        ]));

        return $this;
    }

    /**
     * Удалить карту сайта.
     *
     * @param string $location - Указывает местоположение файла Sitemap
     *
     * @return $this
     */
    public function removeSitemap($location)
    {
        $this->collection->remove(new UrlModel([
            'location' => $location,
        ]));

        return $this;
    }

    /**
     * Количество карт
     *
     * @return int
     */
    public function countItems()
    {
        return $this->collection->count();
    }

    /**
     * Записать данные в карту.
     *
     * @return void
     */
    public function write()
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->startElement('sitemapindex');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        /**
         * @var UrlModel $item
         */
        foreach ($this->collection as $item) {
            $writer->startElement('sitemap');
            $writer->writeElement('loc', $item->getLocation());
            $writer->writeElement('lastmod', $item->getLastModified());
            $writer->endElement();
        }

        $writer->endElement();
        $writer->endDocument();

        $path = $this->getFilePath();

        if ($this->useGzipCompress) {
            $path = 'compress.zlib://'.$path;
        }

        file_put_contents($path, $writer->flush());
    }

    /**
     * Нормализовать путь к карте сайта
     * @param string $path
     * @return string
     */
    protected function normalizeFilePath($path)
    {
        $parts = explode('/', $path);
        $name       = array_pop($parts);
        $partsName  = explode('.', $name);

        if( $partIndex = array_search('xml', $partsName, true)) {
            unset($partsName[$partIndex]);
        }

        if( $partIndex = array_search('gz', $partsName, true)) {
            unset($partsName[$partIndex]);
        }

        $partsName[] = 'xml';

        if( $this->useGzipCompress ) {
            $partsName[] = 'gz';
        }

        $parts[] = implode('.', $partsName);
        return implode('/', $parts);
    }

    /**
     * Наполнить колекцию (прочитать карту сайта).
     *
     * @return void
     */
    protected function fillCollection()
    {
        $content = file_get_contents($this->getFilePath());

        if (true === $this->useGzipCompress && $gzcontent = gzinflate(substr($content, 10, -8))) {
            $content = $gzcontent;
        }

        /**
         * @var \SimpleXMLElement $reader
         */
        $reader = @simplexml_load_string($content);

        if ($reader && property_exists($reader, 'sitemap')) {
            foreach ($reader->sitemap as $element) {
                if (property_exists($element, 'loc') && property_exists($element, 'lastmod')) {
                    $this->addSitemap($element->loc, strtotime($element->lastmod));
                }
            }
        }
    }
}
