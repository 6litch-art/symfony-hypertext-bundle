<?php

namespace Hypertext\Bundle\DependencyInjection;

use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;
use Hypertext\Bundle\Factory\HypertextFactory;

class CacheWarmer implements CacheWarmerInterface
{
    /**
     * @var string
     */
    protected string $shellVerbosity;

    /**
     * @var HypertextFactory
     */
    protected $hypertextFactory;

    public function __construct(HypertextFactory $hypertextFactory)
    {
        $this->shellVerbosity = getenv("SHELL_VERBOSITY");
        $this->hypertextFactory   = $hypertextFactory;
    }

    public function isOptional(): bool
    {
        return false;
    }
    public function warmUp($cacheDir): array
    {
        if ($this->shellVerbosity > 0 && php_sapi_name() == "cli") {
            echo " // Warming up cache... Hypertext access bundle.. ";
        }

        $htpasswd = $this->hypertextFactory->htpasswd();
        if ($this->shellVerbosity > 0 && php_sapi_name() == "cli" && $htpasswd) {
            echo ".htpasswd.. ";
        }

        $htaccess = $this->hypertextFactory->htaccess();
        if ($this->shellVerbosity > 0 && php_sapi_name() == "cli" && $htaccess) {
            echo ".htaccess.. ";
        }

        if ($this->shellVerbosity > 0 && php_sapi_name() == "cli") {
            echo PHP_EOL.PHP_EOL;
        }

        return [];
    }
}
