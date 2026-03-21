DROP   TABLE IF EXISTS v4titles;

CREATE TABLE v4titles LIKE title26;

INSERT INTO  v4titles SELECT * FROM title26;

UPDATE       v4titles SET seats = 7 WHERE org='crt-sup';
