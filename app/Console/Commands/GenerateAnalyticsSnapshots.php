<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Analytic;
use App\Models\DisasterEvent;
use App\Models\EvacuationCenter;

use App\Models\AnalyticsJobLog;
use App\Models\AnalyticsJobStatus;

use App\Services\LiveAnalyticsService;

class GenerateAnalyticsSnapshots extends Command
{
    protected $signature = 'analytics:generate';

    protected $description =
        'Generate analytics snapshots';

    public function handle(
        LiveAnalyticsService $analytics
    ) {

        $processingStatus =
            AnalyticsJobStatus::where(
                'status_key',
                'processing'
            )->first();

        $completedStatus =
            AnalyticsJobStatus::where(
                'status_key',
                'completed'
            )->first();

        $failedStatus =
            AnalyticsJobStatus::where(
                'status_key',
                'failed'
            )->first();

        $job = AnalyticsJobLog::create([

            'status_id' => $processingStatus->status_id,

            'started_at' => now(),

            'message' => 'Generating snapshots...',
        ]);

        try {

            $events = DisasterEvent::all();

            foreach ($events as $event) {

                $eventData =
                    $analytics->getEventAnalytics(
                        $event->event_id
                    );

                Analytic::create([

                    'evacuation_event_id' =>
                        $event->event_id,

                    'evacuation_center_id' =>
                        null,

                    'snapshot_type' => 'hourly',

                    ...$eventData,

                    'recorded_at' => now(),
                ]);

                $centers = EvacuationCenter::where(
                    'current_event_id',
                    $event->event_id
                )->get();

                foreach ($centers as $center) {

                    $centerData =
                        $analytics->getCenterAnalytics(

                            $event->event_id,

                            $center->evacuation_center_id
                        );

                    Analytic::create([

                        'evacuation_event_id' =>
                            $event->event_id,

                        'evacuation_center_id' =>
                            $center->evacuation_center_id,

                        'snapshot_type' => 'hourly',

                        ...$centerData,

                        'recorded_at' => now(),
                    ]);
                }
            }

            $job->update([

                'status_id' =>
                    $completedStatus->status_id,

                'finished_at' => now(),

                'message' =>
                    'Analytics snapshots generated successfully.',
            ]);

            $this->info(
                'Analytics snapshots generated.'
            );

        } catch (\Exception $e) {

            $job->update([

                'status_id' =>
                    $failedStatus->status_id,

                'finished_at' => now(),

                'message' => $e->getMessage(),
            ]);

            $this->error($e->getMessage());
        }
    }
}