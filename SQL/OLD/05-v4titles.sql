DROP   TABLE IF EXISTS v4titles;

CREATE TABLE v4titles LIKE title26;

INSERT INTO  v4titles SELECT * FROM title26;

UPDATE v4titles SET seats = 7 WHERE org='crt-sup';

UPDATE v4titles SET org    = 'mi-prop' WHERE org='mi' AND office='prop';
UPDATE v4titles SET office = ''        WHERE org  IN ('mi', 'mi-ag', 'mi-lt', 'mi-sos', 'mi-boe');
UPDATE v4titles SET office = ''        WHERE org='us';

UPDATE v4titles SET shortname = "Board" WHERE org='comcol-cou';
