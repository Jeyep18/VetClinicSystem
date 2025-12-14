
--   sqlplus username/password@localhost/XE @schema.sql
--   @"c:\Users\john2\OneDrive\Documents\Ateneo\Information Management\VetClinicSystem\oracledb\schema.sql"

SET ECHO ON
SET FEEDBACK ON
SET LINESIZE 200

-- DROP EXISTING OBJECTS

DROP TABLE PAYMENT CASCADE CONSTRAINTS;
DROP TABLE VACCINATION CASCADE CONSTRAINTS;
DROP TABLE VISIT_RECORD CASCADE CONSTRAINTS;
DROP TABLE VETERINARIAN_CONTACT CASCADE CONSTRAINTS;
DROP TABLE CLIENT_CONTACT CASCADE CONSTRAINTS;
DROP TABLE PET CASCADE CONSTRAINTS;
DROP TABLE VETERINARIAN CASCADE CONSTRAINTS;
DROP TABLE CLIENT CASCADE CONSTRAINTS;

DROP SEQUENCE payment_seq;
DROP SEQUENCE vaccination_seq;
DROP SEQUENCE visit_seq;
DROP SEQUENCE vet_contact_seq;
DROP SEQUENCE client_contact_seq;
DROP SEQUENCE vet_seq;
DROP SEQUENCE pet_seq;
DROP SEQUENCE client_seq;

-- CREATE TABLES    

-- CLIENT table
CREATE TABLE CLIENT (
    Client_ID    NUMBER        PRIMARY KEY,
    Firstname    VARCHAR2(50)  NOT NULL,
    Middlename   VARCHAR2(50),
    Lastname     VARCHAR2(50)  NOT NULL,
    Suffix       VARCHAR2(10),
    Address      VARCHAR2(200) NOT NULL
);

-- CLIENT_CONTACT table
CREATE TABLE CLIENT_CONTACT (
    Client_Contact_ID  NUMBER         PRIMARY KEY,
    Client_ID          NUMBER         NOT NULL,
    Contact_Number     VARCHAR2(20)   NOT NULL
);

-- Add FK to CLIENT_CONTACT with ON DELETE CASCADE
ALTER TABLE CLIENT_CONTACT ADD CONSTRAINT fk_contact_client
    FOREIGN KEY (Client_ID) REFERENCES CLIENT(Client_ID) ON DELETE CASCADE;

-- VETERINARIAN table
CREATE TABLE VETERINARIAN (
    Vet_ID        NUMBER         PRIMARY KEY,
    Firstname     VARCHAR2(50)   NOT NULL,
    Middlename    VARCHAR2(50),
    Lastname      VARCHAR2(50)   NOT NULL,
    Suffix        VARCHAR2(10)
);

-- VETERINARIAN_CONTACT table
CREATE TABLE VETERINARIAN_CONTACT (
    Vet_Contact_ID  NUMBER         PRIMARY KEY,
    Vet_ID          NUMBER         NOT NULL,
    Contact_Number  VARCHAR2(20)   NOT NULL
);

-- Add FK to VETERINARIAN_CONTACT
ALTER TABLE VETERINARIAN_CONTACT ADD CONSTRAINT fk_vet_contact
    FOREIGN KEY (Vet_ID) REFERENCES VETERINARIAN(Vet_ID) ON DELETE CASCADE;

-- PET table
CREATE TABLE PET (
    Pet_ID      NUMBER         PRIMARY KEY,
    Owner_ID    NUMBER         NOT NULL,
    Pet_Name    VARCHAR2(50)   NOT NULL,
    Breed       VARCHAR2(50),
    Birthdate   DATE,
    Markings    VARCHAR2(200)
);

-- Add FK to PET
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

-- Add FK to VISIT_RECORD
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

-- Add FK to VACCINATION
ALTER TABLE VACCINATION ADD CONSTRAINT fk_vaccination_visit 
    FOREIGN KEY (Visit_ID) REFERENCES VISIT_RECORD(Visit_ID) ON DELETE CASCADE;

-- PAYMENT table
CREATE TABLE PAYMENT (
    Payment_ID      NUMBER          PRIMARY KEY,
    Visit_ID        NUMBER          NOT NULL,
    Payment_Date    DATE            DEFAULT SYSDATE NOT NULL,
    Amount          NUMBER(10,2)    NOT NULL,
    Payment_Method  VARCHAR2(20)    NOT NULL,
    Payment_Status  VARCHAR2(20)    DEFAULT 'PENDING' NOT NULL
);

-- Add FK and constraints to PAYMENT
ALTER TABLE PAYMENT ADD CONSTRAINT fk_payment_visit
    FOREIGN KEY (Visit_ID) REFERENCES VISIT_RECORD(Visit_ID) ON DELETE CASCADE;

