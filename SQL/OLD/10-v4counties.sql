DROP   TABLE IF EXISTS v4counties;

CREATE TABLE v4counties LIKE          county;

INSERT INTO  v4counties SELECT * FROM county;
