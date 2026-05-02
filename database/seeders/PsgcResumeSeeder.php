<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PsgcResumeSeeder extends Seeder
{
    private $codeCache = [];
    
    public function run(): void
    {
        $jsonPath = database_path('data/psgc.json');
        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        // Find Region 7
        $regionKey = $this->findRegionKey($data, '07');
        $regionData = $data[$regionKey];
        
        // Find Cebu province
        $cebuProvince = null;
        foreach ($regionData['province_list'] as $provinceName => $provinceData) {
            if (stripos($provinceName, 'CEBU') !== false) {
                $cebuProvince = $provinceData;
                break;
            }
        }

        $province = DB::table('provinces')->where('province_name', 'CEBU')->first();
        $provinceId = $province->province_id;
        $provinceCode = $province->province_code;

        // Get existing cities with their barangay counts
        $existingCities = DB::table('cities')
            ->where('province_id', $provinceId)
            ->get();
            
        $this->command->info("Cities in DB: " . $existingCities->count());
        
        // Check for cities with 0 barangays and cities that need fixing
        $citiesToFix = [];
        $cityIds = [];
        
        foreach ($existingCities as $city) {
            $barangayCount = DB::table('barangays')->where('city_id', $city->city_id)->count();
            $cityIds[$city->city_name] = $city->city_id;
            
            if ($barangayCount == 0) {
                $citiesToFix[] = $city->city_name;
                $this->command->warn("  ⚠️  {$city->city_name} (ID: {$city->city_id}) has 0 barangays - needs fixing");
            }
        }

        // Find completely missing cities
        $missingCities = [];
        foreach ($cebuProvince['municipality_list'] as $cityName => $cityData) {
            if (!isset($cityIds[$cityName])) {
                $missingCities[$cityName] = $cityData;
            }
        }

        // Combine cities to fix (missing + empty)
        $allToAdd = array_merge($missingCities);
        foreach ($citiesToFix as $cityName) {
            if (isset($cebuProvince['municipality_list'][$cityName])) {
                $allToAdd[$cityName] = $cebuProvince['municipality_list'][$cityName];
            }
        }

        if (empty($allToAdd)) {
            $this->command->info('✅ Everything is complete!');
            return;
        }

        $this->command->info('Cities to fix/add: ' . count($allToAdd));
        
        // Get ALL existing barangay codes to avoid duplicates
        $existingCodes = DB::table('barangays')->pluck('barangay_code')->toArray();
        $this->command->info('Existing barangay codes: ' . count($existingCodes));

        DB::beginTransaction();
        
        try {
            foreach ($allToAdd as $cityName => $cityData) {
                $expectedCount = count($cityData['barangay_list'] ?? []);
                
                // Check if city exists but has 0 barangays
                $existingCity = DB::table('cities')
                    ->where('province_id', $provinceId)
                    ->where('city_name', $cityName)
                    ->first();

                if ($existingCity && DB::table('barangays')->where('city_id', $existingCity->city_id)->count() == 0) {
                    // City exists but empty - use existing city_id
                    $cityId = $existingCity->city_id;
                    $this->command->info("  🔧 FIXING: {$cityName} (existing ID: {$cityId}, adding {$expectedCount} barangays)");
                    
                    // Delete orphaned zipcode if exists
                    DB::table('zipcodes')->where('city_id', $cityId)->delete();
                } else {
                    // New city
                    $cityCode = $this->generateUniqueCode($cityName, 'CITY', $provinceCode);
                    $cityId = DB::table('cities')->insertGetId([
                        'city_code' => $cityCode,
                        'city_name' => $cityName,
                        'province_id' => $provinceId,
                    ]);
                    $this->command->info("  ➕ ADDING: {$cityName} (new ID: {$cityId}, {$expectedCount} barangays)");
                }

                // Add zipcode
                DB::table('zipcodes')->insertOrIgnore([
                    'zipcode' => $this->generateZipCode(),
                    'city_id' => $cityId,
                ]);

                // Add barangays
                if (isset($cityData['barangay_list']) && is_array($cityData['barangay_list'])) {
                    $barangayInserts = [];
                    $addedCount = 0;
                    
                    foreach ($cityData['barangay_list'] as $barangayName) {
                        $barangayCode = $this->generateSafeCode($barangayName, 'BRGY', $cityId . '-' . $cityName, $existingCodes);
                        $existingCodes[] = $barangayCode;
                        
                        $barangayInserts[] = [
                            'barangay_code' => $barangayCode,
                            'barangay_name' => $barangayName,
                            'city_id' => $cityId,
                        ];
                        $addedCount++;
                        
                        if (count($barangayInserts) >= 100) {
                            DB::table('barangays')->insert($barangayInserts);
                            $barangayInserts = [];
                        }
                    }
                    
                    if (!empty($barangayInserts)) {
                        DB::table('barangays')->insert($barangayInserts);
                    }
                    
                    $this->command->info("    ✓ Added {$addedCount} barangays");
                }
            }

            DB::commit();
            
            // Final summary
            $totalBarangays = DB::table('barangays')
                ->whereIn('city_id', function($q) use ($provinceId) {
                    $q->select('city_id')->from('cities')->where('province_id', $provinceId);
                })->count();
                
            $this->command->info('');
            $this->command->info('✅ DONE! Total Cebu barangays: ' . $totalBarangays);

        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('ERROR: ' . $e->getMessage());
            throw $e;
        }
    }

    private function generateSafeCode(string $name, string $prefix, string $seed, array $existingCodes): string
    {
        $code = $this->generateUniqueCode($name, $prefix, $seed);
        
        $maxAttempts = 100;
        while (in_array($code, $existingCodes) && $maxAttempts > 0) {
            $suffix = strtoupper(Str::random(5));
            $code = substr($code, 0, min(14, strlen($code) - 6)) . '-' . $suffix;
            $maxAttempts--;
        }
        
        return $code;
    }

    private function findRegionKey(array $data, string $regionCode): ?string
    {
        foreach ([$regionCode, ltrim($regionCode, '0'), 'REGION VII', 'REGION 7'] as $possibleKey) {
            if (isset($data[$possibleKey])) return $possibleKey;
            foreach (array_keys($data) as $key) {
                if (strtoupper($key) === strtoupper($possibleKey)) return $key;
                if (isset($data[$key]['region_name']) && 
                    (stripos($data[$key]['region_name'], 'REGION VII') !== false || 
                     stripos($data[$key]['region_name'], 'CENTRAL VISAYAS') !== false)) {
                    return $key;
                }
            }
        }
        return null;
    }

    private function generateUniqueCode(string $name, string $prefix, string $parentCode): string
    {
        $name = str_replace(['(', ')', '.', ','], '', $name);
        $slug = Str::slug($name, '-');
        $baseCode = strtoupper($prefix . '-' . $slug);
        
        if (strlen($baseCode) <= 20) {
            if (!isset($this->codeCache[$baseCode])) {
                $this->codeCache[$baseCode] = true;
                return $baseCode;
            }
            $counter = 1;
            $newCode = $baseCode . '-' . $counter;
            while (isset($this->codeCache[$newCode]) || strlen($newCode) > 20) {
                $counter++;
                $newCode = $baseCode . '-' . $counter;
            }
            $this->codeCache[$newCode] = true;
            return $newCode;
        }
        
        $words = explode('-', $slug);
        $abbrev = '';
        foreach ($words as $word) {
            if (!empty($word)) $abbrev .= substr($word, 0, 1);
        }
        
        $parentHash = substr(md5($parentCode), 0, 3);
        $code = strtoupper($prefix . '-' . $abbrev . '-' . $parentHash . substr(md5($name), 0, 3));
        $code = substr($code, 0, 20);
        
        if (isset($this->codeCache[$code])) {
            $code = substr($code, 0, 14) . '-' . Str::random(5);
        }
        
        $this->codeCache[$code] = true;
        return $code;
    }

    private function generateZipCode(): string
    {
        return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }
}