ALTER TABLE PAYMENT ADD CONSTRAINT chk_payment_method
    CHECK (Payment_Method IN ('Cash', 'Credit Card', 'Debit Card', 'Gcash'));

ALTER TABLE PAYMENT ADD CONSTRAINT chk_payment_status
    CHECK (Payment_Status IN ('PAID', 'PENDING'));

ALTER TABLE PAYMENT ADD CONSTRAINT chk_amount CHECK (Amount > 0);

-- CREATE SEQUENCES

CREATE SEQUENCE client_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE client_contact_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE pet_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE vet_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE vet_contact_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE visit_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE vaccination_seq START WITH 1 INCREMENT BY 1 NOCACHE;
CREATE SEQUENCE payment_seq START WITH 1 INCREMENT BY 1 NOCACHE;

-- CREATE TRIGGERS

CREATE OR REPLACE TRIGGER trg_client_id
BEFORE INSERT ON CLIENT
FOR EACH ROW
BEGIN
    IF :NEW.Client_ID IS NULL THEN
        SELECT client_seq.NEXTVAL INTO :NEW.Client_ID FROM DUAL;
    END IF;
END;
/

CREATE OR REPLACE TRIGGER trg_client_contact_id
BEFORE INSERT ON CLIENT_CONTACT
FOR EACH ROW
BEGIN
    IF :NEW.Client_Contact_ID IS NULL THEN
        SELECT client_contact_seq.NEXTVAL INTO :NEW.Client_Contact_ID FROM DUAL;
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

CREATE OR REPLACE TRIGGER trg_vet_contact_id
BEFORE INSERT ON VETERINARIAN_CONTACT
FOR EACH ROW
BEGIN
    IF :NEW.Vet_Contact_ID IS NULL THEN
        SELECT vet_contact_seq.NEXTVAL INTO :NEW.Vet_Contact_ID FROM DUAL;
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

CREATE OR REPLACE TRIGGER trg_payment_id
BEFORE INSERT ON PAYMENT
FOR EACH ROW
BEGIN
    IF :NEW.Payment_ID IS NULL THEN
        SELECT payment_seq.NEXTVAL INTO :NEW.Payment_ID FROM DUAL;
    END IF;
END;
/

-- INSERT MOCK DATA

-- Veterinarians
INSERT INTO VETERINARIAN (Firstname, Middlename, Lastname, Suffix) VALUES ('Dr. Marynade', '', 'Atole-Orgaya', '');
INSERT INTO VETERINARIAN (Firstname, Middlename, Lastname, Suffix) VALUES ('Dr. Antonnete', '', 'Gonzaga', '');

-- Veterinarian Contact Numbers
INSERT INTO VETERINARIAN_CONTACT (Vet_ID, Contact_Number) VALUES (1, '09170000001');
INSERT INTO VETERINARIAN_CONTACT (Vet_ID, Contact_Number) VALUES (2, '09170000002');

-- Clients 
INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
VALUES ('Juan', 'Santos', 'Dela Cruz', '', 'Block 5 Lot 12, Brgy. San Isidro, Quezon City');
INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
VALUES ('Maria', 'Reyes', 'Garcia', '', '123 Rizal St., Brgy. Poblacion, Makati City');
INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
VALUES ('Pedro', '', 'Bautista', 'Jr.', '45 Mabini Ave., Brgy. Kapitolyo, Pasig City');
INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
VALUES ('Ana', 'Lopez', 'Santos', '', '789 Bonifacio St., Brgy. Magallanes, Taguig City');
INSERT INTO CLIENT (Firstname, Middlename, Lastname, Suffix, Address) 
VALUES ('Carlos', 'Mendoza', 'Reyes', 'III', '56 Aguinaldo Blvd., Brgy. Dasmariñas, Cavite');

-- Client Contact Numbers
INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) VALUES (1, '09171234567');
INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) VALUES (2, '09289876543');
INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) VALUES (3, '09991112233');
INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) VALUES (4, '09185556789');
INSERT INTO CLIENT_CONTACT (Client_ID, Contact_Number) VALUES (5, '09064443322');

-- Pets (Various breeds common in Philippines)
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (1, 'Bantay', 'Aspin', DATE '2021-03-15', 'Brown with white chest, floppy ears');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (1, 'Mingming', 'Puspin', DATE '2022-06-20', 'Orange tabby, green eyes');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (2, 'Choco', 'Shih Tzu', DATE '2020-01-10', 'Brown and white, fluffy coat');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (2, 'Whitey', 'Japanese Spitz', DATE '2019-08-05', 'Pure white, pointed ears');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (3, 'Bruno', 'Labrador Retriever', DATE '2020-11-25', 'Chocolate brown, muscular build');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (4, 'Princess', 'Persian Cat', DATE '2021-07-12', 'White with blue eyes, long fur');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (4, 'Max', 'Beagle', DATE '2022-02-28', 'Tricolor, black saddle');
INSERT INTO PET (Owner_ID, Pet_Name, Breed, Birthdate, Markings) 
VALUES (5, 'Lucky', 'Golden Retriever', DATE '2021-09-18', 'Golden coat, friendly disposition');

