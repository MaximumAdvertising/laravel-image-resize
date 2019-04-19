# Image Resize Helper for Laravel 5.x
Dynamically resize an image and returns the URL using Intervention and Storage

[![Latest Stable Version](https://poser.pugx.org/maximumadvertising/laravel-image-resize/v/stable)](https://packagist.org/packages/maximumadvertising/laravel-image-resize)
[![Latest Unstable Version](https://poser.pugx.org/maximumadvertising/laravel-image-resize/v/unstable)](https://packagist.org/packages/maximumadvertising/laravel-image-resize)

## Require
- Laravel 5+
- Intervention Image ^2.4

## Supported Filesystem Disks
- Local
- S3
- Oss (Aliyun Cloud Storage)

## Installation
This package can be installed through Composer.
 ```
composer require maximumadvertising/laravel-image-resize:@dev
 ```

Publish config and assets (Optional)
 ```
php artisan vendor:publish --provider="Mxmm\ImageResize\ImageResizeServiceProvider"
 ```

## Usage
```$xslt
<img src="{{ ImageResize::url('originalDir/filename.jpg', width, height, [action]) }}" />
<img src="{{ ImageResize::url('originalDir/filename.jpg', 200, 200, fit) }}" />
<img src="{{ ImageResize::url('originalDir/filename.jpg', 200, 200) }}" />
```
sample output
```$xslt
<img src="https://localhost/thumbs/originalDir/fit/140x160/filename.jpg" />
```
