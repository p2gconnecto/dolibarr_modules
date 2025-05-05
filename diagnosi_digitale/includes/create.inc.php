<?php

$error = 0;
$this->db->begin();

$fieldnames = [];
$values = [];

foreach ($this->fields as $key => $field) {
    if (in_array($key, ['rowid', 'datec', 'tms'])) continue; // Ignora datec e tms

    $fieldnames[] = $key;
    $val = $this->$key ?? null;

    switch ($field['type']) {
        case 'date':
            $values[] = ($val ? "'".$this->db->idate($val)."'" : "NULL");
            break;

        case 'integer':
        case 'int':
            $values[] = ($val === '' || $val === null) ? "NULL" : (int) $val;
            break;

        case 'double':
        case 'double(24,8)':
            $values[] = ($val === '' || $val === null) ? "NULL" : (float) $val;
            break;

        default:
            $values[] = $val !== null ? "'".$this->db->escape($val)."'" : "NULL";
            break;
    }
}


$sql = "INSERT INTO " . MAIN_DB_PREFIX . $this->table_element;
$sql .= " (" . implode(', ', $fieldnames) . ")";
$sql .= " VALUES (" . implode(', ', $values) . ")";

$res = $this->db->query($sql);

if ($res) {
    $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . $this->table_element);
} else {
    $error++;
    $this->errors[] = $this->db->lasterror();
}

return !$error ? ($this->db->commit() && $this->id) : ($this->db->rollback() && -1);
