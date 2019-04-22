<?php
namespace Mxmm\ImageResize\Tests\Unit;

use Illuminate\Support\Facades\Storage;
use Mxmm\ImageResize\Tests\TestCase;
use Mxmm\ImageResize\Facade as ImageResize;

class ResizeTest extends TestCase
{
    /** @test */
    public function it_can_resize_an_image()
    {
        $path = ImageResize::path('test.png', 300, 300, 'resize');

        Storage::assertExists($path);
    }

    /** @test */
    public function it_can_fit_an_image()
    {
        $path = ImageResize::path('test.png', 300, 300, 'fit');

        Storage::assertExists($path);
    }

    /** @test */
    public function it_can_resize_an_image_with_auto_height()
    {
        $path = ImageResize::path('test.png', 300, null, 'resize');

        Storage::assertExists($path);
    }
}
