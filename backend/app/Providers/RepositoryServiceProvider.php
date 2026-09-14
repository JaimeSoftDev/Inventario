<?php

namespace App\Providers;

use App\Repositories\Contracts\EntradaStockRepositoryInterface;
use App\Repositories\Contracts\MovimientoStockRepositoryInterface;
use App\Repositories\Eloquent\EloquentEntradaStockRepository;
use App\Repositories\Eloquent\EloquentMovimientoStockRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(EntradaStockRepositoryInterface::class, EloquentEntradaStockRepository::class);
        $this->app->bind(MovimientoStockRepositoryInterface::class, EloquentMovimientoStockRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
