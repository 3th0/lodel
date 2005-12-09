<?php
/**
 * Fichier de fonction IMAP
 *
 * PHP version 4
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno C�nou, Jean Lamy
 *
 * Home page: http://www.lodel.org
 *
 * E-Mail: lodel@lodel.org
 *
 * All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 675 Mass Ave, Cambridge, MA 02139, USA.
 *
 * @author Ghislain Picard
 * @author Jean Lamy
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Bruno C�nou, Jean Lamy
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel
 */

require_once "func.php";

/**
 * Retire les pi�ces jointes des mails sur une bo�te mail donn�e
 *
 * Cette fonction utilise les options lodelmail.host, lodelmail.user, lodelmail.passwd.
 *
 * @return le nombre de pi�ces jointes
 */
function checkmailforattachments()
{
	$options = getoption(array ("lodelmail.host", "lodelmail.user", "lodelmail.passwd"), "");
	if (count($options) != 3 || !$options['lodelmail.host']) {
		die('ERROR: To use this feature, you must create and fill the options host, user and passwd in the group lodelmail. See in the administration interface ');
	}

	list ($host, $port) = explode(":", $options['lodelmail.host']);
	$mailserver = "{".$host.":". ($port ? $port : "110")."/pop3}INBOX";
	$passwd = $options['lodelmail.passwd'];
	$user = $options['lodelmail.user'];

	$mbox = imap_open($mailserver, $user, $passwd);

	if ($mbox === false) {
		die(imap_last_error());
		return;
	}

	$nbattachment = 0;
	$nbmsg = imap_num_msg($mbox);

	for ($msgno = 1; $msgno <= $nbmsg; $msgno ++) {
		$nbattachment += extractattachments($mbox, $msgno, "(je?pg|png|gif|tiff|sxw|doc|rtf|html?)");
		imap_delete($mbox, $msgno);
	}
	imap_expunge($mbox);

	return $nbattachment;
}

/**
 * Extrait les pi�ces jointes des mails d'une bo�te donn�e
 *
 * @param object $mbox la bo�te mail
 * @param integer $mnum le num�ro du mail
 * @param string $extre extension accept�es
 * @param integer $struct par d�faut 0.la structure des pi�ces jointes
 * @param integer $pno par d�faut vide la partie des attachements (cas des mails multiparts)
 *
 */
function extractattachments($mbox, $mnum, $extre, $struct = 0, $pno = "")
{
	$nbattachment = 0;
	if ($struct === 0) {
		$struct = imap_fetchstructure($mbox, $mnum);
	}
	switch ($struct->type) {
	case 1 : // multipart
		// look for the subpart
		$partno = 1;
		if ($pno) {
			$pno .= ".";
		}
		while (list ($j) = each($struct->parts)) {
			$nbattachment += extractattachments($mbox, $mnum, $extre, $struct->parts[$j], $pno.$partno);
			$partno ++;
		}
		break;
	case 2 : // message
		// decode
		$nbattachment += extractattachments($mbox, $mnum, $extre, $struct->parts[0], $pno);
		break;
	case 5 :
	default : // other
		// fetch the body of the part
		$body = imap_fetchbody($mbox, $mnum, $pno);

		// dcode
		if ($struct->encoding == 3) {
			$body = imap_base64($body);
		}
		elseif ($struct->encoding == 4) {
			$body = imap_qprint($body);
		}

		// get the filename
		if ($struct->parameters[0]->attribute == "NAME") {
			$filename = $struct->parameters[0]->value;
		}	else {
			return; // no filename don't download
		}
		$filename = preg_replace("/[^\w\.]/", "_", $filename);
		$extpos = strrpos($filename, ".");
		$ext = substr($filename, $extpos);

		// check the extension is valid
		if (!preg_match("/^\.".$extre."$/i", $ext)) {
			return;
		}

		if (strlen($filename) > 127) { // limit the length of the filename
			$filename = substr($filename, 0, 127 - strlen($ext)).$ext;
		}

		// save the attachment as $filename
		writefile(SITEROOT."CACHE/upload/".$filename, $body);
		$nbattachment ++;
		break;
	}
	return $nbattachment;
}
?>