<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BusinessSetting extends Model
{
    use HasFactory;


    protected $fillable = [
        "allow_receptionist_user_discount",
        "discount_percentage_limit",

        "slot_duration",
        'STRIPE_KEY',
        "STRIPE_SECRET",
        "business_id",
        'stripe_enabled',
        'is_expert_price',
        'is_auto_booking_approve',
        'allow_pay_after_service',
        'allow_expert_booking',
        'allow_expert_self_busy',
        'allow_expert_booking_cancel',
        'allow_expert_take_payment',
        'allow_expert_view_revenue',
        'allow_expert_view_customer_details',
        'allow_receptionist_add_question',
        'default_currency',
        'default_language',
        'vat_enabled',
        'vat_percentage',
        'vat_number'
    ];





    protected $hidden = [
        'STRIPE_KEY',
        "STRIPE_SECRET"
    ];













}
