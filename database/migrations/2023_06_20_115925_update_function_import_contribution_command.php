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
                               -- Declaración EXPLICITA del cursor
                               cur_contribution CURSOR FOR select * from payroll_commands where year_p = year_period and month_p = month_period and base_wage >0 and reimbursement=false;
                               record_row payroll_commands%ROWTYPE;
                           begin
                              --***************************************
                              --Funcion importar planilla comando--
                              --***************************************
                              -- Procesa el cursor
                              FOR record_row IN cur_contribution loop
                                  --if not exists(select * from contributions where affiliate_id = record_row.affiliate_id and month_year = date_period and deleted_at is null ) then
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
                                 created_at,updated_at,contributionable_type,contributionable_id)
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
                                   record_row.id);
                                   num_import:=num_import+1;
                                  --end if;
                              END LOOP;
                              acction:='Importación realizada con éxito';
                              RETURN num_import;
                          end;
                   $$;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
      //  Schema::dropIfExists('function_import_contribution_command');
    }
};
