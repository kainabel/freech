CREATE TABLE tefinch_forum (
  id            int(11)    unsigned auto_increment,
  name          varchar(100)        NOT NULL,
  description   varchar(255)        NOT NULL,
  active        tinyint(3) unsigned DEFAULT '1',
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id)
) TYPE=innoDB;


CREATE TABLE tefinch_message (
  id            int(11)    unsigned auto_increment,
  forumid       int(11)    unsigned NOT NULL,
  threadid      int(11)    unsigned NOT NULL,
  n_children    int(11)    unsigned DEFAULT 0,
  n_descendants int(11)    unsigned DEFAULT 0,
  path          varbinary(255),
  name          varchar(255)        NOT NULL,
  title         varchar(255)        NOT NULL,
  text          text                NOT NULL,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  active        tinyint(3) unsigned DEFAULT '1',
  PRIMARY KEY (id),
  INDEX(forumid),
  INDEX(threadid),
  FOREIGN KEY (forumid) REFERENCES tefinch_forum(id) ON DELETE CASCADE
) TYPE=innoDB;


CREATE TABLE tefinch_group (
  id            int(11)    unsigned auto_increment,
  name          varchar(100)        NOT NULL,
  active        tinyint(3) unsigned DEFAULT '1',
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id)
) TYPE=innoDB;


CREATE TABLE tefinch_user (
  id            int(11)    unsigned auto_increment,
  login         varchar(100)        NOT NULL,
  firstname     varchar(100)        NOT NULL,
  lastname      varchar(100)        NOT NULL,
  mail          varchar(200)        NOT NULL,
  homepage      varchar(255)        NOT NULL,
  im            varchar(100)        NOT NULL,
  signature     varchar(255)        NOT NULL,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  lastlogin     TIMESTAMP,
  PRIMARY KEY (id)
) TYPE=innoDB;


CREATE TABLE tefinch_permission (
  id            int(11)    unsigned auto_increment,
  name          varchar(100)        NOT NULL,
  description   varchar(100)        NOT NULL,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id)
) TYPE=innoDB;


CREATE TABLE tefinch_group_permission (
  id            int(11)    unsigned auto_increment,
  g_id          int(11)    unsigned DEFAULT 0,
  p_id          int(11)    unsigned DEFAULT 0,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id),
  INDEX(g_id),
  INDEX(p_id),
  FOREIGN KEY (g_id) REFERENCES tefinch_group(id)      ON DELETE SET NULL,
  FOREIGN KEY (p_id) REFERENCES tefinch_permission(id) ON DELETE SET NULL
) TYPE=innoDB;


CREATE TABLE tefinch_group_user (
  id            int(11)    unsigned auto_increment,
  g_id          int(11)    unsigned DEFAULT 0,
  u_id          int(11)    unsigned DEFAULT 0,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id),
  INDEX(g_id),
  INDEX(u_id),
  FOREIGN KEY (g_id) REFERENCES tefinch_group(id) ON DELETE SET NULL,
  FOREIGN KEY (u_id) REFERENCES tefinch_user(id)  ON DELETE SET NULL
) TYPE=innoDB;


INSERT INTO tefinch_forum (name, description, created) VALUES ('Forum', 'Default forum', NULL);
