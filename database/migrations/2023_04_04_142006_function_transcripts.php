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

        DB::statement("CREATE OR REPLACE FUNCTION public.identified_affiliate_transcript(order_entry integer, identity_card_entry character varying, first_name_entry character varying, second_name_entry character varying, last_name_entry character varying, mothers_last_name_entry character varying, date_contribution date)
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
                            and date_entry <= date_contribution
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
            $$;"
        );

        DB::statement("CREATE OR REPLACE FUNCTION public.search_affiliate_transcript(db_name_intext text,month integer,year integer)
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
                 date_period date := (year||'-'||month||'-'||01)::date;
                 ------------------------------
                 cant varchar ;
                ---------------------------------
              -- Declaración EXPLICITA del cursor
              cur_payroll CURSOR for (select * from dblink(db_name_intext,'SELECT id,obs,uni,mes,a_o,car,pat,mat,nom,nom2,niv,gra,sue,cat,gan,mus,est,carg,fro,ori,affiliate_id,state,criteria FROM  payroll_copy_transcripts where state = ''unrealized'' and mes='||month||' and a_o='||year||'')
              as  payroll_copy_transcripts(id integer,obs character varying(250),uni character varying(250),mes integer,a_o integer,car character varying(250),pat character varying(250),mat character varying(250),nom character varying(250),nom2 character varying(250),
              niv character varying(250),gra  character varying(250),sue decimal(13,2),cat decimal(13,2),gan decimal(13,2) ,mus decimal(13,2) ,est decimal(13,2),carg decimal(13,2),fro decimal(13,2),ori decimal(13,2),
              affiliate_id integer,state character varying(250) ,criteria character varying(250)));
              begin
                   --************************************************************
                   --*Funcion busqueda de afiliados transcripciones
                   --************************************************************
                   -- Procesa el cursor
              FOR record_row IN cur_payroll loop
                  if identified_affiliate_transcript(criterion_one,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period) > 0 then
                      affiliate_id_result := identified_affiliate_transcript(criterion_one,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period);
                      type_state:='1-CI-PN-PA-SA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  elsif identified_affiliate_transcript(criterion_two,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period) > 0 THEN
                      affiliate_id_result := identified_affiliate_transcript(criterion_two,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period);
                      type_state:='2-CI-sPN-sPA-sSA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  elsif identified_affiliate_transcript(criterion_three,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period) > 0 THEN
                      affiliate_id_result := identified_affiliate_transcript(criterion_three,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period);
                      type_state:='3-partCI-PN-PA-SA';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  elsif identified_affiliate_transcript(criterion_four,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period) > 0 THEN
                      affiliate_id_result := identified_affiliate_transcript(criterion_four,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period);
                      type_state:='4-CI';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  elsif identified_affiliate_transcript(criterion_five,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period) > 0 THEN
                      affiliate_id_result := identified_affiliate_transcript(criterion_five,record_row.car,record_row.nom,record_row.nom2,record_row.pat,record_row.mat,date_period);
                      type_state:='5-sCI-sPN-sAP-sSN-FI';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''',affiliate_id='||affiliate_id_result||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  else
                      type_state:='6-CREAR';
                      cant:= (select dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET state=''accomplished'',criteria='''||type_state||''' WHERE payroll_copy_transcripts.id= '||record_row.id||''));
                  END IF;
              END LOOP;
              return true;
              end;$$;");

       DB::statement("CREATE OR REPLACE FUNCTION public.registration_payroll_command_transcript(db_name_intext text ,user_id_reg integer, month_copy integer, year_copy integer)
       RETURNS character varying
       LANGUAGE plpgsql
            AS $$
                declare
                affiliate_state_id_reg_jub integer := 5;
                id_affiliate integer := 0;
                message_into varchar:= '';
                type_reg integer := 10;
                update_affiliate varchar;
                record_row_payroll record;
                hierarchy_id_into int :=0 ;
                degree_id_into int := 0;
                affiliate_type_into varchar;
                category_id_into int := 0;

                ------------------------------
                --- Declaración EXPLICITA del cursor
                cur_payroll_create_affiliate CURSOR for (SELECT * FROM dblink(db_name_intext,'SELECT id,car,pat,mat,nom,nom2,
                      niv,gra,cat,sue,criteria,affiliate_id,mes,a_o  FROM payroll_copy_transcripts
                      WHERE criteria=''6-CREAR''and affiliate_id is null and mes = '||month_copy||' and a_o = '||year_copy||'')
                      AS  payroll_copy_transcripts(id integer,car character varying(250),pat character varying(250),mat character varying(250),nom character varying(250),
                        nom2 character varying(250),niv character varying(250),gra character varying(250),cat NUMERIC(13,2),sue NUMERIC(13,2),criteria character varying(250), affiliate_id character varying(250),mes integer, a_o integer ));
                  begin
                  --************************************************************
                  --*Funcion para la creacion de affiliados
                  --************************************************************
                  -- Procesa el cursor
                  FOR record_row IN cur_payroll_create_affiliate LOOP
                     hierarchy_id_into := (SELECT get_hierarchy_id(record_row.niv,record_row.gra));
                     IF record_row.niv = '4' AND record_row.gra = '15' THEN
                        record_row.niv:= '3';
                     END IF;
                     hierarchy_id_into:= (SELECT id  FROM hierarchies  WHERE code::numeric = record_row.niv::numeric);
                     degree_id_into:= (SELECT id FROM degrees d  WHERE d.code::numeric = record_row.gra::numeric AND d.hierarchy_id = hierarchy_id_into);
                     category_id_into:= (SELECT get_category_id(record_row.cat,record_row.sue));
                     INSERT INTO affiliates (user_id, affiliate_state_id, first_name, second_name, last_name, mothers_last_name, identity_card, degree_id, category_id, gender, created_at, updated_at)
                     VALUES (user_id_reg, affiliate_state_id_reg_jub, record_row.nom, record_row.nom2, record_row.pat, record_row.mat, record_row.car, degree_id_into, category_id_into,'M', current_timestamp, current_timestamp);
                     id_affiliate:= (SELECT id  FROM affiliates a  WHERE a.identity_card = record_row.car) ;
                     ---Realizar Actualización del affiliate _id en la tabla payroll_copy_transcripts
                     update_affiliate:=  (SELECT dblink_exec(db_name_intext, 'UPDATE payroll_copy_transcripts SET affiliate_id='||id_affiliate||' WHERE payroll_copy_transcripts.id= '||record_row.id||''));  
                     ---Registro historial de creación del afiliado
                     message_into := 'Creación de Afiliado';
                     INSERT INTO affiliate_records(user_id, affiliate_id, degree_id, category_id, type_id, message,created_at, updated_at)
                     VALUES (user_id_reg, id_affiliate, degree_id_into, category_id_into, type_reg,message_into, current_timestamp, current_timestamp);
                     END LOOP;
                      --************************************************************
                      --* Importación de planillas
                      --************************************************************
                     FOR record_row_payroll IN (SELECT * FROM dblink(db_name_intext,'SELECT id, uni, mes, a_o, car, pat, mat, nom, nom2, niv, gra, sue, cat, gan, mus, est, carg, fro, ori, affiliate_id, criteria FROM payroll_copy_transcripts
                      WHERE  mes = '||month_copy||' and a_o = '||year_copy||'') 
                     AS  payroll_copy_transcripts(id integer, uni character varying(250), mes integer, a_o integer, car character varying(250), pat character varying(250), mat character varying(250), nom character varying(250), nom2 character varying(250),
                         niv character varying(250), gra character varying(250), sue NUMERIC(13,2), cat NUMERIC(13,2), gan NUMERIC(13,2), mus NUMERIC(13,2), est NUMERIC(13,2), carg NUMERIC(13,2), fro NUMERIC(13,2), ori NUMERIC(13,2), affiliate_id character varying(250), criteria character varying(250))) LOOP
                         IF record_row_payroll.niv = '4' AND record_row_payroll.gra = '15' THEN
                            record_row_payroll.niv:= '3';
                         END IF;
                         IF record_row_payroll.criteria = '6-CREAR' THEN
                            affiliate_type_into:= 'NUEVO';
                         ELSE
                            affiliate_type_into:= 'REGULAR';
                         END IF;
                         hierarchy_id_into:= (SELECT id  FROM hierarchies  WHERE code::numeric = record_row_payroll.niv::numeric);
                         degree_id_into:= (SELECT id FROM degrees d  WHERE d.code::numeric = record_row_payroll.gra::numeric AND d.hierarchy_id = hierarchy_id_into);
                         category_id_into:= (SELECT get_category_id(record_row_payroll.cat,record_row_payroll.sue));
                         INSERT INTO payroll_transcripts(affiliate_id,month_p, year_p, identity_card, last_name, mothers_last_name, first_name, second_name, hierarchy_id, degree_id,category_id, base_wage, seniority_bonus, gain, total, study_bonus, position_bonus, border_bonus, east_bonus, affiliate_type, created_at, updated_at) 
                         VALUES(record_row_payroll.affiliate_id::bigint, record_row_payroll.mes, record_row_payroll.a_o, record_row_payroll.car, record_row_payroll.pat, record_row_payroll.mat, record_row_payroll.nom, record_row_payroll.nom2, hierarchy_id_into, degree_id_into, category_id_into, record_row_payroll.sue, record_row_payroll.cat, 
                         record_row_payroll.gan, record_row_payroll.mus, record_row_payroll.est, record_row_payroll.carg, record_row_payroll.fro, record_row_payroll.ori, affiliate_type_into, current_timestamp, current_timestamp);
                     END LOOP;
                      RETURN TRUE;
                   END;
            $$;");

DB::statement("CREATE OR REPLACE FUNCTION public.get_retirement_fund_amount_transcript(date_period date, total numeric)
RETURNS numeric
LANGUAGE plpgsql
AS $$
    declare
        cr_retirement_fund numeric:=0;
        retirement_fund_into numeric:=0;
        cr_mortuary_quota numeric:=0;
        cr_fcsspn numeric:=0;
    begin
    --*****************************************************************************************--
    --Funcion para obtener monto de fondo de retiro consierando solo el total aporte------------
    --*****************************************************************************************--
        select retirement_fund into cr_retirement_fund from contribution_rates cr where month_year = date_period limit 1;
        select mortuary_quota into cr_mortuary_quota from contribution_rates cr where month_year = date_period limit 1;
        select fcsspn into cr_fcsspn from contribution_rates cr where month_year = date_period limit 1;

        if(date_period > '1987-04-01') then
            retirement_fund_into:= (total * cr_retirement_fund)/(cr_retirement_fund + cr_mortuary_quota);
        else
            retirement_fund_into:= (total * cr_retirement_fund)/(cr_retirement_fund + cr_fcsspn);
        end if;
    return round(retirement_fund_into,2);
    end;
  $$;");

DB::statement("CREATE OR REPLACE FUNCTION public.import_period_contribution_transcript(date_period date, user_id_into integer, year_period integer, month_period integer)
RETURNS numeric
LANGUAGE plpgsql
AS $$
                 declare
                     acction varchar;
                     quotable_into numeric:=0;
                     percentage numeric:=0;
                     num_import int:=0;
                     retirement_fund_amount numeric:=0;
                     mortuary_quota_amount numeric:=0;
                     contribution_id bigInt:=0;
                             -- Declaración EXPLICITA del cursor
                             cur_contribution CURSOR FOR select * from payroll_transcripts where year_p = year_period and month_p = month_period and total >0;
                             record_row payroll_transcripts%ROWTYPE;
                         begin
                            --***************************************
                            --Funcion importar planilla transcrita--
                            --***************************************
                            -- Procesa el cursor
                            FOR record_row IN cur_contribution loop
                              contribution_id := (select id from contributions where affiliate_id = record_row.affiliate_id and month_year = date_period and deleted_at is null);
                               quotable_into:= 0;
                               retirement_fund_amount  := 0;
                               mortuary_quota_amount:= 0;

                               retirement_fund_amount :=  get_retirement_fund_amount_transcript(date_period,record_row.total);
                               if(date_period > '1987-04-01') then
                                mortuary_quota_amount:= record_row.total - retirement_fund_amount;
                               end if;

                               if contribution_id is null then
                                    INSERT INTO contributions (
                                    user_id,affiliate_id,degree_id,
                                    --unit_id,
                                    --breakdown_id,
                                    category_id,
                                    month_year,type,base_wage,seniority_bonus,
                                    study_bonus,position_bonus,border_bonus,east_bonus,
                                    gain,
                                    --payable_liquid,
                                    quotable,
                                    retirement_fund,mortuary_quota,total,
                                    created_at,updated_at,contributionable_type,contributionable_id)
                                    VALUES (
                                    user_id_into,
                                    record_row.affiliate_id,
                                    record_row.degree_id,
                                    --record_row.unit_id,
                                    --record_row.breakdown_id,
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
                                    --record_row.payable_liquid,
                                    quotable_into,
                                    retirement_fund_amount,
                                    mortuary_quota_amount,
                                    record_row.total,
                                    current_timestamp,
                                    current_timestamp,
                                    'payroll_transcripts',
                                    record_row.id);
                                    num_import:=num_import+1;
                              else 
                                UPDATE contributions
                                  set contributionable_type = 'payroll_transcripts',
                                  contributionable_id = record_row.id,
                                  retirement_fund = retirement_fund_amount,
                                  mortuary_quota = mortuary_quota_amount,
                                  updated_at = current_timestamp,
                                  total  = case WHEN total  = 0 THEN record_row.total else total end
                                 where id = contribution_id;
                                num_import:=num_import+1;
                               end if;
                            END LOOP;
                            acction:='Importación realizada con éxito';
                            RETURN num_import;
                        end;
                 $$
;");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
