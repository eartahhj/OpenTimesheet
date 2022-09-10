<?php
// NOTE: This is a legacy library that has not been refactored. It is used to create forms.

mb_internal_encoding('UTF-8');
mb_regex_encoding('UTF-8');

define('TIPO_STRINGA',1);
define('TIPO_INTERO',2);
define('TIPO_REALE',3);
define('TIPO_EMAIL',4);
define('TIPO_URL',5);
define('TIPO_DATA',6);
define('TIPO_DATAORA',7);
define('TIPO_ORA',8);
define('TIPO_TELEFONO',9);
define('TIPO_BOOLEANO',10);
define('TIPO_SINO',11);
define('TIPO_CODFISC',12);
define('TIPO_PIVA',13);
define('TIPO_CFOPIVA',14);
define('TIPO_JPEG',15);
define('TIPO_PNG',16);
define('TIPO_IMMAGINE',17);
define('TIPO_PDF',18);
define('TIPO_FILE',19);
define('TIPO_NOMEFILE',20);

define('CAMPO_INPUTTEXT',1);
define('CAMPO_CHECKBOX',2);
define('CAMPO_SELECT',3);
define('CAMPO_LISTARADIO',4);
define('CAMPO_RADIOSINO',5);
define('CAMPO_TEXTAREA',6);
define('CAMPO_HIDDEN',7);
define('CAMPO_INPUTPASSWORD',8);
define('CAMPO_FILE',9);
define('CAMPO_LISTACHECKBOX',10);

define('ERRORE_BLOCCO',1);
define('ERRORE_INLINE',2);

define('SELEZIONARE','Selezionare');

class Campo
{
 public $campo;
 public $valore;
 public $nome;
 public $tipo;
 public $obbligatorio=FALSE;
 public $default='';
 public $max=FALSE;
 public $min=FALSE;
 public $valori=FALSE;
 public $placeholder='';
 public $aiuto='';
 public $class='';
 public $nodb=FALSE;
 public $immagine=NULL;
 public $mime=NULL;
 public $method=NULL;
 public $pattern=NULL;
 public $disabled = false;
 public $autocomplete = '';

 function __construct($campo,$nome='',$tipo=TIPO_STRINGA,$opzioni=NULL)
 {
  global $_pagina;

  $this->campo=$campo;
  $this->nome=$nome;
  $this->tipo=$tipo;
  if(is_array($opzioni) and count($opzioni)>0 and isset($opzioni['method']) and is_array($opzioni['method'])) $this->method=&$opzioni['method'];
  else $this->method=&$_POST;
  if($tipo==TIPO_JPEG or $tipo==TIPO_PNG or $tipo==TIPO_IMMAGINE or $tipo==TIPO_PDF or $tipo==TIPO_FILE)
  {
   $this->valore=$_FILES[$campo];
   if($_FILES[$campo] and is_array($_FILES[$campo])) $this->mime=$_FILES[$campo]['type'];
  }
  else $this->valore=$this->method[$campo];
  if(is_array($opzioni) and count($opzioni)>0)
  {
   if($opzioni['valore']) $this->valore=(($opzioni['valore']===FALSE)?$this->method[$campo]:$opzioni['valore']);
   if($opzioni['obbligatorio']) $this->obbligatorio=TRUE;
   if($opzioni['default']) $this->default=$opzioni['default'];
   if($opzioni['max']) $this->max=$opzioni['max'];
   if($opzioni['min']) $this->min=$opzioni['min'];
   if($opzioni['valori']) $this->valori=$opzioni['valori'];
   if($opzioni['placeholder']) $this->placeholder=$opzioni['placeholder'];
   if($opzioni['aiuto']) { $this->aiuto=$opzioni['aiuto']; $_pagina->help=TRUE; }
   if($opzioni['class']) $this->class=$opzioni['class'];
   if($opzioni['pattern']) $this->pattern=$opzioni['pattern'];
   if($opzioni['nodb']) $this->nodb=$opzioni['nodb'];
   if($opzioni['disabled']) $this->disabled=$opzioni['disabled'];
   if($opzioni['autocomplete']) $this->autocomplete=$opzioni['autocomplete'];
  }
 }
}

class Campi
{
 public $campi;
 public $err;
 public $valoriDB;
 public $valoriPuri;
 public $dati=null;
 public $datiDB=FALSE;
 public $formID;
 public $corrente=FALSE;
 public $idCorrente=0;
 public $fileDir;
 public $fileURL;
 public $pari=FALSE;
 public $method=NULL;

 function __construct($formID,$classe='',$fileDir='',$fileURL='',$method=NULL)
 {
  $this->campi=array();
  $this->err=array();
  $this->valoriDB=array();
  $this->valoriPuri=array();
  $this->formID=$formID;
  if(isset($method) and is_array($method)) $this->method=&$method;
  else $this->method=&$_POST;
  if($this->method and $this->method['formID']==$this->formID) $this->corrente=TRUE;
  $this->fileDir=$fileDir;
  $this->fileURL=$fileURL;
  if($classe) {
      $this->dati=new $classe();
  } else {
      $this->dati = new \stdClass();
  }
 }

