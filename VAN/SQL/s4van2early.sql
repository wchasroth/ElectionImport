DROP   TABLE IF EXISTS s4van2early;

CREATE TABLE           s4van2early (
   van_precinct_id   int          NOT NULL, index(van_precinct_id),
   early_id          int
);
