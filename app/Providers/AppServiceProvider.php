<?php

    namespace App\Providers;

    use Illuminate\Support\ServiceProvider;

    /**
     * Class AppServiceProvider
     *
     * @package App\Providers
     */
    class AppServiceProvider extends ServiceProvider {
        /**
         * Bootstrap any application services.
         *
         * @return void
         */
        public function boot() {
            //
        }

        /**
         * Register any application services.
         *
         * @return void
         */
        public function register() {
            if ($this->app->environment() !== 'production') {
                $this->app->register(\Barryvdh\LaravelIdeHelper\IdeHelperServiceProvider::class);
                $this->app->register(\Barryvdh\TranslationManager\ManagerServiceProvider::class);
            }
        }
    }