 function controllaValori()
 {
  $_IMAGETYPE2MIME=array(IMAGETYPE_JPEG=>'image/jpeg',IMAGETYPE_GIF=>'image/gif',IMAGETYPE_PNG=>'image/png');

  foreach($this->campi as $k=>$v)
  {
   unset($valore); unset($vDB);
   if($v->tipo==TIPO_FILE or $v->tipo==TIPO_PDF)
   {
    if(is_uploaded_file($v->valore['tmp_name']))
    {
     $mime=mime_content_type($v->valore['tmp_name']);
     if(!$mime) $mime=$v->valore['type'];
     $this->campi[$k]->mime=$mime;
     if($v->tipo==TIPO_PDF)
      if($mime!='application/pdf')
       $this->err[$v->campo]='Il file caricato non è in formato PDF';
    }
    continue;
   }
   if($v->tipo==TIPO_JPEG or $v->tipo==TIPO_PNG or $v->tipo==TIPO_IMMAGINE)
   {
    if($v->max) list($max_w,$max_h)=explode(',',$v->max);
    if($v->min) list($min_w,$min_h)=explode(',',$v->min);
    if(is_uploaded_file($v->valore['tmp_name']))
    {
     $is=getimagesize($v->valore['tmp_name']);
     if(!$is)
      $this->err[$v->campo]='L’immagine non è in un formato riconosciuto';
     else
     {
      list($i_w,$i_h,$i_t)=$is;
      if($v->max and ($i_w>$max_w or $i_h>$max_h))
       $this->err[$v->campo]="L’immagine è troppo grande (max {$max_w}x{$max_h}).";
      elseif($v->min and ($i_w<$min_w or $i_h<$min_h))
       $this->err[$v->campo]="L’immagine è troppo piccola (min. {$min_w}x{$min_h}).";
      else
      {
       if($v->tipo==TIPO_JPEG and $i_t!=IMAGETYPE_JPEG)
        $this->err[$v->campo]='L’immagine non è in formato JPEG.';
       if($v->tipo==TIPO_PNG and $i_t!=IMAGETYPE_PNG)
        $this->err[$v->campo]='L’immagine non è in formato PNG.';
       if($v->tipo==TIPO_IMMAGINE and $i_t!=IMAGETYPE_JPEG and $i_t!=IMAGETYPE_GIF and $i_t!=IMAGETYPE_PNG)
        $this->err[$v->campo]='L’immagine non è in un formato riconosciuto (JPEG, GIF o PNG).';
      }
      $this->campi[$k]->mime=$is['mime'];
      $this->campi[$k]->immagine=$is;
     }
    }
    continue;
   }
   $valore=trim($v->valore);
   if($v->tipo==TIPO_URL and $valore=='http://') $valore='';
   elseif($v->tipo==TIPO_TELEFONO and $valore=='+39') $valore='';
   if($v->obbligatorio and $valore==='')
   {
    $this->err[$v->campo]=_('Questo campo è obbligatorio');
    continue;
   }
   if($v->tipo==TIPO_INTERO)
   {
    if($valore and !mb_eregi('^-?[0-9]+$',$valore)) { $this->err[$v->campo]=_('Valore non valido'); continue; }
    if($valore==='') { $vDB='null'; $this->valoriPuri[$v->campo]=''; }
    else
    {
     $valore=(int)$valore;
     $vDB=$this->valoriPuri[$v->campo]=$valore;
     if($v->max!==FALSE and $valore>$v->max) { $this->err[$v->campo]=_('Valore troppo grande'); continue; }
     if($v->min!==FALSE and $valore<$v->min) { $this->err[$v->campo]=_('Valore troppo piccolo'); continue; }
    }
   }
   elseif($v->tipo==TIPO_REALE)
   {
    if($valore and !mb_eregi('^-?[0-9]+[.,]?[0-9]*$',$valore) and !mb_eregi('^-?[0-9]*[.,][0-9]+$',$valore)) { $this->err[$v->campo]=_('Valore non valido'); continue; }
    if($valore==='') { $vDB='null'; $this->valoriPuri[$v->campo]=''; }
    else
    {
     $valore=(float)str_replace(',','.',$valore);
     $vDB=$this->valoriPuri[$v->campo]=$valore;
     if($v->max!==FALSE and $valore>$v->max) { $this->err[$v->campo]=_('Valore troppo grande'); continue; }
     if($v->min!==FALSE and $valore<$v->min) { $this->err[$v->campo]=_('Valore troppo piccolo'); continue; }
    }
   }
   elseif($v->tipo==TIPO_BOOLEANO)
   {
    $vDB=($valore=='t')?"'t'":"'f'";
    $this->valoriPuri[$v->campo]=($valore=='t')?'vero':'falso';
   }
   elseif($v->tipo==TIPO_SINO)
   {
    $vDB=($valore=='s')?"'s'":"'n'";
    $this->valoriPuri[$v->campo]=($valore=='s')?'sì':'no';
   }
   else
   {
    $valore=validaStringa($valore);
    if($valore and $v->max and mb_strlen($valore)>$v->max) { $this->err[$v->campo]=_('Testo troppo lungo'); continue; }
    if($valore and $v->min and mb_strlen($valore)<$v->min) { $this->err[$v->campo]=_('Testo troppo corto'); continue; }
    if($v->tipo==TIPO_EMAIL)
    {
     $valore=mb_strtolower($valore);
     if($valore)
     {
      if(!mb_ereg("^[a-z0-9\_\-]+(\\.?[a-z0-9\_\-]+)*@([a-z0-9\_\-]+\\.)+[a-z0-9]+$",$valore))
       $this->err[$v->campo]=_('Indirizzo non valido');
      elseif(!checkdnsrr(mb_substr($valore,mb_strpos($valore,'@')+1),'MX'))
       $this->err[$v->campo]=_('Dominio non valido');
     }
    }
    elseif($v->tipo==TIPO_DATA)
    {
     if($valore)
     {
      $valore=contrData($valore,$v->valori);
      if(!$valore) { $this->err[$v->campo]=_('Data non valida'); continue; }
     }
    }
    elseif($v->tipo==TIPO_ORA)
    {
     if($valore)
     {
      $valore=contrOra($valore);
      if(!$valore) { $this->err[$v->campo]=_('Ora non valida'); continue; }
     }
    }
    elseif($v->tipo==TIPO_DATAORA)
    {
     if($valore)
     {
      $valore=contrDataOra($valore,$v->valori);
      if(!$valore) { $this->err[$v->campo]=_('Data od ora non valida'); continue; }
     }
    }
    elseif($v->tipo==TIPO_CODFISC)
    {
     if($valore)
     {
      $valore=mb_strtoupper($valore); $err='';
      if(!validaCF($valore,$err)) { $this->err[$v->campo]=$err; continue; }
     }
    }
    elseif($v->tipo==TIPO_PIVA)
    {
     if($valore)
     {
      $valore=mb_strtoupper($valore); $err='';
      if(!validaPIVA($valore,$err)) { $this->err[$v->campo]=$err; continue; }
     }
    }
    elseif($v->tipo==TIPO_CFOPIVA)
    {
     if($valore)
     {
      $valore=mb_strtoupper($valore); $err='';
      if(!validaCF($valore,$err))
       if(!validaPIVA($valore,$err))
        { $this->err[$v->campo]='Il valore inserito non è un codice fiscale o una partita IVA valida.'; continue; }
     }
    }
    elseif($v->tipo==TIPO_NOMEFILE)
    {
     $valore=returnValidatedFileName($valore);
    }
    if($valore and is_array($v->valori) and count($v->valori))
    {
     if(!isset($v->valori[$valore])) { $this->err[$v->campo]=_('Valore non valido'); continue; }
     $vDB=pg_escape_literal($valore);
    }
    else $vDB=((($v->tipo==TIPO_DATA or $v->tipo==TIPO_ORA or $v->tipo==TIPO_DATAORA) and !$valore)?'null':pg_escape_literal($valore));
    $this->valoriPuri[$v->campo]=$valore;
   }
   if($v->nodb) {
       $this->dati->{$v->campo}=$valore;
   } else {
       $this->valoriDB[$v->campo]=$vDB;
   }

   if (empty($this->err)) {
       $this->campi[$k]->default = $valore;
   }
  }
  if(count($this->err)>0) return FALSE;
  else return TRUE;
 }

