<?php
class Clienti extends DbTable
{
    protected static $classToCreate='AnagraficaCliente';
    protected static $dbTable='clienti';
    public static $classNameReadable='Clienti';
    public $where="codconto ILIKE 'C%'"; # Solo clienti, no fornitori
    public $ordiniAmmessi=['nome','id','localita','provincia','telex','attivo'];
    public $ordine='nome';
    public $colonnaValorePerListaOpzioni='nome';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'nome'=>[
                'label'=>'Cliente',
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
                'label'=>'Prov.',
                'isSortable'=>true
            ],
            'stato'=>[
                'label'=>'Stato',
                'isSortable'=>false
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ]
        ];

        return;
    }

    final public function setAssociatedResultsTableHeaderCells(): void
    {
        $this->associatedResultsTableHeaderCells = $this->tableHeaderCells;
    }

    final public function setWhere($where='')
    {
        parent::setWhere($where);
        $statoClienteRichiesto=null;
        if (stripos($_SERVER['REQUEST_URI'], 'client')) {
            if (isset($_REQUEST['nome']) and $_REQUEST['nome']) {
                $nomeCliente = strtolower($_REQUEST['nome']);
                $this->where.=($this->where?' AND ':'');
                if (strlen($nomeCliente) == 1) {
                    $this->where.="nome ILIKE ".pg_escape_literal("$nomeCliente%");
                } else {
                    $this->where.="nome ILIKE ".pg_escape_literal("%$nomeCliente%");
                }
            }
            if (isset($_REQUEST['stato'])) {
                if ($_REQUEST['stato']!=='') {
                    $statoClienteRichiesto=(int)$_REQUEST['stato'];
                }
            }
        }
        if ($statoClienteRichiesto!==null) {
            if ($statoClienteRichiesto===STATO_NORMALE_E_SEGNALATO) {
                $this->where.=($this->where?' AND ':'').' (stato='.STATO_NORMALE.' OR stato='.STATO_SEGNALATO.')';
            }
            if ($statoClienteRichiesto===STATO_NORMALE) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_NORMALE;
            }
            if ($statoClienteRichiesto===STATO_SEGNALATO) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_SEGNALATO;
            }
            if ($statoClienteRichiesto===STATO_BLOCCATO) {
                $this->where.=($this->where?' AND ':'').' stato='.STATO_BLOCCATO;
            }
        }
    }
}

class AnagraficaCliente extends DbTableRecord
{
    protected static $dbTable='clienti';
    public static $classNameReadable='Cliente';
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
    public $statoGenerale=STATO_NORMALE; # Stato del cliente basato sui vari stati (ordini, contabilità, ecc.)

    public $contatti=[]; # Informazioni dalla tabella contatti
    public $attivo=false;
    public $domini=[];

    final private function impostaStatoGenerale()
    {
        global $_pagina, $_database;
        # Stato generale del cliente se almeno uno stato è Segnalato o Bloccato
        if ($this->stato_altro==STATO_BLOCCATO or $this->stato_bolle==STATO_BLOCCATO or $this->stato_contabilita==STATO_BLOCCATO or $this->stato_fatture==STATO_BLOCCATO or $this->stato_magazzino==STATO_BLOCCATO or $this->stato_noteaccredito==STATO_BLOCCATO or $this->stato_ordini==STATO_BLOCCATO) {
            # Almeno uno stato è Bloccato
            if ($this->statoGenerale===STATO_NORMALE or $this->statoGenerale===STATO_SEGNALATO) {
                # Se il cliente nel DB risulta in stato Normale o Segnalato lo rende Bloccato
                $this->statoGenerale=STATO_BLOCCATO;
                if ($_database->queryUpdate(static::$dbTable, ['stato'=>$this->statoGenerale], 'id='.$this->id)) {
                    $_pagina->messaggi[]=new MessaggioInfo('Il cliente '.$this->nomeAzienda.' aveva uno stato non aggiornato. Aggiornamento eseguito. Nuovo stato: Bloccato.');
                } else {
                    $_pagina->messaggi[]=new MessaggioErrore('Il cliente '.$this->nomeAzienda.' ha uno stato non aggiornato. Errore nell\'aggiornamento');
                }
            }
        } elseif ($this->stato_altro==STATO_SEGNALATO or $this->stato_bolle==STATO_SEGNALATO or $this->stato_contabilita==STATO_SEGNALATO or $this->stato_fatture==STATO_SEGNALATO or $this->stato_magazzino==STATO_SEGNALATO or $this->stato_noteaccredito==STATO_SEGNALATO or $this->stato_ordini==STATO_SEGNALATO) {
            # Almeno uno stato è Segnalato
            if ($this->statoGenerale===STATO_NORMALE) {
                # Se il cliente nel DB risulta in stato Normale lo rende Segnalato
                $this->statoGenerale=STATO_SEGNALATO;
                if ($_database->queryUpdate(static::$dbTable, ['stato'=>$this->statoGenerale], 'id='.$this->id)) {
                    $_pagina->messaggi[]=new MessaggioInfo('Il cliente '.$this->nomeAzienda.' aveva uno stato non aggiornato. Aggiornamento eseguito. Nuovo stato: Segnalato');
                } else {
                    $_pagina->messaggi[]=new MessaggioErrore('Il cliente '.$this->nomeAzienda.' ha uno stato non aggiornato. Errore nell\'aggiornamento.');
                }
            }
        }
    }

