# env
[![Build Status](https://travis-ci.com/phoole/env.svg?branch=master)](https://travis-ci.com/phoole/env)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/phoole/env/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/phoole/env/?branch=master)
[![Code Climate](https://codeclimate.com/github/phoole/env/badges/gpa.svg)](https://codeclimate.com/github/phoole/env)
[![PHP 7](https://img.shields.io/packagist/php-v/phoole/env/1.0.3)](https://packagist.org/packages/phoole/env)
[![Latest Stable Version](https://img.shields.io/packagist/vpre/phoole/env.svg?style=flat)](https://packagist.org/packages/phoole/env)
[![License](https://img.shields.io/github/license/phoole/env)]()

**phoole/env** is a library to load environment variables from a .env file. It requires PHP 7.2+ and is compliant with [PSR-1][PSR-1], [PSR-4][PSR-4], [PSR-12][PSR-12].

[PSR-1]: http://www.php-fig.org/psr/psr-1/ "PSR-1: Basic Coding Standard"
[PSR-4]: http://www.php-fig.org/psr/psr-4/ "PSR-4: Autoloader"
[PSR-12]: http://www.php-fig.org/psr/psr-2/ "PSR-12: Extended Coding Style Guide"
[variable]: https://www.gnu.org/software/bash/manual/html_node/Shell-Parameter-Expansion.html "Shell Variable Expansion"

Installation
---
Install via the `composer` utility.

```
composer require "phoole/env=1.*.*"
```

or add the following lines to your `composer.json`

```json
{
    "require": {
       "phoole/env": "~1.0.0"
    }
}
```

Usage
---

- Put your environments in file `.env`. By default, existing environment variables are not overwritten.

  ```shell
  # this is comment line
  BASE_DIR = /usr/local  # spaces allowed

  # use reference here
  APP_DIR = ${BASE_DIR}/app   # /usr/local/app

  # use bash style :- or :=
  TMP_DIR = ${SYSTEM_TMP_DIR:-${APP_DIR}/tmp} 
  ```
  See [shell variable expansion][variable] for `:-` and `:=` usage.

- Load and use your env variables in PHP script

  ```php
  <?php
  // ...
  $env = new Phoole\Env\Environment();

  # load env file, will NOT overwrite existing env variables
  $env->load(__DIR__ . '/.env');

  // use your env
  echo getenv('APP_DIR');
  ```

Features
---

- Support shell default values, `${param:-new}` or `${param:=new}`

- By default, **WILL NOT** overwrite any existing environment variables.

  To overwrite existing env variables,

  ```php
  env->load('./.env', TRUE);
  ```

- Relaxed syntax (not compatible with bash) in env file

  ```php
  # spaces before and after '=' is allowed. NOT recommended though
  ROOT_DIR = /var/tmp

  # same as above
  ROOT_DIR=/var/tmp

  # same as above
  ROOT_DIR="/var/tmp"
  ```

- [PSR-1][PSR-1], [PSR-4][PSR-4], [PSR-12][PSR-12] compliant.

Testing
---

```bash
$ composer test
```

Dependencies
---

- PHP >= 7.2.0

License
---

 - [Apache 2.0](https://www.apache.org/licenses/LICENSE-2.0)
