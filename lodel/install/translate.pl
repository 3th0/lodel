#!/usr/bin/perl
#
#  LODEL - Logiciel d'Edition ELectronique.
#
#  Copyright (c) 2001-2002, Ghislain Picard, Marin Dacos
#  Copyright (c) 2003, Ghislain Picard, Marin Dacos, Luc Santeramo, Nicolas Nutten, Anne Gentil-Beccot
#  Copyright (c) 2004, Ghislain Picard, Marin Dacos, Luc Santeramo, Anne Gentil-Beccot, Bruno C�nou
#  Copyright (c) 2005, Ghislain Picard, Marin Dacos, Luc Santeramo, Gautier Poupeau, Jean Lamy, Bruno C�nou
#  Copyright (c) 2006, Marin Dacos, Luc Santeramo, Bruno C�nou, Jean Lamy, Mika�l Cixous, Sophie Malafosse
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

#
# Usage: versionning.pl liste de fichier
#

my $offset=shift @ARGV;

unless ($offset=~/^\d+$/) {
  die ("preciser l'offset fournit par transfer.php\n");
}



foreach $filename (@ARGV) {

#lecture du fichier
  open (TXT,$filename);
  $file=join '',<TXT>;
  close (TXT);

  $change=0;
# changement pour passer en version 5
  $change+= $file=~s/(<\/?)boucle\b/$1LOOP/gi;
  $change+= $file=~s/(<\/?)avant\b/$1BEFORE/gi;
  $change+= $file=~s/(<\/?)apres\b/$1AFTER/gi;
  $change+= $file=~s/(<\/?)premier\b/$1DOFIRST/gi;
  $change+= $file=~s/(<\/?)dernier\b/$1DOLAST/gi;
  $change+= $file=~s/(<\/?)first\b/$1DOFIRST/gi;
  $change+= $file=~s/(<\/?)last\b/$1DOLAST/gi;
  $change+= $file=~s/(<\/?)corps\b/$1DO/gi;
  $change+= $file=~s/(<\/?)sinon\b/$1ALTERNATIVE/gi;
  $change+= $file=~s/<ALTERNATIVE\/>(.*?)<\/LOOP>/$a=$1; if ($a=~m!<LOOP>!) { $&; print STDERR "Attention un alternatif n'a pas ete gere\n"; } else { "<ALTERNATIVE>$a<\/ALTERNATIVE><\/LOOP>"} /gise;
  $change+= $file=~s/(<\/?)texte\b/$1TEXT/gi;
  $file=~s/<if\s+([^>]+)\s*>/$b=$&; $a=$1; if ($a=~m!COND=!) { $b; } else {  $change++; $a=~y!\"!'!; "<IF COND=\"$a\">"; }/gei;

#
  $change+=$file=~s/(WHERE\s*=\s*\"[^\"]*)type_periode([^\"]*\")/$1type='periode'$2/g;
  $change+=$file=~s/(WHERE\s*=\s*\"[^\"]*)type_geographie([^\"]*\")/$1type='geographie'$2/g;
  $change+=$file=~s/(TABLE=\"indexls\")/TABLE=\"entrees\" WHERE=\"type='motcle'\"/g;
  $change+=$file=~s/idindexl/identree/g;
  $change+=$file=~s/\[\#MOT\]/[\#NOM]/g;

# chgt de auteur en personne
  $change+=$file=~s/(TABLE=\"auteurs\")/TABLE=\"personnes\" WHERE=\"type='auteur'\"/g;
  $change+=$file=~s/(<LOOP[^>]+id)auteur([^>]+>)/$1personne$2/g;
# chgt du a la fusion publications documents
  $change+=$file=~s/\[\#PUBLICATION\]/[\#IDPARENT]/g;
  $change+=$file=~s/\[\#PARENT\]/[\#IDPARENT]/g;
  $change+=$file=~s/(<LOOP[^>]+TABLE\s*=\s*\"publications\"[^>]+\b(?:parent|id)='?)(\d+)/ $1.($2+$offset); /ge;
  $change+=$file=~s/(<LOOP[^>]+)\bparent\b([^>]+>)/$1idparent$2/g;


  $change+=$file=~s/\[\#SUPERADMIN\]/[\#ADMINLODEL]/g;
  $change+=$file=~s/\[GIF_VISAGE_SUPERADMIN\]/[GIF_VISAGE_ADMINLODEL]/g;

# changement de status en statut
  $change+=$file=~s/\[\#STATUS\]/[\#STATUT]/g;
  $change+=$file=~s/\bstatus\b/statut/g;
  $change+=$file=~s/\bSTATUS\b/STATUT/g;

# changement theme en rubrique
  $change+=$file=~s/UN_SOUS_THEME/UNE_SOUS_RUBRIQUE/g;
  $change+=$file=~s/UN_THEME/UNE_RUBRIQUE/g;
  $change+=$file=~s/GRANDS_THEMES/GRANDES_RUBRIQUES/g;
  $change+=$file=~s/THEMES_PUBLIES/RUBRIQUES_PUBLIEES/g;
  $change+=$file=~s/THEME_PRECEDENT/RUBRIQUE_PRECEDENTE/g;
  $change+=$file=~s/THEME_SUIVANT/RUBRIQUE_SUIVANTE/g;
  $change+=$file=~s/DERNIER_THEME/DERNIERE_RUBRIQUE/g;
  $change+=$file=~s/THEME/RUBRIQUE/g;
  $change+=$file=~s/le\s*dernier\s*th[e|�]me/la derni�re rubrique/g;
  $change+=$file=~s/un\s*sous-th[e|�]me/une sous-rubrique/g;
  $change+=$file=~s/du\s*th[e|�]me/de la rubrique/g;
  $change+=$file=~s/le\s*th[e|�]me/la rubrique/g;
  $change+=$file=~s/un\s*th[e|�]me/une rubrique/g;
  $change+=$file=~s/th[e|�]me\s*pr�c�dent/rubrique pr�c�dente/g;
  $change+=$file=~s/th[e|�]me\s*suivant/rubrique suivante/g;
  $change+=$file=~s/th[e|�]me/rubrique/ig;

# convert the comment

  # protect the script tags
  $change+=$file=~s/\r//g;
  $change+=$file=~s/(<SCRIPT\b[^>]*>[\s\n]*)<!--+/$1/igs;
  $change+=$file=~s/--+>([\s\n]*<\/SCRIPT>)/$1/igs;
  # convert the HTML comment into Lodel comment
  $change+=$file=~s/<!--([^\[])/<!--[$1/g;
  $change+=$file=~s/([^\]])-->/$1]-->/g;
  # convert SCRIPT comment
  $change+=$file=~s/(<SCRIPT\b[^>]*>)/$1<!--/gi;
  $change+=$file=~s/(<\/SCRIPT>)/-->$1/gi;

# ajout de nom aux loop qui n'en ont pas
  $loopnb=1;
  ($name)=($filename=~m/(\w*)\.\w*$/);
  $change+=$file=~s/<LOOP\s+(NAME\s*=\s*""\s+){0,1}TABLE\s*=\s*/"<LOOP NAME=\"loop_".$name.$loopnb++."\" TABLE="/eg;

# changement de parent en idparent dans les WHERE
  $change+=$file=~s/<LOOP\s+([^>]*)WHERE\s*=\s*"parent\s*=\s*'(\[#ID\]|0)'"/<LOOP $1WHERE="idparent='$2'/g;

# changement de statut><=>... en statut eq|ne... dans les WHERE
  $change+=$file=~s/(WHERE\s*=\s*")([^"]*)"/$1.callback($2).'"'/ge;

# changement du nom des differents niveaux d'utilisateur
   $change+=$file=~s/\[\#ADMINLODEL\]/\[\#DROITADMINLODEL\]/g;
   $change+=$file=~s/\[\#ADMIN\]/\[\#DROITADMIN\]/g;
   $change+=$file=~s/\[\#EDITEUR\]/\[\#DROITEDITEUR\]/g;
   $change+=$file=~s/\[\#REDACTEUR\]/\[\#DROITREDACTEUR\]/g;
   $change+=$file=~s/\[\#VISITEUR\]/\[\#DROITVISITEUR\]/g;

# changement de meta_image en icone
   $change+=$file=~s/\[\#META_IMAGE\]/\[\#ICONE\]/g;

# changement de insert_template 
  $change+=$file=~s/<\?\s+insert_template\s*\("(\w+)"\);\s*\?>/<USE TEMPLATEFILE="$1">/g;

# changement de #COMMENTAIRE en #COMMENTAIREINTERNE
  $change+=$file=~s/\[\#COMMENTAIRE\]/\[\#COMMENTAIREINTERNE\]/g;

# changement de #REVUE en #SITE
  $change+=$file=~s/\[\#REVUE\]/\[\#SITE\]/g;

# changement des index
  $change+=$file=~s/auteurs\.html/personnes.html\?type=auteur/g;
  $change+=$file=~s/mots\.html/entrees.html\?type=motcle/g;
  $change+=$file=~s/geos\.html/entrees.html\?type=geographie/g;
  $change+=$file=~s/chronos\.html/entrees.html\?type=periode/g;

# changement de #LANG en #LANGUE
  $change+=$file=~s/\[\#LANG\]/\[\#LANGUE\]/g;

# changement des series en collection
  $change+=$file=~s/serie_hierarchique/collection/g;
  $change+=$file=~s/serie_lineaire/collection/g;

# changement de #TEXTEPUBLI
  $change+=$file=~s/COND="\[\#TEXTEPUBLIE\]"/COND="\[\#DATEPUBLI\] le today\(\)"/g;

#
  next unless $change;
  print "$filename:",$change,"\n";


# ecriture du fichier
  open (TXT,">$filename");
  print TXT $file;
  close (TXT);
}

sub callback {
  $res = $_[0];
  $res =~ s/=/ eq /g;
  $res =~ s/>=/ ge /g;
  $res =~ s/>/ gt /g;
  $res =~ s/<=/ le /g;
  $res =~ s/<>/ ne /g;
  $res =~ s/</ lt /g;
  $res =~ s/publication\s*(eq|=)\s*/idparent eq /g;
  return $res;
}
