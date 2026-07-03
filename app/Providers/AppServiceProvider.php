<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\JournalEntry;
use App\Models\Transaction;
use App\Observers\JournalEntryObserver;
use App\Observers\TransactionObserver;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecurityScheme;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Transaction::observe(TransactionObserver::class);
        JournalEntry::observe(JournalEntryObserver::class);

        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi) {
                $openApi->secure(
                    SecurityScheme::http('basic')
                );
            });
    }
}
