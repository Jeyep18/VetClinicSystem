CREATE TABLE CLIENT (
    Client_ID        NUMBER        PRIMARY KEY,
    Firstname        VARCHAR2(50)  NOT NULL,
    Middlename       VARCHAR2(50),
    Lastname         VARCHAR2(50)  NOT NULL,
    Suffix           VARCHAR2(10),
    Address          VARCHAR2(200) NOT NULL
);

CREATE TABLE CLIENT_CONTACT (
    Client_Contact_ID  NUMBER         PRIMARY KEY,
    Client_ID          NUMBER         NOT NULL,
    Contact_Number     VARCHAR2(20)   NOT NULL,
    
    CONSTRAINT uq_client_contact UNIQUE (Client_ID, Contact_Number),

    CONSTRAINT fk_contact_client
        FOREIGN KEY (Client_ID)
        REFERENCES CLIENT(Client_ID)
);

CREATE TABLE PET (
    Pet_ID      NUMBER         PRIMARY KEY,
    Owner       NUMBER         NOT NULL,
    Pet_Name    VARCHAR2(50)   NOT NULL,
    Breed       VARCHAR2(50),
    Birthdate   DATE,
    Markings    VARCHAR2(200),

    CONSTRAINT fk_pet_owner
        FOREIGN KEY (Owner)
        REFERENCES CLIENT(Client_ID)
);

CREATE TABLE VETERINARIAN (
    Vet_ID        NUMBER         PRIMARY KEY,
    Firstname     VARCHAR2(50)   NOT NULL,
    Middlename    VARCHAR2(50),
    Lastname      VARCHAR2(50)   NOT NULL,
    Suffix        VARCHAR2(10),
    Specialization NUMBER
);

CREATE TABLE VETERINARIAN_CONTACT (
    Vet_Contact_ID  NUMBER         PRIMARY KEY,
    Vet_ID          NUMBER         NOT NULL,
    Contact_Number  VARCHAR2(20)   NOT NULL,

    CONSTRAINT fk_vet_contact
        FOREIGN KEY (Vet_ID)
        REFERENCES VETERINARIAN(Vet_ID)
);

CREATE TABLE SPECIALIZATION (
    Specialization_ID   NUMBER         PRIMARY KEY,
    Consultation_Fee    NUMBER(10,2)   NOT NULL
);

ALTER TABLE VETERINARIAN
ADD CONSTRAINT fk_vet_specialization
FOREIGN KEY (Specialization)
REFERENCES SPECIALIZATION(Specialization_ID);

CREATE TABLE VISIT_RECORD (
    Visit_ID       NUMBER        PRIMARY KEY,
    Visit_Date     DATE          NOT NULL,
    Pet_Weight     NUMBER(5,2)   NOT NULL,
    Pet            NUMBER        NOT NULL,
    Veterinarian   NUMBER        NOT NULL,

    CONSTRAINT fk_visit_pet
        FOREIGN KEY (Pet)
        REFERENCES PET(Pet_ID),

    CONSTRAINT fk_visit_vet
        FOREIGN KEY (Veterinarian)
        REFERENCES VETERINARIAN(Vet_ID),

    CONSTRAINT chk_weight CHECK (Pet_Weight > 0)
);

CREATE TABLE PAYMENT (
    Payment_ID      NUMBER          PRIMARY KEY,
    Payment_Date    DATE            NOT NULL,
    Amount          NUMBER(10,2)    NOT NULL,
    Payment_Method  VARCHAR2(20)    NOT NULL,
    Visit_ID        NUMBER          NOT NULL,

    CONSTRAINT fk_payment_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID),

    CONSTRAINT chk_payment_method
        CHECK (Payment_Method IN ('Cash', 'Credit Card', 'Debit Card', 'Gcash'))
);

CREATE TABLE VACCINATION (
    Vaccination_ID  NUMBER         PRIMARY KEY,
    Visit_ID        NUMBER         NOT NULL,
    Vaccine_Name    VARCHAR2(50)   NOT NULL,
    Against         VARCHAR2(100),
    Manufacturer    VARCHAR2(50),
    Lot_No          VARCHAR2(30)   NOT NULL,
    Next_Schedule   DATE,

    CONSTRAINT fk_vaccination_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID)
);

CREATE TABLE DEWORMING (
    Deworming_ID  NUMBER         PRIMARY KEY,
    Visit_ID      NUMBER         NOT NULL,
    Dewormer_Name VARCHAR2(100)   NOT NULL,
    Dosage        VARCHAR2(50)   NOT NULL,
    Next_Schedule DATE,

    CONSTRAINT fk_deworming_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID)
);

CREATE TABLE DIAGNOSTIC_TEST (
    DiagnosticTest_ID  NUMBER         PRIMARY KEY,
    Visit_ID           NUMBER         NOT NULL,
    Test_Type          VARCHAR2(50)   NOT NULL,
    Result             VARCHAR2(255),

    CONSTRAINT fk_test_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID)
);

CREATE TABLE GROOMING (
    Grooming_ID   NUMBER         PRIMARY KEY,
    Visit_ID      NUMBER         NOT NULL,
    Grooming_Type VARCHAR2(50)   NOT NULL,
    Groomer_Name  VARCHAR2(100),

    CONSTRAINT fk_grooming_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID)
);

CREATE TABLE CHECK_UP (
    CheckUp_ID        NUMBER         PRIMARY KEY,
    Visit_ID          NUMBER         NOT NULL,
    Observation_Notes VARCHAR2(255),
    Next_Schedule     DATE,

    CONSTRAINT fk_checkup_visit
        FOREIGN KEY (Visit_ID)
        REFERENCES VISIT_RECORD(Visit_ID)
);