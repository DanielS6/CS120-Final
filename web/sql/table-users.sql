-- List of all user accounts
CREATE TABLE users (
	user_id INT UNSIGNED AUTO_INCREMENT NOT NULL,
	user_email VARCHAR(255) DEFAULT '' NOT NULL,
	user_pass_hash VARCHAR(255) DEFAULT '' NOT NULL,
	-- Bitmask of any applicable flags
	user_flags TINYINT DEFAULT 0 NOT NULL,

	PRIMARY KEY(user_id),
	-- Cannot use the same email multiple times
	UNIQUE INDEX user_email(user_email)
)