<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateViewAffiliatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("
            CREATE OR REPLACE VIEW public.view_affiliates
            AS 
            SELECT a.id AS id_affiliate,
                a.identity_card AS identity_card_affiliate,
                ca.first_shortened AS city_exp_first_shortened_affiliate,
                c.name AS city_birth_affiliate,
                a.registration AS registration_affiliate,
                a.first_name AS first_name_affiliate,
                a.second_name AS second_name_affiliate,
                a.last_name AS last_name_affiliate,
                a.mothers_last_name AS mothers_last_name_affiliate,
                a.surname_husband AS surname_husband_affiliate,
                concat_full_name(a.first_name, a.second_name, a.last_name, a.mothers_last_name, a.surname_husband) AS full_name_affiliate,
                d.name AS name_degree,
                d.shortened AS shortened_degree,
                afs.name AS name_affiliate_state,
                ast.name AS state_type_affiliate,
                u.district AS district_unit,
                u.name AS name_unit,
                u.shortened AS shortened_unit,
                cat.name AS name_category,
                p.type AS type_pension_entity,
                p.name AS name_pension_entity
            FROM affiliates a
                LEFT JOIN degrees d ON d.id = a.degree_id
                LEFT JOIN affiliate_states afs ON afs.id = a.affiliate_state_id
                LEFT JOIN affiliate_state_types ast ON afs.affiliate_state_type_id = ast.id
                LEFT JOIN cities ca ON a.city_identity_card_id = ca.id
                LEFT JOIN cities c ON a.city_birth_id = c.id
                LEFT JOIN units u ON u.id = a.unit_id 
                LEFT JOIN categories cat ON cat.id = a.category_id   
                LEFT JOIN pension_entities p ON p.id = a.pension_entity_id;
        ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP VIEW view_affiliates");
    }
};
