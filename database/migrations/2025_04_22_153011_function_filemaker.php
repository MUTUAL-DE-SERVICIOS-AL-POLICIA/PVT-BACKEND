<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE EXTENSION IF NOT EXISTS pg_trgm;"); //Para crear la extensión pg_trgm para usar la función similarity()
        
        DB::statement("CREATE OR REPLACE FUNCTION public.identified_affiliate_filemaker(order_entry integer, identity_card_entry character varying, registration_entry character varying, first_name_entry character varying, second_name_entry character varying, last_name_entry character varying, mothers_last_name_entry character varying, surname_husband_entry character varying)
        RETURNS integer
        LANGUAGE plpgsql
        AS $$
           DECLARE
                affiliate_id integer;
                begin
                     CASE
                        WHEN (order_entry = 1 ) THEN --Busqueda de afiliado por CI igual, nombre, paterno, materno y apellido de casada similares
                            select id into affiliate_id from affiliates where
                            identity_card ILIKE identity_card_entry
                            AND word_similarity(first_name , first_name_entry) >= 0.5
                            AND word_similarity(last_name, last_name_entry) >= 0.5
                            AND word_similarity(mothers_last_name, mothers_last_name_entry) >= 0.5;

                        WHEN (order_entry = 2 ) THEN --Busqueda de afiliado por CI sin complemento,nombre, paterno similares
                            select id into affiliate_id from affiliates where
                            split_part(identity_card,'-',1) ILIKE identity_card_entry
                            AND (word_similarity(first_name, first_name_entry) >= 0.5 or word_similarity(first_name, second_name_entry) >= 0.5)
                            AND word_similarity(last_name, last_name_entry) >= 0.4;

                        WHEN (order_entry = 3 ) then --Busqueda de afiliado por CI similar matricula, nombre, paterno igual--
                            select id into affiliate_id from affiliates where
                            word_similarity(identity_card, identity_card_entry) >= 0.4
                            AND (COALESCE(registration, '') ILIKE COALESCE(registration_entry, ''))
                            AND (COALESCE(first_name, '') ILIKE COALESCE(first_name_entry, ''))
                            AND (COALESCE(last_name, '') ILIKE COALESCE(last_name_entry, ''));

                        WHEN (order_entry = 4 ) then  --Busqueda de afiliado por matricula igual y apellido de casada del archivo con apellido paterno de affiliates, verificando que la aportante es la conyugue
                            select id into affiliate_id from affiliates where registration is not null
                             AND registration_entry is not null                           
                             AND COALESCE(registration, '') ILIKE COALESCE(registration_entry, '')
                             AND word_similarity(last_name, surname_husband_entry) >= 0.5;

                        WHEN (order_entry = 5 ) THEN --Busqueda de conyuge por CI sin complemento,nombre, paterno similares
                            select s.affiliate_id into affiliate_id from spouses s where
                            split_part(identity_card,'-',1) ILIKE identity_card_entry
                            AND (word_similarity(first_name , first_name_entry) >= 0.5 or word_similarity(second_name , second_name_entry) >= 0.5)
                            AND word_similarity(last_name, last_name_entry) >= 0.5;

                        WHEN (order_entry = 6 ) then --Busqueda de conyuge por CI similar matricula, nombre, paterno igual--
                            select s.affiliate_id into affiliate_id from spouses s where
                            word_similarity(identity_card, identity_card_entry) >= 0.4
                            AND (COALESCE(registration, '') ILIKE COALESCE(registration_entry, ''))
                            AND (COALESCE(first_name, '') ILIKE COALESCE(first_name_entry, ''))
                            AND (COALESCE(last_name, '') ILIKE COALESCE(last_name_entry, ''));

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
            $$;"
        );

       DB::statement("CREATE OR REPLACE FUNCTION public.search_affiliate_filemaker(db_name_intext text)
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
                 criterion_five integer:= 5;--spouses
                 criterion_six integer:= 6; --spouses
                 --date_period date := (year||'-'||month||'-'||01)::date;
                 ------------------------------
                 cant varchar ;
                ---------------------------------
              -- Declaración EXPLICITA del cursor
              cur_payroll CURSOR for (select * from dblink( db_name_intext,'SELECT id, mes, a_o, carnet, matricula, pat, mat, nom, nom2, ap_casada, grado, desc_mens, class_rent, tipo, affiliate_id, state, criteria FROM  payroll_copy_filemaker where state = ''unrealized''')
              as  payroll_copy_filemaker( id integer, mes integer, a_o integer, carnet character varying(250), matricula character varying(250), pat character varying(250), mat character varying(250), nom character varying(250), nom2 character varying(250), ap_casada character varying(250), grado character varying(250), desc_mens decimal(13,2), class_rent character varying(250), tipo character varying(250), affiliate_id integer, state character varying(250), criteria character varying(250)));
              begin
                   --************************************************************
                   --*Funcion filemaker busqueda de afiliados y affiliate_id de spouses  
                   --************************************************************
                   -- Procesa el cursor
              FOR record_row IN cur_payroll loop
                  if identified_affiliate_filemaker(criterion_one, record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada ) > 0 then
                      affiliate_id_result := identified_affiliate_filemaker( criterion_one, record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='1-CI-sPN-sPA-sSA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VEJEZ'' WHERE payroll_copy_filemaker.id= '||record_row.id||''));

                  elsif identified_affiliate_filemaker(criterion_two,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada) > 0 THEN
                      affiliate_id_result := identified_affiliate_filemaker(criterion_two,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='2-partCI-sPN-sPA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VEJEZ''  WHERE payroll_copy_filemaker.id= '||record_row.id||''));

                  elsif identified_affiliate_filemaker(criterion_three,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada) > 0 THEN
                      affiliate_id_result := identified_affiliate_filemaker(criterion_three,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='3-sCI-MAT-PN-PA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VEJEZ''  WHERE payroll_copy_filemaker.id= '||record_row.id||''));

                  elsif identified_affiliate_filemaker(criterion_four,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada) > 0 THEN
                      affiliate_id_result := identified_affiliate_filemaker(criterion_four,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='4-MAT-APCAS';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VEJEZ''  WHERE payroll_copy_filemaker.id= '||record_row.id||''));

                  elsif identified_affiliate_filemaker(criterion_five,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada) > 0 THEN
                      affiliate_id_result := identified_affiliate_filemaker(criterion_five,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='5-cCI-sPN-sPA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VIUDEDAD''  WHERE payroll_copy_filemaker.id= '||record_row.id||''));

                  elsif identified_affiliate_filemaker(criterion_six,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada) > 0 THEN
                      affiliate_id_result := identified_affiliate_filemaker(criterion_six,record_row.carnet, record_row.matricula, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.ap_casada);
                      type_state:='6-partcCI-sPN-sPA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||', tipo=''VIUDEDAD''  WHERE payroll_copy_filemaker.id= '||record_row.id||''));
                  else
                      type_state:='7-no-identificado';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_filemaker SET state=''accomplished'',criteria='''||type_state||''' WHERE payroll_copy_filemaker.id= '||record_row.id||''));
                  END IF;
              END LOOP;
              return true;
              end;
              $$;"
        );

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
