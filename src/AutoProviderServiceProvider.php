<?php namespace Quince\AutoProvider;

use Illuminate\Support\ServiceProvider;

class AutoProviderServiceProvider extends ServiceProvider {

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/auto-provider.php' => config_path('auto-provider.php')
		]);
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

}
