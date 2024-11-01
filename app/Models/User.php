<?php

namespace App\Models;


use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles,SoftDeletes;
    public $blocked_slots = [];
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guard_name = "api";
    protected $fillable = [
        'weekly_minimum_days',
        'weekly_minimum_hours',
        'first_Name',
        'last_Name',
        "business_id",
        'phone',
        'image',
        'address_line_1',
        'address_line_2',
        'country',
        'city',
        'postcode',
        "lat",
        "long",
        'email',
        'password',
        "created_by",
        'is_active'
    ];
    public function translation(){
        return $this->hasMany(UserTranslation::class,'user_id', 'id');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        "site_redirect_token",
        "email_verify_token",
        "email_verify_token_expires",
        "resetPasswordToken",
        "resetPasswordExpires"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];


    public function rota(){
        return $this->hasMany(ExpertRota::class,'expert_id', 'id');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'customer_id', 'id');
    }
    public function lastBooking()
    {
        return $this->bookings()->latest()->limit(1);
    }



    public function services()
    {
        return $this->bookings()
            ->join('booking_sub_services', 'bookings.id', '=', 'booking_sub_services.booking_id')
            ->join('sub_services', 'booking_sub_services.sub_service_id', '=', 'sub_services.id')
            ->where('bookings.garage_id', auth()->user()->business_id)
            ->select(
                'bookings.customer_id',
                'sub_services.id',
                'sub_services.name',
                DB::raw('COUNT(sub_services.id) as selection_count')  // Count for each service
            )
            ->groupBy('sub_services.id', 'bookings.customer_id', 'sub_services.name');
    }

    public function expert_bookings()
    {
        return $this->hasMany(Booking::class, 'expert_id', 'id');
    }

}
