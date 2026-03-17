DROP   TABLE IF EXISTS v4jurisdictions;

CREATE TABLE v4jurisdictions LIKE jurisdiction;

INSERT INTO  v4jurisdictions SELECT * FROM jurisdiction;
