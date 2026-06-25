<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        /**
         * payroll_copy_commands (db_aux)
         */
        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->enum('type_payroll', ['mensual', 'reintegro', 'regularizacion'])
                ->default('mensual');
        });

        DB::connection('db_aux')->table('payroll_copy_commands')->update([
            'type_payroll' => DB::raw("
                CASE
                    WHEN reimbursement = true THEN 'reintegro'
                    ELSE 'mensual'
                END
            ")
        ]);

        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->dropColumn('reimbursement');
        });

        /**
         * payroll_commands
         */
        Schema::table('payroll_commands', function (Blueprint $table) {
            $table->enum('type_payroll', ['mensual', 'reintegro', 'regularizacion'])
                ->default('mensual');
        });

        DB::table('payroll_commands')->update([
            'type_payroll' => DB::raw("
                CASE
                    WHEN reimbursement = true THEN 'reintegro'
                    ELSE 'mensual'
                END
            ")
        ]);

        Schema::table('payroll_commands', function (Blueprint $table) {
            $table->dropColumn('reimbursement');
        });

        /**
         * reimbursements
         */
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->enum('type_payroll', ['reintegro', 'regularizacion'])
                ->default('reintegro');
        });

        DB::table('reimbursements')->update([
            'type_payroll' => 'reintegro'
        ]);

        /**
         * Eliminar índice
         */
        DB::statement("
            ALTER TABLE public.reimbursements
            DROP CONSTRAINT IF EXISTS reimbursements_affiliate_id_month_year_unique
        ");

        /**
         * Crear índice
         */
        DB::statement("
            CREATE UNIQUE INDEX reimbursements_affiliate_month_active_unique
            ON public.reimbursements
            USING btree (affiliate_id, month_year, type_payroll)
            WHERE deleted_at IS NULL
        ");
        /**
         * adcionar columna contribution_rate
         */
        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->decimal('regulation_rf', 10, 2)->default(0)->nullable(true)->comment('Regulación del porcentaje restante del aporte de Fondo de Retiro');
        });
    }

    public function down()
    {
        /**
         * Eliminar nuevo índice parcial
         */
        DB::statement("
            DROP INDEX IF EXISTS reimbursements_affiliate_month_active_unique
        ");

        /**
         * payroll_copy_commands
         */
        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->boolean('reimbursement')->default(false);
        });

        DB::connection('db_aux')->table('payroll_copy_commands')->update([
            'reimbursement' => DB::raw("
                CASE
                    WHEN type_payroll = 'reintegro' THEN true
                    ELSE false
                END
            ")
        ]);

        Schema::connection('db_aux')->table('payroll_copy_commands', function (Blueprint $table) {
            $table->dropColumn('type_payroll');
        });

        /**
         * payroll_commands
         */
        Schema::table('payroll_commands', function (Blueprint $table) {
            $table->boolean('reimbursement')->default(false);
        });

        DB::table('payroll_commands')->update([
            'reimbursement' => DB::raw("
                CASE
                    WHEN type_payroll = 'reintegro' THEN true
                    ELSE false
                END
            ")
        ]);

        Schema::table('payroll_commands', function (Blueprint $table) {
            $table->dropColumn('type_payroll');
        });

        /**
         * reimbursements
         */
        Schema::table('reimbursements', function (Blueprint $table) {
            $table->dropColumn('type_payroll');
        });

        /**
         * Restaurar índice
         */
        DB::statement("
            ALTER TABLE public.reimbursements
            ADD CONSTRAINT reimbursements_affiliate_id_month_year_unique
            UNIQUE (affiliate_id, month_year)
        ");

        /**
         * eliminar columna fr_regulation
         */
        Schema::table('contribution_rates', function (Blueprint $table) {
            $table->dropColumn('regulation_rf');
        });
    }
};