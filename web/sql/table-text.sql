-- List of text associated with users
CREATE TABLE text (
	text_user INT UNSIGNED NOT NULL,
	text_content TEXT,

	-- One text per user max
	PRIMARY KEY(text_user)
)