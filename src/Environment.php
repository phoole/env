<?php declare(strict_types=1);
/**
 * Phoole (PHP7.2+)
 *
 * @category  Library
 * @package   Phoole\Env
 * @copyright Copyright (c) 2019 Hong Zhang
 */
namespace Phoole\Env;

/**
 * Load environment key/value pairs from certain path.
 *
 * @package Phoole\Env
 * @version 1.0
 */
class Environment
{
    /**
     * Load environment variables from a .env file
     *
     * @param  string $path          full path of the .env file
     * @param  bool   $overwrite     overwrite existing values
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
            $this->setEnv($key, $this->deReference($val), $overwrite);
        }
        return $this;
    }

    /**
     * load content of a file into array
     *
     * @param  string $path full path of the .env file
     * @return array
     */
    protected function loadPath(string $path): array
    {
        if ($str = \file_get_contents($path)) {
            return $this->parseString($str);
        }
        return [];
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
     * replace ${ENV} into real value
     *
     * @param  string $str string to de-reference
     * @return string
     */
    protected function deReference(string $str): string
    {
        if (false === strpos($str, '${')) {
            return $str;
        }

        return preg_replace_callback(
            '~(\${((?:[^\${}]+|(?R))*)})~',
            function ($matched) {
                return $this->expandValue(self::deReference($matched[2]));
            },
            $str
        );
    }

    /**
     * expand with bash style default value
     *
     *   :- replace with default value if not set
     *   := set & replace with default value if not set
     *
     * @param  string $str str to expand
     * @return string
     */
    protected function expandValue(string $str): string
    {
        $default = '';
        if (false !== strpos($str, ':-')) {
            list($str, $default) = explode(':-', $str, 2);
        } elseif (false !== strpos($str, ':=')) {
            list($str, $default) = explode(':=', $str, 2);
            $this->setEnv($str, $default, false);
        }
        return getenv($str) === false ? $default : getenv($str);
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
