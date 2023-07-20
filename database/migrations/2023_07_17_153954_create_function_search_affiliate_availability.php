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
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm;"); //funcion para determinar similitud

        DB::statement("CREATE OR REPLACE FUNCTION identified_affiliate_availability(order_entry integer, identity_card_entry character varying, first_name_entry character varying, second_name_entry character varying, last_name_entry character varying, mothers_last_name_entry character varying)
            RETURNS integer
            LANGUAGE plpgsql
            AS $$
                DECLARE
                    affiliate_id integer;
                    begin
                        CASE
                            WHEN (order_entry = 1 ) THEN --Busqueda de afiliado por CI, nombre, paterno y materno iguales--
                                select id into affiliate_id from affiliates where
                                identity_card ILIKE identity_card_entry
                                AND first_name ILIKE first_name_entry
                                AND (COALESCE(last_name, '') ILIKE COALESCE(last_name_entry, ''))
                                AND (COALESCE(mothers_last_name, '') ILIKE COALESCE(mothers_last_name_entry, ''));

                            WHEN (order_entry = 2 ) THEN --Busqueda de afiliado por CI igual y nombre, paterno y materno similares--
                                select id into affiliate_id from affiliates where
                                identity_card ILIKE  identity_card_entry
                                AND word_similarity(first_name , first_name_entry) >= 0.5
                                AND word_similarity(last_name, last_name_entry) >= 0.5
                                AND word_similarity(mothers_last_name, mothers_last_name_entry) >= 0.5;

                            WHEN (order_entry = 3 ) THEN --Busqueda de afiliado por CI sin complemento,nombre, paterno y materno iguales--
                                select id into affiliate_id from affiliates where
                                split_part(identity_card,'-',1) ILIKE identity_card_entry
                                AND first_name ILIKE first_name_entry
                                AND (COALESCE(last_name, '') ILIKE COALESCE(last_name_entry, ''))
                                AND (COALESCE(mothers_last_name, '') ILIKE COALESCE(mothers_last_name_entry, ''));

                            WHEN (order_entry = 4 ) then --Busqueda de afiliado por CI para sugerir--
                                select id into affiliate_id from affiliates where
                                identity_card ILIKE identity_card_entry;

                            WHEN (order_entry = 5 ) then  --Busqueda de afiliado por CI ,nombre, paterno y materno similares--
                                select id into affiliate_id from affiliates where
                                word_similarity(identity_card , identity_card_entry) >= 0.5
                                AND word_similarity(first_name , first_name_entry) >= 0.5
                                AND word_similarity(last_name, last_name_entry) >= 0.5
                                AND word_similarity(mothers_last_name, mothers_last_name_entry) >= 0.5
                                limit 1;
                            ELSE
                            affiliate_id := 0;
                            END CASE;

                    IF affiliate_id  is not NULL THEN
                        affiliate_id := affiliate_id;
                    ELSE
                        affiliate_id := 0;
                    END IF;
                    return affiliate_id;
                    END;
                    $$
            ;"
        );

        DB::statement("CREATE OR REPLACE FUNCTION search_affiliate_availability(db_name_intext text, month integer, year integer)
            RETURNS character varying
            LANGUAGE plpgsql
            AS $$
                            declare
                            type_state varchar;
                            affiliate_id_result integer;
                            criterion_one integer:= 1;
                            criterion_two integer:= 2;
                            criterion_three integer:= 3;
                            criterion_four integer:= 4;
                            criterion_five integer:= 5;
                            criterion_six integer:= 6;
                            ------------------------------
                            cant varchar ;
                            ---------------------------------
                        -- Declaración EXPLICITA del cursor
                        cur_payroll CURSOR for (select * from dblink(db_name_intext,'SELECT id, cedula, grado, paterno, materno, primer_nombre, segundo_nombre, situacion_laboral, unidad, mes, a_o, state, affiliate_id, criteria FROM copy_affiliates_availability where state = ''unrealized'' and mes='||month||' and a_o='||year||'')
                        as copy_affiliates_availability(id integer, cedula varchar, grado varchar, paterno varchar, materno varchar, primer_nombre varchar, segundo_nombre varchar,  situacion_laboral varchar, unidad varchar, mes integer, a_o integer, 

                        state varchar, affiliate_id integer, criteria varchar));
                        begin
                            --************************************************************
                            --*Funcion busqueda de afiliados en disponibilidad
                            --************************************************************
                            -- Procesa el cursor
                        FOR record_row IN cur_payroll loop
                            if identified_affiliate_availability(criterion_one,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno) > 0 then
                                affiliate_id_result := identified_affiliate_availability(criterion_one,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno);
                                type_state:='1-CI-PN-PA-SA';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE copy_affiliates_availability.id= '||record_row.id||''));
                            elsif identified_affiliate_availability(criterion_two,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno) > 0 THEN
                                affiliate_id_result := identified_affiliate_availability(criterion_two,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno);
                                type_state:='2-CI-sPN-sPA-sSA';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE copy_affiliates_availability.id= '||record_row.id||''));
                            elsif identified_affiliate_availability(criterion_three,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno) > 0 THEN
                                affiliate_id_result := identified_affiliate_availability(criterion_three,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno);
                                type_state:='3-partCI-PN-PA-SA';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE copy_affiliates_availability.id= '||record_row.id||''));
                            elsif identified_affiliate_availability(criterion_four,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno) > 0 THEN
                                affiliate_id_result := identified_affiliate_availability(criterion_four,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno);
                                type_state:='4-CI';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE copy_affiliates_availability.id= '||record_row.id||''));
                            elsif identified_affiliate_availability(criterion_five,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno) > 0 THEN
                                affiliate_id_result := identified_affiliate_availability(criterion_five,record_row.cedula,record_row.primer_nombre,record_row.segundo_nombre,record_row.paterno,record_row.materno);
                                type_state:='5-sCI-sPN-sAP-sSN-FI';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                            else
                                type_state:='6-CREAR';
                                cant:= (select dblink_exec(db_name_intext, 'UPDATE copy_affiliates_availability SET state=''accomplished'',criteria='''||type_state||''' WHERE copy_affiliates_availability.id= '||record_row.id||''));
                            END IF;
                        END LOOP;
                        return true;
                        end;$$
            ;"
        );
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('function_search_affiliate_availability');
    }
};
