<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement("CREATE OR REPLACE FUNCTION public.get_retirement_fund_amount(date_period date,percentage numeric, total numeric, base_wage numeric )
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
                    declare
                        cr_retirement_fund numeric:=0;
                        retirement_fund_into numeric:=0;
                        cr_mortuary_quota numeric:=0;
       
                    begin
                    --*********************************************--
                    --Funcion para obtener monto de fondo de retiro--
                    --*********************************************--
                         select retirement_fund into cr_retirement_fund from contribution_rates cr where month_year = date_period limit 1;
                        select mortuary_quota into cr_mortuary_quota from contribution_rates cr where month_year = date_period limit 1;
       
                         if (percentage = round(cr_retirement_fund+cr_mortuary_quota,2)) then
                            retirement_fund_into:= total * cr_retirement_fund/percentage;
                         elsif (percentage = round(cr_mortuary_quota,2)) THEN
                             retirement_fund_into:=0;
                         elseif (total=0 and base_wage >0) THEN
                             retirement_fund_into:=0;
                         else
                            RAISE exception '(%)', 'Unknown percentage of contribution!';
                         end if;
                    return round(retirement_fund_into,2);
                    end;
                $$
       ;");

        DB::statement("CREATE OR REPLACE FUNCTION public.identified_affiliate_command(identity_card_into character varying, last_name_into character varying, mothers_last_name_into character varying, surname_husband_into character varying, first_name_into character varying, second_name_into character varying, birth_date_into date, date_entry_into date)
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
                       declare
                               -------------------------------------------------------------------------
                               ----FUNCION IDENTIFICAR AFILIADO DE COMANDO----
                               -------------------------------------------------------------------------
                              affiliate_id integer;
                              count_id int:=0;
                       begin
                           if exists(SELECT id FROM affiliates WHERE identity_card = identity_card_into) then
                               SELECT id into affiliate_id FROM affiliates WHERE identity_card = identity_card_into;
                               SELECT count(id) into count_id FROM affiliates WHERE identity_card = identity_card_into;
                           else
                              select id into affiliate_id from affiliates
                              where (last_name = last_name_into or last_name is null) 
                              and (mothers_last_name = mothers_last_name_into  or (mothers_last_name is null and mothers_last_name_into is null))
                              and (surname_husband = surname_husband_into or (surname_husband is null and surname_husband_into is null))
                              and (first_name = first_name_into or (first_name is null and first_name_into is null))
                              and (second_name = second_name_into or (second_name is null and second_name_into is null))
                              and (birth_date = birth_date_into or (birth_date is null and birth_date_into is null))
                              and (date_entry  = date_entry_into or (date_entry  is null  and date_entry_into is null));
                              select count(id) into count_id from affiliates 
                              where (last_name = last_name_into or last_name is null) 
                              and (mothers_last_name = mothers_last_name_into  or (mothers_last_name is null and mothers_last_name_into is null))
                              and (surname_husband = surname_husband_into or (surname_husband is null and surname_husband_into is null))
                              and (first_name = first_name_into or (first_name is null and first_name_into is null))
                              and (second_name = second_name_into or (second_name is null and second_name_into is null))
                              and (birth_date = birth_date_into or (birth_date is null and birth_date_into is null))
                              and (date_entry  = date_entry_into or (date_entry  is null  and date_entry_into is null));

                           end if;

                            IF count_id = 1 is NULL THEN
                               affiliate_id := affiliate_id;
                            ELSIF  count_id = 0 then
                               affiliate_id :=  0;
                            ELSIF  count_id > 1 then
                                affiliate_id :=  -1;
                            END IF;
                            RETURN affiliate_id;
                       END;
                   $$;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //Schema::dropIfExists('function_calculate_command');
    }
};
