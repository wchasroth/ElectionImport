/* Manual fix-ups for Manchester's conversion from village to city (Washtenaw County) */

DELETE FROM v4elections WHERE org IN ('vil', 'vil-cou') AND district=50660;
DELETE FROM v4elections WHERE org='city-cou'            AND district=50660 AND name='Cynthia Dresch' AND year='2023-11-07';
DELETE FROM v4elections WHERE org='city-cou'            AND district=50660 AND name='Steven Harvey'  AND year='2023-11-07';

DELETE FROM v4elections WHERE org='city-cou'            AND district=50660 AND name='Herb Mahony';
DELETE FROM v4elections WHERE org='city-cou'            AND district=50660 AND name='Patrick J. DuRussel';
