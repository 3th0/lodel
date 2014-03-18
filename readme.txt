Ce mod�le remplace l�extension �Lodel stylist� pour OpenOffice qui ne fonctionne pas dans la version 3 d�OpenOffice. Il permet d�appliquer les styles d�clar�s dans le mod�le �ditorial distribu� avec la version 0.8, 0.9 et 1.0 de Lodel. Il fonctionne sous OpenOffice 3.3 et LibreOffice 3.3.

Il est distribu� en licence GPL sur SourceSup : https://sourcesup.cru.fr/frs/?group_id=193 (modele_revuesorg_OO.zip)
Les �volutions de ce mod�le seront annonc�es sur le blog de Lodel : http://blog.lodel.org/tag/modele

L�archive zip contient 3 fichiers :

    modele_revuesorg_fr.ott : le mod�le de document
    raccourcis_modele_revorg_fr.cfg : le fichier de configuration des raccourcis claviers permettant d�appliquer les styles de documents.
	readme.txt : ce fichier

Comment l�utiliser

    Dans OpenOffice writter, v�rifier le niveau de s�curit� pour l�execution des macros :  menu Outils > Options > Openoffice.org > S�curit� > S�curit� des macros > choisir �Niveau de s�curit� moyen�.
    Un double clic sur modele_revuesorg_fr.ott ouvre un nouveau document bas� sur le mod�le (autoriser l�ex�cution des macros, bien-s�r).
    Le menu  �Lodel� disponible dans la barre des menus permet d�appliquer les styles d�clar�s dans Lodel.
    Pour attacher les raccourcis claviers permettant d�appliquer les styles : menu Outils > Personnaliser > Clavier > Charger : choisir le fichier �raccourcis_modele_revorg_fr.cfg� et valider. Les raccourcis clavier sont alors actifs. Ils sont affich�s dans le menu �Lodel� en face des styles correspondants.

Restrictions connues

    La touche �alt� n�est disponible dans les raccrourcis clavier d�OpenOffice que depuis la version 3.2. Pour les versions ant�rieures, la plupart des raccourcis clavier ne sont pas disponibles.
    Dans OpenOffice, les images ne sont pas n�cessairement contenues dans un paragraphe distinct. Il faut veiller � ins�rer un paragraphe styl� en �Standard� ou en �Annexe� et contenant l�ancre de l�image et ancrer l'image comme caract�re : options de l'image (double-clic sur l'image) : onglet type : ancrer comme caract�re.

    Importation des documents dans Lodel 0.8 ou 0.9 (ServOO) :
	- Les listes � puces sont interpr�t�es par Servoo (Lodel 0.8 et 0.9) comme des listes ordonn�es : les listes � puces seront affich�es dans Lodel comme des listes num�rot�es. Les listes � puces sont correctement interpr�t�es par OpenText (Lodel 1.x)
	- Il faut enregistrer le document au format sxw

	Importation des documents dans Lodel 1.x (OpenText) :
    - Les listes � puces sont correctement interpr�t�es par OpenText (Lodel 1.x).
    - Tous les formats de fichiers compatibles avec OpenOffice 3 sont reconnus. Il est cependant pr�f�rable d'utiliser le format odt.

Cr�dits
Matthieu Heuz�, Jean-Fran�ois Rivi�re

Ce mod�le est distribu� en licence GPL. Merci de faire �tat de vos essais, qu�ils soient fructueux ou non, sur la liste lodel-users (https://listes.cru.fr/sympa/info/lodel-users).

Personnalisation du mod�le
Il est bien-s�r possible d�ajouter d�autres styles correspondant � un autre mod�le �ditorial.

Le principe de ce mod�le de document est le suivant :

    le mod�le de document contient des styles de paragraphes dont les noms sont d�clar�s dans le mod�le �ditorial de Lodel ;
    le mod�le contient des macros qui appliquent ces styles (un macro, tr�s simple, par style) ;
    le mod�le contient enfin un menu personnalis� qui permet d�ex�cuter ces macros.

Les raccourcis clavier permettant d�ex�cuter les macros ne peuvent �tre enregistr�es dans le mod�le. C�est pour cette raison qu�il faut les charger depuis un fichier diff�rent.

Pour ajouter un style pour un autre mod�le �ditorial au menu Lodel :

    Ouvrez le mod�le de document dans OpenOffice 3.2 (veillez � ouvrir le mod�le de document, pas un nouveau document bas� sur le mod�le).
    Ajoutez un style dans le mod�le de document (dans la fen�tre �Styles et formatage�).
    Enregistrez une nouvelle macro qui applique ce style : �Outils� > �Macros� > �Enregistrez un macro� puis appliquer le style et cliquez sur �Terminer l�enregistrement� et enregistrez cette macro dans le mod�le : modele_revuesorg_fr.ott > Lodel > Module1 en lui donnant si possible un nom explicite.
    Pour ajouter cette macro au menu Lodel : Outils > Personnaliser > Menus. Choisissez le menu �Lodel� ou un de ses sous-menus. Cliquez sur �Ajouter� et s�lectionnez la macro que vous venez de cr�er puis �Fermer� et Validez.
    Enregistrez votre mod�le. C�est fait.

Si vous souhaitez associer un raccourci clavier � une macro, vous pouvez suivre ce guide tr�s explicite : http://wiki.services.openoffice.org/wiki/FR/Documentation/Writer_Guide/Assignation_raccourcis

La sauvegarde des raccourcis semble ne pas fonctionner sans OpenOffice 3.2 (le fichier produit �tait vide). Elle fontcionne tr�s bien dans LibreOffice 3.2.

