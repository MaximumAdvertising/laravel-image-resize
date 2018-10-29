<?php

namespace Mxmm\ImageResize;

use Image, Storage, Log;

class ImageResize
{
    private $path, $width, $height, $action, $basename, $targetPath, $targetMetaData = [];

    public function __construct($path)
    {
        $this->path         = $path;
        $this->basename     = pathinfo($this->path)['basename'];
    }

    private function settings(int $width, int $height, string $action = 'fit'): ImageResize
    {
        $this->width    = $width;
        $this->height   = $height;
        $this->action   = $action;
        $this->setTargetPath();
        return $this;
    }

    private function setTargetPath(): ImageResize
    {
        $targetSubDirName   = $this->width . 'x' . $this->height;
        $targetDirName      = config('image-resize.dir') . pathinfo($this->path)['dirname'] . '/' . $this->action . '/' . $targetSubDirName . '/';
        $targetPath         = $targetDirName . $this->basename;
        // check for temp preview images
        if(strpos($this->path, config('image-resize.preview-temp-dir')) !== false) {
            $targetPath = config('image-resize.preview-temp-dir') . config('image-resize.dir')
                . $this->action . '_' . $targetDirName . '/' . $this->basename;
        }

        $this->targetPath = $targetPath;
        return $this;
    }
    
    public static function url(string $path, int $width, int $height, $action = 'fit'): string
    {
        if (!$path || $width < 1 || $height < 1){
            return '';
        }

        $image = new ImageResize($path);
        $image->settings($width, $height, $action);

        try {
            $image->targetMetaData = \Storage::getMetadata($image->targetPath);
        } catch (\Exception $e) {
            if (config('filesystems.default') != 'local' && !\Storage::exists($path)){
                if (!\Storage::disk('public')->exists($path)) {
                    return '';
                }
                // File exists in local public disc but not in cloud
                $image->upload($path, \Storage::disk('public')->get($path), \Storage::disk('public')->mimeType(path));
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
        $url = '';
        $adapter    = \Storage::getAdapter();
        if (array_key_exists('info', $this->targetMetaData)){

            $url        = $this->targetMetaData['info']['url'];

            if (get_class($adapter) == 'Jacobcyl\AliOSS\AliOssAdapter'){
                $url = $adapter->getUrl($this->targetPath);
            }

        } else if (array_key_exists('path', $this->targetMetaData)){
            $url = \Storage::url($this->targetMetaData['path']);
        }

        if (\Request::secure() == true){
            $url = str_replace('http:', 'https:', $url);
        }

        return $url;
    }

    private function resize(): bool
    {
        try {
            $sourceMetaData = \Storage::getMetadata($this->path);
        } catch (\Exception $e) {
            \Log::error('[IMAGE Resize] source file does not exist [' . $e->getMessage() . ']');
            return false;
        }

        if (array_key_exists('last-modified', $this->targetMetaData) &&
            $this->targetMetaData['last-modified'] > $sourceMetaData['last-modified']){
            return false;
        }

        switch ($this->action) {
            case 'fit':
                try {
                    $image = Image::make(\Storage::get($this->path))->fit($this->width, $this->height, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize(); // do not enlarge small images
                    })->encode(\Storage::mimeType($this->path));

                    $this->upload($this->targetPath, (string) $image, \Storage::mimeType($this->path));
                } catch (\Exception $e) {
                    \Log::error('[IMAGE SERVICE] Failed to resize image "' . $this->targetPath . '" [' . $e->getMessage() . ']');
                    return false;
                }
                break;
            default:
                return false;
        }

        $this->targetMetaData = \Storage::getMetadata($this->targetPath);

        return true;
    }

    private function upload($path, $image, $contentType)
    {
        $browserCache = config('image-resize.browser-cache');

        \Storage::getDriver()->put($path, $image, [
            'visibility' => 'public',
            'Expires' => gmdate('D, d M Y H:i:s', time() + $browserCache) . ' GMT',
            'CacheControl' => 'public, max-age=' . $browserCache,
            'ContentType' => $contentType,
            'ContentDisposition' => 'inline; filename="' . $this->basename . '"',
        ]);
    }

    private function filePlaceholder($info, $path)
    {
        if (in_array($info['extension'], ['mp4', 'webm'])) {
            return asset(config('image-resize.placeholder-video'));
        } elseif (in_array($info['extension'], ['svg'])){
            return \Storage::url($path);
        } else {
            return asset(config('image-resize.placeholder-file'));
        }
    }
}