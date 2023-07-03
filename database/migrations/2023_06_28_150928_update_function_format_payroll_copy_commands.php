<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionFormatPayrollCopyCommands extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('db_aux')->statement("CREATE OR REPLACE FUNCTION public.format_payroll_copy_commands(month_copy integer, year_copy integer, reimbursement_copy boolean)
        RETURNS SETOF payroll_copy_commands
        LANGUAGE plpgsql
        AS $$
               DECLARE
                   -- Declaración EXPLICITA del cursor
                   cur_payroll CURSOR FOR SELECT * FROM payroll_copy_commands where mes = month_copy and a_o = year_copy and reimbursement = reimbursement_copy; 
                   record_row payroll_copy_commands%ROWTYPE;
               BEGIN
                  -- Procesa el cursor
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
                  return query select * from payroll_copy_commands where mes = month_copy and a_o = year_copy;
                 return;
       END $$;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('db_aux')->statement("DROP FUNCTION format_payroll_copy_commands");
    }
}
