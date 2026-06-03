CREATE FUNCTION uuid6_to_timestamp(uuid CHAR (36))
    RETURNS DATETIME(6)
    DETERMINISTIC
    NO SQL
BEGIN
  DECLARE ticks BIGINT UNSIGNED;   -- 100-ns intervals since 1582-10-15
  DECLARE unix_micros BIGINT;      -- microseconds since 1970-01-01 (UTC)

  -- Reject anything that isn't a version-6 UUID
  IF NOT IS_UUID(uuid) OR SUBSTRING(uuid, 15, 1) <> '6' THEN
    RETURN NULL;
  END IF;

  -- v6 keeps the timestamp most-significant-first, so concatenate in order
  SET ticks = CONV(CONCAT(
    SUBSTRING(uuid,  1, 8),   -- time_high
    SUBSTRING(uuid, 10, 4),   -- time_mid
    SUBSTRING(uuid, 16, 3)    -- time_low (skip version nibble at 15)
  ), 16, 10);

  -- 10 ticks per microsecond; shift the epoch from 1582-10-15 to 1970-01-01
  SET unix_micros = CAST(ticks DIV 10 AS SIGNED) - 12219292800000000;

  -- Add the microseconds straight onto the Unix epoch (no float, no TZ shift)
  RETURN DATE_ADD(CAST('1970-01-01 00:00:00' AS DATETIME(6)), INTERVAL unix_micros MICROSECOND);
END