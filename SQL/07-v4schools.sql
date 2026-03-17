DROP   TABLE IF EXISTS v4schools;

CREATE TABLE v4schools LIKE school_district;

INSERT INTO  v4schools SELECT * FROM school_district;
