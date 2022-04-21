<?php

declare(strict_types=1);

namespace Loner\Stream\Site;

/**
 * UNIX 相关
 */
trait Unix
{
    /**
     * @inheritDoc
     */
    public static function transport(): string
    {
        return 'unix';
    }

    /**
     * @inheritDoc
     */
    public function getTarget(): string
    {
        return $this->target;
    }
}
