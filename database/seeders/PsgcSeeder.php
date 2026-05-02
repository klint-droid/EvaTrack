<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class PsgcSeeder extends Seeder
{
    private $codeCache = [];
    
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = database_path('data/psgc.json');
        
        if (!File::exists($jsonPath)) {
            $this->command->error('PSGC JSON file not found at: ' . $jsonPath);
            return;
        }

        $jsonContent = File::get($jsonPath);
        $data = json_decode($jsonContent, true);

        if (!$data) {
            $this->command->error('Invalid JSON file or empty content');
            return;
        }

        $this->command->info('Starting PSGC data seeding...');
        
        // ====== REGION 7 + CEBU ONLY FILTER ======
        $regionCode = '07'; // Central Visayas
        $targetProvince = 'CEBU'; // Cebu province only
        $regionKey = $this->findRegionKey($data, $regionCode);
        
        if (!$regionKey) {
            $this->command->error("Region 7 not found in JSON data. Available regions: " . implode(', ', array_keys($data)));
            return;
        }
        
        $regionData = $data[$regionKey];
        
        // Filter provinces to Cebu only
        $filteredProvinces = [];
        if (isset($regionData['province_list'])) {
            foreach ($regionData['province_list'] as $provinceName => $provinceData) {
                if (stripos($provinceName, $targetProvince) !== false) {
                    $filteredProvinces[$provinceName] = $provinceData;
                    $this->command->info("✅ Found: {$provinceName}");
                }
            }
        }
        
        if (empty($filteredProvinces)) {
            $this->command->error("Province '{$targetProvince}' not found in Region 7.");
            $this->command->info("Available provinces: " . implode(', ', array_keys($regionData['province_list'])));
            return;
        }
        
        $regionData['province_list'] = $filteredProvinces;
        $data = [$regionKey => $regionData]; // Replace with filtered data
        // ====== END FILTER ======
        
        $this->command->info('Regions found: ' . count($data));
        $this->command->info('⚠️  PROCESSING REGION 7 - CEBU PROVINCE ONLY: ' . $regionData['region_name']);
        
        DB::beginTransaction();
        
        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Clear existing data
            $this->command->info('Clearing existing geographic data...');
            DB::table('puroks')->truncate();
            DB::table('sitios')->truncate();
            DB::table('barangays')->truncate();
            DB::table('zipcodes')->truncate();
            DB::table('cities')->truncate();
            DB::table('provinces')->truncate();
            DB::table('regions')->truncate();
            
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
            
            $totalBarangays = 0;
            $totalCities = 0;
            $totalProvinces = 0;
            $regionsCount = 0;
            
            // Process only Region 7 - Cebu
            foreach ($data as $code => $regData) {
                $regionName = $regData['region_name'];
                $this->command->info("Processing Region: {$regionName} ({$code})");
                
                // Create Region
                $regionId = DB::table('regions')->insertGetId([
                    'region_code' => $code,
                    'region_name' => $regionName,
                ]);
                $regionsCount++;
                
                if (!isset($regData['province_list'])) {
                    $this->command->warn("  No province_list for region {$regionName}");
                    continue;
                }
                
                // Process provinces (only Cebu)
                foreach ($regData['province_list'] as $provinceName => $provinceData) {
                    $this->command->info("  Province: {$provinceName}");
                    
                    // Create Province - use region code for uniqueness
                    $provinceCode = $this->generateUniqueCode($provinceName, 'PROV', $code);
                    $provinceId = DB::table('provinces')->insertGetId([
                        'province_code' => $provinceCode,
                        'province_name' => $provinceName,
                        'region_id' => $regionId,
                    ]);
                    $totalProvinces++;
                    
                    if (!isset($provinceData['municipality_list'])) {
                        $this->command->warn("    No municipality_list for {$provinceName}");
                        continue;
                    }
                    
                    // Process municipalities/cities
                    foreach ($provinceData['municipality_list'] as $cityName => $cityData) {
                        $barangayCount = isset($cityData['barangay_list']) ? count($cityData['barangay_list']) : 0;
                        $this->command->info("    {$cityName} ({$barangayCount} barangays)");
                        
                        // Create City/Municipality - use province code for uniqueness
                        $cityCode = $this->generateUniqueCode($cityName, 'CITY', $provinceCode);
                        $cityId = DB::table('cities')->insertGetId([
                            'city_code' => $cityCode,
                            'city_name' => $cityName,
                            'province_id' => $provinceId,
                        ]);
                        $totalCities++;
                        
                        // Create zip code for this city
                        DB::table('zipcodes')->insert([
                            'zipcode' => $this->generateZipCode(),
                            'city_id' => $cityId,
                        ]);
                        
                        // Process barangays
                        if (isset($cityData['barangay_list']) && is_array($cityData['barangay_list'])) {
                            $barangayInserts = [];
                            
                            foreach ($cityData['barangay_list'] as $barangayName) {
                                // Use city code to make barangay code unique
                                $barangayCode = $this->generateUniqueCode($barangayName, 'BRGY', $cityCode);
                                
                                $barangayInserts[] = [
                                    'barangay_code' => $barangayCode,
                                    'barangay_name' => $barangayName,
                                    'city_id' => $cityId,
                                ];
                                
                                // Batch insert every 500 records
                                if (count($barangayInserts) >= 500) {
                                    DB::table('barangays')->insert($barangayInserts);
                                    $totalBarangays += count($barangayInserts);
                                    $barangayInserts = [];
                                }
                            }
                            
                            // Insert remaining
                            if (!empty($barangayInserts)) {
                                DB::table('barangays')->insert($barangayInserts);
                                $totalBarangays += count($barangayInserts);
                            }
                        }
                    }
                }
            }
            
            DB::commit();
            
            // Summary
            $this->command->info('');
            $this->command->info('=========================================');
            $this->command->info('REGION 7 - CEBU PROVINCE SEEDING COMPLETED');
            $this->command->info('=========================================');
            $this->command->info("Regions:    {$regionsCount}");
            $this->command->info("Provinces:  {$totalProvinces}");
            $this->command->info("Cities:     {$totalCities}");
            $this->command->info("Barangays:  {$totalBarangays}");
            $this->command->info('');
            
        } catch (\Exception $e) {
            DB::rollBack();
            $this->command->error('ERROR: ' . $e->getMessage());
            $this->command->error('File: ' . $e->getFile() . ':' . $e->getLine());
            throw $e;
        }
    }
    
    /**
     * Find the correct key for Region 7 in the JSON data
     */
    private function findRegionKey(array $data, string $regionCode): ?string
    {
        // Check if key exists directly (e.g., "07", "7", "VII")
        foreach ([$regionCode, ltrim($regionCode, '0'), 'REGION VII', 'REGION 7'] as $possibleKey) {
            if (isset($data[$possibleKey])) {
                return $possibleKey;
            }
            // Case-insensitive search
            foreach (array_keys($data) as $key) {
                if (strtoupper($key) === strtoupper($possibleKey)) {
                    return $key;
                }
                // Check if region_name contains "REGION VII" or "07"
                if (isset($data[$key]['region_name']) && 
                    (stripos($data[$key]['region_name'], 'REGION VII') !== false || 
                     stripos($data[$key]['region_name'], 'REGION 7') !== false ||
                     stripos($data[$key]['region_name'], 'CENTRAL VISAYAS') !== false)) {
                    return $key;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Generate a unique code by incorporating parent context
     */
    private function generateUniqueCode(string $name, string $prefix, string $parentCode): string
    {
        // Clean the name
        $name = str_replace(['(', ')', '.', ','], '', $name);
        $slug = Str::slug($name, '-');
        
        // Create base code
        $baseCode = strtoupper($prefix . '-' . $slug);
        
        // If base code is already short enough, add a short uniqueness suffix
        if (strlen($baseCode) <= 20) {
            // Check if this code was already generated
            if (!isset($this->codeCache[$baseCode])) {
                $this->codeCache[$baseCode] = true;
                return $baseCode;
            }
            // If duplicate, add incremental number
            $counter = 1;
            $newCode = $baseCode . '-' . $counter;
            while (isset($this->codeCache[$newCode]) || strlen($newCode) > 20) {
                $counter++;
                $newCode = $baseCode . '-' . $counter;
            }
            $this->codeCache[$newCode] = true;
            return $newCode;
        }
        
        // For long names, create abbreviation with hash
        $words = explode('-', $slug);
        $abbrev = '';
        foreach ($words as $word) {
            if (!empty($word)) {
                $abbrev .= substr($word, 0, 1);
            }
        }
        
        // Use parent code influence for uniqueness
        $parentHash = substr(md5($parentCode), 0, 3);
        $code = strtoupper($prefix . '-' . $abbrev . '-' . $parentHash . substr(md5($name), 0, 3));
        $code = substr($code, 0, 20);
        
        // Ensure uniqueness
        if (isset($this->codeCache[$code])) {
            $code = substr($code, 0, 17) . '-' . rand(100, 999);
            while (isset($this->codeCache[$code])) {
                $code = substr($code, 0, 17) . '-' . rand(100, 999);
            }
        }
        
        $this->codeCache[$code] = true;
        return $code;
    }
    
    /**
     * Generate a sample zip code
     */
    private function generateZipCode(): string
    {
        return str_pad(rand(1000, 9999), 4, '0', STR_PAD_LEFT);
    }
}