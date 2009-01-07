-- This script deletes all unconfirmed users that are older than 48 hours.
DELETE FROM freech_user
WHERE status=2
AND updated<FROM_UNIXTIME(UNIX_TIMESTAMP() - 60*60*48)
AND id IN (SELECT user_id FROM freech_posting);
