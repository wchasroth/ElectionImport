DROP   TABLE IF EXISTS v4counties;

CREATE TABLE v4counties LIKE          county;

INSERT INTO  v4counties SELECT * FROM county;

ALTER TABLE  v4counties ADD COLUMN complete tinyint NOT NULL DEFAULT 0;
