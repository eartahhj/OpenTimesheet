<?php
class VoceNavigazione
{
    public $id=0;
    public $url='';
    public $titolo='';
    public $sottovoci=[];
    public $errori=[];
    public $classeIconaCSS='';

    public function __construct(int $id, string $url, string $titolo, string $classeIconaCSS='', array $sottovoci=[])
    {
        global $_pagina;
        $this->id=$id;
        $this->url=$url;
        $this->titolo=$titolo;
        $this->classeIconaCSS=$classeIconaCSS;
        $this->sottovoci=$sottovoci;
    }
}

class Navigazione
{
    public $lista=[];
    public $errori=[];
    public $classeCSS='';

    public function __construct(array $voci,string $classeCSS='')
    {
        if (!empty($voci)) {
            $this->lista=$voci;
        }
        if($classeCSS) {
            $this->classeCSS=$classeCSS;
        }
    }

    public function __toString()
    {
        global $_config;
        
        $html='';
        $html.='<ul'.($this->classeCSS?' class="'.$this->classeCSS.'"':'').'>'."\n";
        $scriptCorrente=$_SERVER['SCRIPT_NAME'];

        if ($_config['cPath']) {
            $scriptCorrente=str_replace($_config['cPath'], '', $scriptCorrente);
        }

        $scriptCorrente=str_replace('.php', '', substr($scriptCorrente, 1, strlen($scriptCorrente)));

        if ($slashPosition = stripos($scriptCorrente, '/')) {
            $scriptCorrente = substr($scriptCorrente, 0, $slashPosition);
        }

        foreach ($this->lista as $voce=>$datiVoce) {
            $selezionato=stripos($datiVoce->url, $scriptCorrente);
            $html.='<li'.($selezionato!==false?' class="sel"':'').'>';
            $html.='<a id="voce-'.$datiVoce->id.'"';
            if($datiVoce->url) {
                $html.=' href="'.$_config['cPath'].'/'.$datiVoce->url.'"';
            }
            $html.='>';
            if ($datiVoce->classeIconaCSS) {
                $html.='<abbr title="'.$datiVoce->titolo.'" class="'.$datiVoce->classeIconaCSS.'"></abbr>';
            }
            $html.=$datiVoce->titolo;
            $html.="</a>";
            if (!empty($datiVoce->sottovoci)) { # Se la voce corrente ha delle sottovoci
                $html.="<ul>\n";
                foreach ($datiVoce->sottovoci as $sottovoce=>$datiSottoVoce) {
                    $html.='<li><a id="voce-' . $datiVoce->id . '-sottovoce-'.$datiSottoVoce->id.'"';
                    if($datiSottoVoce->url) {
                        $html.=' href="'.$_config['cPath'];
                        if(substr($datiSottoVoce->url,0,1)!='/') {
                            $html.='/'.$datiVoce->url.'/';
                        }
                        $html.=$datiSottoVoce->url.'"';
                    }
                    $html.='>';
                    if ($datiSottoVoce->classeIconaCSS) {
                        $html.='<abbr title="'.$datiSottoVoce->titolo.'" class="'.$datiSottoVoce->classeIconaCSS.'"></abbr>';
                    }
                    $html.=$datiSottoVoce->titolo;
                    $html.="</a></li>\n";
                }
                $html.="</ul>\n";
            }
            $html.="</li>\n";
        }
        $html.="</ul>\n";
        return $html;
    }
}
