<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CreateNotificationTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string("name")->nullable();
            $table->string("type");
            $table->text("template");
            $table->text("link");
            $table->boolean("is_active");
            $table->timestamps();
        });

        DB::table('notification_templates')->insert(
            array(
                [

                    'type' => 'bid_created_by_garage_owner',
                    "template"=> json_encode("A garage named [garage_name] posted a bid. its owner is [garage_owner_name]"),
                    "link"=> json_encode("/[garage_id]/[bid_id]"),
                    "is_active" => 1
                ],
                [

                    'type' => 'bid_updated_by_garage_owner',
                    "template"=> json_encode("A garage named [garage_name] updated their bid. its owner is"),
                    "link"=> json_encode("/[garage_id]/[bid_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'bid_accepted_by_client',
                    "template"=> json_encode("A client named [customer_name] accepted your bid."),
                    "link"=> json_encode("/[customer_id]/[pre_booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'bid_rejected_by_client',
                    "template"=> json_encode("A client named [customer_name] rejected your bid."),
                    "link"=> json_encode("/[customer_id]/[pre_booking_id]"),
                    "is_active" => 1
                ],


                [

                    'type' => 'booking_created_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking is updated by the garage named [garage_owner_name],[garage_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],
                [

                    'type' => 'booking_updated_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking is updated by the garage named[garage_owner_name],[garage_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_status_changed_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking status updated by the garage named[garage_owner_name],[garage_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_confirmed_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking confirmed by the garage named [garage_name]."),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],
                [

                    'type' => 'booking_deleted_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking deleted by the garage named[garage_owner_name],[garage_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_rejected_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! your booking rejected by the garage named [garage_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],


                [

                    'type' => 'booking_created_by_client',
                    "template"=> json_encode("A new booking is requested by [customer_name] for [Date] [Time]."),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],
                // @@@ @@@
                // hello [garage_owner_name]!  booking created by [customer_name]
                [

                    'type' => 'booking_updated_by_client',
                    "template"=> json_encode("hello [garage_owner_name]!  booking updated by [customer_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_deleted_by_client',
                    "template"=> json_encode("hello [garage_owner_name]!  booking deleted by [customer_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_accepted_by_client',
                    "template"=> json_encode("Congratulations, [customer_name] have confirmed the booking [Date] and [Time]."),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'booking_rejected_by_client',
                    "template"=> json_encode("hello [garage_owner_name]!  booking rejected by [customer_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'job_created_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]!  job created  by [garage_owner_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'job_updated_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]!  job updated  by [garage_owner_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],

                [

                    'type' => 'job_status_changed_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]!  job status changed  by [garage_owner_name] "),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],
                [

                    'type' => 'job_deleted_by_garage_owner',
                    "template"=> json_encode("hello [customer_name]! job deleted by [garage_owner_name]"),
                    "link"=> json_encode("/[customer_id]/[booking_id]"),
                    "is_active" => 1
                ],



            )
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('notification_templates');
    }
}
