<?php

namespace App\Services;
use Illuminate\Support\Facades\Http;
use App\Models\Household;
class ExternalApiService
{
    /**
     * Create a new class instance.
     */
    public function getHouseholds()
    {
        $response = Http::withHeaders([
            'x-api-key' => env('OTHER_SYSTEM_API_KEY')
        ])->get(env('OTHER_SYSTEM_BASE_URL') . '/api/households');

        if($response->failed()){
            throw new \Exception("Failed to fetch data from External API");
        }

        return $response->json();
    }

    public function syncHouseholds(){
        $data = $this->getHouseholds();

        if(!$data || !is_array($data)){
            return response()->json(['message' => 'Failed to fetch data from External API'], 500);
        }

        foreach($data as $h){
            Household::updateOrCreate(
                ['household_id' => $h['household_id']],
                [
                    'household_name' => $h['household_name'] ?? 'Uknown', 
                    'phone_number' => $h['phone_number'] ?? null,
                    'last_synced_at' => now(),
                ]
            );
        }
    }
}
