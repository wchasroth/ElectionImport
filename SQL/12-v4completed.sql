DROP   TABLE IF EXISTS v4completed;
CREATE TABLE           v4completed (
   type  varchar(10) NOT NULL DEFAULT '',  index(type),
   id    int         NOT NULL DEFAULT  0,  index(district),
   primary key (type, district)
);
