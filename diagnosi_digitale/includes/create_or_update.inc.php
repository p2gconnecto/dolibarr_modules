<?php

if (!empty($object->fk_societe) && !empty($object->fk_projet)) {
    $sql_check = "SELECT rowid FROM " . MAIN_DB_PREFIX . $object->table_element . "
                  WHERE fk_societe = " . ((int) $object->fk_societe) . "
                  AND fk_projet = " . ((int) $object->fk_projet) . "
                  LIMIT 1";
    $res_check = $db->query($sql_check);

    if ($res_check && $db->num_rows($res_check)) {
        $obj = $db->fetch_object($res_check);
        $object->id = $obj->rowid;
        $object->rowid = $obj->rowid;

        $result = $object->update($user);
        if ($result <= 0) {
            setEventMessages($langs->trans("ErrorUpdateFailed"), $object->errors, 'errors');
        }
    } else {
        $result = $object->create($user);
        if ($result <= 0) {
            setEventMessages($langs->trans("ErrorCreateFailed"), $object->errors, 'errors');
        }
    }
}
