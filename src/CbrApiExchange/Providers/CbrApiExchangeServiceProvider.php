<?php
/*
 * Copyright (c) 2021. GeorgII. george.webfullstack@gmail.com
 */

namespace CbrApiExchange\Providers;

use CbrApiExchange\CbrApiExchange;
use Illuminate\Support\ServiceProvider;

/**
 * Class CbrApiExchangeServiceProvider
 */
class CbrApiExchangeServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('cbrApiExchange', CbrApiExchange::class);
    }
}