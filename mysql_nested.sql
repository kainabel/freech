CREATE TABLE tefinch1 (
  id       int(11)          auto_increment,
  threadid int(11) NOT NULL,
  lft      int(11) NOT NULL CHECK(lft > 0),
  rgt      int(11) NOT NULL UNIQUE CHECK(rgt > 1),
  name     varchar(255),
  title    varchar(255),
  text     text,
  updated  TIMESTAMP,
  created  TIMESTAMP,
  active   tinyint(3) unsigned DEFAULT '1',
  PRIMARY KEY (id),
  INDEX(id),
  INDEX(threadid),
  INDEX(lft),
  INDEX(rgt),
  CONSTRAINT order_okay CHECK (lft < rgt)
) TYPE=innoDB;

INSERT INTO tefinch1 (lft, rgt) VALUES (0, 1);
