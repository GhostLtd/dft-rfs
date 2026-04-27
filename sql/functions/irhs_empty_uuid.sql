CREATE FUNCTION irhs_empty_uuid(seed VARCHAR(128), ctx VARCHAR(32))
RETURNS CHAR(36)
DETERMINISTIC
BEGIN
  -- 16-byte binary from MD5
  DECLARE d VARBINARY(16);
  DECLARE b7 INT;
  DECLARE b9 INT;

  SET d  = UNHEX(MD5(CONCAT(ctx, seed)));

  -- Bytes are 1-indexed in SUBSTR; PHP used data[6] and data[8] (0-indexed)
  -- so here we modify positions 7 and 9.
  SET b7 = (ORD(SUBSTR(d, 7, 1)) & 15) | 64;   -- version 4
  SET b9 = (ORD(SUBSTR(d, 9, 1)) & 63) | 128;  -- variant 10

  -- Rebuild d with modified bytes 7 and 9
  SET d = CONCAT(
    SUBSTR(d, 1, 6),
    CHAR(b7),
    SUBSTR(d, 8, 1),
    CHAR(b9),
    SUBSTR(d,10)   -- from byte 10 to end
  );

  -- Format as 8-4-4-4-12 hex with hyphens
  RETURN LOWER(CONCAT(
    SUBSTR(HEX(d),  1, 8), '-',  -- 4 bytes
    SUBSTR(HEX(d),  9, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 13, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 17, 4), '-',  -- 2 bytes
    SUBSTR(HEX(d), 21, 12)       -- 6 bytes
  ));
END
