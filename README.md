# Image Resize Helper for Laravel 5.x
Dynamically resize an image and returns the URL using Intervention and Storage

[![Latest Stable Version](https://poser.pugx.org/maximumadvertising/laravel-image-resize/v/stable)](https://packagist.org/packages/maximumadvertising/laravel-image-resize)
[![Latest Unstable Version](https://poser.pugx.org/maximumadvertising/laravel-image-resize/v/unstable)](https://packagist.org/packages/maximumadvertising/laravel-image-resize)

## Require
- Laravel 5+

## Installation
 ```
 composer require maximumadvertising/laravel-image-resize:@dev
 ```
 Add to package service providers
 ```$xslt
Mxmm\ImageResize\ImageResizeServiceProvider::class,
```
and Alias
```$xslt
'ImageResize' => Mxmm\ImageResize\Facade::class,
``` 
#### Publish and override config (Optional)
`$ php artisan vendor:publish --provider="Mxmm\ImageResize\ImageResizeServiceProvider"`

## Usage
```$xslt
<img src="{{ imageResize::url('path\to\image-file', width, height) }}" />
```

