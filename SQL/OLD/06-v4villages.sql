DROP   TABLE IF EXISTS v4villages;

CREATE TABLE v4villages LIKE village;

INSERT INTO  v4villages SELECT * FROM village;
