<?php

namespace App\Http\Utils;

use App\Models\AutomobileCategory;
use App\Models\AutomobileMake;
use App\Models\AutomobileModel;
use App\Models\Garage;
use App\Models\GarageAutomobileMake;
use App\Models\GarageAutomobileModel;
use App\Models\GarageService;
use App\Models\GarageSubService;
use App\Models\Service;
use App\Models\Shop;
use App\Models\SubService;
use Exception;

trait ShopUtil
{


    public function shopOwnerCheck($shop_id) {

        $queryArray = [
            "id" => $shop_id
        ];

        if(!auth()->user()->hasRole("superadmin") && !auth()->user()->hasRole("data_collector") ) {
            $queryArray["owner_id"] =  auth()->user()->id;
        } else if(!auth()->user()->hasRole("superadmin")) {
            $queryArray["created_by"] =  auth()->user()->id;
        }


        $shop_id = $shop_id;
        $shop = Shop::where($queryArray)
        ->first();
        if (!$shop) {
            return false;
        }
        return $shop;
    }

}
