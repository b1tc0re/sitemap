<?php

namespace DeftCMS\Components\b1tc0re\Sitemap\Models;

/**
 * Типы частоты изменения страницы.
 *
 * @author	    b1tc0re
 * @copyright   2020-2021 DeftCMS (https://deftcms.ru/)
 *
 * @since	    Version 0.0.9a
 */
class FrequencyTypes
{
    const ALWAYS = 'always';
    const HOURLY = 'hourly';
    const DAILY = 'daily';
    const WEEKLY = 'weekly';
    const MONTHLY = 'monthly';
    const YEARLY = 'yearly';
    const NEVER = 'never';

    /**
     * Допустимые значения.
     *
     * @var array
     */
    public static $validFrequency = [
        self::ALWAYS,
        self::HOURLY,
        self::DAILY,
        self::WEEKLY,
        self::MONTHLY,
        self::YEARLY,
        self::NEVER,
    ];
}
