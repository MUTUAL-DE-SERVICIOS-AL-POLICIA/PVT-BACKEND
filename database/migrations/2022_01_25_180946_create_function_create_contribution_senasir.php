<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionCreateContributionSenasir extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE FUNCTION public.create_contribution_senasir(affiliate bigint, year_copy date, user_reg integer, payroll_senasir_id integer)
        RETURNS varchar
        as $$
        declare

           type_acction varchar;
           id_contribution_passive int;
        begin
            --*******************************************************************************
            --Funcion par crear un nuevo registro en la tabla contribution_passive--
            --*******************************************************************************
            id_contribution_passive:= serch_affiliate_period_senasir(affiliate,year_copy);
             IF id_contribution_passive = 0 then
                   type_acction:= 'created';

               -- Creacion de un nuevo registro de la contribucion con estado Pagado = 2
                   INSERT INTO public.contribution_passives(user_id, affiliate_id, month_year, quotable, rent_pension, dignity_rent, interest, total, created_at,updated_at,affiliate_rent_class,contribution_state_id, contributionable_type, contributionable_id)
                   SELECT user_reg as user_id, pvs.affiliate_id,year_copy as month_year, (pvs.total_won-pvs.dignity_rent) as quotable, pvs.total_won as rent_pension,pvs.dignity_rent as dignity_rent, 0 as interest, pvs.discount_contribution_muserpol as total,(select current_timestamp as created_at),(select current_timestamp as updated_at), CASE rent_class
                        when 'VIUDEDAD' then 'VIUDEDAD'
                        else 'VEJEZ'
                        end
                    as affiliate_rent_class,2 as contribution_state_id,'payroll_senasirs'::character varying as contributionable_type, payroll_senasir_id as contributionable_id from payroll_senasirs pvs
                    WHERE id=payroll_senasir_id;
            END IF;
            RETURN type_acction ;
        end;
        $$ LANGUAGE 'plpgsql'
       ");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP FUNCTION create_contribution_senasir");
    }
}
