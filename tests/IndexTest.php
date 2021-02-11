<?php

use DeftCMS\Components\b1tc0re\Sitemap\Index;
use PHPUnit\Framework\TestCase;

class IndexTest extends TestCase
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
    private $fileName = 'siteindex';

    /**
     * Подтверждает действительность siteindex согласно схеме XSD.
     *
     * @param string $fileName
     */
    protected function assertIsValidIndex($fileName): void
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
    protected function getIsGzipContent($content, $path): string
    {
        $startSequence = pack('H*', '1F8B08');

        if (strpos($content, $startSequence, 1) !== false) {
            return gzinflate(substr($content, 10, -8));
        }

        return pathinfo($path, PATHINFO_EXTENSION) !== 'gz' ? $content : gzinflate(substr($content, 10, -8));
    }

    /**
     * Проверка добовления, удаление и запись карты сайта
     */
    public function testAddItemRemoveAndWrite(): void
    {
        $index = new Index($this->documentRoot . $this->fileName, false, false);

        // Проверка правильности добавление ссылок
        $index->addSitemap('https://example.com/path/to/document/1/');
        $index->addSitemap('https://example.com/path/to/document/2/');
        $index->addSitemap('https://example.com/path/to/document/3/');
        self::assertEquals(3, $index->countItems());

        // Проверка удаление ссылок
        $index->removeSitemap('https://example.com/path/to/document/3/');
        self::assertEquals(2, $index->countItems());

        // Проверка дубликатов
        $index->addSitemap('https://example.com/path/to/document/2/');
        self::assertEquals(2, $index->countItems());

        $index->write();

        self::assertFileExists($index->getFilePath());
        $this->assertIsValidIndex($index->getFilePath());

        unlink($index->getFilePath());
    }

    /**
     * Проверка сжатия карты сайта (записи,читение)
     */
    public function testGzipWriteReadSitemap(): void
    {
        $index = new Index($this->documentRoot . $this->fileName, true, false);
        // Проверка правильности добавление ссылок
        $index->addSitemap('https://example.com/path/to/document/1/');
        $index->addSitemap('https://example.com/path/to/document/2/');
        $index->addSitemap('https://example.com/path/to/document/3/');

        $index->write();

        self::assertFileExists($index->getFilePath());
        $this->assertIsValidIndex($index->getFilePath());

        // Проверка читение
        $index = new Index($this->documentRoot . $this->fileName, true, true);
        self::assertEquals(3, $index->countItems());

        $index->addSitemap('https://example.com/path/to/document/4/');
        self::assertEquals(4, $index->countItems());

        self::assertFileExists($index->getFilePath());
        $this->assertIsValidIndex($index->getFilePath());

        unlink($index->getFilePath());
    }
}
