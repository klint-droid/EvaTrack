<?php

namespace App\Infrastructure\Providers;

use Illuminate\Support\ServiceProvider;
use App\Domains\Households\Repositories\HouseholdRepositoryInterface;
use App\Domains\Households\Repositories\EloquentHouseholdRepository;
use App\Domains\Evacuations\Repositories\EvacuationRepositoryInterface;
use App\Domains\Evacuations\Repositories\EloquentEvacuationRepository;
use App\Domains\EvacuationCenters\Repositories\EvacuationCenterRepositoryInterface;
use App\Domains\EvacuationCenters\Repositories\EloquentEvacuationCenterRepository;
use App\Domains\EvacuationEvents\Repositories\EvacuationEventRepositoryInterface;
use App\Domains\EvacuationEvents\Repositories\EloquentEvacuationEventRepository;
use App\Domains\AccommodationUnits\Repositories\AccommodationUnitRepositoryInterface;
use App\Domains\AccommodationUnits\Repositories\EloquentAccommodationUnitRepository;
use App\Domains\ResourceRequests\Repositories\ResourceRequestRepositoryInterface;
use App\Domains\ResourceRequests\Repositories\EloquentResourceRequestRepository;
use App\Domains\CenterIssueReports\Repositories\CenterIssueReportRepositoryInterface;
use App\Domains\CenterIssueReports\Repositories\EloquentCenterIssueReportRepository;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(HouseholdRepositoryInterface::class, EloquentHouseholdRepository::class);
        $this->app->bind(EvacuationRepositoryInterface::class, EloquentEvacuationRepository::class);
        $this->app->bind(EvacuationCenterRepositoryInterface::class, EloquentEvacuationCenterRepository::class);
        $this->app->bind(EvacuationEventRepositoryInterface::class, EloquentEvacuationEventRepository::class);
        $this->app->bind(AccommodationUnitRepositoryInterface::class, EloquentAccommodationUnitRepository::class);
        $this->app->bind(ResourceRequestRepositoryInterface::class, EloquentResourceRequestRepository::class);
        $this->app->bind(CenterIssueReportRepositoryInterface::class, EloquentCenterIssueReportRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
