<?php

namespace IchiharaYamato\Blade;

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\Factory;
use Illuminate\View\FileViewFinder;
use Illuminate\View\ViewFinderInterface;

class Blade
{
    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var BladeCompiler
     */
    protected $compiler;

    /**
     * @var Container
     */
    protected $container;

    /**
     * @var string
     */
    protected $viewPaths;

    /**
     * @var string
     */
    protected $cachePath;

    /**
     * Create a new Blade instance.
     *
     * @param  array|string  $viewPaths
     * @param  string  $cachePath
     * @param  \Illuminate\View\Factory|null  $factory
     * @return void
     */
    public function __construct($viewPaths, string $cachePath, Factory $factory = null)
    {
        $this->viewPaths = $viewPaths;
        $this->cachePath = $cachePath;

        $this->setupContainer();

        if (isset($factory)) {
            $this->factory = $factory;
        } else {
            $this->setupFactory();
        }
    }

    /**
     * Setup the IoC container instance.
     *
     * @return void
     */
    protected function setupContainer()
    {
        $this->container = new Container;

        $this->container->singleton('files', function () {
            return new Filesystem;
        });

        $this->container->singleton('events', function () {
            return new Dispatcher($this->container);
        });

        // Laravel 11 の BladeCompiler はコンストラクタが変わっている
        $this->container->singleton('blade.compiler', function () {
            $compiler = new BladeCompiler(
                $this->container['files'], 
                $this->cachePath
            );
            
            // カスタムディレクティブなどの初期化
            
            return $compiler;
        });
    }

    /**
     * Setup view factory.
     *
     * @return void
     */
    protected function setupFactory()
    {
        $resolver = new EngineResolver;

        $resolver->register('blade', function () {
            if (!isset($this->compiler)) {
                $this->compiler = $this->container['blade.compiler'];
            }
            return new CompilerEngine($this->compiler);
        });

        $finder = new FileViewFinder($this->container['files'], (array) $this->viewPaths);

        $this->factory = new Factory(
            $resolver,
            $finder,
            $this->container['events']
        );
    }

    /**
     * Compile the view at the given path.
     *
     * @param  string  $path
     * @return void
     */
    public function compile($path = null)
    {
        if (!isset($this->compiler)) {
            $this->compiler = $this->container['blade.compiler'];
        }
        
        $path = $path ?: $this->viewPaths;

        $path = is_array($path) ? reset($path) : $path;

        $directory = $path;

        if (! is_dir($directory)) {
            $directory = dirname($path);
        }

        foreach ($this->container['files']->allFiles($directory) as $file) {
            if ($file->getExtension() === 'php' && strpos($file->getFilename(), '.blade.') !== false) {
                $this->compiler->compile($file->getRealPath());
            }
        }
    }

    /**
     * Render a template.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return string
     */
    public function render($view, $data = [], $mergeData = [])
    {
        return $this->make($view, $data, $mergeData)->render();
    }

    /**
     * Create a new view instance.
     *
     * @param  string  $view
     * @param  array   $data
     * @param  array   $mergeData
     * @return \Illuminate\View\View
     */
    public function make($view, $data = [], $mergeData = [])
    {
        return $this->factory->make($view, $data, $mergeData);
    }

    /**
     * Get the evaluated view contents for a named view.
     *
     * @param  string  $view
     * @param  array   $data
     * @return string
     */
    public function view($view, $data = [])
    {
        return $this->make($view, $data)->render();
    }

    /**
     * Add a piece of shared data to the environment.
     *
     * @param  array|string  $key
     * @param  mixed  $value
     * @return mixed
     */
    public function share($key, $value = null)
    {
        return $this->factory->share($key, $value);
    }

    /**
     * Register a view composer event.
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function composer($views, $callback)
    {
        return $this->factory->composer($views, $callback);
    }

    /**
     * Register a view creator event.
     *
     * @param  array|string  $views
     * @param  \Closure|string  $callback
     * @return array
     */
    public function creator($views, $callback)
    {
        return $this->factory->creator($views, $callback);
    }

    /**
     * Add a new namespace to the loader.
     *
     * @param  string  $namespace
     * @param  string|array  $hints
     * @return $this
     */
    public function addNamespace($namespace, $hints)
    {
        $this->factory->addNamespace($namespace, $hints);

        return $this;
    }

    /**
     * Get the compiler
     *
     * @return \Illuminate\View\Compilers\BladeCompiler
     */
    public function getCompiler()
    {
        if (!isset($this->compiler)) {
            $this->compiler = $this->container['blade.compiler'];
        }
        
        return $this->compiler;
    }

    /**
     * Register a custom Blade compiler.
     *
     * @param  callable  $compiler
     * @return $this
     */
    public function extend(callable $compiler)
    {
        $this->getCompiler()->extend($compiler);

        return $this;
    }

    /**
     * Register a handler for custom directives.
     *
     * @param  string  $name
     * @param  callable  $handler
     * @return $this
     */
    public function directive($name, callable $handler)
    {
        $this->getCompiler()->directive($name, $handler);

        return $this;
    }

    /**
     * Get the view factory.
     *
     * @return \Illuminate\View\Factory
     */
    public function getFactory()
    {
        return $this->factory;
    }
}