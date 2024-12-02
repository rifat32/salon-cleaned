<?php

namespace Database\Seeders;

use App\Models\AutomobileCategory;

use Illuminate\Database\Seeder;
use File;
use Illuminate\Support\Facades\DB;

class AutomobileCarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

     AutomobileCategory::create([
            "name" => "car"
        ]);


    }
}
