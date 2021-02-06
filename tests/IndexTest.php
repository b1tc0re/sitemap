<?php

use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
{
    /**
     * Подтверждает действительность siteindex согласно схеме XSD.
     *
     * @param string $fileName
     */
    protected function assertIsValidIndex($fileName)
    {
        $gzStartSequence = pack('H*', '1f8b08');
        $content = file_get_contents($fileName);
        (strpos($content, $gzStartSequence, 1) === false) && $content = gzinflate(substr($content, 10, -8));

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        self::assertTrue($xml->schemaValidate(__DIR__.'/siteindex.xsd'));
    }

    /**
     * Запись карты сайта.
     */
    public function _testWritingFile()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml');
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        self::assertFileExists($fileName);
        $this->assertIsValidIndex($fileName);

        unlink($fileName);
    }

    /**
     * Прочитать карту сайта.
     */
    public function _testReadFile()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml');
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName);
        $index->addSitemap('http://example.com/sitemap_2.xml');

        self::assertEquals($index->countItems(), 3);
        $this->assertIsValidIndex($fileName);

        unlink($fileName);
    }

    /**
     * Проверить запись карты сайта в сжатом виде.
     */
    public function testWritingFileGz()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml.gz', true);
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        self::assertFileExists($fileName);
        $this->assertIsValidIndex($fileName);

        unlink($fileName);
    }

    /**
     * Прочитать карту сайта.
     */
    public function testReadFileGz()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml.gz', true);

        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName, true);

        self::assertEquals($index->countItems(), 2);

        $this->assertIsValidIndex($fileName);

        unlink($fileName);
    }

    /**
     * Проверить функцию подсчета для записи.
     */
    public function testCountWrMethod()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml', true);

        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());

        self::assertEquals($index->countItems(), 2);
        $index->write();

        self::assertEquals($index->countItems(), 2);

        unlink($fileName);
    }

    /**
     * Проверить функцию подсчета для читения.
     */
    public function testCountRMethod()
    {
        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName = __DIR__.'/sitemap_index.xml');
        $index->addSitemap('http://example.com/sitemap.xml');
        $index->addSitemap('http://example.com/sitemap_2.xml', time());
        $index->write();

        $index = new DeftCMS\Components\b1tc0re\Sitemap\Index($fileName);
        $index->addSitemap('http://example.com/sitemap_3.xml', time());

        self::assertEquals($index->countItems(), 3);

        unlink($fileName);
    }
}
