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
  password      varchar(100)        NOT NULL,
  firstname     varchar(100)        NOT NULL,
  lastname      varchar(100)        NOT NULL,
  mail          varchar(200)        NOT NULL,
  homepage      varchar(255),
  im            varchar(100),
  signature     varchar(255),
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
  FOREIGN KEY (g_id) REFERENCES tefinch_group(id)      ON DELETE CASCADE,
  FOREIGN KEY (p_id) REFERENCES tefinch_permission(id) ON DELETE CASCADE
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
  FOREIGN KEY (g_id) REFERENCES tefinch_group(id) ON DELETE CASCADE,
  FOREIGN KEY (u_id) REFERENCES tefinch_user(id)  ON DELETE CASCADE
) TYPE=innoDB;


CREATE TABLE tefinch_forum (
  id            int(11)    unsigned auto_increment,
  name          varchar(100)        NOT NULL,
  description   varchar(255)        NOT NULL,
  active        tinyint(3) unsigned DEFAULT '1',
  ownerid       int(11)    unsigned,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  PRIMARY KEY (id),
  INDEX(ownerid),
  FOREIGN KEY (ownerid) REFERENCES tefinch_user(id) ON DELETE SET NULL
) TYPE=innoDB;


CREATE TABLE tefinch_message (
  id            int(11)    unsigned auto_increment,
  forumid       int(11)    unsigned NOT NULL,
  threadid      int(11)    unsigned NOT NULL,
  n_children    int(11)    unsigned DEFAULT 0,
  n_descendants int(11)    unsigned DEFAULT 0,
  path          varbinary(255),
  u_id          int(11)    unsigned DEFAULT 0,
  name          varchar(255)        NOT NULL,
  title         varchar(255)        NOT NULL,
  text          text                NOT NULL,
  updated       TIMESTAMP,
  created       TIMESTAMP,
  active        tinyint(3) unsigned DEFAULT '1',
  PRIMARY KEY (id),
  INDEX(forumid),
  INDEX(threadid),
  INDEX(u_id),
  FOREIGN KEY (forumid) REFERENCES tefinch_forum(id) ON DELETE CASCADE,
  FOREIGN KEY (u_id)    REFERENCES tefinch_user(id)  ON DELETE SET NULL
) TYPE=innoDB;


INSERT INTO tefinch_user (id, login, password, firstname, lastname, mail, created)
                  VALUES (1, 'root', '', 'root', 'root', '', NULL);
INSERT INTO tefinch_user (id, login, password, firstname, lastname, mail, created)
                  VALUES (2, 'anonymous', '', 'Anonymous', 'George', '', NULL);

INSERT INTO tefinch_forum (name, description, ownerid, created)
                   VALUES ('Forum', 'Default forum', 1, NULL);
