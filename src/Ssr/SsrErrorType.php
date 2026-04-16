<?php

declare(strict_types=1);

namespace Nytodev\InertiaBundle\Ssr;

enum SsrErrorType: string
{
    case BrowserApi = 'browser-api';
    case ComponentResolution = 'component-resolution';
    case Render = 'render';
    case Connection = 'connection';
    case Unknown = 'unknown';

    public static function fromString(?string $value): self
    {
        if (null === $value) {
            return self::Unknown;
        }

        return self::tryFrom($value) ?? self::Unknown;
    }
}
