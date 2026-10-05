DROP   TABLE IF EXISTS s4dropboxes;

CREATE TABLE           s4dropboxes (
   id         int          NOT NULL AUTO_INCREMENT,   primary key(id),
   address    varchar(120) NOT NULL DEFAULT '',
   hours      varchar(120) NOT NULL DEFAULT '',
   directions varchar(120) NOT NULL DEFAULT ''
);
