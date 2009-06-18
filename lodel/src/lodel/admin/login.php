<?php
/**
 * Fichier de Login
 *
 * PHP versions 4 et 5
 *
 * LODEL - Logiciel d'Edition ELectronique.
 *
 * Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
 * Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * Copyright (c) 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * Copyright (c) 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * Copyright (c) 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
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
 * @copyright 2001-2002, Ghislain Picard, Marin Dacos
 * @copyright 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
 * @copyright 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
 * @copyright 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
 * @copyright 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
 * @copyright 2007, Marin Dacos, Bruno C�nou, Sophie Malafosse, Pierre-Alain Mignot
 * @copyright 2008, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @copyright 2009, Marin Dacos, Bruno C�nou, Pierre-Alain Mignot, In�s Secondat de Montesquieu, Jean-Fran�ois Rivi�re
 * @licence http://www.gnu.org/copyleft/gpl.html
 * @version CVS:$Id:
 * @package lodel/source/lodel/admin
 */
define('backoffice', true);
require 'siteconfig.php';

try
{
    include 'auth.php';
    
    $login = C::get('login');
    
    if($login && C::get('passwd') && C::get('passwd2')) {
        include 'loginfunc.php';
        $retour = change_passwd(C::get('datab'), $login, C::get('old_passwd'), C::get('passwd'), C::get('passwd2'));
        switch($retour)
        {
            case true:
                // on relance la proc�dure d'identification
                if (!check_auth(C::get('login'), C::get('passwd'), C::get('site', 'cfg'))) {
                    C::set('error_login', 1);
                } else {
                    //V�rifie que le site est bloqu� si l'utilisateur est pas lodeladmin
                    if(C::get('rights', 'lodeluser') < LEVEL_ADMINLODEL) {
                        usemaindb();
                        C::set('site_bloque', $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='".C::get('site', 'cfg')."' AND status >= 32")));
                        usecurrentdb();
                        if(C::get('site_bloque') == 1) {
                            C::set('error_site_bloque', 1);
                            break;
                        }
                    }
                    // et on ouvre une session
                    $err = open_session(C::get('login'));
                    if ((string)$err === 'error_opensession') {
                        C::set($err, 1);
                        break;
                    } else {
                        check_internal_messaging();
                        header ("Location: http://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
                    }
                }
                break;
            case 'error_passwd':
                C::set('suspended', 1);
                break;
            case false: // bad login/passwd
            default: 
                C::set('error_login', 1);
                break;
        }
    } elseif ($login) {
        include 'loginfunc.php';
        do {
            if (!check_auth(C::get('login'), C::get('passwd'))) {
                C::set('error_login', 1);
                break;
            }
            //V�rifie que le site est bloqu� si l'utilisateur est pas lodeladmin
            if(C::get('rights', 'lodeluser') < LEVEL_ADMINLODEL) {
                usemaindb();
                C::set('site_bloque', $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='".C::get('site', 'cfg')."' AND status >= 32")));
                usecurrentdb();
                if(C::get('site_bloque') == 1) {
                    C::set('error_site_bloque', 1);
                    break;
                }
            }
            //v�rifie que le compte n'est pas en suspend. Si c'est le cas, on am�ne l'utilisateur � modifier son mdp, sinon on l'identifie
            if(!check_suspended()) {
                C::set('suspended', 1);
                break;
            }
            else {
                // ouvre une session
                if ((string)open_session(C::get('login')) === 'error_opensession') {
                    C::set('error_opensession', 1);
                    break;
                }
            }
            check_internal_messaging();
            header ("Location: http://". $_SERVER['SERVER_NAME']. ($_SERVER['SERVER_PORT'] != 80 ? ':'. $_SERVER['SERVER_PORT'] : ''). C::get('url_retour'));
        } while (0);
    }
    
    C::set('passwd', null);
    C::set('passwd2', null);
    C::set('old_passwd', null);
    // variable: sitebloque
    /*if ($context['error_sitebloque']) { // on a deja verifie que la site est bloque.
        $context['site_bloque'] = 1;
    } else { // test si le site est bloque dans la DB.
        
        usemaindb();
        $context['site_bloque'] = $db->getOne(lq("SELECT 1 FROM #_MTP_sites WHERE name='$site' AND status >= 32"));
        usecurrentdb();
    }*/
    
    View::getView()->render('login');
}
catch(Exception $e)
{
	echo $e->getContent();
	exit();
}
?>