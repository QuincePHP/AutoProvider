# AutoProvider
Automatically register service providers located in `Providers` for laravel 5

## Installation

Add auto-provider to your composer.json file:

```JSON
"require": {
  "artdarek/oauth-4-laravel": "dev-master"
}
```

Update your vendor by typing this on terminal:

```
$ composer update
```

### Register the Package

Register package service provider in `providers` array inside `config/app.php`:

```php
'providers' => [
    // ...

    'Quince\AutoProvider\AutoProviderServiceProvider',
],
```

### Publish Package Configs

In your terminal type:

```
$ php artisan vendor:publish
```

the configuration file could be found in `config\auto-provider`.
there's two option inside the config file; `providers_folder_path` and `app_namespace`.
+ `providers_folder_path` is path to `Provide` folder where you keep your service providers there.
If you put your service providers somewhere else, you should point this option to your desired folder.
+ `app_namespace` is your application namespace. All laravel 5 application has `App` namespace for `app` folder by default.
If you changed your application namespace by running `$ php artisan app:name YouDesiredNameSpace` you should update this option to your new application namespace.

## TODO

+ For now, service providers which is deffered, would not be automatically registered.
+ Write unit tests, *(I didn't have so much time to unit test, but I have tested it and it worked)*
