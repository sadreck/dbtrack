CREATE TRIGGER {%NAME%} AFTER {%TYPE%} ON {%TABLE%} FOR EACH ROW
BEGIN

INSERT INTO dbtrack_actions (tablename, timeadded, actiontype)
VALUES('{%TABLE%}', UNIX_TIMESTAMP(), {%ACTION%});

SET @lastid = (SELECT LAST_INSERT_ID());

{%PRIMARYKEYS%}

{%INSERTS%}

END