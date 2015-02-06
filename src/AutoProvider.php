<?php namespace Quince\AutoProvider; 

use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\ProviderRepository;

class AutoProvider {

	/**
	 * @var Application
	 */
	private $app;

	/**
	 * @var Repository
	 */
	private $config;

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
	 */
	public function run()
	{
		$manifestPath = $this->app->storagePath().'/framework/services.json';

		(new ProviderRepository($this->app, new Filesystem, $manifestPath))
			->load($this->getProviders());
	}

	/**
	 * @return array
	 */
	private function getProviders()
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
	 * @param $filename
	 */
	private function addProvider($filename)
	{
		$className = $this->guessClassName($filename);

		if (class_exists($className)) {
			$this->providers[] = $className;
		}
	}

	/**
	 * @param $filename
	 * @return mixed
	 */
	private function guessClassName($filename)
	{
		return $this->config->get('auto-provider.app_namespace') . '\\Providers\\' . $filename;
	}

}