    final public function setRowCells() : void
    {
        global $_config;

        $this->rowCells=[
            'cliente'=>'<a href="cliente.php?id='.$this->id.'">'.$this->nomeAzienda.'</a>',
            'email'=>'<a href="mailto:'.$this->email.'">'.$this->email.'</a>',
            'localita'=>$this->localita,
            'provincia'=>$this->provincia,
            'stato'=>$_config['stato'][$this->statoGenerale],
            'modifica'=>'<a href="cliente.php?id='.$this->id.'"><abbr title="Modifica cliente"></abbr></a>'
        ];

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function returnHtmlTableRow()
    {
        $html = '';

        $html .= '<tr class="' . ($this->attivo ? 'attivo' : 'inattivo') . ($this->tableRowCssClass ? ' ' . $this->tableRowCssClass : '') . '">';

        foreach($this->rowCells as $cellName => $cellValue) {
            $html .= '<td' . ($cellName == 'modifica' ? ' class="azione-modifica"' : '') . '>' . $cellValue . "</td>\n";
        }

        $html .= "</tr>\n";

        return $html;
    }

    final public function associaDomini()
    {
        global $_database,$_pagina;
        if (!$risDomini=pg_query($_database->connection, "SELECT id FROM domini WHERE cliente=$this->id ORDER BY name")) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'estrazione dei domini associati al cliente');
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

    final public function associaContatti()
    {
        $contatti=new Contatti;
        $contatti->setWhere("cliente={$this->id}");
        $contatti->ottieniRisultatiFiltrati();
        $this->contatti=clone $contatti;
        unset($contatti);
    }

    final public function associaCaselleEmail()
    {
        if (!class_exists('CaselleEmail')) {
            $_pagina->messaggi[]=new MessaggioErrore('Classe CaselleEmail non settata');
            require_once('funzioni/classi/caselle-email.php');
        }
        $caselleEmail=new CaselleEmail;
        $where="cliente={$this->id}";
        $caselleEmail->setWhere($where);
        $caselleEmail->ottieniRisultatiFiltrati();
        if (count($caselleEmail->lista)) {
            $this->caselleEmailAssociate=$caselleEmail;
        }
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
        if (isset($this->record->codconto)) {
            $this->codconto=htmlspecialchars($this->record->codconto);
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
        $this->setTableRowCssClass(strtolower(Config::$customerStatus[$this->statoGenerale]));

        return;
    }

    final public function buildHtml() : string
    {
        global $_pagina,$_config;
        parent::buildHtml();
        $html='';
        if ($this->indirizzo) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Indirizzo</h4>';
            $html.='<p><a href="https://www.google.com/maps?q='.urlencode($this->indirizzo).'" target="_blank">'.$this->indirizzo.'</a></p>';
            $html.='</div>'."\n";
        }
        if ($this->telefono) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Telefono</h4>';
            $html.='<p><a href="tel:+39'.preg_replace('/\s+/', '', $this->telefono).'">'.$this->telefono.'</a></p>';
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
        if ($this->piva) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Partita IVA</h4>';
            $html.='<p>'.$this->piva.'</p>';
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
