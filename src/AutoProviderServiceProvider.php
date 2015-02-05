<?php namespace Quince\AutoProvider;

use Illuminate\Support\ServiceProvider;

class AutoProviderServiceProvider extends ServiceProvider {

	public function boot()
	{
		$this->publishes([
			__DIR__ . '/config/config.php' => $this->app->make('path.config') . 'config.php'
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
