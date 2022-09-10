<?php
class Fornitori extends DbTable
{
    protected static $classToCreate='AnagraficaFornitore';
    protected static $dbTable='fornitori';
    public $where="codice ILIKE 'F%'";
    public $ordiniAmmessi=['nome','id','localita','provincia','telex','attivo'];
    public $ordine='nome';
    public $colonnaValorePerListaOpzioni='nome';

    public function setWhere($where='')
    {
        $statoFornitoreRichiesto='';
        if (stripos($_SERVER['REQUEST_URI'], 'fornitor')) {
            if (isset($_REQUEST['nome']) and $_REQUEST['nome']) {
                $this->where.=($this->where?' AND ':'')."nome ILIKE '%".strtolower($_REQUEST['nome'])."%'";
            }
            if (isset($_REQUEST['stato'])) {
                if ($_REQUEST['stato']!=='') {
                    $statoFornitoreRichiesto=(int)$_REQUEST['stato'];
                }
            }
        }
        if($statoFornitoreRichiesto) {
            if ($statoFornitoreRichiesto===STATO_NORMALE_E_SEGNALATO) {
                $this->where.=($this->where?' AND ':'').' (stato='.STATO_NORMALE.' OR stato='.STATO_SEGNALATO.')';
            }
            if ($statoFornitoreRichiesto===STATO_NORMALE) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_NORMALE;
            }
            if ($statoFornitoreRichiesto===STATO_SEGNALATO) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_SEGNALATO;
            }
            if ($statoFornitoreRichiesto===STATO_BLOCCATO) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_BLOCCATO;
            }
        }
    }

    final protected function setTableHeaderCells(): void
    {
        $this->tableHeaderCells=[
            'nome'=>[
                'label'=>'Fornitore',
                'isSortable'=>true
            ],
            'telex'=>[
                'label'=>'Email',
                'isSortable'=>false
            ],
            'localita'=>[
                'label'=>'Località',
                'isSortable'=>true
            ],
            'provincia'=>[
                'label'=>'Provincia',
                'isSortable'=>true
            ],
            'stato'=>[
                'label'=>'Stato',
                'isSortable'=>false
            ],
            'modifica'=>[
                'label'=>'Modifica',
                'isSortable'=>false
            ]
        ];
    }

    final public function setAssociatedResultsTableHeaderCells(): void
    {
        $this->associatedResultsTableHeaderCells = $this->tableHeaderCells;
    }
}

class AnagraficaFornitore extends DbTableRecord
{
    protected static $dbTable='fornitori';
    public $nomeResponsabileDomini='';
    public $codFiscResponsabileDomini='';
    public $codice='';
    public $indirizzo='';
    public $localita='';
    public $cap='';
    public $provincia='';
    public $telefono='';
    public $fax='';
    public $email='';
    public $pec='';
    public $note='';
    public $dati_titolare='';
    public $nomeAzienda='';
    public $aggiornamento='';
    public $piva='';
    public $marketing_email='';
    public $marketing_note='';
    public $stato_altro=STATO_NORMALE;
    public $stato_bolle=STATO_NORMALE;
    public $stato_contabilita=STATO_NORMALE;
    public $stato_fatture=STATO_NORMALE;
    public $stato_magazzino=STATO_NORMALE;
    public $stato_noteaccredito=STATO_NORMALE;
    public $stato_ordini=STATO_NORMALE;
    public $statoGenerale=STATO_NORMALE;

    public $contatti=[];
    public $attivo=false;

    private function impostaStatoGenerale()
    {
        global $_pagina, $_database;
    }

