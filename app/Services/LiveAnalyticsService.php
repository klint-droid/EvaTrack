<?php

namespace App\Services;

use Carbon\Carbon;

use App\Models\EvacuationRecord;
use App\Models\EvacuatedMember;

class LiveAnalyticsService
{
    /*
    |--------------------------------------------------------------------------
    | OVERALL EVENT ANALYTICS
    |--------------------------------------------------------------------------
    */

    public function getEventAnalytics($eventId)
    {
        $evacuationIds = EvacuationRecord::where(
            'event_id',
            $eventId
        )->pluck('evacuation_id');

        $members = EvacuatedMember::with([

            'member.gender',
            'member.vulnerableGroupDetails',
            'evacuationRecord',

        ])
        ->whereIn(
            'evacuation_id',
            $evacuationIds
        )
        ->get();

        return $this->buildAnalytics($members);
    }

    /*
    |--------------------------------------------------------------------------
    | PER CENTER ANALYTICS
    |--------------------------------------------------------------------------
    */

    public function getCenterAnalytics(
        $eventId,
        $centerId
    ) {

        $evacuationIds = EvacuationRecord::where(
            'event_id',
            $eventId
        )
        ->where(
            'center_id',
            $centerId
        )
        ->pluck('evacuation_id');

        $members = EvacuatedMember::with([

            'member.gender',
            'member.vulnerableGroupDetails',
            'evacuationRecord',

        ])
        ->whereIn(
            'evacuation_id',
            $evacuationIds
        )
        ->get();

        return $this->buildAnalytics($members);
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD ANALYTICS
    |--------------------------------------------------------------------------
    */

    protected function buildAnalytics($members)
    {
        return [

            /*
            |--------------------------------------------------------------------------
            | Totals
            |--------------------------------------------------------------------------
            */

            'total_population' =>

                $members->count(),

            'total_household' =>

                $members
                    ->pluck(
                        'evacuationRecord.household_id'
                    )
                    ->unique()
                    ->count(),

            /*
            |--------------------------------------------------------------------------
            | Gender Counts
            |--------------------------------------------------------------------------
            */

            'male_count' =>

                $members->filter(function ($m) {

                    return optional(
                        optional($m->member)->gender
                    )->gender_key === 'male';

                })->count(),

            'female_count' =>

                $members->filter(function ($m) {

                    return optional(
                        optional($m->member)->gender
                    )->gender_key === 'female';

                })->count(),

            /*
            |--------------------------------------------------------------------------
            | Age Groups
            |--------------------------------------------------------------------------
            */

            'children_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age <= 12;

                })->count(),

            'adult_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age >= 18
                        && $age <= 59;

                })->count(),

            'elderly_count' =>

                $members->filter(function ($m) {

                    $birthDate =
                        optional($m->member)->birth_date;

                    if (!$birthDate) {
                        return false;
                    }

                    $age = Carbon::parse(
                        $birthDate
                    )->age;

                    return $age >= 60;

                })->count(),

            /*
            |--------------------------------------------------------------------------
            | Vulnerable Groups
            |--------------------------------------------------------------------------
            */

            'pwd_count' =>

                $members->filter(function ($m) {

                    return optional($m->member)
                        ->vulnerableGroupDetails
                        ->contains(
                            'vulnerable_group_key',
                            'pwd'
                        );

                })->count(),

            'pregnant_count' =>

                $members->filter(function ($m) {

                    return optional($m->member)
                        ->vulnerableGroupDetails
                        ->contains(
                            'vulnerable_group_key',
                            'pregnant'
                        );

                })->count(),
        ];
    }
}