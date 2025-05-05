<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class Industria40Element
 */
class Industria40Element extends CommonObject
{
    public $element = 'industria40element';
    public $table_element = 'industria40element';
    public $picto = 'generic';

    public $id;
    public $fk_industria40project;
    public $type;
    public $produttore;
    public $piva;
    public $modello;
    public $matricola;
    public $anno_costruzione;
    public $descrizione;
    public $image_file;
    public $invoice_file;
    public $contract_file;
    public $ce_declaration_file;
    public $datasheet_file;
    public $manual_file;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user)
    {
        $error = 0;

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."industria40element (";
        $sql .= "fk_industria40project, type, produttore, piva, modello, matricola, anno_costruzione, descrizione";
        $sql .= ") VALUES (";
        $sql .= (int) $this->fk_industria40project.",";
        $sql .= "'".$this->db->escape($this->type)."',";
        $sql .= "'".$this->db->escape($this->produttore)."',";
        $sql .= "'".$this->db->escape($this->piva)."',";
        $sql .= "'".$this->db->escape($this->modello)."',";
        $sql .= "'".$this->db->escape($this->matricola)."',";
        $sql .= (int) $this->anno_costruzione.",";
        $sql .= "'".$this->db->escape($this->descrizione)."'";
        $sql .= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        if (!$this->db->query($sql)) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."industria40element");
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT rowid, fk_industria40project, type, produttore, piva, modello, matricola, anno_costruzione, descrizione";
        $sql .= " FROM ".MAIN_DB_PREFIX."industria40element";
        $sql .= " WHERE rowid = ".(int) $id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->fk_industria40project = $obj->fk_industria40project;
                $this->type = $obj->type;
                $this->produttore = $obj->produttore;
                $this->piva = $obj->piva;
                $this->modello = $obj->modello;
                $this->matricola = $obj->matricola;
                $this->anno_costruzione = $obj->anno_costruzione;
                $this->descrizione = $obj->descrizione;

                return 1;
            }
            return 0;
        } else {
            $this->errors[] = $this->db->lasterror();
            return -1;
        }
    }
}

?>
