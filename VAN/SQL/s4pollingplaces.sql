DROP   TABLE IF EXISTS s4pollingplaces;

CREATE TABLE           s4pollingplaces (
   van_precinct_id  int         NOT NULL, index(van_precinct_id),
   location         varchar( 60) NOT NULL DEFAULT '',
   address          varchar(100) NOT NULL DEFAULT '',
   lat              varchar( 20) NOT NULL DEFAULT '',
   lng              varchar( 20) NOT NULL DEFAULT ''
);
