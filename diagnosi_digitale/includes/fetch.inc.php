<?php
if (empty($id) || !is_numeric($id)) {
    return -1;
}

$id = intval($id);

if (empty($this->table_element)) {
    //dol_syslog(__METHOD__ . " Table element is not defined.", LOG_ERR);
    return -1;
}

$sql = "SELECT * FROM " . MAIN_DB_PREFIX . $this->table_element . " WHERE rowid = " . ((int) $id);
dol_syslog("FETCH.INC.PHP: Executing SQL - " . $sql, LOG_DEBUG);

$res = $this->db->query($sql);
if ($res) {
    dol_syslog("FETCH.INC.PHP: Query executed successfully", LOG_DEBUG);
} else {
    dol_syslog("FETCH.INC.PHP: SQL Error - " . $this->db->lasterror(), LOG_ERR);
}

if ($res && $this->db->num_rows($res)) {
    $obj = $this->db->fetch_object($res);
    foreach ($this->fields as $key => $field) {
        if (property_exists($obj, $key)) {
            $this->$key = $obj->$key;
        }
    }
    $this->id = $obj->rowid;
    $this->rowid = $obj->rowid;
    return 1;
} else {
    $this->errors[] = $this->db->lasterror();
    //dol_syslog(__METHOD__ . " ERROR=" . $this->db->lasterror(), LOG_ERR);
    //dol_syslog(__METHOD__ . " FAILED SQL=" . $sql, LOG_ERR);
    return -1;
}
