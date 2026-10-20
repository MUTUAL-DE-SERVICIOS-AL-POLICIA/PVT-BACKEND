<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFunctionFormatText extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection('db_aux')->statement("CREATE OR REPLACE FUNCTION public.format_text(value varchar)
        RETURNS varchar
        AS $$
        DECLARE
            format_text varchar;
        BEGIN
            IF value IS NOT NULL AND char_length(trim(value)) > 0 THEN
                format_text = UPPER(REGEXP_REPLACE(TRIM(value), '\\s+', ' ', 'g'));
                RETURN format_text;
            ELSE
                RETURN NULL;
            END IF;
        END;
        $$ LANGUAGE plpgsql;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::connection('db_aux')->statement("DROP FUNCTION format_text");
    }
}