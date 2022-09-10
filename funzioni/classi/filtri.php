<?php
class Filtro
{
    private $colonnaIndice='';
    private $dbTable='';
    private $ordiniAmmessi=[];
    private $query='';
    private $classToCreate='';
    public $ordine='';
    public $ordineDir='';
    public $risultati=[];
    public $errori=[];
    public $maxPerPagina=0;
    public $where='';
    public $totaleRisultati=0;

     public function __construct(string $colonnaIndice='id', string $dbTable, string $ordine, array $ordiniAmmessi=[], string $where='')
     {
         global $_config,$_database,$_pagina;
         $pagina=0;
         $offset=0;
         $ordineDir='ASC';
         $limit=0;
         if (isset($_config['maxPerPagina'])) {
             $limit=(int)$_config['maxPerPagina'];
         }
         if (isset($_REQUEST['ordine'])) {
             $ordine=$_REQUEST['ordine'];
         }
         if (isset($_REQUEST['ordineDir'])) {
             $ordineDir=$_REQUEST['ordineDir'];
         }
         if (isset($_REQUEST['pagina'])) {
             $pagina=(int)$_REQUEST['pagina'];
         }
         if (isset($_REQUEST['risultatiPerPagina'])) {
             $limit=$_config['risultatiPerPaginaAmmessi'][(int)$_REQUEST['risultatiPerPagina']];
         }
         if (!empty($ordiniAmmessi) and !in_array($ordine, $ordiniAmmessi)) {
             $ordine=$ordiniAmmessi[0];
             $_pagina->messaggi[]=new MessaggioErrore('Attenzione: È stato selezionato un tipo di ordinamento non ammesso. Ordine resettato al default.');
         }
         if ($ordineDir!='ASC' and $ordineDir!='DESC') {
             $ordineDir='ASC';
         }
         if ($pagina<1) {
             $pagina=1;
         }
         $offset=($limit*($pagina-1));

         $this->colonnaIndice=$colonnaIndice;
         $this->dbTable=$dbTable;
         $this->ordine=$ordine;
         $this->ordineDir=$ordineDir;
         $this->ordiniAmmessi=$ordiniAmmessi;
         $this->maxPerPagina=$limit;
         $this->where=($where?' WHERE '.$where:'');
         $this->query="SELECT * FROM $this->dbTable".($this->where?$this->where:'')." ORDER BY $ordine $ordineDir ".($limit?' LIMIT '.$limit:'')." OFFSET $offset";
         $_pagina->messaggi[]=new MessaggioDebug($this->query);
         if ($r=pg_query($_database->connection, $this->query)) {
             while ($row=pg_fetch_object($r)) {
                 $this->risultati[$row->{$this->colonnaIndice}]=$row;
             }
             pg_free_result($r);
         }

         $queryTotale="SELECT count({$this->colonnaIndice}) AS tot FROM $this->dbTable".($this->where?$this->where:'');
         if ($r=pg_query($_database->connection, $queryTotale)) {
             while ($row=pg_fetch_object($r)) {
                 $this->totaleRisultati=$row->tot;
             }
             pg_free_result($r);
         }
     }

    public function __toString()
    {
        global $_pagina;
        $html='';
        return $html;
    }
}

class FiltroMultiplo {
        private $colonne='';
        private $tabelleDB='';
        private $ordiniAmmessi=[];
        private $query='';
        public $ordine='';
        public $ordineDir='';
        public $risultati=[];
        public $errori=[];
        public $maxPerPagina=0;
        public $where='';
        public $totaleRisultati=0;

         public function __construct(string $colonne, string $tabelleDB, string $ordine, array $ordiniAmmessi=[], string $where='', $mostraTutti=false)
         {
             global $_config,$_database,$_pagina;
             $pagina=0;
             $offset=0;
             $ordineDir='ASC';
             $limit=0;
             if (!$mostraTutti and isset($_config['maxPerPagina'])) {
                 $limit=(int)$_config['maxPerPagina'];
             }
             if (isset($_REQUEST['ordine'])) {
                 $ordine=$_REQUEST['ordine'];
             }
             if (isset($_REQUEST['ordineDir'])) {
                 $ordineDir=$_REQUEST['ordineDir'];
             }
             if (isset($_REQUEST['pagina'])) {
                 $pagina=(int)$_REQUEST['pagina'];
             }
             if (isset($_REQUEST['risultatiPerPagina'])) {
                 $limit=$_config['risultatiPerPaginaAmmessi'][(int)$_REQUEST['risultatiPerPagina']];
             }
             if (!empty($ordiniAmmessi) and !in_array($ordine, $ordiniAmmessi)) {
                 $ordine=$ordiniAmmessi[0];
                 $_pagina->messaggi[]=new MessaggioErrore('Attenzione: È stato selezionato un tipo di ordinamento non ammesso. Ordine resettato al default.');
             }
             if ($ordineDir!='ASC' and $ordineDir!='DESC') {
                 $ordineDir='ASC';
             }
             if ($pagina<1) {
                 $pagina=1;
             }
             $offset=($limit*($pagina-1));

             $this->colonne=$colonne;
             $this->tabelleDB=$tabelleDB;
             $this->ordine=$ordine;
             $this->ordineDir=$ordineDir;
             $this->ordiniAmmessi=$ordiniAmmessi;
             $this->maxPerPagina=$limit;
             $this->where=($where?' WHERE '.$where:'');
             $colonneDaSelezionare='';
             $i=1;
             foreach($colonne as $colonna=>$alias) {
                 $colonneDaSelezionare.="$colonna AS $alias";
                 if($i!=count($colonne)) {
                     $colonneDaSelezionare.=',';
                 }
             }
             $this->query="SELECT $colonna FROM $this->tabelleDB".($this->where?$this->where:'')." ORDER BY $ordine $ordineDir ".($limit?' LIMIT '.$limit:'')." OFFSET $offset";
             $_pagina->messaggi[]=new MessaggioDebug($this->query);
             if ($r=pg_query($_database->connection, $this->query)) {
                 while ($row=pg_fetch_object($r)) {
                     $this->risultati[]=$row;
                 }
                 pg_free_result($r);
             }
             $queryTotale="SELECT count({$this->colonnaIndice}) as tot FROM $this->dbTable".($this->where?$this->where:'');
             if ($r=pg_query($_database->connection, $queryTotale)) {
                 while ($row=pg_fetch_object($r)) {
                     $this->totaleRisultati=$row->tot;
                 }
                 pg_free_result($r);
             }
         }

        public function __toString()
        {
            global $_pagina;
            $html='';
            return $html;
        }
}
