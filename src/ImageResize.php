<?php
namespace Mxmm\ImageResize;

use Intervention\Image\Facades\Image;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Adapter\Local as LocalAdapter;
use Request;
use Storage;
use Exception;

class ImageResize
{
/**
* @var String $path Image source file path
 */
    private $path;
/**
 * Intervention Image method. Currently only supports 'fit' and 'resize' method
 * @var String $action|fit
 */
    private $action;

    private $width;
    private $height;
    private $basename;
    private $adapter;
    private $targetPath;
    private $targetMetaData = [];
    protected $config;

    public function __construct(array $config, string $path = null)
    {
        $this->config       = $config;
        $this->path         = $path;
        $this->basename     = pathinfo($this->path)['basename'];
    }

    private function settings(int $width, int $height, string $action = 'fit'): ImageResize
    {
        $this->width    = $width;
        $this->height   = $height;
        $this->action   = $action;
        $this->adapter  = Storage::getAdapter();
        $this->setTargetPath();
        return $this;
    }

    private function setTargetPath(): ImageResize
    {
        $targetDirName       = $this->config['dir'] . pathinfo($this->path)['dirname'] . '/';
        $targetDirName      .= $this->action . '/' . $this->width . 'x' . $this->height . '/';
        $this->targetPath    = $targetDirName . $this->basename;
        return $this;
    }

    public static function url(string $path, int $width, int $height, $action = 'fit'): string
    {
        if (!$path || $width < 1 || $height < 1) {
            return '';
        }

        $image = new ImageResize(config('image-resize'), $path);
        $image->settings($width, $height, $action);

        try {
            $image->targetMetaData = Storage::getMetadata($image->targetPath);
        } catch (Exception $e) {
            if (!$image->adapter instanceof LocalAdapter && !Storage::exists($path)) {
                if (!Storage::disk('public')->exists($path)) {
                    return '';
                }
                // File exists in local public disk but not in cloud
                $image->upload($path, Storage::disk('public')->get($path), Storage::disk('public')->mimeType($path));
            }
        }

        if (!in_array(pathinfo($path)['extension'], ['jpg', 'jpeg', 'png', 'gif'])) {
            return $image->filePlaceholder(pathinfo($path), $path);
        }

        $image->resize();

        return $image->getUrl();
    }

    private function getUrl(): string
    {
        if (method_exists($this->adapter, 'getUrl')) {
            $url = $this->adapter->getUrl($this->targetPath);
        } elseif ($this->adapter instanceof AwsS3Adapter) {
            $url = $this->getAwsUrl();
        } elseif ($this->adapter instanceof LocalAdapter) {
            $url = Storage::url($this->targetPath);
        } else {
            $url = '';
        }

        if (Request::secure() == true) {
            $url = str_replace('http:', 'https:', $url);
        }

        return $url;
    }

    private function getAwsUrl(): string
    {
        $endpoint = $this->adapter->getClient()->getEndpoint();
        $path     =  '/' . ltrim($this->adapter->getPathPrefix() . $this->targetPath, '/');

        if (!is_null($domain = Storage::getConfig()->get('url'))) {
            $url = rtrim($domain, '/') . $path;
        } else {
            $url  = $endpoint->getScheme() . '://' . $this->adapter->getBucket() . '.' . $endpoint->getHost() . $path;
        }

        return $url;
    }

    private function resize(): bool
    {
        try {
            $sourceMetaData = Storage::getMetadata($this->path);
        } catch (Exception $e) {
            return false;
        }

        if (array_key_exists('last-modified', $this->targetMetaData) &&
            $this->targetMetaData['last-modified'] > $sourceMetaData['last-modified']) {
            return false;
        }

        switch ($this->action) {
            case 'fit':
            case 'resize':
                try {
                    $image = Image::make(Storage::get($this->path))
                        ->{$this->action}($this->width, $this->height, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        })->encode(Storage::mimeType($this->path));

                    $this->upload($this->targetPath, (string) $image, Storage::mimeType($this->path));
                } catch (Exception $e) {
                    return false;
                }
                break;
            default:
                return false;
        }

        $this->targetMetaData = Storage::getMetadata($this->targetPath);

        return true;
    }

    private function upload($path, $image, $contentType)
    {
        Storage::getDriver()->put($path, $image, [
            'visibility'         => 'public',
            'Expires'            => gmdate('D, d M Y H:i:s', time() + $this->config['browser-cache']) . ' GMT',
            'CacheControl'       => 'public, max-age=' . $this->config['browser-cache'],
            'ContentType'        => $contentType,
            'ContentDisposition' => 'inline; filename="' . $this->basename . '"',
        ]);
    }

    private function filePlaceholder(array $info, string $path): string
    {
        if (in_array($info['extension'], ['mp4', 'webm'])) {
            $url = asset('/vendor/laravel-image-resize/images/placeholders/video.svg');
        } elseif (in_array($info['extension'], ['svg'])) {
            $url = Storage::url($path);
        } else {
            $url = asset('/vendor/laravel-image-resize/images/placeholders/file.svg');
        }

        return $url;
    }
}
