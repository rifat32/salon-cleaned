<?php

return [
    "roles_permission" => [
        [
            "role" => "superadmin",
            "permissions" => [
                "global_garage_background_image_create",
                "global_garage_background_image_view",
                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "role_create",
                "role_update",
                "role_view",
                "role_delete",

                "garage_create",
                "garage_update",
                "garage_view",
                "garage_delete",

                "shop_create",
                "shop_update",
                "shop_view",
                "shop_delete",

                "automobile_create",
                "automobile_update",
                "automobile_view",
                "automobile_delete",

                "service_create",
                "service_update",
                "service_view",
                "service_delete",

                "fuel_station_create",
                "fuel_station_update",
                "fuel_station_view",
                "fuel_station_delete",

                "fuel_station_service_create",
                "fuel_station_service_update",
                "fuel_station_service_view",
                "fuel_station_service_delete",

                "template_create",
                "template_update",
                "template_view",
                "template_delete",


                "payment_type_create",
                "payment_type_update",
                "payment_type_view",
                "payment_type_delete",



                "affiliation_create",
                "affiliation_update",
                "affiliation_view",
                "affiliation_delete",

                "questions_create",
                "questions_update",
                "questions_view",
                "questions_delete",

                "review_create",
                "review_update",
                "review_view",
                "review_delete",


                // this permission may remove later
                "garage_affiliation_create",
                "garage_affiliation_update",
                "garage_affiliation_view",
                "garage_affiliation_delete",
                // end this permission may remove later

                "garage_gallery_create",
                "garage_gallery_view",
                "garage_gallery_delete",

                "shop_gallery_create",
                "shop_gallery_view",
                "shop_gallery_delete",

                "fuel_station_gallery_create",
                "fuel_station_gallery_view",
                "fuel_station_gallery_delete",


                "product_category_create",
                "product_category_update",
                "product_category_view",
                "product_category_delete",

                "product_create",
                "product_update",
                "product_view",
                "product_delete",

            ],
        ],
        [
            "role" => "data_collector",
            "permissions" => [
                "garage_create",
                "garage_update",
                "garage_view",
                "garage_delete",

                "shop_create",
                "shop_update",
                "shop_view",
                "shop_delete",


                "fuel_station_create",
                "fuel_station_update",
                "fuel_station_view",
                "fuel_station_delete",

                "fuel_station_service_create",
                "fuel_station_service_update",
                "fuel_station_service_view",
                "fuel_station_service_delete",


                "affiliation_create",
                "affiliation_update",
                "affiliation_view",
                "affiliation_delete",




                "garage_affiliation_create",
                "garage_affiliation_update",
                "garage_affiliation_view",
                "garage_affiliation_delete",

                "garage_gallery_create",
                "garage_gallery_view",
                "garage_gallery_delete",

                "shop_gallery_create",
                "shop_gallery_view",
                "shop_gallery_delete",

                "fuel_station_gallery_create",
                "fuel_station_gallery_view",
                "fuel_station_gallery_delete",

            ],
        ],
        [
            "role" => "garage_owner",
            "permissions" => [


                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "sub_service_price_create",
                "sub_service_price_update",
                "sub_service_price_view",
                "sub_service_price_delete",



                "service_create",
                "service_update",
                "service_view",
                "service_delete",

                "expert_rota_create",
                "expert_rota_update",
                "expert_rota_activate",
                "expert_rota_view",
                "expert_rota_delete",


                "questions_create",
                "questions_update",
                "questions_view",
                "questions_delete",

                "garage_update",
                "garage_view",


                "garage_times_update",
                "garage_times_view",

                "garage_rules_update",
                "garage_rules_view",

                "garage_gallery_create",
                "garage_gallery_view",
                "garage_gallery_delete",

                "garage_services_create",
                "garage_services_update",
                "garage_services_view",
                "garage_services_delete",

                "garage_automobile_create",
                "garage_automobile_update",
                "garage_automobile_view",
                "garage_automobile_delete",


                "booking_create",
                "booking_update",
                "booking_view",
                "booking_delete",

                "job_create",
                "job_update",
                "job_view",
                "job_delete",


                "coupon_create",
                "coupon_update",
                "coupon_view",
                "coupon_delete",


                "affiliation_create",
                "affiliation_update",
                "affiliation_view",
                "affiliation_delete",


                "garage_affiliation_create",
                "garage_affiliation_update",
                "garage_affiliation_view",
                "garage_affiliation_delete",



                "garage_service_price_create",
                "garage_service_price_update",
                "garage_service_price_view",
                "garage_service_price_delete",


                "garage_package_create",
                "garage_package_update",
                "garage_package_view",
                "garage_package_delete",

                "job_bids_create",
                "job_bids_update",
                "job_bids_view",
                "job_bids_delete",

                "product_category_view",


                "global_garage_background_image_view",

                "stripe_setting_update",
                "system_setting_view",



            ],
        ],

        [
            "role" => "business_receptionist",
            "permissions" => [


                "user_create",
                "user_update",
                "user_view",
                "user_delete",

                "sub_service_price_create",
                "sub_service_price_update",
                "sub_service_price_view",
                "sub_service_price_delete",



                "service_create",
                "service_update",
                "service_view",
                "service_delete",

                "expert_rota_create",
                "expert_rota_update",
                "expert_rota_activate",
                "expert_rota_view",
                "expert_rota_delete",


                "questions_create",
                "questions_update",
                "questions_view",
                "questions_delete",

                "garage_update",
                "garage_view",


                "garage_times_update",
                "garage_times_view",

                "garage_rules_update",
                "garage_rules_view",

                "garage_gallery_create",
                "garage_gallery_view",
                "garage_gallery_delete",

                "garage_services_create",
                "garage_services_update",
                "garage_services_view",
                "garage_services_delete",

                "garage_automobile_create",
                "garage_automobile_update",
                "garage_automobile_view",
                "garage_automobile_delete",


                "booking_create",
                "booking_update",
                "booking_view",
                "booking_delete",

                "job_create",
                "job_update",
                "job_view",
                "job_delete",


                "coupon_create",
                "coupon_update",
                "coupon_view",
                "coupon_delete",


                "affiliation_create",
                "affiliation_update",
                "affiliation_view",
                "affiliation_delete",


                "garage_affiliation_create",
                "garage_affiliation_update",
                "garage_affiliation_view",
                "garage_affiliation_delete",



                "garage_service_price_create",
                "garage_service_price_update",
                "garage_service_price_view",
                "garage_service_price_delete",


                "garage_package_create",
                "garage_package_update",
                "garage_package_view",
                "garage_package_delete",

                "job_bids_create",
                "job_bids_update",
                "job_bids_view",
                "job_bids_delete",

                "product_category_view",


                "global_garage_background_image_view",

                "stripe_setting_update",
                "system_setting_view",



            ],
        ],





        [
            "role" => "business_experts",
            "permissions" => [


                "sub_service_price_create",
                "sub_service_price_update",
                "sub_service_price_view",
                "sub_service_price_delete",




                "expert_rota_create",
                "expert_rota_update",
                "expert_rota_activate",
                "expert_rota_view",
                "expert_rota_delete",

                "questions_create",
                "questions_update",
                "questions_view",
                "questions_delete",

                "garage_update",
                "garage_view",


                "garage_times_update",
                "garage_times_view",

                "garage_rules_update",
                "garage_rules_view",

                "garage_gallery_create",
                "garage_gallery_view",
                "garage_gallery_delete",

                "garage_services_create",
                "garage_services_update",
                "garage_services_view",
                "garage_services_delete",

                "garage_automobile_create",
                "garage_automobile_update",
                "garage_automobile_view",
                "garage_automobile_delete",


                "booking_create",
                "booking_update",
                "booking_view",
                "booking_delete",

                "job_create",
                "job_update",
                "job_view",
                "job_delete",


                "coupon_create",
                "coupon_update",
                "coupon_view",
                "coupon_delete",


                "affiliation_create",
                "affiliation_update",
                "affiliation_view",
                "affiliation_delete",


                "garage_affiliation_create",
                "garage_affiliation_update",
                "garage_affiliation_view",
                "garage_affiliation_delete",



                "garage_service_price_create",
                "garage_service_price_update",
                "garage_service_price_view",
                "garage_service_price_delete",


                "garage_package_create",
                "garage_package_update",
                "garage_package_view",
                "garage_package_delete",

                "job_bids_create",
                "job_bids_update",
                "job_bids_view",
                "job_bids_delete",

                "product_category_view",






            ],
        ],

        [
            "role" => "shop_owner",
            "permissions" => [




                "product_category_view",

                "product_create",
                "product_update",
                "product_view",
                "product_delete",

                "shop_gallery_create",
                "shop_gallery_view",
                "shop_gallery_delete",
            ],
        ],
        [
            "role" => "customer",
            "permissions" => [

                "review_create",
                "review_update",
                "review_view",
                "review_delete",


                "questions_view",


            ],
        ],



    ],
    "roles" => [
        "superadmin",
        "data_collector",
        "garage_owner",
        "shop_owner",
        "customer",
        "business_experts",
        "business_receptionist"

    ],
    "permissions" => [


        "sub_service_price_create",
        "sub_service_price_update",
        "sub_service_price_view",
        "sub_service_price_delete",



        "expert_rota_create",
        "expert_rota_update",
        "expert_rota_activate",
        "expert_rota_view",
        "expert_rota_delete",



        "stripe_setting_update",
        "system_setting_view",


        "global_garage_background_image_create",
        "global_garage_background_image_view",


        "user_create",
        "user_update",
        "user_view",
        "user_delete",


        "role_create",
        "role_update",
        "role_view",
        "role_delete",

        "garage_create",
        "garage_update",
        "garage_view",
        "garage_delete",

        "shop_create",
        "shop_update",
        "shop_view",
        "shop_delete",

        "automobile_create",
        "automobile_update",
        "automobile_view",
        "automobile_delete",


        "service_create",
        "service_update",
        "service_view",
        "service_delete",

        "fuel_station_create",
        "fuel_station_update",
        "fuel_station_view",
        "fuel_station_delete",

        "fuel_station_service_create",
        "fuel_station_service_update",
        "fuel_station_service_view",
        "fuel_station_service_delete",


        "template_create",
        "template_update",
        "template_view",
        "template_delete",



        "garage_times_update",
        "garage_times_view",

        "garage_rules_update",
        "garage_rules_view",


        "garage_gallery_create",
        "garage_gallery_view",
        "garage_gallery_delete",

        "shop_gallery_create",
        "shop_gallery_view",
        "shop_gallery_delete",

        "fuel_station_gallery_create",
        "fuel_station_gallery_view",
        "fuel_station_gallery_delete",

        "garage_services_create",
        "garage_services_update",
        "garage_services_view",
        "garage_services_delete",





        "payment_type_create",
        "payment_type_update",
        "payment_type_view",
        "payment_type_delete",



        "booking_create",
        "booking_update",
        "booking_view",
        "booking_delete",



        "job_create",
        "job_update",
        "job_view",
        "job_delete",



        "coupon_create",
        "coupon_update",
        "coupon_view",
        "coupon_delete",


        "affiliation_create",
        "affiliation_update",
        "affiliation_view",
        "affiliation_delete",

        "garage_affiliation_create",
        "garage_affiliation_update",
        "garage_affiliation_view",
        "garage_affiliation_delete",



        "garage_service_price_create",
        "garage_service_price_update",
        "garage_service_price_view",
        "garage_service_price_delete",

        "garage_package_create",
        "garage_package_update",
        "garage_package_view",
        "garage_package_delete",


        "job_bids_create",
        "job_bids_update",
        "job_bids_view",
        "job_bids_delete",

        "questions_create",
        "questions_update",
        "questions_view",
        "questions_delete",

        "review_create",
        "review_update",
        "review_view",
        "review_delete",

        "garage_automobile_create",
        "garage_automobile_update",
        "garage_automobile_view",
        "garage_automobile_delete",



        "product_category_create",
        "product_category_update",
        "product_category_view",
        "product_category_delete",

        "product_create",
        "product_update",
        "product_view",
        "product_delete",

    ],
    "unchangeable_roles" => [
        "superadmin"
    ],
    "unchangeable_permissions" => [
        "garage_update",
        "garage_view",
    ],
    "user_image_location" => "user_image",
    "garage_gallery_location" => "garage_gallery",

    "shop_gallery_location" => "shop_gallery",
    "fuel_station_gallery_location" => "fuel_station_gallery",

    "affiliation_logo_location" => "affiliation_logo",

    "pre_booking_file_location" => "pre_booking_files",





    "garage_background_image_location" => "garage_background_image",
    "garage_background_image_location_full" => "garage_background_image/garage_background_image.jpeg",

    "temporary_files_location" => "temporary_files",
];
