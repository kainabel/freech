CREATE TABLE tefinch (
  id            int(11)                 auto_increment,
  forumid       int(11)        NOT NULL,
  threadid      int(11)        NOT NULL,
  n_descendants int(11)        NOT NULL,
  path          varbinary(255),
  name          varchar(255)   NOT NULL,
  title         varchar(255)   NOT NULL,
  text          text           NOT NULL,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  active        tinyint(3) unsigned DEFAULT '1',
  PRIMARY KEY (id),
  INDEX(id),
  INDEX(path)
) TYPE=innoDB;
