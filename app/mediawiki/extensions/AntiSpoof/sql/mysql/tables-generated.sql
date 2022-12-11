-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: sql/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE /*_*/spoofuser (
  su_name VARCHAR(255) NOT NULL,
  su_normalized VARCHAR(255) DEFAULT NULL,
  su_legal TINYINT(1) DEFAULT NULL,
  su_error TEXT DEFAULT NULL,
  INDEX su_normname_idx (su_normalized, su_name),
  PRIMARY KEY(su_name)
) /*$wgDBTableOptions*/;
