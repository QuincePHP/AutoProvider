<?php namespace Quince\AutoProvider; 

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;

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
	 * @param Application $app
	 * @param Repository  $config
	 */
	public function __construct(Application $app, Repository $config)
	{
		$this->app = $app;
		$this->config = $config;
	}

	/**
	 * Register service providers inside Providers folder
	 *
	 * @return void
	 */
	public function run()
	{
		$manifestPath = $this->app->storagePath().'/framework/services.json';

		(new ProviderRepository($this->app, new Filesystem, $manifestPath))
			->load($this->getProviders());
	}

	/**
	 * Get list of service providers inside Provider folder
	 *
	 * @return array
	 */
	protected function getProviders()
	{
		$files = scandir($this->config->get('auto-provider.providers_folder'));

		foreach ($files as $file) {
			$fileInfo = pathinfo($file);

			if ($fileInfo['extension'] == 'php') {
				$this->addProvider($fileInfo['filename']);
			}
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
			$this->providers[] = $className;
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
		return $this->config->get('auto-provider.app_namespace') . '\\Providers\\' . $filename;
	}

}
