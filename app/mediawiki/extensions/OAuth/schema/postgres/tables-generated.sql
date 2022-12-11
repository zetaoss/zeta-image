-- This file is automatically generated using maintenance/generateSchemaSql.php.
-- Source: schema/tables.json
-- Do not modify this file directly.
-- See https://www.mediawiki.org/wiki/Manual:Schema_changes
CREATE TABLE oauth_registered_consumer (
  oarc_id SERIAL NOT NULL,
  oarc_consumer_key TEXT NOT NULL,
  oarc_name TEXT NOT NULL,
  oarc_user_id INT NOT NULL,
  oarc_version TEXT NOT NULL,
  oarc_callback_url TEXT NOT NULL,
  oarc_callback_is_prefix TEXT DEFAULT NULL,
  oarc_description TEXT NOT NULL,
  oarc_email TEXT NOT NULL,
  oarc_email_authenticated TIMESTAMPTZ DEFAULT NULL,
  oarc_developer_agreement SMALLINT DEFAULT 0 NOT NULL,
  oarc_owner_only SMALLINT DEFAULT 0 NOT NULL,
  oarc_wiki TEXT NOT NULL,
  oarc_grants TEXT NOT NULL,
  oarc_registration TIMESTAMPTZ NOT NULL,
  oarc_secret_key TEXT DEFAULT NULL,
  oarc_rsa_key TEXT DEFAULT NULL,
  oarc_restrictions TEXT NOT NULL,
  oarc_stage SMALLINT DEFAULT 0 NOT NULL,
  oarc_stage_timestamp TIMESTAMPTZ NOT NULL,
  oarc_deleted SMALLINT DEFAULT 0 NOT NULL,
  oarc_oauth_version SMALLINT DEFAULT 1 NOT NULL,
  oarc_oauth2_allowed_grants TEXT DEFAULT NULL,
  oarc_oauth2_is_confidential SMALLINT DEFAULT 1 NOT NULL,
  PRIMARY KEY(oarc_id)
);

CREATE UNIQUE INDEX oarc_consumer_key ON oauth_registered_consumer (oarc_consumer_key);

CREATE UNIQUE INDEX oarc_name_version_user ON oauth_registered_consumer (
  oarc_name, oarc_user_id, oarc_version
);

CREATE INDEX oarc_user_id ON oauth_registered_consumer (oarc_user_id);

CREATE INDEX oarc_stage_timestamp ON oauth_registered_consumer (
  oarc_stage, oarc_stage_timestamp
);


CREATE TABLE oauth_accepted_consumer (
  oaac_id SERIAL NOT NULL,
  oaac_wiki TEXT NOT NULL,
  oaac_user_id INT NOT NULL,
  oaac_consumer_id INT NOT NULL,
  oaac_access_token TEXT NOT NULL,
  oaac_access_secret TEXT NOT NULL,
  oaac_grants TEXT NOT NULL,
  oaac_accepted TIMESTAMPTZ NOT NULL,
  oaac_oauth_version SMALLINT DEFAULT 1 NOT NULL,
  PRIMARY KEY(oaac_id)
);

CREATE UNIQUE INDEX oaac_access_token ON oauth_accepted_consumer (oaac_access_token);

CREATE UNIQUE INDEX oaac_user_consumer_wiki ON oauth_accepted_consumer (
  oaac_user_id, oaac_consumer_id, oaac_wiki
);

CREATE INDEX oaac_consumer_user ON oauth_accepted_consumer (oaac_consumer_id, oaac_user_id);

CREATE INDEX oaac_user_id ON oauth_accepted_consumer (oaac_user_id, oaac_id);


CREATE TABLE oauth2_access_tokens (
  oaat_id SERIAL NOT NULL,
  oaat_identifier VARCHAR(255) NOT NULL,
  oaat_expires TIMESTAMPTZ NOT NULL,
  oaat_acceptance_id INT NOT NULL,
  oaat_revoked SMALLINT DEFAULT 0 NOT NULL,
  PRIMARY KEY(oaat_id)
);

CREATE UNIQUE INDEX oaat_identifier ON oauth2_access_tokens (oaat_identifier);

CREATE INDEX oaat_acceptance_id ON oauth2_access_tokens (oaat_acceptance_id);