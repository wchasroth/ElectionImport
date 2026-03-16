DROP   TABLE IF EXISTS v4commcolleges;

CREATE TABLE v4commcolleges LIKE comm_college26;

INSERT INTO  v4commcolleges SELECT * FROM comm_college26;

ALTER  TABLE v4commcolleges ADD COLUMN complete tinyint NOT NULL DEFAULT 0;