 function creaInsert($tabella)
 {
  $query1=$query2='';
  foreach($this->valoriDB as $k=>$v)
  {
   $query1.=$k.',';
   $query2.=$v.',';
  }
  return "insert into $tabella (".mb_substr($query1,0,-1).') values ('.mb_substr($query2,0,-1).')';
 }

 function creaUpdate($tabella,$where)
 {
  $query='';
  foreach($this->valoriDB as $k=>$v)
   $query.="$k=$v,";
  return "update $tabella set ".mb_substr($query,0,-1).' where '.$where;
 }

 function creaMail()
 {
  $body='';
  foreach($this->valoriPuri as $k=>$v)
  {
   $body.=$this->campi[$k]->nome.': ';
   if(is_array($this->campi[$k]->valori) and count($this->campi[$k]->valori) and $this->campi[$k]->valori[$v]) $body.=$this->campi[$k]->valori[$v];
   else $body.=$v;
   $body.="\n";
  }
  return $body;
 }

 function valoreAttuale($campo,$r=null)
 {
  return ($this->corrente?$this->campi[$campo]->valore:($r?$r->$campo:$this->campi[$campo]->default));
 }

 function creaInputHidden($campo,$r=null)
 {
  $val=$this->valoreAttuale($campo,$r);
  echo "<input type=\"hidden\" name=\"$campo\" value=\"".htmlspecialchars($val)."\" />";
 }

 function stampaErrore($campo, $tipo=ERRORE_BLOCCO)
 {
     $htmlErrore = '';

    if($this->err[$campo]) {
        $htmlErrore .= '<div class="errore">';
        $htmlErrore .= '<h4>'.$this->err[$campo].'</h4>';
        $htmlErrore .= '</div>';
    }

    return $htmlErrore;
 }

