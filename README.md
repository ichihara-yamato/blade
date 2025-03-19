# Blade

[![Latest Stable Version](http://img.shields.io/github/release/ichihara-yamato/blade.svg)](https://packagist.org/packages/ichihara-yamato/blade) [![Coverage Status](http://img.shields.io/coveralls/ichihara-yamato/blade.svg)](https://coveralls.io/r/ichihara-yamato/blade)

The standalone version of [Laravel's Blade templating engine](https://laravel.com/docs/5.8/blade) for use outside of Laravel.

<p align="center">
<img src="https://jenssegers.com/static/media/blade2.png" height="200">
</p>

## Installation

Install using composer:

```bash
composer require ichihara-yamato/blade
```

## Usage

Create a Blade instance by passing it the folder(s) where your view files are located, and a cache folder. Render a template by calling the `make` method. More information about the Blade templating engine can be found on http://laravel.com/docs/5.8/blade.

```php
use IchiharaYamato\Blade\Blade;

$blade = new Blade('views', 'cache');

echo $blade->make('homepage', ['name' => 'Ichihara Yamato'])->render();
```

Alternatively you can use the shorthand method `render`:

```php
echo $blade->render('homepage', ['name' => 'Ichihara Yamato']);
```

You can also extend Blade using the `directive()` function:

```php
$blade->directive('datetime', function ($expression) {
    return "<?php echo with({$expression})->format('F d, Y g:i a'); ?>";
});
```

Which allows you to use the following in your blade template:

```
Current date: @datetime($date)
```

The Blade instances passes all methods to the internal view factory. So methods such as `exists`, `file`, `share`, `composer` and `creator` are available as well. Check out the [original documentation](https://laravel.com/docs/12.x/views) for more information.

## Integrations

- [Phalcon Slayer Framework](https://github.com/phalconslayer/slayer) comes out of the box with Blade.
