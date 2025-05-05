-- SQL script to create tables for Industria 4.0 module

-- Table: industria40_perizia
CREATE TABLE IF NOT EXISTS llx_industria40_perizia (
    rowid           INTEGER AUTO_INCREMENT PRIMARY KEY,
    ref             VARCHAR(128) NOT NULL,          -- Reference/Name of the Perizia
    fk_soc          INTEGER NOT NULL,               -- Link to llx_societe
    entity          INTEGER DEFAULT 1 NOT NULL,     -- Entity link

    datec           DATETIME,                       -- Creation date
    tms             TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, -- Last modification date
    fk_user_creat   INTEGER,                        -- User who created
    fk_user_modif   INTEGER                         -- User who last modified
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Table: industria40project
CREATE TABLE IF NOT EXISTS llx_industria40project (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    ref VARCHAR(128) NOT NULL,
    fk_societe INT NOT NULL,
    fk_perizia INT DEFAULT NULL,            -- Link to the Perizia record (llx_industria40_perizia.rowid)
    description TEXT,
    status SMALLINT DEFAULT 0, /* 0 = draft, 1 = in progress, 2 = completed */
    date_creation DATETIME DEFAULT NULL,
    tms TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE (ref), -- Ensure unique project references
    FOREIGN KEY (fk_perizia) REFERENCES llx_industria40_perizia(rowid) -- Add FK constraint now
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: industria40element
CREATE TABLE IF NOT EXISTS llx_industria40element (
    rowid INT AUTO_INCREMENT PRIMARY KEY,
    fk_industria40project INT NOT NULL,
    type ENUM('MACHINE', 'PLC', 'CENTRALINA', 'SENSOR', 'ACCESSORY') DEFAULT 'MACHINE',
    produttore VARCHAR(255) NOT NULL,
    piva VARCHAR(32) DEFAULT NULL,
    modello VARCHAR(255) NOT NULL,
    matricola VARCHAR(128) DEFAULT NULL,
    anno_costruzione YEAR DEFAULT NULL,
    descrizione TEXT DEFAULT NULL,
    image_file VARCHAR(255) DEFAULT NULL,
    invoice_file VARCHAR(255) DEFAULT NULL,
    contract_file VARCHAR(255) DEFAULT NULL,
    ce_declaration_file VARCHAR(255) DEFAULT NULL,
    datasheet_file VARCHAR(255) DEFAULT NULL,
    manual_file VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (fk_industria40project) REFERENCES llx_industria40project(rowid) ON DELETE CASCADE,
    UNIQUE (fk_industria40project, matricola) -- Ensure unique elements per project by serial number
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- End of db_tables_create.sql
