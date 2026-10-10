How to update VAN-supplied data about clerks, election day polling places,
early voting locations, and drop boxes.

On the server, in miv_dm_v4, VAN, run 'go'.  Download the generated tar file
to the laptop.  Run all of the below on the laptop.  Only when it has completed
successfully, upload the results and import into the production database.

1. Precincts.  Precincts are mostly static during election time.
   We should update it early in election season, each year.

      a. Backup current s4precincts table.

      b. Get the URL for the VAN precinct table, that maps county, juris, ward, pct => van_precinct_id.
         https://docs.google.com/spreadsheets/d/1YDSZdbTMKlpOeud_XCPYo7Oga2SxTxdhp92TR67LFiw/edit?gid=0#gid=0

      c. Download the data as TSV.  Make sure it matches the format from the previous data,
         see ei/VAN/DATA/2026-09-04-polling-precinctcodes.tsv

      d. Run ei/php/importPrecincts.php to wipe and rebuild the table.

2. Clerks.  This needs more experimentation and testing (and possibly some fixes
   to importClerks.php) before we proceed.  Use the existing s4clerks table.

3. Polling places.
      a. Backup s4pollingplaces

      b. Download most current data.  From the VAN spreadsheet at 
         https://docs.google.com/spreadsheets/d/1uuRfymuS3-Hxqyvw-KGAa8aUpnaXert6a_HltdyazbQ/edit?gid=114503377#gid=114503377

         download the "Locations" sheet as TSV.  Rename to 'locations.tsv'.

      c. Run ei/php/importPollingPlaces.php with locations.tsv as input.

4. Early voting locations.
      a. Backup s4earlyvoting and s4van2early.

      b. Download most current data.  From the (same) VAN spreadsheet at 
         https://docs.google.com/spreadsheets/d/1uuRfymuS3-Hxqyvw-KGAa8aUpnaXert6a_HltdyazbQ/edit?gid=114503377#gid=114503377

         download the "EV by Precinct" sheet as TSV.  Rename to 'early.tsv'.

      c. Recreate the s4earlyvoting table with ei/VAN/SQL/s4earlyvoting.sql

      d. Run ei/php/importEarlyVoting.php with early.tsv as input.

5. Drop boxes

      a. Backup s4dropboxes and s4van2drop.

      b. Download most current data.  From the (same) VAN spreadsheet at 
         https://docs.google.com/spreadsheets/d/1uuRfymuS3-Hxqyvw-KGAa8aUpnaXert6a_HltdyazbQ/edit?gid=114503377#gid=114503377

         download the "Dropbox by Precinct" sheet as TSV.  Rename to 'dropbox.tsv'.

      c. Recreate the s4dropboxes table with ei/VAN/SQL/s4dropboxes.sql

      d. Run ei/php/importDropboxes.php with dropbox.tsv as input.

6. Upload to server
      Run ei/VAN/upload script.

7. Import on server.

 

=============================================================================

Misc notes:

Finding a voter's drop-box(es):

Start with their county, juris_id, wardpct from the s4streets table.

Split ward/pct (wppp) into ward and pct.

From s4precincts, use county,juris,ward,pct to find precinct_id.

From s4van2drop, use (van_)precinct_id to find drop_id(s!)

From s4dropboxes, find all matching rows (address, directions, hours) for
   the matching drop_ids.