    final public function setRowCells(): void
    {
        global $_config;
        $this->rowCells=[
            'cliente'=>'<a href="cliente.php?id='.$this->id.'">'.$this->nomeAzienda.'</a>',
            'email'=>'<a href="mailto:'.$this->email.'">'.$this->email.'</a>',
            'localita'=>$this->localita,
            'provincia'=>$this->provincia,
            'stato'=>$_config['stato'][$this->statoGenerale],
            'modifica'=>'<a href="registrar.php?id='.$this->id.'"><abbr title="Modifica registrar"></abbr></a>'
        ];
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function associaDomini()
    {
        global $_database,$_pagina;
        if (!$risDomini=pg_query($_database->connection, "SELECT id FROM domini WHERE fornitore=$this->id ORDER BY name")) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'estrazione dei domini associati al fornitore');
        } else {
            if (pg_num_rows($risDomini)>0) {
                while ($dominio=pg_fetch_object($risDomini)) {
                    if (isset($dominio->registrar) and $dominio->registrar) {
                        $dominio->registrar=new Registrar($dominio->registrar);
                    }
                    $this->domini[]=new Dominio($dominio->id);
                }
            }
            pg_free_result($risDomini);
        }
    }

    final public function associaContatti() {
        $this->contatti=new Contatti($this->id);
    }

    public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        if (isset($this->record->nomeresp)) {
            $this->nomeResponsabileDomini=htmlspecialchars($this->record->nomeresp);
        }
        if (isset($this->record->cfresp)) {
            $this->codFiscResponsabileDomini=htmlspecialchars($this->record->cfresp);
        }
        if (isset($this->record->codice)) {
            $this->codice=htmlspecialchars($this->record->codice);
        }
        if (isset($this->record->indirizzo)) {
            $this->indirizzo=htmlspecialchars($this->record->indirizzo);
        }
        if (isset($this->record->localita)) {
            $this->localita=htmlspecialchars($this->record->localita);
            $this->indirizzo.=' '.$this->localita;
        }
        if (isset($this->record->cap)) {
            $this->cap=htmlspecialchars($this->record->cap);
            $this->indirizzo.=' '.$this->cap;
        }
        if (isset($this->record->provincia)) {
            $this->provincia=htmlspecialchars($this->record->provincia);
            $this->indirizzo.=' '.$this->provincia;
        }
        if (isset($this->record->telefono)) {
            $this->telefono=htmlspecialchars($this->record->telefono);
        }
        if (isset($this->record->fax)) {
            $this->fax=htmlspecialchars($this->record->fax);
        }
        if (isset($this->record->telex)) {
            $this->email=htmlspecialchars(strtolower($this->record->telex));
        }
        if (isset($this->record->pec)) {
            $this->pec=htmlspecialchars(strtolower($this->record->pec));
        }
        if (isset($this->record->note)) {
            $this->note=htmlspecialchars($this->record->note);
        }
        if (isset($this->record->note1)) {
            $this->dati_titolare=htmlspecialchars($this->record->note1);
        }
        if (isset($this->record->nome)) {
            $this->nomeAzienda=htmlspecialchars($this->record->nome);
        }
        if (isset($this->record->aggiornamento)) {
            $this->aggiornamento=DateTime::createFromFormat('d/m/Y H:i:s.u', $this->record->aggiornamento);
        }
        if (isset($this->record->piva)) {
            $this->piva=htmlspecialchars($this->record->piva);
        }
        if (isset($this->record->marketing_email)) {
            $this->marketing_email=htmlspecialchars($this->record->marketing_email);
        }
        if (isset($this->record->marketing_note)) {
            $this->marketing_note=htmlspecialchars($this->record->marketing_note);
        }
        if (isset($this->record->statoaltro)) {
            $this->stato_altro=$this->record->statoaltro;
        }
        if (isset($this->record->statobolle)) {
            $this->stato_bolle=$this->record->statobolle;
        }
        if (isset($this->record->statocontabilita)) {
            $this->stato_contabilita=$this->record->statocontabilita;
        }
        if (isset($this->record->statofatture)) {
            $this->stato_fatture=$this->record->statofatture;
        }
        if (isset($this->record->statomagazzino)) {
            $this->stato_magazzino=$this->record->statomagazzino;
        }
        if (isset($this->record->statonoteaccredito)) {
            $this->stato_noteaccredito=$this->record->statonoteaccredito;
        }
        if (isset($this->record->attivo)) {
            $this->attivo=$this->record->attivo;
        }
        if (isset($this->record->stato)) {
            $this->statoGenerale=(int)$this->record->stato;
        }

        $this->impostaStatoGenerale();
    }

    public function __toString()
    {
        global $_pagina;
        $html='';
        if ($this->piva) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Partita IVA</h4>';
            $html.='<p>'.$this->piva.'</p>';
            $html.='</div>'."\n";
        }
        if ($this->telefono) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Telefono</h4>';
            $html.='<p><a href="tel:+39'.preg_replace('/\s+/', '', $this->telefono).'">'.$this->telefono.'</a></p>';
            $html.='</div>'."\n";
        }
        if ($this->fax) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Fax</h4>';
            $html.='<p>'.$this->fax.'</p>';
            $html.='</div>'."\n";
        }
        if ($this->email) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Email</h4>';
            $html.='<p><a href="mailto:'.$this->email.'">'.$this->email.'</a></p>';
            $html.='</div>'."\n";
        }
        if ($this->marketing_email) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Email Marketing</h4>';
            $html.='<p><a href="mailto:'.$this->marketing_email.'">'.$this->marketing_email.'</a></p>';
            $html.='</div>'."\n";
        }
        if ($this->marketing_note) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Note di contatto Marketing</h4>';
            $html.='<p>'.$this->marketing_note.'</p>';
            $html.='</div>'."\n";
        }
        if ($this->indirizzo) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Indirizzo</h4>';
            $html.='<p><a href="https://www.google.com/maps?q='.urlencode($this->indirizzo).'" target="_blank">'.$this->indirizzo.'</a></p>';
            $html.='</div>'."\n";
        }
        if ($this->nomeResponsabileDomini) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Nome Referente domini</h4>';
            $html.='<p>'.$this->nomeResponsabileDomini.'</p>';
            $html.='</div>'."\n";
        }
        if ($this->codFiscResponsabileDomini) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Codice Fiscale Referente domini</h4>';
            $html.='<p>'.$this->codFiscResponsabileDomini.'</p>';
            $html.='</div>'."\n";
        }
        return $html;
    }
}
