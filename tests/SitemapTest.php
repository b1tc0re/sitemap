<?php use PHPUnit\Framework\TestCase;

class SitemapTest extends TestCase
{
    /**
     * Подтверждает действительность sitemap согласно схеме XSD
     *
     * @param string $fileName
     * @param bool $xhtml
     */
    protected function assertIsValidSitemap($fileName, $xhtml = false)
    {
        $gzStartSequence = pack('H*', '1f8b08');
        $content         = file_get_contents($fileName);
        (strpos($content, $gzStartSequence, 1) === false) && $content = gzinflate(substr($content,10,-8));

        $xsdFileName = $xhtml ? 'sitemap_xhtml.xsd' : 'sitemap.xsd';

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        self::assertTrue($xml->schemaValidate(__DIR__ . '/' . $xsdFileName));
    }

    /**
     * Подтверждает действительность siteindex согласно схеме XSD
     *
     * @param string $fileName
     */
    protected function assertIsValidIndex($fileName)
    {
        $gzStartSequence = pack('H*', '1f8b08');
        $content         = file_get_contents($fileName);
        (strpos($content, $gzStartSequence, 1) === false) && $content = gzinflate(substr($content,10,-8));

        $xml = new \DOMDocument();
        $xml->loadXML($content);

        self::assertTrue($xml->schemaValidate(__DIR__ . '/siteindex.xsd'));
    }

    /**
     * Проверить запись карты сайта
     */
    public function testWriteSitemap()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml');
        $map->addItem('http://example.com/1');
        $map->addItem('http://example.com/2');

        self::assertEquals($map->countItems(), 2);

        $map->write();

        self::assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    /**
     * Проверить схему с языками
     */
    public function testWriteLanguages()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml');
        $map->addItem([
            'ru' => 'http://example.com/1/',
            'en' => 'http://example.com/en/1/',
            'us' => 'http://example.com/us/1/',
        ]);

        $map->addItem([
            'ru' => 'http://example.com/2/',
            'en' => 'http://example.com/en/2/',
            'us' => 'http://example.com/us/2/',
        ]);

        self::assertEquals($map->countItems(), 2);
        $map->write();

        self::assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName, true);
        unlink($fileName);
    }

    /**
     * Проверить максимальное количество адресов
     */
    public function testWriteOverflowUrls()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml', false, __DIR__);
        $map->setMaxUrls(300);


        for ($i = 0; $i < 600; $i++)
        {
            $map->addItem("http://example.com/{$i}");
        }

        $map->write();

        self::assertFileExists($fileName);
        self::assertFileExists($path1 = __DIR__ . '/0_sitemap.xml');
        self::assertFileExists($path2 = __DIR__ . '/1_sitemap.xml');

        $this->assertIsValidIndex($fileName);
        $this->assertIsValidSitemap($path1);
        $this->assertIsValidSitemap($path2);

        unlink($fileName);
        unlink($path1);
        unlink($path2);
    }

    /**
     * Проверить читение карты сайта
     */
    public function testReadSitemap()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml');
        $map->addItem('http://example.com/1');
        $map->addItem('http://example.com/2');
        $map->write();

        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName);

        $map->addItem('http://example.com/3');
        $map->addItem('http://example.com/3'); // Дубликат
        $map->addItem('http://example.com/4');

        self::assertEquals($map->countItems(), 4);
        $this->assertIsValidSitemap($fileName);

        unlink($fileName);
    }

    /**
     * Проверить читение карты сайта
     */
    public function testReadSitemapLanguages()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml');

        $map->addItem([
            'ru' => 'http://example.com/1/',
            'en' => 'http://example.com/en/1/',
            'us' => 'http://example.com/us/1/',
        ]);

        $map->addItem([
            'ru' => 'http://example.com/2/',
            'en' => 'http://example.com/en/2/',
            'us' => 'http://example.com/us/2/',
        ]);

        $map->write();

        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName);

        $map->addItem([ // Copy
            'ru' => 'http://example.com/2/',
            'en' => 'http://example.com/en/2/',
            'us' => 'http://example.com/us/2/',
        ]);

        $map->addItem([
            'ru' => 'http://example.com/3/',
            'en' => 'http://example.com/en/3/',
            'us' => 'http://example.com/us/3/',
        ]);

        self::assertEquals($map->countItems(), 3);
        unset($fileName);
    }

    /**
     * Проверить запись сжатаю карту сайта
     */
    public function testWriteSitemapGz()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml.gz', true);
        $map->addItem('http://example.com/1');
        $map->addItem('http://example.com/2');
        $map->addItem('http://example.com/3');

        self::assertEquals($map->countItems(), 3);

        $map->write();

        self::assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName, false);

        unlink($fileName);
    }

    /**
     * Проверить сжатаю карту сайта
     */
    public function testReadSitemapGz()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml.gz', true);
        $map->addItem('http://example.com/1');
        $map->addItem('http://example.com/2');
        $map->addItem('http://example.com/3');

        $map->write();
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml.gz', true);
        $map->addItem('http://example.com/4');

        self::assertEquals($map->countItems(), 4);
        self::assertFileExists($fileName);
        $this->assertIsValidSitemap($fileName, false);
        unlink($fileName);
    }

    /**
     * Проверить максимальное количество адресов
     */
    public function testWriteOverflowUrlsGz()
    {
        $map = new DeftCMS\Components\b1tc0re\Sitemap\Sitemap($fileName = __DIR__ . '/sitemap.xml.gz', true, __DIR__);
        $map->setMaxUrls(300);

        for ($i = 0; $i < 600; $i++)
        {
            $map->addItem("http://example.com/{$i}");
        }

        $map->write();

        self::assertFileExists($fileName);
        self::assertFileExists($path1 = __DIR__ . '/0_sitemap.xml.gz');
        self::assertFileExists($path2 = __DIR__ . '/1_sitemap.xml.gz');

        $this->assertIsValidIndex($fileName);
        $this->assertIsValidSitemap($path1);
        $this->assertIsValidSitemap($path2);

        unlink($fileName);
        unlink($path1);
        unlink($path2);
    }
}