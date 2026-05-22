<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        // 1. format_payroll_copy_commands (db_aux)
        DB::connection('db_aux')->statement("CREATE OR REPLACE FUNCTION public.format_payroll_copy_commands(month_copy integer, year_copy integer, type_payroll_copy varchar)
        RETURNS SETOF payroll_copy_commands
        LANGUAGE plpgsql
        AS $$
               DECLARE
                   cur_payroll CURSOR FOR SELECT * FROM payroll_copy_commands where mes = month_copy and a_o = year_copy and type_payroll = type_payroll_copy; 
                   record_row payroll_copy_commands%ROWTYPE;
               BEGIN
                  FOR record_row IN cur_payroll loop
                       update payroll_copy_commands set 
                       car_formato = format_identity_card(record_row.car),
                       sue_formato = format_amount_decimal(record_row.sue),
                       cat_formato = format_amount_decimal(record_row.cat),
                       est_formato = format_amount_decimal(record_row.est),
                       carg_formato = format_amount_decimal(record_row.carg),
                       fro_formato = format_amount_decimal(record_row.fro),
                       ori_formato = format_amount_decimal(record_row.ori),
                       gan_formato = format_amount_decimal(record_row.gan),
                       mus_formato = format_amount_decimal(record_row.mus),
                       lpag_formato = format_amount_decimal(record_row.lpag),
                       nac_formato =  TO_DATE(record_row.nac,'DDMMYYYY'),
                       ing_formato = TO_DATE(record_row.ing,'DDMMYYYY'),
                       updated_at = current_timestamp
                       where id = record_row.id and mes = month_copy and a_o = year_copy;
                  END LOOP;
                  return query select * from payroll_copy_commands where mes = month_copy and a_o = year_copy and type_payroll = type_payroll_copy;
       END $$;");

        // 2. registration_payroll_command (main db)
        DB::statement("CREATE OR REPLACE FUNCTION public.registration_payroll_command(conection_db_aux character varying, month_copy integer, year_copy integer, type_payroll_copy varchar, user_id_into integer)
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
               declare
                        ----variables----
                        num_validated bigint := 0;
                        record_row RECORD;
                        affiliate_id_into bigint:=0;
                        affiliate_id_into2 bigint:=0;
                        breakdown_id_into int:=0;
                        unit_id_into int:=0;
                        hierarchy_id_into int:=0;
                        degree_id_into int:=0;
                        category_id_into int:=0;
                        affiliate_state_id_into int:=0;
                        message varchar := 'REGULAR';
                        -----------------------------------------------------------------------------
                       ----FUNCIÓN PARA REGISTRAR LOS DATOS VALIDADOS DE PLANILLA DE COMANDO----
                       -------------------------------------------------------------------------
               BEGIN
               FOR record_row IN  
               select *
                from dblink(conection_db_aux,
                            'select id,uni,desg,mes,a_o,car,car_formato,pat,mat,apes,nom,nom2,eciv,niv,gra,sex,
               sue,sue_formato,cat,cat_formato,est,est_formato,carg,carg_formato,fro,fro_formato,ori,ori_formato,
               gan,gan_formato,mus,mus_formato,lpag,lpag_formato,nac,nac_formato,ing,ing_formato,is_validated,type_payroll,dtr FROM payroll_copy_commands
               ') 
               AS payroll_copy_commands(id bigint,uni varchar,desg integer,mes integer,a_o integer,car varchar,
               car_formato varchar,pat varchar,mat varchar,apes varchar,nom varchar,nom2 varchar,eciv varchar,niv varchar,
               gra varchar,sex varchar,sue varchar,sue_formato NUMERIC(13,2),cat varchar,cat_formato NUMERIC(13,2),est varchar,est_formato NUMERIC(13,2),
               carg varchar,carg_formato NUMERIC(13,2),fro varchar,fro_formato NUMERIC(13,2),ori varchar,ori_formato NUMERIC(13,2),
               gan varchar,gan_formato NUMERIC(13,2),mus varchar,mus_formato NUMERIC(13,2),lpag varchar,lpag_formato NUMERIC(13,2),nac varchar,
               nac_formato date,ing varchar,ing_formato date,is_validated boolean,type_payroll varchar,dtr integer)
               where mes = month_copy and a_o = year_copy and type_payroll = type_payroll_copy and is_validated = false
              loop
                  message:='REGULAR';
                  affiliate_id_into:= (SELECT identified_affiliate_command(record_row.car_formato,insert_text(record_row.pat),insert_text(record_row.mat),
                  insert_text(record_row.apes),insert_text(record_row.nom),insert_text(record_row.nom2),record_row.nac_formato,record_row.ing_formato));
                  breakdown_id_into:=  (select get_breakdown_id(record_row.desg));
                  unit_id_into := (select get_unit_id(breakdown_id_into,record_row.uni));
                  hierarchy_id_into := (select get_hierarchy_id(record_row.niv,record_row.gra));
                  degree_id_into := (select get_degree_id(hierarchy_id_into,record_row.gra));
                  category_id_into := (select get_category_id(record_row.cat_formato,record_row.sue_formato));
                  affiliate_state_id_into := (select get_affiliate_state_id(record_row.desg));

                  if affiliate_id_into <=0 then
                          INSERT INTO affiliates (
                       identity_card, affiliate_state_id,type,
                       unit_id,degree_id,category_id,
                       user_id,last_name,mothers_last_name,
                       surname_husband,first_name,second_name,civil_status,
                       gender, birth_date,date_entry,
                       created_at,updated_at)
                       VALUES (record_row.car_formato,get_affiliate_state_id(record_row.desg),get_type(record_row.desg),
                       unit_id_into,degree_id_into,category_id_into,
                       user_id_into,replace_character(insert_text(record_row.pat)),replace_character(insert_text(record_row.mat)),
                       replace_character(insert_text(record_row.apes)),replace_character(insert_text(record_row.nom)), replace_character(insert_text(record_row.nom2)),record_row.eciv,
                       record_row.sex,record_row.nac_formato,record_row.ing_formato, current_timestamp,current_timestamp);
                       message:='NUEVO';
                   end if;
                      affiliate_id_into2:= (SELECT identified_affiliate_command(record_row.car_formato,insert_text(record_row.pat),insert_text(record_row.mat),
                      insert_text(record_row.apes),insert_text(record_row.nom),insert_text(record_row.nom2),record_row.nac_formato,record_row.ing_formato));

                      INSERT INTO payroll_commands(affiliate_id, affiliate_state_id, unit_id, breakdown_id, category_id, month_p, year_p, identity_card, last_name, mothers_last_name, surname_husband, first_name, second_name, civil_status, hierarchy_id, degree_id, gender, base_wage, seniority_bonus, study_bonus, position_bonus, border_bonus, east_bonus, gain, total, payable_liquid, birth_date, date_entry, affiliate_type, created_at, updated_at, days_worked, type_payroll)
                      VALUES (affiliate_id_into2,affiliate_state_id_into,unit_id_into,breakdown_id_into,category_id_into, record_row.mes,
                      record_row.a_o, record_row.car_formato, replace_character(insert_text(record_row.pat)),replace_character(insert_text(record_row.mat)),
                      replace_character(insert_text(record_row.apes)),replace_character(insert_text(record_row.nom)), replace_character(insert_text(record_row.nom2)),
                      record_row.eciv,hierarchy_id_into,degree_id_into,record_row.sex,record_row.sue_formato,
                      record_row.cat_formato,record_row.est_formato,record_row.carg_formato,record_row.fro_formato,
                      record_row.ori_formato,record_row.gan_formato,record_row.mus_formato,
                      record_row.lpag_formato,record_row.nac_formato,record_row.ing_formato,message,
                      current_timestamp,current_timestamp,record_row.dtr,record_row.type_payroll);
                      num_validated:=num_validated+1;
              END LOOP;
              RETURN num_validated;
              END $$;");

        // 3. import_period_contribution_command (main db)
        DB::statement("CREATE OR REPLACE FUNCTION public.import_period_contribution_command(date_period date, user_id_into integer, year_period integer, month_period integer)
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
                    declare
                        acction varchar;
                        quotable numeric:=0;
                        percentage numeric:=0;
                        num_import int:=0;
                        retirement_fund_amount numeric:=0;
                        mortuary_quota_amount numeric:=0;
                        cur_contribution CURSOR FOR select * from payroll_commands where year_p = year_period and month_p = month_period and base_wage > 0 and type_payroll = 'mensual' and deleted_at is null;
                        record_row payroll_commands%ROWTYPE;
                    begin
                        FOR record_row IN cur_contribution loop
                            quotable:= record_row.base_wage + record_row.seniority_bonus + record_row.study_bonus + 
                                        record_row.position_bonus + record_row.border_bonus + record_row.east_bonus;
                            percentage:= round((record_row.total/quotable)*100,2);
                            retirement_fund_amount :=  get_retirement_fund_amount(date_period,percentage,record_row.total,record_row.base_wage);
                            mortuary_quota_amount:= record_row.total - retirement_fund_amount; 

                            INSERT INTO contributions (
                            user_id,affiliate_id,degree_id,unit_id,breakdown_id,
                            category_id,month_year,type,base_wage,seniority_bonus,
                            study_bonus,position_bonus,border_bonus,east_bonus,
                            gain,payable_liquid,quotable,
                            retirement_fund,mortuary_quota,total,
                            created_at,updated_at,contributionable_type,contributionable_id, days_worked)
                            VALUES (
                            user_id_into,
                            record_row.affiliate_id,
                            record_row.degree_id,
                            record_row.unit_id,
                            record_row.breakdown_id,
                            record_row.category_id,
                            date_period,
                            'Planilla',
                            record_row.base_wage,
                            record_row.seniority_bonus,
                            record_row.study_bonus,
                            record_row.position_bonus,
                            record_row.border_bonus,
                            record_row.east_bonus,
                            record_row.gain,
                            record_row.payable_liquid,
                            quotable,
                            retirement_fund_amount,
                            mortuary_quota_amount,
                            record_row.total,
                            current_timestamp,
                            current_timestamp,
                            'payroll_commands',
                            record_row.id, record_row.days_worked
                            );
                            num_import:=num_import+1;
                        END LOOP;
                        RETURN num_import;
                    end;
                $$;");

        // 4. import_period_reimbursement_command (main db)
        DB::statement("CREATE OR REPLACE FUNCTION public.import_period_reimbursement_command(date_period date, user_id_into integer, year_period integer, month_period integer, type_payroll_copy varchar)
        RETURNS numeric
        LANGUAGE plpgsql
        AS $$
                   declare
                       acction varchar;
                       quotable numeric:=0;
                       percentage numeric:=0;
                       num_import int:=0;
                       retirement_fund_amount numeric:=0;
                       mortuary_quota_amount numeric:=0;
                       cur_reimbursement CURSOR FOR select * from payroll_commands where year_p = year_period and month_p = month_period and base_wage > 0 and type_payroll = type_payroll_copy and deleted_at is null;
                       record_row payroll_commands%ROWTYPE;
                   begin
                        FOR record_row IN cur_reimbursement loop
                            quotable:= record_row.base_wage + record_row.seniority_bonus + record_row.study_bonus + 
                                        record_row.position_bonus + record_row.border_bonus + record_row.east_bonus;
							percentage:= round((record_row.total/quotable)*100,2);
                            retirement_fund_amount :=  get_retirement_fund_reimbursement(date_period, percentage, record_row.total, record_row.affiliate_id, type_payroll_copy);
                            mortuary_quota_amount:= record_row.total - retirement_fund_amount; 
      
                            INSERT INTO reimbursements (
								    user_id,
								    affiliate_id,
								    degree_id,
								    unit_id,
								    breakdown_id,
								    month_year,
								    type,
								    base_wage,
								    seniority_bonus,
                                    study_bonus,
									position_bonus,
									border_bonus,
									east_bonus,
                                    gain,
									payable_liquid,
									quotable,
                                    retirement_fund,
									mortuary_quota,									
									total,
                                    created_at,
									updated_at,
                                    category_id,
									contributionable_type,
									contributionable_id,
                                    days_worked,
                                    type_payroll)						
                            VALUES (
                                  user_id_into,
                                  record_row.affiliate_id,
                                  record_row.degree_id,
                                  record_row.unit_id,
                                  record_row.breakdown_id,
                                  date_period,
                                  'Planilla',
                                  record_row.base_wage,
                                  record_row.seniority_bonus,
                                  record_row.study_bonus,
                                  record_row.position_bonus,
                                  record_row.border_bonus,
                                  record_row.east_bonus,
                                  record_row.gain,
                                  record_row.payable_liquid,
                                  quotable,
                                  retirement_fund_amount,
                                  mortuary_quota_amount,                                  
                                  record_row.total,
                                  current_timestamp,
                                  current_timestamp,
                                  record_row.category_id,
                                  'payroll_commands',
                                  record_row.id,
                                  record_row.days_worked,
                                  record_row.type_payroll
                            );
                            num_import:=num_import+1;
                        END LOOP;
                        RETURN num_import;
                    end;
                $$;");

        //5. get_retirement_fund_reimbursement
       DB::statement("CREATE OR REPLACE FUNCTION public.get_retirement_fund_reimbursement(date_period date, percentage numeric, total numeric, affiliate_id numeric, type_payroll character varying)
        RETURNS numeric
        LANGUAGE plpgsql
       AS $$
                    declare
                        cr_retirement_fund numeric:=0;
                        retirement_fund_into numeric:=0;
                        cr_mortuary_quota numeric:=0;
                        cr_regulation_rf numeric:=0;
       
                    begin
                    --*********************************************--
                    --Funcion para obtener monto de fondo de retiro--
                    --*********************************************--
                        --select round(avg(retirement_fund),2) into cr_retirement_fund from contribution_rates cr where month_year <= date_period and extract(year from month_year)= format_year_into;
                        --select round(avg(mortuary_quota),2) into cr_mortuary_quota from contribution_rates cr where month_year <= date_period and extract(year from month_year)= format_year_into;
	                    select retirement_fund into cr_retirement_fund from contribution_rates cr where month_year = date_period limit 1;
                        select mortuary_quota into cr_mortuary_quota from contribution_rates cr where month_year = date_period limit 1;
                        select regulation_rf into cr_regulation_rf from contribution_rates cr where month_year = date_period limit 1;

                        if(type_payroll = 'reintegro') then
                            ---CASO 1: Porcentajes segun Contrib.Rates
                            if (percentage = round(cr_retirement_fund+cr_mortuary_quota,2)) then
                                retirement_fund_into:= total * cr_retirement_fund/percentage;
                            ELSIF (percentage = round(cr_mortuary_quota,2)) THEN
                                retirement_fund_into:=0;
                            --CASO2: Porcentaje distinto
                            ELSIF (percentage <> round(cr_mortuary_quota,2) and total>0) THEN
                                retirement_fund_into:=get_retirement_fund_reimbursement_calculator(date_period,total,affiliate_id);
                                RAISE NOTICE 'retirement_fund_into'
                            else
                                RAISE EXCEPTION 'Ocurrio un error al calcular el monto, revisar';
                            end if;
                        else
                            ---CASO 1: Porcentajes segun Contrib.Rates
                            if (percentage = round(cr_regulation_rf,2)) THEN
                                retirement_fund_into:= total;
                            --CASO2: Porcentaje distinto
                            ELSIF (percentage <> round(cr_regulation_rf,2) and total>0) THEN                             
                                RAISE EXCEPTION 
								'Error en el afiliado % porque el porcentaje calculado es inválido (%).', 
								affiliate_id, percentage;
                            else
                                RAISE EXCEPTION 'Ocurrio un error al calcular el monto, revisar';
                            end if;
                        end if;
                    return round(retirement_fund_into,2);
                    end;
                $$
       ;");
                
    }

    public function down()
    {
        // Rollback functions would need to revert to boolean versions, 
        // but since we dropped the column, it's better to just leave them or drop them.
    }
};
