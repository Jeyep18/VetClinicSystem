-- ============================================================
-- VETERINARY CLINIC MANAGEMENT SYSTEM - VACCINATION MODULE
-- Oracle Database Schema (SQL*Plus Compatible)
-- ============================================================
-- Run this script in SQL*Plus:
--   sqlplus username/password@localhost/XE @schema.sql
-- ============================================================

-- Set SQL*Plus formatting
SET ECHO ON
SET FEEDBACK ON
SET LINESIZE 200

-- ============================================================
-- STEP 1: DROP EXISTING OBJECTS (ignore errors if not exist)
-- ============================================================

DROP TABLE VACCINATION CASCADE CONSTRAINTS;
DROP TABLE VISIT_RECORD CASCADE CONSTRAINTS;
DROP TABLE PET CASCADE CONSTRAINTS;
DROP TABLE VETERINARIAN CASCADE CONSTRAINTS;
DROP TABLE CLIENT CASCADE CONSTRAINTS;

DROP SEQUENCE vaccination_seq;
DROP SEQUENCE visit_seq;
DROP SEQUENCE vet_seq;
DROP SEQUENCE pet_seq;
DROP SEQUENCE client_seq;

-- ============================================================
-- STEP 2: CREATE TABLES
-- ============================================================

-- CLIENT table
CREATE TABLE CLIENT (
    Client_ID    NUMBER        PRIMARY KEY,
    Firstname    VARCHAR2(50)  NOT NULL,
    Middlename   VARCHAR2(50),
    Lastname     VARCHAR2(50)  NOT NULL,
    Suffix       VARCHAR2(10),
    Address      VARCHAR2(200) NOT NULL
);

-- VETERINARIAN table
CREATE TABLE VETERINARIAN (
    Vet_ID      NUMBER         PRIMARY KEY,
    Vet_Name    VARCHAR2(100)  NOT NULL
);

-- PET table
CREATE TABLE PET (
    Pet_ID      NUMBER         PRIMARY KEY,
    Owner_ID    NUMBER         NOT NULL,
    Pet_Name    VARCHAR2(50)   NOT NULL,
    Breed       VARCHAR2(50),
    Birthdate   DATE,
    Markings    VARCHAR2(200)
);

-- Add foreign key to PET
ALTER TABLE PET ADD CONSTRAINT fk_pet_owner 
    FOREIGN KEY (Owner_ID) REFERENCES CLIENT(Client_ID) ON DELETE CASCADE;

-- VISIT_RECORD table
CREATE TABLE VISIT_RECORD (
    Visit_ID       NUMBER        PRIMARY KEY,
    Visit_Date     DATE          DEFAULT SYSDATE NOT NULL,
    Pet_Weight     NUMBER(5,2)   NOT NULL,
    Pet_ID         NUMBER        NOT NULL,
    Vet_ID         NUMBER        NOT NULL
);

-- Add foreign keys to VISIT_RECORD
ALTER TABLE VISIT_RECORD ADD CONSTRAINT fk_visit_pet 
    FOREIGN KEY (Pet_ID) REFERENCES PET(Pet_ID) ON DELETE CASCADE;

ALTER TABLE VISIT_RECORD ADD CONSTRAINT fk_visit_vet 
    FOREIGN KEY (Vet_ID) REFERENCES VETERINARIAN(Vet_ID);

ALTER TABLE VISIT_RECORD ADD CONSTRAINT chk_weight CHECK (Pet_Weight > 0);

-- VACCINATION table
CREATE TABLE VACCINATION (
    Vaccination_ID  NUMBER         PRIMARY KEY,
    Visit_ID        NUMBER         NOT NULL,
    Vaccine_Name    VARCHAR2(50)   NOT NULL,
    Against         VARCHAR2(100),
    Manufacturer    VARCHAR2(50),
    Lot_No          VARCHAR2(30)   NOT NULL,
    Next_Schedule   DATE
);

-- Add foreign key to VACCINATION
ALTER TABLE VACCINATION ADD CONSTRAINT fk_vaccination_visit 
    FOREIGN KEY (Visit_ID) REFERENCES VISIT_RECORD(Visit_ID) ON DELETE CASCADE;

-- ============================================================
-- STEP 3: CREATE SEQUENCES
-- ============================================================

CREATE SEQUENCE client_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE pet_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE vet_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE visit_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE vaccination_seq START WITH 1 INCREMENT BY 1 NOCACHE;

-- ============================================================
-- STEP 4: CREATE TRIGGERS
-- ============================================================

CREATE OR REPLACE TRIGGER trg_client_id
BEFORE INSERT ON CLIENT
FOR EACH ROW
BEGIN
    IF :NEW.Client_ID IS NULL THEN
        SELECT client_seq.NEXTVAL INTO :NEW.Client_ID FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_pet_id
BEFORE INSERT ON PET
FOR EACH ROW
BEGIN
    IF :NEW.Pet_ID IS NULL THEN
        SELECT pet_seq.NEXTVAL INTO :NEW.Pet_ID FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_vet_id
BEFORE INSERT ON VETERINARIAN
FOR EACH ROW
BEGIN
    IF :NEW.Vet_ID IS NULL THEN
        SELECT vet_seq.NEXTVAL INTO :NEW.Vet_ID FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_visit_id
BEFORE INSERT ON VISIT_RECORD
FOR EACH ROW
BEGIN
    IF :NEW.Visit_ID IS NULL THEN
        SELECT visit_seq.NEXTVAL INTO :NEW.Visit_ID FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_vaccination_id
BEFORE INSERT ON VACCINATION
FOR EACH ROW
BEGIN
    IF :NEW.Vaccination_ID IS NULL THEN
        SELECT vaccination_seq.NEXTVAL INTO :NEW.Vaccination_ID FROM DUAL;
    END IF;
END;
/

-- ============================================================
-- STEP 5: INSERT SEED DATA
-- ============================================================

INSERT INTO VETERINARIAN (Vet_Name) VALUES ('Dr. Juan Dela Cruz');
INSERT INTO VETERINARIAN (Vet_Name) VALUES ('Dr. Maria Santos');

COMMIT;

-- ============================================================
-- STEP 6: VERIFY INSTALLATION
-- ============================================================

SELECT 'Tables created:' AS STATUS FROM DUAL;
SELECT table_name FROM user_tables WHERE table_name IN ('CLIENT', 'PET', 'VETERINARIAN', 'VISIT_RECORD', 'VACCINATION');

SELECT 'Sequences created:' AS STATUS FROM DUAL;
SELECT sequence_name FROM user_sequences WHERE sequence_name LIKE '%_SEQ';

SELECT 'Veterinarians seeded:' AS STATUS FROM DUAL;
SELECT * FROM VETERINARIAN;

PROMPT
PROMPT ============================================================
PROMPT   Schema installation complete!
PROMPT ============================================================
