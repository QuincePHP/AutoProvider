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
	 * @param Application $app
	 * @param Repository  $config
	 * @param Filesystem  $file
	 */
	public function __construct(Application $app, Repository $config, Filesystem $file)
	{
		$this->app = $app;
		$this->config = $config;
		$this->file = $file;
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
			$files = scandir($folder);

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
		return $this->config->get('auto-provider.app_namespace') . '\\' . $this->getProviderFolder() . '\\' . $filename;
	}

	/**
	 * @return mixed
	 */
	protected function getProviderFolder()
	{
		$folder = str_replace(
			'/',
			'\\',
			substr(
				$this->config->get('auto-provider.providers_folder_path'),
				strlen(app_path()) + 1
			)
		);

		return $folder;
	}

	/**
	 * @param $className
	 * @return bool
	 */
	protected function isAddable($className)
	{
		return !$this->alreadyLoaded($className) && !$this->createProvider($className)->isDeferred();
	}

	/**
	 * @param $className
	 * @return bool
	 */
	protected function alreadyLoaded($className)
	{
		$manifest = $this->loadManifest();

		return (in_array($className, $manifest['providers']));
	}

	/**
	 * @return mixed
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
	 * @return string
	 */
	protected function getManifestPath()
	{
		return $this->app->storagePath() . '/framework/services.json';
	}

	/**
	 * @param $provider
	 * @return ServiceProvider
	 */
	protected function createProvider($provider)
	{
		return new $provider($this->app);
	}

	/**
	 * Overwrite old manifest with new manifest
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
	 * @param $providers
	 */
	protected function registerProviders($providers)
	{
		foreach ($providers as $provider) {
			$this->app->register($this->createProvider($provider));
		}
	}

}
