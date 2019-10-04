<?php
/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Env
 * @copyright Copyright (c) 2019 Hong Zhang
 */
declare(strict_types=1);

namespace Phoole\Env;

use Phoole\Base\Reference\{ReferenceTrait, ReferenceInterface};

/**
 * Load environment key/value pairs from certain path.
 *
 * @package Phoole\Env
 */
class Environment implements ReferenceInterface
{
    use ReferenceTrait;

    /**
     * Load environment variables from a .env file
     *
     * @param  string $path          full path of the .env file
     * @param  bool   $overwrite     overwrite existing values
     * @throws \RuntimeException     if $path not readable
     * @return object $this          able to chain
     */
    public function load(string $path, bool $overwrite = false): object
    {
        return $this->parse($this->loadPath($path), $overwrite);
    }

    /**
     * Parse an array to set environment variables
     *
     * @param  array  $arr          full path of the .env file
     * @param  bool   $overwrite    overwrite existing env values
     * @return object $this         able to chain
     */
    public function parse(array $arr, bool $overwrite = false): object
    {
        foreach ($arr as $key => $val) {
            $this->setEnv($key, $this->deReferenceString($val), $overwrite);
        }
        return $this;
    }

    /**
     * load content of a file into array
     *
     * @param  string $path        full path of the .env file
     * @throws \RuntimeException   if $path not readable
     * @return array
     */
    protected function loadPath(string $path): array
    {
        try {
            if ($str = \file_get_contents($path)) {
                return $this->parseString($str);
            }
            return [];
        } catch (\Throwable $e) {
            throw new \RuntimeException($e->getMessage());
        }
    }

    /**
     * Parse 'ENV = value' into pairs
     *
     * @param  string $str  string to parse
     * @return array
     */
    protected function parseString(string $str): array
    {
        $regex =
        '~^\s*+
            (?:
                (?:([^#\s=]++) \s*+ = \s*+
                    (?|
                        (([^"\'#\s][^#\n]*?)) |
                        (["\'])((?:\\\2|.)*?)\2
                    )?
                ) |
                (?: (\.|source) \s++ ([^#\n]*) )
            )\s*?(?:[#].*)?
        $~mx';

        $pairs = [];
        if (\preg_match_all($regex, $str, $matched, \PREG_SET_ORDER)) {
            foreach ($matched as $m) {
                if (isset($m[3])) {
                    $pairs[$m[1]] = $m[3];
                } else {
                    $pairs[$m[1]] = '';
                }
            }
        }
        return $pairs;
    }

    /**
     * {@inheritDoc}
     */
    protected function getReference(string $name)
    {
        $default = '';
        if (false !== strpos($name, ':-')) {
            list($name, $default) = explode(':-', $name, 2);
        } elseif (false !== strpos($name, ':=')) {
            list($name, $default) = explode(':=', $name, 2);
            $this->setEnv($name, $default, false);
        }
        return getenv($name) === false ? $default : getenv($name);
    }

    /**
     * @param  string $key key to set
     * @param  string $val value to set
     * @param  bool   $overwrite
     * @return void
     */
    protected function setEnv(string $key, string $val, bool $overwrite): void
    {
        if ($overwrite || false === getenv($key)) {
            putenv("$key=$val");
        }
    }
}