 function creaInputText($campo,$r=null,$size=0)
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $val=$this->valoreAttuale($campo,$r);
     $max=0; $min=0;
     $placeholder=$def->placeholder;
     if($def->tipo==TIPO_INTERO) $max=10;
     elseif($def->tipo==TIPO_DATA) { $max=10; $min=6; if(!$placeholder) $placeholder=_('gg/mm/aaaa'); }
     elseif($def->tipo==TIPO_DATAORA) { $max=19; $min=11; if(!$placeholder) $placeholder=_('gg/mm/aaaa oo:mm:ss'); }
     elseif($def->tipo==TIPO_REALE) $max=12;
     elseif($def->max) $max=$def->max;
     if($def->min) $min=$def->min;
     $htmlCampo.='<input type="';
     if($def->tipo==TIPO_EMAIL) $htmlCampo.='email';
     elseif($def->tipo==TIPO_URL) $htmlCampo.='url';
     elseif($def->tipo==TIPO_INTERO or $def->tipo==TIPO_REALE) $htmlCampo.='number';
     else $htmlCampo.='text';
     $htmlCampo.="\" name=\"$campo\" id=\"$campo\" title=\"$def->nome\" value=\"".htmlspecialchars($max?mb_substr($val,0,$max):$val)."\"".
     ($size?" size=\"$size\"":'').
     ($def->class?" class=\"$def->class\"":'').
     (($max and $def->tipo!=TIPO_INTERO and $def->tipo!=TIPO_REALE)?' maxlength="'.$max.'"':'').
     (($min and $def->tipo!=TIPO_INTERO and $def->tipo!=TIPO_REALE)?' minlength="'.$min.'"':'').
     (($def->max and ($def->tipo==TIPO_INTERO or $def->tipo==TIPO_REALE))?' max="'.$def->max.'"':'').
     (($def->min and ($def->tipo==TIPO_INTERO or $def->tipo==TIPO_REALE))?' min="'.$def->min.'"':'').
     (($def->tipo==TIPO_REALE)?' step="any"':'').
     (($def->tipo==TIPO_INTERO)?' step="1"':'').
     ($def->obbligatorio?' required="required"':'').
     ($placeholder?" placeholder=\"$placeholder\"":'').
     ($def->pattern?" pattern=\"$def->pattern\"":'').
     ($def->disabled ? ' disabled="disabled"' : '').
     ($def->autocomplete ? ' autocomplete="' . $def->autocomplete . '"' : '').
     ' />';
     return $htmlCampo;
 }

 function creaInputPassword($campo,$r=null,$size=0)
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $val=$this->valoreAttuale($campo,$r);
     $max=0;
     $placeholder=$def->placeholder;
     if($def->max) {
         $max=$def->max;
     }
     $htmlCampo.="<input type=\"password\" name=\"$campo\" id=\"$campo\" title=\"$def->nome\" value=\"".htmlspecialchars($max?mb_substr($val,0,$max):$val)."\"".
     ($size?" size=\"$size\"":'').
     ($def->class?" class=\"$def->class\"":'').
     ($max?' maxlength="'.$max.'"':'').
     ($def->obbligatorio?' required="required"':'').
     ($placeholder?" placeholder=\"$placeholder\"":'').
     ($def->disabled ? ' disabled="disabled"' : '').
     ($def->autocomplete ? ' autocomplete="' . $def->autocomplete . '"' : '').
     ' />';
     return $htmlCampo;
 }

 function creaCheckbox($campo,$r=null,$valore='t')
 {
     $htmlCampo='';
     $val=$this->valoreAttuale($campo,$r);
     $htmlCampo.="<input type=\"checkbox\" name=\"$campo\" id=\"$campo\" value=\"$valore\"".(($val==$valore)?' checked="checked"':'').($def->class?" class=\"$def->class\"":'').($def->obbligatorio?' required="required"':'').
     ($def->disabled ? ' disabled="disabled"' : '').
     ' />';
     return $htmlCampo;
 }

 function creaSelect($campo,$r=null,$select='')
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $val=$this->valoreAttuale($campo,$r);
     $htmlCampo.="<select name=\"$campo\" id=\"$campo\" title=\"$def->nome\"".($def->obbligatorio?' required="required"':'').($def->class?" class=\"$def->class\"":'').
     ($def->disabled ? ' disabled="disabled"' : '').
     ">\n";
     if($select) $htmlCampo.='<option value=""'.(!$val?' selected="selected"':'').">$select</option>\n";
     if(is_array($def->valori))
     foreach($def->valori as $k=>$v)
     $htmlCampo.="<option value=\"$k\"".(($k==$val)?' selected="selected"':'').'>'.htmlspecialchars($v)."</option>\n";
     $htmlCampo.='</select>';
     return $htmlCampo;
 }

 function creaListaRadio($campo,$r=null,$pre='',$post='',$sep="<br />\n")
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $val=$this->valoreAttuale($campo,$r);
     $htmlCampo.=$pre;
     $n=0;
     foreach($def->valori as $k=>$v) {
        $htmlCampo.='<label for="'.$campo.'-'.(++$n).'"><input type="radio" name="'.$campo.'" id="'.$campo.'-'.$n.'" value="'.$k.'"'.(($val==$k)?' checked="checked"':'').' />'.htmlspecialchars($v).'</label>'.$sep;
     }
     $htmlCampo.=$post;
     return $htmlCampo;
 }

 function creaListaCheckbox($campo, $valoriChecked=[], $pre='', $post='')
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $htmlCampo.=$pre;
     $n=0;
     foreach($def->valori as $k=>$v) {
        $htmlCampo.='<label for="'.$campo.'-'.(++$n).'"';
        if($valoriChecked and isset($valoriChecked[$k])) {
            $htmlCampo.=' class="label-checked"';
        }
        $htmlCampo .= '><input type="checkbox" name="'.$campo.'-'.$k.'" id="'.$campo.'-'.$n.'" value="'.$k.'"';
        if($valoriChecked and isset($valoriChecked[$k])) {
            $htmlCampo.=' checked="checked"';
        }
        $htmlCampo.=' />'.htmlspecialchars($v).'</label>'.$sep;
     }
     $htmlCampo.=$post;
     return $htmlCampo;
 }

 function creaRadioSiNo($campo,$r=null,$valoreT='',$valoreF='',$inizializzato=TRUE)
 {
     $htmlCampo='';
     $val=$this->valoreAttuale($campo,$r);
     if(!$valoreT) {
         $valoreT='t';
     }
     if(!$valoreF) {
         $valoreF='f';
     }
     $htmlCampo.='<label for="'.$campo.'-s"><input type="radio" name="'.$campo.'" id="'.$campo.'-s" value="'.$valoreT.'"'.(($val==$valoreT)?' checked="checked"':'').' />'._('Sì')."</label>\n";
     $htmlCampo.='<label for="'.$campo.'-n"><input type="radio" name="'.$campo.'" id="'.$campo.'-n" value="'.$valoreF.'"'.(($inizializzato?($val!=$valoreT):($val==$valoreF))?' checked="checked"':'').' />'._('No').'</label>';
     $htmlCampo.=$post;
    return $htmlCampo;
 }

 function creaTextarea($campo,$r=null,$righe=5,$colonne=50)
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $val=$this->valoreAttuale($campo,$r);
     $htmlCampo.="<textarea name=\"$campo\" id=\"$campo\" title=\"$def->nome\" cols=\"$colonne\" rows=\"$righe\"".
     ($def->max?' maxlength="'.$def->max.'"':'').
     ($def->min?' minlength="'.$def->min.'"':'').
     ($def->obbligatorio?' required="required"':'').
     ($def->placeholder?" placeholder=\"$def->placeholder\"":'').
     ($def->pattern?" pattern=\"$def->pattern\"":'').
     ($def->class?" class=\"$def->class\"":'').
     ($def->disabled ? ' disabled="disabled"' : '').
     '>'.htmlspecialchars($val).'</textarea>';
     return $htmlCampo;
 }

 function creaFile($campo,$r=null,$url='',$canc='')
 {
     global $_pagina,$_cms_pagina;
     $htmlCampo='';
     $def=$this->campi[$campo];
     $htmlCampo.='<input type="file" name="'.$campo.'" id="'.$campo.'"'.($def->obbligatorio?' required="required"':'').($def->class?" class=\"$def->class\"":'').
     ($def->disabled ? ' disabled="disabled"' : '').
     ' />'."\n";
     if($url) {
         $htmlCampo.='<div class="file-presente">'."\n";
         $htmlCampo.='File presente: <a href="'.$url.'">'.$url."</a>\n";
         if($estensione=$_pagina->ritornaEstensioneFile($url)) {
             if(in_array($estensione, $_pagina->estensioniImmaginiAmmesse)) {
                $htmlCampo.='<div class="img-anteprima"><a href="'.$url.'"><img src="'.$url.'" /></a></div>'."\n";
            }
         }
         $htmlCampo.="</div>\n";
         if($canc) {
             $htmlCampo.=' <label><input type="checkbox" name="'.$canc.'-'.$campo.'" value="t" />Cancella</label>';
         }
     }
     return $htmlCampo;
 }

 public function getTipoCampoDaNumero($numeroTipo, $tipoDaOpzioni='')
 {
     switch($numeroTipo)
     {
        case CAMPO_INPUTTEXT:
            if($tipoDaOpzioni==TIPO_INTERO or $tipoDaOpzioni==TIPO_REALE) {
                return 'number';
            }
            if($tipoDaOpzioni==TIPO_EMAIL) {
                return 'email';
            }
            if($tipoDaOpzioni==TIPO_URL) {
                return 'url';
            }
            return 'text';
            break;
        case CAMPO_INPUTPASSWORD:
            return 'password';
            break;
        case CAMPO_HIDDEN:
            return 'hidden';
            break;
        case CAMPO_SELECT:
            return 'select';
            break;
        case CAMPO_CHECKBOX:
            return 'checkbox';
            break;
        case CAMPO_LISTACHECKBOX:
                return 'checkbox checkbox-list';
                break;
        case CAMPO_LISTARADIO:
            return 'radio radio-list';
            break;
        case CAMPO_RADIOSINO:
            return 'radio';
            break;
        case CAMPO_TEXTAREA:
            return 'textarea';
            break;
        case CAMPO_FILE:
            return 'file';
            break;
    }
 }

 function creaCampoDIV($campo,$tipo,$r=NULL,$opz1='',$opz2='',$opz3='', $classeCss='')
 {
     $htmlCampo='';
     $def=$this->campi[$campo];
     $tipoCampo=$this->getTipoCampoDaNumero($tipo, $def->tipo);

     switch($tipo)
     {
        case CAMPO_INPUTTEXT:
            $htmlCampo.=$this->creaInputText($campo,$r,$opz1);
            break;
        case CAMPO_INPUTPASSWORD:
            $htmlCampo.=$this->creaInputPassword($campo,$r,$opz1);
            break;
        case CAMPO_HIDDEN:
        $htmlCampo.=$this->creaInputHidden($campo,$r);
        if($def->valori) {
            $htmlCampo.=$def->valori[$this->valoreAttuale($campo,$r)];
        } else {
            $htmlCampo.=htmlspecialchars($this->valoreAttuale($campo,$r));
        }
            break;
        case CAMPO_SELECT:
            $htmlCampo.=$this->creaSelect($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_CHECKBOX:
            $htmlCampo.=$this->creaCheckbox($campo,$r,$opz1);
            break;
        case CAMPO_LISTACHECKBOX:
            $htmlCampo.=$this->creaListaCheckbox($campo,$r,$opz1,$opz2,$opz3);
            break;
        case CAMPO_LISTARADIO:
            $htmlCampo.=$this->creaListaRadio($campo,$r,$opz1,$opz2,$opz3);
            break;
        case CAMPO_RADIOSINO:
            $htmlCampo.=$this->creaRadioSiNo($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_TEXTAREA:
            $htmlCampo.=$this->creaTextarea($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_FILE:
            $htmlCampo.=$this->creaFile($campo,$r,$opz1,$opz2);
            break;
    }

    $class='';
    if($classeCss) {
         $class.=$classeCss;
     } else {
         $class.='col-m-6';
     }
     $class.=' campo-'.$tipoCampo;
     if($def->obbligatorio) {
         $class.=' obbligatorio';
     }
     $html.="<div class=\"campo $class\">\n";
     if($def->nome) {
         $html.='<h4><label'.(($tipo!=CAMPO_LISTARADIO and $tipo!=CAMPO_RADIOSINO)?' for="'.$campo.'"':'').'>';
         $html.=$def->nome;
         
         if($def->obbligatorio) {
             $html.='&#160;*';
         }
         $html.="</label></h4>\n";
     }
     $html .= $this->stampaErrore($campo);
     if($def->aiuto) {
         $html.='<div class="aiuto">'.$def->aiuto.'</div>';
     }
     $html.='<div class="campo-html">';
     $html.=$htmlCampo;
     $html.="</div>\n";
     $html.="</div>\n";
     echo $html;
 }

 function creaCampoDivCustom($campo,$tipo,$classeCss,$r=NULL,$opz1='',$opz2='',$opz3='') {
     $this->creaCampoDIV($campo,$tipo,$r,$opz1,$opz2,$opz3,$classeCss);
 }

 function creaCampoCol($campo,$tipo,$colonne,$r=NULL,$opz1='',$opz2='',$opz3='')
 {
     $htmlCampo='';
     $def=$this->campi[$campo];

     switch($tipo)
     {
         case CAMPO_INPUTTEXT:
            $this->creaInputText($campo,$r,$opz1);
            break;
         case CAMPO_HIDDEN:
            $htmlCampo.=$this->creaInputHidden($campo,$r);
            if($def->valori) $htmlCampo.=$def->valori[$this->valoreAttuale($campo,$r)];
            else $htmlCampo.=htmlspecialchars($this->valoreAttuale($campo,$r));
            break;
        case CAMPO_SELECT:
            $htmlCampo.=$this->creaSelect($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_CHECKBOX:
            $htmlCampo.=$this->creaCheckbox($campo,$r,$opz1);
            break;
        case CAMPO_LISTARADIO:
            $htmlCampo.=$this->creaListaRadio($campo,$r,$opz1,$opz2,$opz3);
            break;
        case CAMPO_RADIOSINO:
            $htmlCampo.=$this->creaRadioSiNo($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_TEXTAREA:
            $htmlCampo.=$this->creaTextarea($campo,$r,$opz1,$opz2);
            break;
        case CAMPO_FILE:
            $htmlCampo.=$this->creaFile($campo,$r,$opz1,$opz2,$opz3);
            break;
     }

     if($def->obbligatorio) {
         $class=' obbligatorio';
     }
     $htmlCampo.="<div class=\"campo$colonne$class\">\n";
     if($def->nome)
     {
         echo '<h4><label'.(($tipo!=CAMPO_LISTARADIO and $tipo!=CAMPO_RADIOSINO)?' for="'.$campo.'"':'').'>'.$def->nome;
         if($def->obbligatorio) {
             echo '&#160;*';
         }
         echo "</label></h4>\n";
     }
     echo $this->stampaErrore($campo);
     if($def->aiuto) {
         echo '<div class="aiuto">'.$def->aiuto.'</div>';
     }
     echo '<p>';
     echo $htmlCampo;
     echo "</p></div>\n";
 }

 function stampaValoreDIV($campo,$r,$sempre=FALSE)
 {
  if($sempre or $r->$campo!=='')
  {
   if($this->pari) $class='pari';
   else $class='dispari';
   echo '<div class="campo '.$class."\">\n<h4>".$this->campi[$campo]->nome."</h4>\n";
   echo $this->stampaErrore($campo);
   echo htmlspecialchars($r->$campo)."</div>\n";
   $this->pari=!$this->pari;
  }
 }

 function creaCampoTR($campo,$tipo,$r=NULL,$opz1='',$opz2='',$opz3='')
 {
     $htmlCampo='';
     $def=$this->campi[$campo];

     switch($tipo)
     {
         case CAMPO_INPUTTEXT:
            $htmlCampo.=$this->creaInputText($campo,$r,$opz1);
            break;
         case CAMPO_SELECT:
            $htmlCampo.=$this->creaSelect($campo,$r,$opz1,$opz2);
            break;
         case CAMPO_CHECKBOX:
            $htmlCampo.=$this->creaCheckbox($campo,$r,$opz1);
            break;
         case CAMPO_LISTARADIO:
            $htmlCampo.=$this->creaListaRadio($campo,$r,$opz1,$opz2,$opz3);
            break;
         case CAMPO_RADIOSINO:
            $htmlCampo.=$this->creaRadioSiNo($campo,$r,$opz1,$opz2);
            break;
         case CAMPO_TEXTAREA:
            $htmlCampo.=$this->creaTextarea($campo,$r,$opz1,$opz2);
            break;
         case CAMPO_FILE:
            $htmlCampo.=$this->creaFile($campo,$r,$opz1,$opz2,$opz3);
            break;
     }

     if($this->pari) $class='pari';
     else $class='dispari';
     if($def->obbligatorio) $class.=' obbligatorio';
     echo "<tr class=\"$class\">\n";
     echo '<th>';
     if($def->nome)
     {
         echo '<label'.(($tipo!=CAMPO_LISTARADIO and $tipo!=CAMPO_RADIOSINO)?' for="'.$campo.'"':'').'>'.$def->nome;
         if($def->obbligatorio) echo '&#160;*';
         echo '</label>';
     }
     echo "</th>\n<td>";
     echo $this->stampaErrore($campo);
     echo $htmlCampo;
     if($def->aiuto) echo '<div class="aiuto">'.$def->aiuto.'</div>';
     echo "</td>\n</tr>\n";
     $this->pari=!$this->pari;
 }

 function stampaValoreTR($campo,$r,$sempre=FALSE)
 {
  if($sempre or $r->$campo!=='')
  {
   if($this->pari) $class='pari';
   else $class='dispari';
   echo '<tr class="'.$class."\">\n<th>".$this->campi[$campo]."</th>\n<td>";
   echo $this->stampaErrore($campo);
   echo htmlspecialchars($r->$campo)."</td>\n</tr>\n";
   $this->pari=!$this->pari;
  }
 }

 public function checkFileUploadReturnError(string $fieldName, string $newFilePrefix='', string $previousFilePath='')
 {
     global $_pagina;
     if ($_FILES[$fieldName]['tmp_name'] and is_uploaded_file($_FILES[$fieldName]['tmp_name'])) {
         $fileName = returnValidatedFileName($_FILES[$fieldName]['name']);
         $estensione = $_pagina->ritornaEstensioneFile($fileName);
         if (!$_pagina->verificaSeEstensioneAmmessa($estensione, $_pagina->estensioniImmaginiAmmesse)) {
             return 'Tipo di immagine non ammesso';
         }
         $newFilePath=$fileName;
         if($newFilePrefix) {
             $newFilePath=$newFilePrefix.$newFilePath;
         }
         if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $_pagina->prodottiDir.$newFilePath)) {
             $this->valoriDB[$fieldName]="'".pg_escape_string($newFilePath)."'";
             $_pagina->logOperazione(LOG_INS_FILE, $fileName, md5_file($_pagina->prodottiDir.$newFilePath));
             if($previousFilePath and is_file($previousFilePath)) {
                 if(!unlink($previousFilePath)) {
                     return 'Errore nella cancellazione del file precedente';
                 }
             }
         } else {
             return 'Impossibile copiare '.$_FILES[$fieldName]['tmp_name'].'.';
         }
     }
     return '';
 }
}

define('DATA_QUALSIASI',0);
define('DATA_FUTURA',1);
define('DATA_PASSATA',-1);

function contrData($data,$vincolo=DATA_QUALSIASI)
{
 if(!mb_ereg("^([0-3]?[0-9])[ \/\.\-]([0-1]?[0-9])[ \/\.\-]([0-9]{2,4})$",trim($data),$m)) return FALSE;
 $giorno=(int)$m[1]; $mese=(int)$m[2]; $anno=(int)$m[3];
 if($anno<100)
 {
  if($vincolo==DATA_FUTURA) $anno+=2000;
  elseif($vincolo==DATA_PASSATA)
  {
   if($anno<=(int)date('y')) $anno+=2000;
   else $anno+=1900;
  }
  else
  {
   if($anno<=(int)date('y')+10) $anno+=2000;
   else $anno+=1900;
  }
 }
 if($anno<1800 or $anno>3000) return FALSE;
 if(!checkdate($mese,$giorno,$anno)) return FALSE;
 if($vincolo==DATA_FUTURA and mktime(23,59,59,$mese,$giorno,$anno)<time()) return FALSE;
 elseif($vincolo==DATA_PASSATA and mktime(0,0,0,$mese,$giorno,$anno)>time()) return FALSE;
 return sprintf('%02d/%02d/%04d',$giorno,$mese,$anno);
}

function contrOra($ore)
{
 if(mb_ereg('^([0-9]{1,2})[.:,]([0-9]{1,2})[.:,]([0-9]{1,2})$',$ore,$aore))
 { $ora=(int)$aore[1]; $minuti=(int)$aore[2]; $secondi=(int)$aore[3]; }
 elseif(mb_ereg('^([0-9]{1,2})[.:,]([0-9]{1,2})$',$ore,$aore))
 { $ora=(int)$aore[1]; $minuti=(int)$aore[2]; $secondi=0; }
 elseif(mb_ereg('^([0-9]{1,2})$',$ore,$aore))
 { $ora=(int)$aore[1]; $minuti=0; $secondi=0; }
 else return FALSE;
 if($ora<0 or $ora>23 or $minuti<0 or $minuti>59 or $secondi<0 or $secondi>59) return FALSE;
 return sprintf('%02d:%02d:%04d',$ora,$minuti,$secondi);
}

function contrDataOra($dataOra,$ritorna=FALSE,$vincolo=DATA_QUALSIASI)
{
 list($data,$ora)=explode(' ',$dataOra);
 $data=trim($data); $ora=trim($ora);
 if(!$data or !$ora) return FALSE;
 $ora=contrOra($ora);
 if($ora===FALSE) return FALSE;
 $data=contrData($data,DATA_QUALSIASI);
 if($data===FALSE) return FALSE;
 list($ora,$minuti,$secondi)=explode(':',$ora);
 list($giorno,$mese,$anno)=explode('/',$data);
 if($vincolo==DATA_FUTURA and mktime($ora,$minuti,$secondi,$mese,$giorno,$anno)<time()) return FALSE;
 elseif($vincolo==DATA_PASSATA and mktime($ora,$minuti,$secondi,$mese,$giorno,$anno)>time()) return FALSE;
 return sprintf('%s %02d:%02d:%04d',$data,$ora,$minuti,$secondi);
}

function confrData($d1,$d2)
{
 mb_ereg('^([0-9]{1,2})[ \/\.\-]([0-9]{1,2})[ \/\.\-]([0-9]{4})$',trim($d1),$adata);
 $giorno1=(int)$adata[1]; $mese1=(int)$adata[2]; $anno1=(int)$adata[3];
 $data1=mktime(0,0,0,$mese1,$giorno1,$anno1);
 mb_ereg('^([0-9]{1,2})[ \/\.\-]([0-9]{1,2})[ \/\.\-]([0-9]{4})$',trim($d2),$adata);
 $giorno2=(int)$adata[1]; $mese2=(int)$adata[2]; $anno2=(int)$adata[3];
 $data2=mktime(0,0,0,$mese2,$giorno2,$anno2);
 return ($data1>$data2)?1:(($data1<$data2)?-1:0);
}

function data2Timestamp($data)
{
 mb_ereg('^([0-9]{1,2})[ \/\.\-]([0-9]{1,2})[ \/\.\-]([0-9]{2,4}) +([0-9]{1,2})[.:]([0-9]{1,2})[.:]([0-9]{1,2})[.:]?',trim($data),$adata);
 $giorno=(int)$adata[1]; $mese=(int)$adata[2]; $anno=(int)$adata[3];
 $ora=(int)$adata[4]; $minuti=(int)$adata[5]; $secondi=(int)$adata[6];
 return mktime($ora,$minuti,$secondi,$mese,$giorno,$anno);
}

function validaNomeFile(string $url)
{
    return returnValidatedFileName($url);
}

function validaStringa($s)
{
 return(mb_ereg_replace('\&\#[0-9]+\;','',str_replace('&quot;','"',$s)));
}


function validaCF($valore,&$err)
{
 $CONTROLLO_CF=array('0'=>1,'1'=>0,'2'=>5,'3'=>7,'4'=>9,'5'=>13,'6'=>15,'7'=>17,'8'=>19,'9'=>21,'A'=>1,'B'=>0,'C'=>5,'D'=>7,'E'=>9,'F'=>13,'G'=>15,'H'=>17,'I'=>19,'J'=>21,'K'=>2,'L'=>4,'M'=>18,'N'=>20,'O'=>11,'P'=>3,'Q'=>6,'R'=>8,'S'=>12,'T'=>14,'U'=>16,'V'=>10,'W'=>22,'X'=>25,'Y'=>24,'Z'=>23);

 $valore=mb_strtoupper($valore);
 if(mb_strlen($valore)!=16) { $err='Il codice fiscale deve essere lungo 16 caratteri'; return FALSE; }
 if(!mb_ereg("^[A-Z0-9]+$",$valore)) { $err='Il codice fiscale deve contenere solo lettere e cifre'; return FALSE; }
 $s=0;
 for($n=1; $n<=13; $n+=2)
 {
  $c=$valore[$n];
  if('0'<=$c and $c<='9') $s+=ord($c)-ord('0');
  else $s+=ord($c)-ord('A');
 }
 for($n=0; $n<=14; $n+=2)
  $s+=((int)$CONTROLLO_CF[$valore[$n]]);
 if(chr($s%26+ord('A'))!=$valore[15]) { $err='Il codice di controllo non corrisponde'; return FALSE; }
 return TRUE;
}

function validaPIVA($valore,&$err)
{
 $valore=mb_strtoupper($valore);
 if(mb_strlen($valore)!=11) { $err='La partiva IVA deve essere lunga 11 caratteri'; return FALSE; }
 if(!mb_ereg("^[0-9]+$",$valore)) { $err='La partiva IVA deve contenere solo cifre'; return FALSE; }
 $s=0;
 for($n=0; $n<=9; $n+=2) $s+=ord($valore[$n])-ord('0');
 for($n=1; $n<=9; $n+=2)
 {
  $c=2*(ord($valore[$n])-ord('0') );
  if($c>9) $c-=9;
  $s+=$c;
 }
 if((10-$s%10)%10!=ord($valore[10])-ord('0')) { $err='Il codice di controllo non corrisponde'; return FALSE; }
 return TRUE;
}

define('RIDIMENSIONA_ASPETTO',0);
define('RIDIMENSIONA_DISTORCI',1);
define('RIDIMENSIONA_TAGLIA',2);

function ridimensiona($maxw,$maxh,$fileOrig,$fileDest,$ridimensiona=RIDIMENSIONA_ASPETTO)
{
 $err=FALSE;
 if($maxh or $maxw)
 {
  list($i_w,$i_h,$i_t)=getimagesize($fileOrig);
  if(($maxw and $i_w>$maxw) or ($maxh and $i_h>$maxh))
  {
   switch($i_t)
   {
    case IMAGETYPE_GIF: $imgOrig=imagecreatefromgif($fileOrig); break;
    case IMAGETYPE_PNG: $imgOrig=imagecreatefrompng($fileOrig); break;
    case IMAGETYPE_JPEG: $imgOrig=imagecreatefromjpeg($fileOrig); break;
   }
   switch($ridimensiona)
   {
    case RIDIMENSIONA_DISTORCI:
     $imgRid=imagecreatetruecolor($maxw,$maxh);
     imagecopyresampled($imgRid,$imgOrig,0,0,0,0,$maxw,$maxh,$i_w,$i_h);
     break;
    case RIDIMENSIONA_TAGLIA:
     $zoomx=((float)$maxw/(float)$i_w); $zoomy=((float)$maxh/(float)$i_h);
     if($zoomx>=$zoomy)
     {
      $nw=$maxw; $nh=$i_h*$zoomx;
      $oy=($maxh-$nh)/2; $ox=0;
     }
     else
     {
      $nh=$maxh; $nw=$i_w*$zoomy;
      $ox=($maxw-$nw)/2; $oy=0;
     }
     $imgRid=imagecreatetruecolor($maxw,$maxh);
     imagecopyresampled($imgRid,$imgOrig,$ox,$oy,0,0,$nw,$nh,$i_w,$i_h);
     break;
    default:
     $zoomx=((float)$maxw/(float)$i_w); $zoomy=((float)$maxh/(float)$i_h);
     $zoom=(($zoomx<=$zoomy)?$zoomx:$zoomy);
     $nw=$i_w*$zoom; $nh=$i_h*$zoom;
     $imgRid=imagecreatetruecolor($nw,$nh);
     imagecopyresampled($imgRid,$imgOrig,0,0,0,0,$nw,$nh,$i_w,$i_h);
   }
   switch($i_t)
   {
    case IMAGETYPE_GIF: if(!imagegif($imgRid,$fileDest)) $err="Impossibile creare l’immagine $fileDest.\n"; break;
    case IMAGETYPE_PNG: if(!imagepng($imgRid,$fileDest)) $err="Impossibile creare l’immagine $fileDest.\n"; break;
    case IMAGETYPE_JPEG: if(!imagejpeg($imgRid,$fileDest)) $err="Impossibile creare l’immagine $fileDest.\n"; break;
   }
   imagedestroy($imgRid);
   imagedestroy($imgOrig);
   if(!$err)
   {
    @chmod($fileDest,0664);
   }
   return $err;
  }
 }
 if($err) return $err;
 if(!copy($fileOrig,$fileDest)) $err="Impossibile copiare l’immagine $fileOrig in $fileDest.";
 else
 {
  @chmod($fileDest,0664);
 }
 return $err;
}
