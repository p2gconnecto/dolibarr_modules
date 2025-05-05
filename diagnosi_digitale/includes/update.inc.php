<?php
$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element . " SET ";
$fields = [];

// Log iniziale dello stato della connessione
dol_syslog(__METHOD__ . " Start update.inc.php. DB Connected: " . ($this->db->connected ? 'Yes' : 'No'), LOG_DEBUG);

foreach ($this->fields as $key => $field) {
    if ($key === 'rowid' || $key === 'ref') { // Aggiungi || $key === 'ref'
        continue; // Skip rowid and ref
    }

    if (isset($this->$key)) {
        // Log prima di ogni escape
        dol_syslog(__METHOD__ . " Processing field '$key'. DB Connected before escape: " . ($this->db->connected ? 'Yes' : 'No'), LOG_DEBUG);

        if ($this->$key === null || $this->$key === '') {
            $fields[] = $key . " = " . (in_array($field['type'], ['varchar', 'text', 'date']) ? "''" : "NULL");
        } elseif (isset($field['type']) && in_array($field['type'], ['varchar', 'text', 'date'])) {
            // Verifica la connessione *immediatamente* prima della chiamata a escape
            if (!$this->db->connected) {
                 dol_syslog(__METHOD__ . " FATAL ERROR: DB Connection closed before escaping field '$key'", LOG_ERR);
                 // Potresti voler lanciare un'eccezione qui o uscire per evitare l'errore fatale
                 throw new Exception("Database connection closed unexpectedly before escaping field '$key'");
            }
            $escapedValue = $this->db->escape($this->$key); // <-- Punto dell'errore originale
            $fields[] = $key . " = '" . $escapedValue . "'";
        } elseif (isset($field['type']) && in_array($field['type'], ['int', 'integer', 'double', 'double(24,8)', 'boolean', 'tinyint(1)'])) {
            $fields[] = $key . " = " . $this->$key;
        } else {
             // Verifica la connessione *immediatamente* prima della chiamata a escape
            if (!$this->db->connected) {
                 dol_syslog(__METHOD__ . " FATAL ERROR: DB Connection closed before escaping field '$key' (default case)", LOG_ERR);
                 throw new Exception("Database connection closed unexpectedly before escaping field '$key' (default case)");
            }
            $escapedValue = $this->db->escape($this->$key);
            $fields[] = $key . " = '" . $escapedValue . "'";
        }
    }
}

if (empty($fields)) {
    $this->errors[] = "No fields to update";
    dol_syslog(__METHOD__ . " No fields to update", LOG_ERR);
    return -1;
}

if (empty($this->id) || !is_numeric($this->id)) {
    $this->errors[] = "Invalid rowid";
    dol_syslog(__METHOD__ . " Invalid rowid: " . var_export($this->id, true), LOG_ERR);
    return -1;
}

$sql .= implode(', ', $fields);
$sql .= " WHERE rowid = " . ((int) $this->id);

// Log prima dell'esecuzione della query
dol_syslog(__METHOD__ . " Before executing query. DB Connected: " . ($this->db->connected ? 'Yes' : 'No'), LOG_DEBUG);

dol_syslog(__METHOD__ . " Generated SQL: " . $sql, LOG_DEBUG);

$res = $this->db->query($sql);
if (!$res) {
    $error = $this->db->lasterror();
    $this->errors[] = $error;
    dol_syslog(__METHOD__ . " SQL ERROR: " . $error, LOG_ERR);
    dol_syslog(__METHOD__ . " FAILED SQL: " . $sql, LOG_ERR);
    throw new Exception("Database error: " . $error);
}

// Check if rows were affected
if ($this->db->affected_rows($res) === 0) {
    dol_syslog(__METHOD__ . " No rows updated. Query executed successfully but no changes were made.", LOG_WARNING);
    return 0; // Return a specific code to indicate no changes
}

return 1;