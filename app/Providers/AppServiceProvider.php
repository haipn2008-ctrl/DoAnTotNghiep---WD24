<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\ContractLifecycleAlert;
use App\Models\Setting;
use App\Policies\ContractPolicy;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

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
        Paginator::useTailwind();
        Gate::policy(Contract::class, ContractPolicy::class);
        View::composer('layouts.admin.blocks.header', function ($view): void {
            $notifications = ContractLifecycleAlert::query()
                ->unresolved()
                ->with(['contract.room', 'contract.tenant', 'tenant', 'vehicle'])
                ->latest('detected_at')
                ->latest('id')
                ->limit(8)
                ->get();

            $view->with([
                'adminNotificationCount' => ContractLifecycleAlert::query()->unresolved()->count(),
                'adminNotifications' => $notifications,
            ]);
        });
        View::composer('layouts.client.blocks.sidebar', function ($view): void {
            $view->with('clientSupportPhone', Setting::currentOrCreate()->landlord_phone);
        });
        View::composer('layouts.client.blocks.header', function ($view): void {
            $user = request()->user();
            $view->with('clientUnreadNotificationCount', $user?->unreadNotifications()->count() ?? 0);
        });
    }
}
