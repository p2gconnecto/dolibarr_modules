CREATE TABLE llx_industria40_perizia (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,          -- Reference/Name of the Perizia
    fk_soc          INTEGER NOT NULL,               -- Link to llx_societe
    entity          INTEGER DEFAULT 1 NOT NULL,     -- Entity link

    datec           DATETIME,                       -- Creation date
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last modification date
    fk_user_creat   INTEGER,                        -- User who created
    fk_user_modif   INTEGER                         -- User who last modified
) ENGINE=InnoDB;
