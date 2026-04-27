DROP   TABLE IF EXISTS v4commcolleges;

CREATE TABLE v4commcolleges LIKE comm_college26;

INSERT INTO  v4commcolleges SELECT * FROM comm_college26;
