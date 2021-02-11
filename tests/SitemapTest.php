<?php

use DeftCMS\Components\b1tc0re\Sitemap\Sitemap;
use PHPUnit\Framework\TestCase;

class SitemapTest extends TestCase
{
    /**
     * Путь к корневой папке
     *
     * @var string
     */
    private $documentRoot = __DIR__.DIRECTORY_SEPARATOR;

    /**
     * Название файла карты сайта
     * @var string
     */
    private $fileName = 'sitemap';

    /**
     * Подтверждает действительность sitemap согласно схеме XSD.
     *
     * @param string $fileName
     * @param bool   $xhtml
     */
    private function assertIsValidSitemap($fileName, $xhtml = false): void
    {
        $content = $this->getIsGzipContent(file_get_contents($fileName), $fileName);
        $xsdFileName = $xhtml ? 'sitemap_xhtml.xsd' : 'sitemap.xsd';

        $xml = new \DOMDocument();
        $xml->loadXML($content);
        self::assertTrue($xml->schemaValidate($this->documentRoot.$xsdFileName));
    }

    /**
     * Подтверждает действительность siteindex согласно схеме XSD.
     *
     * @param string $fileName
     */
    private function assertIsValidIndex($fileName): void
    {
        $content = $this->getIsGzipContent(file_get_contents($fileName), $fileName);

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        self::assertTrue($xml->schemaValidate(__DIR__.'/siteindex.xsd'));
    }

    /**
     * Проверить если содержимое является сжатым gzip.
     *
     * @param string $content
     * @param string $path
     *
     * @return string
     */
    private function getIsGzipContent($content, $path): string
    {
        $startSequence = pack('H*', '1F8B08');

        if (strpos($content, $startSequence, 1) !== false) {
            return gzinflate(substr($content, 10, -8));
        }

        return pathinfo($path, PATHINFO_EXTENSION) !== 'gz' ? $content : gzinflate(substr($content, 10, -8));
    }

    /**
     * Проверка переполнения карты сайта
     */
    public function testAddItemRemoveAndWrite(): void
    {
        $sitemap = new Sitemap($this->documentRoot . $this->fileName, false, $this->documentRoot, false);
        $sitemap->addItem('https://example.com/path/to/document/1/');
        $sitemap->addItem('https://example.com/path/to/document/2/');
        $sitemap->addItem('https://example.com/path/to/document/3/');

        self::assertEquals(3, $sitemap->countItems());

        // Проверка удаление ссылок
        $sitemap->removeItem('https://example.com/path/to/document/3/');
        self::assertEquals(2, $sitemap->countItems());

        // Проверка дубликатов
        $sitemap->addItem('https://example.com/path/to/document/2/');
        self::assertEquals(2, $sitemap->countItems());

        $sitemap->write();

        self::assertFileExists($sitemap->getFilePath());
        $this->assertIsValidSitemap($sitemap->getFilePath());

        unlink($sitemap->getFilePath());
    }

    /**
     * Проверка переполнения карты сайта gzip
     */
    public function testAddItemRemoveAndWriteGz(): void
    {
        $sitemap = new Sitemap($this->documentRoot . $this->fileName, true, $this->documentRoot, false);
        $sitemap->addItem('https://example.com/path/to/document/1/');
        $sitemap->addItem('https://example.com/path/to/document/2/');
        $sitemap->addItem('https://example.com/path/to/document/3/');

        self::assertEquals(3, $sitemap->countItems());

        // Проверка удаление ссылок
        $sitemap->removeItem('https://example.com/path/to/document/3/');
        self::assertEquals(2, $sitemap->countItems());

        // Проверка дубликатов
        $sitemap->addItem('https://example.com/path/to/document/2/');
        self::assertEquals(2, $sitemap->countItems());

        $sitemap->write();

        self::assertFileExists($sitemap->getFilePath());
        $this->assertIsValidSitemap($sitemap->getFilePath());

        unlink($sitemap->getFilePath());
    }

    /**
     * Проверка Sitemap для локализованных страниц
     */
    public function testMultilingualSitemap()
    {
        $sitemap = new Sitemap($this->documentRoot . $this->fileName, false, $this->documentRoot, false);
        $sitemap->addItem([
            'en' => 'https://example.com/en/path/to/document/1/',
            'ru' => 'https://example.com/ru/path/to/document/1/'
        ]);
        $sitemap->addItem([
            'en' => 'https://example.com/en/path/to/document/2/',
            'ru' => 'https://example.com/ru/path/to/document/2/'
        ]);
        $sitemap->addItem([
            'en' => 'https://example.com/en/path/to/document/3/',
            'ru' => 'https://example.com/ru/path/to/document/3/'
        ]);

        self::assertEquals(3, $sitemap->countItems());

        $sitemap->write();

        self::assertFileExists($sitemap->getFilePath());
        $this->assertIsValidSitemap($sitemap->getFilePath(), true);

        unlink($sitemap->getFilePath());
    }

    /**
     * Проверка ограничений макс. кол. адресов
     */
    public function testMaxUrls(): void
    {
        $sitemap = new Sitemap($this->documentRoot . $this->fileName, false, $this->documentRoot, false);
        $sitemap->setMaxUrls(5);

        for ($i = 0; $i < 10; $i++)
        {
            $sitemap->addItem('https://example.com/path/to/document/'. $i .'/');
        }

        $sitemap->write();

        self::assertFileExists($sitemap->getFilePath());
        $this->assertIsValidIndex($sitemap->getFilePath());

        unlink($sitemap->getFilePath());

        foreach ($sitemap->getFilePathParts() as $path)
        {
            self::assertFileExists($path);
            $this->assertIsValidSitemap($path);
            unlink($path);
        }
    }

    /**
     * Проверка читение карты сайта
     */
    public function testMaxUrlsReadSitemapParts(): void
    {
        $sitemap = new Sitemap($this->documentRoot . $this->fileName, true, $this->documentRoot, false);
        $sitemap->setMaxUrls(5);

        for ($i = 0; $i < 10; $i++)
        {
            $sitemap->addItem('https://example.com/path/to/document/'. $i .'/');
        }

        $sitemap->write();

        $sitemap = new Sitemap($this->documentRoot . $this->fileName, true, $this->documentRoot, true);

        self::assertEquals(10, $sitemap->countItems());

        unlink($sitemap->getFilePath());
        foreach ($sitemap->getFilePathParts() as $path)
        {
            self::assertFileExists($path);
            $this->assertIsValidSitemap($path);
            unlink($path);
        }
    }
}