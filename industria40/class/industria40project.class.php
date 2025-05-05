<?php

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 * Class Industria40Project
 */
class Industria40Project extends CommonObject
{
    public $element = 'industria40project';
    public $table_element = 'industria40project';
    public $picto = 'generic';

    public $id;
    public $ref;
    public $fk_societe;
    public $description;
    public $status;
    public $doc_perizia;
    public $doc_analisi_tecnica;
    public $date_creation;
    public $tms;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function create($user)
    {
        global $conf;

        $error = 0;

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."industria40project (";
        $sql .= "ref, fk_societe, description, status, date_creation";
        $sql .= ") VALUES (";
        $sql .= "'".$this->db->escape($this->ref)."',";
        $sql .= (int) $this->fk_societe.",";
        $sql .= "'".$this->db->escape($this->description)."',";
        $sql .= (int) $this->status.",";
        $sql .= " NOW()";
        $sql .= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        if (!$this->db->query($sql)) {
            $error++;
            $this->errors[] = $this->db->lasterror();
        }

        if (!$error) {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."industria40project");
            $this->db->commit();
            return $this->id;
        } else {
            $this->db->rollback();
            return -1;
        }
    }

    public function fetch($id)
    {
        $sql = "SELECT rowid, ref, fk_societe, description, status, doc_perizia, doc_analisi_tecnica, date_creation, tms";
        $sql .= " FROM ".MAIN_DB_PREFIX."industria40project";
        $sql .= " WHERE rowid = ".(int) $id;

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql) {
            if ($this->db->num_rows($resql)) {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->fk_societe = $obj->fk_societe;
                $this->description = $obj->description;
                $this->status = $obj->status;
                $this->doc_perizia = $obj->doc_perizia;
                $this->doc_analisi_tecnica = $obj->doc_analisi_tecnica;
                $this->date_creation = $this->db->jdate($obj->date_creation);
                $this->tms = $this->db->jdate($obj->tms);

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
