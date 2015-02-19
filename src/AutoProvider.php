<?php namespace Quince\AutoProvider;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\ServiceProvider;

class AutoProvider {

	/**
	 * @var Application
	 */
	protected $app;

	/**
	 * @var Repository
	 */
	protected $config;

	/**
	 * @var array
	 */
	protected $providers = [];

	/**
	 * @var Filesystem
	 */
	protected $file;

	/**
	 * @var string
	 */
	protected $appNamespace;

	/**
	 * @var string
	 */
	protected $providersFolder;

	/**
	 * @param Application $app
	 */
	public function __construct(Application $app)
	{
		$this->app = $app;
		$this->config = $app['config'];
		$this->file = $app['file']; 
	}

	/**
	 * Register service providers inside Providers folder
	 *
	 * @return void
	 */
	public function run()
	{
		$this->registerProviders($this->getProviders());
	}

	/**
	 * Get list of service providers inside Provider folder
	 *
	 * @return array
	 */
	protected function getProviders()
	{
		if (!empty($folder = $this->config->get('auto-provider.providers_folder_path'))) {
			// List of all files in directory
			$files = $this->file->files($folder);

			foreach ($files as $file) {
				$fileInfo = pathinfo($file);

				if ($fileInfo['extension'] == 'php') {
					$this->addProvider($fileInfo['filename']);
				}
			}

			$this->addProvidersToManifest();
		}

		return $this->providers;
	}

	/**
	 * Check for providers existance and add them to provider container
	 *
	 * @param $filename
	 */
	protected function addProvider($filename)
	{
		$className = $this->guessClassName($filename);

		if (class_exists($className)) {
			if ($this->isAddable($className)) {
				$this->providers[] = $className;
			}
		}
	}

	/**
	 * Guess provider class name
	 *
	 * @param $filename
	 * @return string
	 */
	protected function guessClassName($filename)
	{
		return $this->getAppNamespace() . '\\' . $this->getProviderFolderName() . '\\' . $filename;
	}

	/**
	 * Get the providers folder name
	 *
	 * @return mixed
	 */
	protected function getProviderFolderName()
	{
		if (!is_null($this->providersFolder)) {
			return $this->providersFolder;
		}

		$this->providersFolder = str_replace(
			'/',
			'\\',
			substr(
				$this->config->get('auto-provider.providers_folder_path'),
				strlen(app_path()) + 1
			)
		);

		return $this->providersFolder;
	}

	/**
	 * Check if the provider class is addable
	 *
	 * @param $provider
	 * @return bool
	 */
	protected function isAddable($provider)
	{
		return !$this->isAlreadyLoaded($provider) && !$this->createProvider($provider)->isDeferred();
	}

	/**
	 * check if the provider is already loaded in manifest
	 *
	 * @param $provider
	 * @return bool
	 */
	protected function isAlreadyLoaded($provider)
	{
		$manifest = $this->loadManifest();

		return (in_array($provider, $manifest['providers']));
	}

	/**
	 * Load and return manifest data
	 *
	 * @return string
	 */
	protected function loadManifest()
	{
		$manifestPath = $this->getManifestPath();

		if ($this->file->exists($manifestPath)) {
			return json_decode($this->file->get($manifestPath), true);
		}

		return null;
	}

	/**
	 * Get the path to laravel manifest
	 *
	 * @return string
	 */
	protected function getManifestPath()
	{
		return $this->app->storagePath() . '/framework/services.json';
	}

	/**
	 * Create an instance of the provider
	 *
	 * @param $provider
	 * @return ServiceProvider
	 */
	protected function createProvider($provider)
	{
		return new $provider($this->app);
	}

	/**
	 * Overwrite old manifest with new manifest
	 *
	 * @return void
	 */
	protected function addProvidersToManifest()
	{
		$manifest = $this->loadManifest();
		$manifest['providers'] = array_merge($manifest['providers'], $this->providers);
		$manifest['eager'] = array_merge($manifest['eager'], $this->providers);

		$this->file->put(
			$this->getManifestPath(),
			json_encode($manifest, JSON_PRETTY_PRINT)
		);
	}

	/**
	 * Register providers to laravel's application container
	 *
	 * @param $providers
	 */
	protected function registerProviders($providers)
	{
		foreach ($providers as $provider) {
			$this->app->register($this->createProvider($provider));
		}
	}

	/**
	 * Get the application namespace from the Composer file.
	 *
	 * @return string
	 * @throws \RuntimeException
	 */
	protected function getAppNamespace()
	{
		if (!is_null($this->appNamespace)) {
			return $this->appNamespace;
		}

		$composer = json_decode(file_get_contents(base_path() . '/composer.json'), true);

		foreach ((array) data_get($composer, 'autoload.psr-4') as $namespace => $path) {
			foreach ((array) $path as $pathChoice) {
				if (realpath(app_path()) == realpath(base_path() . '/' . $pathChoice)) {
					return $this->appNamespace = rtrim($namespace, '\\');
				}
			}
		}

		throw new \RuntimeException("Unable to detect application namespace.");
	}

}
