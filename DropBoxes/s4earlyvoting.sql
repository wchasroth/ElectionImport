DROP   TABLE IF EXISTS s4earlyvoting;

CREATE TABLE           s4earlyvoting (
   id         int          NOT NULL AUTO_INCREMENT,   primary key(id),
   location varchar( 80) NOT NULL DEFAULT '',
   address  varchar(120) NOT NULL DEFAULT '',
   lng      varchar( 30) NOT NULL DEFAULT '',
   lat      varchar( 30) NOT NULL DEFAULT '',
   hours    varchar(600) NOT NULL DEFAULT ''
);
