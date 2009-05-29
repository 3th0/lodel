#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
#  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
#  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Micka�l Cixous, Sophie Malafosse
#  Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
#  Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
#  Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
#
#  Home page: http://www.lodel.org
#
#  E-Mail: lodel@lodel.org
#
#                            All Rights Reserved
#
#     This program is free software; you can redistribute it and/or modify
#     it under the terms of the GNU General Public License as published by
#     the Free Software Foundation; either version 2 of the License, or
#     (at your option) any later version.
#
#     This program is distributed in the hope that it will be useful,
#     but WITHOUT ANY WARRANTY; without even the implied warranty of
#     MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#     GNU General Public License for more details.
#
#     You should have received a copy of the GNU General Public License
#     along with this program; if not, write to the Free Software
#     Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.



CREATE TABLE IF NOT EXISTS #_MTP_sites (
	id		INT UNSIGNED NOT NULL auto_increment,
	title		VARCHAR(255) NOT NULL,
	subtitle	TINYTEXT,
	name		VARCHAR(64) NOT NULL,
	path		VARCHAR(64) NOT NULL,
	url		TINYTEXT NOT NULL,

	langdef		CHAR(2) NOT NULL,
	lang		VARCHAR(64) NOT NULL,

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_users (
	id												INT UNSIGNED NOT NULL auto_increment,
	username									VARCHAR(64) BINARY NOT NULL UNIQUE,
	passwd										VARCHAR(64) BINARY NOT NULL,
	lastname									VARCHAR(255),
	firstname 								VARCHAR(255),
	email											VARCHAR(255),

	lang											CHAR(5) NOT NULL,       # user lang
	userrights								TINYINT UNSIGNED DEFAULT '0' NOT NULL,
	gui_user_complexity									TINYINT UNSIGNED DEFAULT '64' NOT NULL,

	nickname									VARCHAR(64),
	function									VARCHAR(255),
	biography									MEDIUMTEXT,
	photo											VARCHAR(255),
	professional_website			VARCHAR(255),
	url_professional_website			VARCHAR(255),
	rss_professional_website	VARCHAR(255),
	personal_website					VARCHAR(255),
	url_personal_website					VARCHAR(255),
	rss_personal_website			VARCHAR(255),
	pgp_key										MEDIUMTEXT,
	alternate_email						VARCHAR(255),
	phonenumber								VARCHAR(15),
	im_identifier							VARCHAR(100),
	im_name										VARCHAR(64),

	rank											INT UNSIGNED DEFAULT '0' NOT NULL,
	status										TINYINT DEFAULT '1' NOT NULL,
	upd												TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_username (username)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_session (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(64) BINARY NOT NULL UNIQUE,
	iduser		INT UNSIGNED DEFAULT '0' NOT NULL,
	site		VARCHAR(64) BINARY NOT NULL,
	currenturl	MEDIUMBLOB,
	userrights	tinyint(3) unsigned NOT NULL default '0',
	context		TEXT,
	expire		INT,  # temps d'expiration entre deux access
	expire2		INT,  # expiration de cette session

	PRIMARY KEY (id),
	KEY index_name (name)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_urlstack (
	id		INT UNSIGNED NOT NULL auto_increment, # faudrait generer le probleme du overflow
	idsession	INT UNSIGNED DEFAULT '0' NOT NULL,
	url		MEDIUMBLOB NOT NULL, # url de retour de l'url en cours
	site		VARCHAR(64) BINARY NOT NULL,
	PRIMARY KEY (id),
	KEY index_idsession (idsession)
) _CHARSET_;


CREATE TABLE IF NOT EXISTS #_MTP_texts (
	id		INT UNSIGNED NOT NULL auto_increment,
	name		VARCHAR(255) NOT NULL,  # name
	contents	TEXT,                   # texte

	lang		CHAR(5) NOT NULL,       # text lang
	textgroup	VARCHAR(255) NOT NULL,   # text group

	status		TINYINT DEFAULT '1' NOT NULL,
	upd		TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_name (name),
	KEY index_lang (lang),
	KEY index_textgroup (textgroup)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_internal_messaging (
  `id` int(10) unsigned NOT NULL auto_increment,
  `idparent` int(10) unsigned NOT NULL,
  `iduser` varchar(255) default NULL,
  `recipient` longtext NOT NULL,
  `recipients` longtext NOT NULL,
  `subject` varchar(255) NOT NULL,
  `body` longtext NOT NULL,
  `incom_date` datetime NOT NULL,
  `cond` tinyint(1) NOT NULL default '0',
  `status` tinyint(4) NOT NULL default '0',
  `upd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  PRIMARY KEY  (`id`)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_translations (
	id			INT UNSIGNED NOT NULL auto_increment,
	lang			CHAR(5) NOT NULL,		# code of the lang
	title			TINYTEXT,
	textgroups		VARCHAR(255),

	translators		TEXT,
	modificationdate	DATE,
	creationdate		DATE,

	rank			INT UNSIGNED DEFAULT '0' NOT NULL,
	status			TINYINT DEFAULT '1' NOT NULL,
	upd			TIMESTAMP,

	PRIMARY KEY (id),
	KEY index_lang (lang)
) _CHARSET_;

CREATE TABLE IF NOT EXISTS #_MTP_mainplugins (
  `id` int(10) unsigned NOT NULL auto_increment,
  `name` varchar(64) character set utf8 collate utf8_bin NOT NULL,
  `upd` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `status` tinyint(4) NOT NULL default '0',
  `trigger_preedit` tinyint(1) NOT NULL default '0',
  `trigger_postedit` tinyint(1) NOT NULL default '0',
  `trigger_prelogin` tinyint(1) NOT NULL default '0',
  `trigger_postlogin` tinyint(1) NOT NULL default '0',
  `trigger_preauth` tinyint(1) NOT NULL default '0',
  `trigger_postauth` tinyint(1) NOT NULL default '0',
  `trigger_preview` tinyint(1) NOT NULL default '0',
  `trigger_postview` tinyint(1) NOT NULL default '0',
  `config` longtext NOT NULL,
  `hooktype` varchar(5) NOT NULL,
  `title` text NOT NULL,
  `description` longtext NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `name` (`name`)
) _CHARSET_;

# suppression de l'administrateur par defaut... c'est ger� par l'interface d'installation.
# Administrateur par defaut. mot de passe : admintmp
#REPLACE INTO #_MTP_users (username,passwd,nom,courriel,privilege) VALUES ('admintmp','f2a69cdb6e81c0cb25bd4fada535cccd','administrateur temporaire','',128);
