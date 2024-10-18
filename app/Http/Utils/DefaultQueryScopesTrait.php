<?php

namespace App\Http\Utils;


use Exception;

use Illuminate\Database\Eloquent\Builder;

trait DefaultQueryScopesTrait
{
    public function getIsActiveAttribute($value)
    {
        $is_active = $value;
        $user = auth()->user();

        if(!empty($user)) {
            if (empty($user->business_id)) {
                if (empty($this->business_id) && $this->is_default == 1) {
                    if (!$user->hasRole("superadmin")) {
                        $disabled = $this->disabled()->where([
                            "created_by" => $user->id
                        ])
                            ->first();
                        if ($disabled) {
                            $is_active = 0;
                        }
                    }
                }
            } else {
                if (empty($this->business_id)) {
                    $disabled = $this->disabled()->where([
                        "business_id" => $user->business_id
                    ])
                        ->first();
                    if ($disabled) {
                        $is_active = 0;
                    }
                }
            }
        }





        return $is_active;
    }



    public function getIsDefaultAttribute($value)
    {

        $is_default = $value;
        $user = auth()->user();

        if (!empty($user)) {

            if (!empty($user->business_id)) {
                if (empty($this->business_id) || $user->business_id !=  $this->business_id) {
                    $is_default = 1;
                }
            } else if ($user->hasRole("superadmin")) {
                $is_default = 0;
            }
        }

        return $is_default;
    }

    public function scopeForSuperAdmin(Builder $query, $table)
    {
        return $query->where($table . '.business_id', NULL)
                     ->where($table . '.is_default', 1)
                     ->when(request()->filled("is_active"), function ($query) use ($table) {
                         return $query->where($table . '.is_active', request()->boolean('is_active'));
                     });
    }


    public function scopeForNonSuperAdmin(Builder $query, $table, $disabled_table, $created_by)
    {
        return $query->where(function ($query) use ($table, $created_by, $disabled_table) {
            $query->where($table . '.business_id', NULL)
                  ->where($table . '.is_default', 1)
                  ->where($table . '.is_active', 1)
                  ->when(request()->has('is_active'), function ($query) use ($created_by, $disabled_table) {
                      if (request()->boolean('is_active')) {
                          return $query->whereDoesntHave('disabled', function ($q) use ($created_by, $disabled_table) {
                              $q->whereIn($disabled_table . '.created_by', [$created_by]);
                          });
                      }
                  })
                  ->orWhere(function ($query) use ($table, $created_by) {
                      $query->where($table . '.business_id', NULL)
                            ->where($table . '.is_default', 0)
                            ->where($table . '.created_by', $created_by)
                            ->when(request()->has('is_active'), function ($query) use ($table) {
                                return $query->where($table . '.is_active', request()->boolean('is_active'));
                            });
                  });
        });
    }


    public function scopeForBusiness(Builder $query, $table,$disabled_table, $created_by, $activeData = false)
    {
        return $query->where(function ($query) use ($table, $created_by, $disabled_table, $activeData) {
            $query->when(request()->boolean('include_defaults'), function ($query) use ($table, $created_by, $disabled_table, $activeData) {
                return $query->where(function ($query) use ($table, $created_by, $disabled_table, $activeData) {
                    $query->where($table . '.business_id', NULL)
                          ->where($table . '.is_default', 1)
                          ->where($table . '.is_active', 1)
                          ->whereDoesntHave('disabled', function ($q) use ($created_by, $disabled_table) {
                              $q->whereIn($disabled_table . '.created_by', [$created_by]);
                          })
                          ->when($activeData || request()->boolean('is_active'), function ($query) use ($disabled_table) {

                                  return $query->whereDoesntHave('disabled', function ($q) use ($disabled_table) {
                                      $q->whereIn($disabled_table . '.business_id', [auth()->user()->business_id]);
                                  });

                          })
                          ->orWhere(function ($query) use ($table,$disabled_table, $created_by, $activeData) {
                              $query->where($table . '.business_id', NULL)
                                    ->where($table . '.is_default', 0)
                                    ->where($table . '.created_by', $created_by)
                                    ->where($table . '.is_active', 1)
                                    ->when($activeData || request()->boolean('is_active'), function ($query) use ($disabled_table) {

                                            return $query->whereDoesntHave('disabled', function ($q) use ($disabled_table) {
                                                $q->whereIn($disabled_table . '.business_id', [auth()->user()->business_id]);
                                            });

                                    });
                          });
                });
            })
            ->orWhere(function ($query) use ($table, $activeData) {
                $query->where($table . '.business_id', auth()->user()->business_id)
                      ->where($table . '.is_default', 0)
                      ->when($activeData || request()->boolean('is_active'), function ($query) use ($table) {
                          return $query->where($table . '.is_active', 1);
                      });
            });
        });
    }






}
