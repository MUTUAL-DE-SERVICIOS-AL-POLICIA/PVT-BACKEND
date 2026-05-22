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
        DB::statement("CREATE OR REPLACE FUNCTION public.update_affiliate_command(date_period date, user_id_into integer, year_period integer, month_period integer)
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
                      declare
                      count_update int:=0;
                       record_row RECORD;
                      message_into varchar := '';
                      type_id int :=0;
                      affiliate_state_name_old varchar := '';
                      affiliate_state_name_new varchar := '';
                      unit_name_old varchar := '';
                      unit_name_new varchar := '';
                      degree_name_old varchar := '';
                      degree_name_new varchar := '';
                      category_name_old varchar := '';
                      category_name_new varchar := '';

                      begin
                      --******************************************
                      --Funcion para actualizar datos de afiliados-
                       --*****************************************
                      -- Procesa el cursor
                         FOR record_row IN
                            select a.*,pc.id as pc_id,pc.affiliate_id as pc_affiliate_id,pc.affiliate_state_id as pc_affiliate_state_id,pc.unit_id as pc_unit_id,
                              pc.breakdown_id as pc_breakdown_id,pc.category_id as pc_category_id,
                              pc.identity_card as pc_identity_card,pc.last_name as pc_last_name,
                              pc.mothers_last_name as pc_mothers_last_name,pc.surname_husband as pc_surname_husband,
                              pc.first_name as pc_first_name,pc.second_name as pc_second_name,
                              pc.civil_status as pc_civil_status,pc.hierarchy_id as pc_hierarchy_id,pc.degree_id as pc_degree_id,
                              pc.gender as pc_gender,pc.birth_date as pc_birth_date,
                              pc.date_entry as pc_date_entry,pc.affiliate_type as pc_affiliate_type, pc.base_wage as pc_base_wage, pc.seniority_bonus as pc_seniority_bonus from payroll_commands pc
                                 inner join affiliates a on  pc.affiliate_id = a.id
                                 where year_p = year_period and month_p = month_period

                         loop
                             if((record_row.pc_affiliate_type ='REGULAR') and (record_row.affiliate_state_id = 1 or record_row.affiliate_state_id = 2 or record_row.affiliate_state_id = 3 or record_row.affiliate_state_id is null)) then
                                 if record_row.identity_card <> record_row.pc_identity_card and insert_text(SPLIT_PART(record_row.identity_card, '-', 2)) is null then
                                     update affiliates set identity_card = record_row.pc_identity_card, updated_at = current_timestamp where id = record_row.id;
                                     message_into:= concat('Afiliado cambio de número de carnet de ', record_row.identity_card,' a ',record_row.pc_identity_card);
                                     type_id :=7;
                                     insert into affiliate_records(user_id,affiliate_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                     count_update:= count_update+1;
                                 end if;
                                --estado
                                 if record_row.affiliate_state_id <> record_row.pc_affiliate_state_id then
                                        select name into affiliate_state_name_old from affiliate_states as2 where id= record_row.affiliate_state_id;
                                     select name into affiliate_state_name_new from affiliate_states as2 where id= record_row.pc_affiliate_state_id;

                                     update affiliates set affiliate_state_id = record_row.pc_affiliate_state_id, updated_at = current_timestamp where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de estado de ',affiliate_state_name_old,' a ',affiliate_state_name_new);
                                     type_id :=1;
                                     insert into affiliate_records(user_id,affiliate_id,affiliate_state_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,record_row.pc_affiliate_state_id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                                --nombres y apellidos
                                 if (record_row.last_name <> record_row.pc_last_name) or (record_row.mothers_last_name <> record_row.pc_mothers_last_name) or (record_row.surname_husband <> record_row.pc_surname_husband) or
                                    (record_row.first_name <> record_row.pc_first_name) or (record_row.second_name <> record_row.pc_second_name)  then
                                     update affiliates set last_name = record_row.pc_last_name,mothers_last_name = record_row.pc_mothers_last_name,
                                     surname_husband = record_row.pc_surname_husband,first_name = record_row.pc_first_name,second_name = record_row.pc_second_name,updated_at=current_timestamp
                                    where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de nombre(s) o apellido(s) de  AP1: ',record_row.last_name,' a ',record_row.pc_last_name,' ; AP2: ',record_row.mothers_last_name,' a ',record_row.pc_mothers_last_name,' ; APES: ',
                                     record_row.surname_husband,' a ',record_row.pc_surname_husband,' ; PN: ',record_row.first_name,' a ',record_row.pc_first_name,' ; SN: ',record_row.second_name,' a ',record_row.pc_second_name);
                                     type_id :=8;
                                     insert into affiliate_records(user_id,affiliate_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                                --unidad
                                if record_row.degree_id <> record_row.pc_degree_id then
                                        select name into unit_name_old from units u where id= record_row.unit_id;
                                     select name into unit_name_new from units u where id= record_row.pc_unit_id;

                                     update affiliates set unit_id = record_row.unit_id,updated_at = current_timestamp where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de unidad de ', unit_name_old,' a ',unit_name_new);
                                     type_id :=3;
                                     insert into affiliate_records(user_id,affiliate_id,unit_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,record_row.pc_unit_id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                                --grado
                                if record_row.degree_id <> record_row.pc_degree_id then
                                        select name into degree_name_old from degrees d where id= record_row.degree_id;
                                     select name into degree_name_new from degrees d where id= record_row.pc_degree_id;

                                     update affiliates set degree_id = record_row.pc_degree_id, updated_at = current_timestamp where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de grado de ', degree_name_old,' a ',degree_name_new);
                                     type_id :=2;
                                     insert into affiliate_records(user_id,affiliate_id,degree_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,record_row.pc_degree_id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                                --categoria
                               if record_row.category_id <> record_row.pc_category_id and (record_row.pc_base_wage > 0 or record_row.pc_seniority_bonus > 0) then
                                        select name into category_name_old from categories c where id= record_row.category_id;
                                     select name into category_name_new from categories c where id= record_row.pc_category_id;

                                     update affiliates set category_id = record_row.pc_category_id, updated_at = current_timestamp where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de categoria de ', category_name_old,' a ',category_name_new);
                                     type_id :=4;
                                     insert into affiliate_records(user_id,affiliate_id,category_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,record_row.pc_category_id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                                --genero
                                 if record_row.gender <> record_row.pc_gender then

                                     update affiliates set gender = record_row.pc_gender, updated_at = current_timestamp where id = record_row.id;

                                     message_into:= concat('Afiliado cambio de género de ', record_row.gender,' a ',record_row.pc_gender);
                                     type_id :=9;
                                     insert into affiliate_records(user_id,affiliate_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                                       count_update:= count_update+1;
                                 end if;
                               ELSIF (record_row.pc_affiliate_type ='NUEVO') THEN
                                   --nuevo
                                     message_into:= 'Afiliado ingresó de Servicio';
                                     type_id :=10;
                                     insert into affiliate_records(user_id,affiliate_id,affiliate_state_id,type_id,date,message,created_at,updated_at) values(user_id_into,record_row.id,record_row.pc_affiliate_state_id,type_id,date_period,message_into,current_timestamp,current_timestamp);
                             end if;
                         END LOOP;

                         RETURN count_update;
                      end
                  $$;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement("DROP FUNCTION update_affiliate_command");
    }
};
