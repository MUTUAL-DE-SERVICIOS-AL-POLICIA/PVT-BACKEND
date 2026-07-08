<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionFormatPayrollCopyRegionals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('db_aux')->statement("CREATE OR REPLACE FUNCTION public.format_payroll_copy_regionals(date_copy date)
        RETURNS SETOF payroll_copy_regionals
        LANGUAGE plpgsql
        AS $$
            DECLARE
                -- Declaración EXPLICITA del cursor
                cur_payroll CURSOR FOR SELECT * FROM payroll_copy_regionals WHERE created_at::date = date_copy;
                record_row payroll_copy_regionals%ROWTYPE;
            BEGIN
                -- Procesamiento de cada registro
                FOR record_row IN cur_payroll LOOP
                    UPDATE payroll_copy_regionals SET
                        carnet = format_identity_card(record_row.carnet),
                        tipo_aportante = format_text(record_row.tipo_aportante),
                        nom = format_text(record_row.nom),
                        nom2 = format_text(record_row.nom2),
                        pat = format_text(record_row.pat),
                        mat = format_text(record_row.mat),
                        ap_casada = format_text(record_row.ap_casada),
                        recibo = format_text(record_row.recibo),
                        updated_at = current_timestamp
                    WHERE id = record_row.id
                    AND created_at::date = date_copy;
                END LOOP;
                RETURN QUERY SELECT * FROM payroll_copy_regionals WHERE created_at::date = date_copy;
                RETURN;
            END $$;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('db_aux')->statement("DROP FUNCTION format_payroll_copy_regionals");
    }
}