<?php
namespace Mxmm\ImageResize\Tests;

use File;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected $imageResize;

    public function setUp(): void
    {
        parent::setUp();
    }

    public function tearDown(): void
    {
        parent::tearDown();

        $this->cleanupTestFiles();
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getEnvironmentSetUp($app)
    {
        $this->setUpTempTestFiles();

        $app['config']->set('filesystems.disks.public', [
            'driver' => 'local',
            'root' => $this->getTempDirectory(),
        ]);

        $app['config']->set('filesystems.default', 'public');
    }

    protected function setUpTempTestFiles()
    {
        $this->initializeDirectory($this->getTempDirectory());
        File::copyDirectory(__DIR__.'/Samples', $this->getTempDirectory());
    }

    protected function initializeDirectory($directory)
    {
        if (File::isDirectory($directory)) {
            File::deleteDirectory($directory);
        }

        File::makeDirectory($directory);
    }

    protected function cleanupTestFiles()
    {
        if (File::isDirectory($this->getTempDirectory())) {
            File::deleteDirectory($this->getTempDirectory());
        }
    }

    protected function getTempDirectory($suffix = '')
    {
        return __DIR__.'/temp'.($suffix == '' ? '' : '/'.$suffix);
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getPackageAliases($app)
    {
        return [
            'ImageResize' => \Mxmm\ImageResize\Facade::class,
            'Image' => \Intervention\Image\Facades\Image::class,
        ];
    }

    /**
     * @param \Illuminate\Foundation\Application $app
     */
    protected function getPackageProviders($app)
    {
        return [
            \Mxmm\ImageResize\ImageResizeServiceProvider::class,
            \Intervention\Image\ImageServiceProvider::class
        ];
    }
}
