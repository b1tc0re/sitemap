<?php

namespace DeftCMS\Components\b1tc0re\Sitemap\Models;

use Countable;
use Traversable;

/**
 * @author	    b1tc0re
 * @copyright   2020-2021 DeftCMS (https://deftcms.ru/)
 *
 * @since	    Version 0.0.9a
 */
class LocationCollection implements \IteratorAggregate, Countable
{
    /**
     * Колекция.
     *
     * @var array
     */
    private $items = [];

    /**
     * Количество элементов.
     *
     * @var int
     */
    private $count = 0;

    /**
     * LocationCollection constructor.
     *
     * @param array $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->count = count($items);
    }

    /**
     * Retrieve an external iterator.
     *
     * @link  https://php.net/manual/en/iteratoraggregate.getiterator.php
     *
     * @return Traversable An instance of an object implementing <b>Iterator</b> or
     *                     <b>Traversable</b>
     *
     * @since 5.0.0
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->items);
    }

    /**
     * Добавить новый адрес если он не сушествует
     *
     * @param UrlModel $value
     */
    public function addNotExist(UrlModel $value)
    {
        if (false === $this->exist($value)) {
            $this->add($value);
        }
    }

    /**
     * Добавить новый адрес если он не сушествует
     *
     * @param UrlModel $value
     */
    public function addUpdateExist(UrlModel $value)
    {
        if (false !== ($index = $this->search($value))) {
            $this->items[$index] = $value;
        } else {
            $this->add($value);
        }
    }

    /**
     * Добавить новый адрес
     *
     * @param UrlModel $value
     */
    public function add(UrlModel $value)
    {
        $this->items[$this->count++] = $value;
    }

    /**
     * Удалить элемент
     *
     * @param UrlModel $value
     */
    public function remove(UrlModel $value)
    {
        if ($index = $this->search($value)) {
            unset($this->items[$index]);
        }
    }

    /**
     * Количество элементов.
     *
     * @return int
     */
    public function count()
    {
        return $this->count;
    }

    /**
     * Проверить если есть указанная карта.
     *
     * @param UrlModel $value
     *
     * @return bool
     */
    public function exist(UrlModel $value)
    {
        return $this->search($value) !== false;
    }

    /**
     * Разбивает колекцию на части.
     *
     * @param int $length - Количество элементов в массиве
     * @param int $chunks - Количество массивов
     *
     * @return LocationCollection[]
     */
    public function chunk(int $length, &$chunks = 0)
    {
        if ($this->count() <= $length) {
            return [$this];
        }

        $collections = [];

        foreach (array_chunk($this->items, $length, true) as $chunk) {
            $collections[] = new self($chunk);
        }

        $chunks = count($collections);

        return $collections;
    }

    /**
     * Получить первый элемент
     *
     * @return UrlModel
     */
    public function first()
    {
        return current($this->items);
    }

    /**
     * Получить индекс найденого элемента.
     *
     * @param UrlModel $element
     *
     * @return int|false
     */
    public function search(UrlModel $element)
    {
        /**
         * @var UrlModel $item
         */
        foreach ($this->items as $index => $item) {
            if ($item->getLocation() === $element->getLocation()) {
                return $index;
            }
        }

        return false;
    }
}