-- Visit Records
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-01-15', 8.5, 1, 1);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-02-10', 3.2, 2, 2);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-02-20', 5.8, 3, 1);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-03-05', 7.2, 4, 2);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-03-18', 28.5, 5, 1);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-04-02', 4.1, 6, 2);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-04-15', 9.3, 7, 1);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-05-08', 25.0, 8, 2);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-06-12', 9.0, 1, 1);
INSERT INTO VISIT_RECORD (Visit_Date, Pet_Weight, Pet_ID, Vet_ID) 
VALUES (DATE '2024-07-20', 6.5, 3, 2);

-- Vaccinations
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (1, 'Anti-Rabies', 'Rabies Virus', 'Rabisin', 'RAB2024-001', DATE '2025-01-15');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (2, 'FVRCP', 'Feline Viral Rhinotracheitis, Calicivirus, Panleukopenia', 'Felocell', 'FEL2024-015', DATE '2025-02-10');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (3, '5-in-1 DHPP', 'Distemper, Hepatitis, Parvovirus, Parainfluenza', 'Nobivac', 'NOB2024-088', DATE '2025-02-20');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (4, 'Anti-Rabies', 'Rabies Virus', 'Defensor', 'DEF2024-042', DATE '2025-03-05');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (5, '8-in-1 DHLPP', 'Distemper, Hepatitis, Leptospirosis, Parvovirus, Parainfluenza', 'Vanguard Plus', 'VAN2024-103', DATE '2025-03-18');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (6, 'FVRCP', 'Feline Viral Rhinotracheitis, Calicivirus, Panleukopenia', 'Purevax', 'PUR2024-056', DATE '2025-04-02');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (7, 'Anti-Rabies', 'Rabies Virus', 'Rabisin', 'RAB2024-078', DATE '2025-04-15');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (8, '5-in-1 DHPP', 'Distemper, Hepatitis, Parvovirus, Parainfluenza', 'Nobivac', 'NOB2024-121', DATE '2025-05-08');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (9, 'Anti-Rabies Booster', 'Rabies Virus', 'Rabisin', 'RAB2024-145', DATE '2025-06-12');
INSERT INTO VACCINATION (Visit_ID, Vaccine_Name, Against, Manufacturer, Lot_No, Next_Schedule) 
VALUES (10, 'Bordetella', 'Kennel Cough', 'Bronchi-Shield', 'BRO2024-067', DATE '2025-07-20');

-- Payments
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (1, DATE '2024-01-15', 850.00, 'Cash', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (2, DATE '2024-02-10', 1200.00, 'Gcash', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (3, DATE '2024-02-20', 1500.00, 'Credit Card', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (4, DATE '2024-03-05', 850.00, 'Cash', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (5, DATE '2024-03-18', 2000.00, 'Debit Card', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (8, DATE '2024-05-08', 1500.00, 'Gcash', 'PAID');
INSERT INTO PAYMENT (Visit_ID, Payment_Date, Amount, Payment_Method, Payment_Status) 
VALUES (9, DATE '2024-06-12', 950.00, 'Cash', 'PAID');

COMMIT;

-- VERIFY INSTALLATION

SELECT 'Tables created:' AS STATUS FROM DUAL;
SELECT table_name FROM user_tables WHERE table_name IN ('CLIENT', 'CLIENT_CONTACT', 'PET', 'VETERINARIAN', 'VETERINARIAN_CONTACT', 'VISIT_RECORD', 'VACCINATION', 'PAYMENT');

SELECT 'Sequences created:' AS STATUS FROM DUAL;
SELECT sequence_name FROM user_sequences WHERE sequence_name LIKE '%_SEQ';

SELECT 'Sample Data Summary:' AS STATUS FROM DUAL;
SELECT 'Veterinarians: ' || COUNT(*) AS COUNT FROM VETERINARIAN;
SELECT 'Clients: ' || COUNT(*) AS COUNT FROM CLIENT;
SELECT 'Pets: ' || COUNT(*) AS COUNT FROM PET;
SELECT 'Visit Records: ' || COUNT(*) AS COUNT FROM VISIT_RECORD;
SELECT 'Vaccinations: ' || COUNT(*) AS COUNT FROM VACCINATION;
SELECT 'Payments: ' || COUNT(*) AS COUNT FROM PAYMENT;

PROMPT
PROMPT ============================================================
PROMPT   Schema installation complete!
PROMPT   Sample data loaded for demonstration.
PROMPT ============================================================

