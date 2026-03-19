
Level 0: complete wipe & rebuild from the ph1/ph2 data we have
   v4initialize
   markImportedCounties

   This is typically a "do once" operation, unless we're intentionally
   rebuilding EVERYTHING from scratch for some (good) reason.

   The remaining levels are done cyclically, as we get in new county
   election data, or new sources of corrections for termlen, voteFor,
   or maxseats.

Level 1: parse any newly added data
   v4parse
   markImportedCounties

Level 2: completely rebuild the v4elections table.
   Includes fix-up scripts for missing termlen, voteFor, maxseats(?)

   v4elections

Level 3: Determine and apply all election winners,
   to offices that are not yet "complete".
      v4compress

      which runs:
         phase13stateCompressor.php
         phase13countyCompressor.php
         phase13cityCompressor.php
         phase13villageCompressor.php
         phase13schoolCompressor.php
         phase13collegeCompressor.php
