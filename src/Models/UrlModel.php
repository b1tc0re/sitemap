<?php namespace DeftCMS\Components\b1tc0re\Sitemap\Models;

/**
 * Модель для каждой записи URL-адреса карты сайта
 *
 *
 * @package     DeftCMS
 * @author	    b1tc0re
 * @copyright   2020-2021 DeftCMS (https://deftcms.ru/)
 * @since	    Version 0.0.9a
 */
class UrlModel
{
    /**
     * URL-адрес страницы
     *
     * @var string
     */
    protected $location;

    /**
     * Ссылки на все языковые и региональные версии страницы, включая основную
     *
     * @var array
     */
    protected $alternates = [];

    /**
     * Дата последнего изменения страницы.
     *
     * @var string
     */
    protected $lastModified;

    /**
     * Вероятная частота изменения этой страницы
     *
     * @var string
     */
    protected $changeFrequency = 'daily';

    /**
     * Priority (0.0-1.0). Default  0.5
     *
     * @var string
     */
    protected $priority = '0.5';

    /**
     * UrlModel constructor.
     *
     * @param array $params - Url parameters
     */
    public function __construct(array $params)
    {
        foreach ($params as $name => $value)
        {
            if( property_exists($this, $name) )
            {
                $setter = "set" . ucfirst($name);
                $this->$setter($value);
            }
        }
    }

    /**
     * Дата последнего изменения страницы.
     *
     * @param int|null $lastModified - timestamp
     *
     * @return $this
     */
    public function setLastModified($lastModified = null)
    {
        $this->lastModified = date('c');
        $lastModified !== null && $this->lastModified = date('c', $lastModified);

        return $this;
    }

    /**
     * Устоновить URL-адрес страницы.
     * @param string $location
     *
     * @throws \InvalidArgumentException
     * @return $this
     */
    public function setLocation(string $location)
    {
        if( !filter_var($location, FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED) )
        {
            throw new \InvalidArgumentException("Please specify valid url.");
        }

        $this->location = $location;

        return $this;
    }

    /**
     * Устоновить приоритетность URL относительно других URL на Вашем сайте
     *
     * @param string|null $priority Default 0.5
     *
     * @return $this
     */
    public function setPriority(string $priority = null)
    {
        if ( $priority === null || !is_numeric($priority) || $priority < 0 || $priority > 1)
        {
            $priority = '0.5';
        }

        $this->priority = number_format($priority, 1, '.', ',');

        return $this;
    }

    /**
     * Устоновить частоту изменения этой страницы
     * @param string|null $changeFrequency
     *
     * @return $this
     */
    public function setChangeFrequency(string $changeFrequency = null)
    {
        if( !in_array($changeFrequency, FrequencyTypes::$validFrequency, true) )
        {
            $changeFrequency = FrequencyTypes::DAILY;
        }

        $this->changeFrequency = $changeFrequency;

        return $this;
    }

    /**
     * Устоновить ссылки на все языковые и региональные версии страницы, включая основную
     * @param array $alternates
     *
     * @return $this
     */
    public function setAlternates(array $alternates)
    {
        $this->alternates = $alternates;
        return $this;
    }

    /**
     * @return string
     */
    public function getChangeFrequency()
    {
        return $this->changeFrequency;
    }

    /**
     * @return string
     */
    public function getLastModified()
    {
        return $this->lastModified;
    }

    /**
     * @return string
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * Ссылки на все языковые и региональные версии страницы, включая основную
     *
     * @return array
     */
    public function getAlternates()
    {
        return $this->alternates;
    }
}
