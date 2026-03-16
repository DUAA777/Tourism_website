<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\RestaurantsImport;
use App\Imports\HotelsImport;
use App\Models\Hotel;
use App\Models\Restaurant;

    class ImportExcelData extends Command
    {
        protected $signature = 'app:import-excel-data';
        protected $description = 'Import restaurants and hotels from Excel files';

    public function handle()
    {
        $restaurantsPath = storage_path('app/mata3eem.xlsx');
        $hotelsPath = storage_path('app/hotels.xlsx');

        Hotel::truncate();
        Restaurant::truncate(); // إذا بدك كمان للمطاعم

        Excel::import(new RestaurantsImport, $restaurantsPath);
        $this->info('Restaurants imported ✅');

        Excel::import(new HotelsImport, $hotelsPath);
        $this->info('Hotels imported ✅');

        $this->info('Import Done ✅');

        return self::SUCCESS;
    }
}