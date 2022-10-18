<?php

namespace king052188\BinaryLoops;

use Illuminate\Support\ServiceProvider;

class BinaryLoopsServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        require __DIR__ .'/routes/routes.php';

        require __DIR__ .'/routes/bot.php';
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
      $this->app->bind('king052188-binaryloops', function() {
        return new BinaryLoops();
      });

      $this->app->bind('king052188-blhelper', function() {
        return new BLHelper();
      });

      $this->app->bind('king052188-blbot', function() {
        return new BLBot();
      });
    }
}
