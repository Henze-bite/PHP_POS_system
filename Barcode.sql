CREATE DATABASE IF NOT EXISTS pos_barcode_db
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;
USE pos_barcode_db;

-- Users
CREATE TABLE Tbl_user (
  User_id      INT           NOT NULL AUTO_INCREMENT,
  User_name    VARCHAR(200)  NOT NULL,
  User_email   VARCHAR(200)  NOT NULL UNIQUE,
  Password     VARCHAR(200)  NOT NULL,
  Role         VARCHAR(50)   NOT NULL,
  PRIMARY KEY (User_id),
  INDEX idx_user_email (User_email)
) ENGINE=InnoDB;

-- Categories
CREATE TABLE Tbl_Category (
  Category_id   INT          NOT NULL AUTO_INCREMENT,
  Category_name VARCHAR(200) NOT NULL,
  PRIMARY KEY (Category_id)
) ENGINE=InnoDB;

-- Products
CREATE TABLE Tbl_Product (
  Product_id     INT             NOT NULL AUTO_INCREMENT,
  Barcode        VARCHAR(1000)   NOT NULL UNIQUE,
  Product_name   VARCHAR(200)    NOT NULL,
  Category_id    INT             NOT NULL,
  Description    VARCHAR(2000),
  Stock          INT             NOT NULL DEFAULT 0,
  Purchase_price DECIMAL(10,2)   NOT NULL,
  Sale_price     DECIMAL(10,2)   NOT NULL,
  Image          VARCHAR(500),
  PRIMARY KEY (Product_id),
  INDEX idx_category (Category_id),
  FOREIGN KEY (Category_id)
    REFERENCES Tbl_Category(Category_id)
    ON DELETE RESTRICT
    ON UPDATE CASCADE
) ENGINE=InnoDB;

-- Tax & Discount Rates
CREATE TABLE Tbl_Taxdis (
  Taxdis_id INT           NOT NULL AUTO_INCREMENT,
  sgst      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  cgst      DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  discount  DECIMAL(5,2)  NOT NULL DEFAULT 0.00,
  PRIMARY KEY (Taxdis_id)
) ENGINE=InnoDB;

-- Invoices
CREATE TABLE Tbl_invoice (
  Invoice_id   INT             NOT NULL AUTO_INCREMENT,
  Order_date   DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
  Sub_total    DECIMAL(10,2)   NOT NULL,
  Discount     DECIMAL(10,2)   NOT NULL,
  sgst         DECIMAL(5,2)    NOT NULL,
  cgst         DECIMAL(5,2)    NOT NULL,
  Total        DECIMAL(10,2)   NOT NULL,
  Payment_type VARCHAR(50)     NOT NULL,
  Due          DECIMAL(10,2)   NOT NULL,
  Paid         DECIMAL(10,2)   NOT NULL,
  PRIMARY KEY (Invoice_id),
  INDEX idx_order_date (Order_date)
) ENGINE=InnoDB;

-- Invoice Details
CREATE TABLE Tbl_Invoice_Detail (
  id            INT           NOT NULL AUTO_INCREMENT,
  invoice_id    INT           NOT NULL,
  Category      VARCHAR(200)  NOT NULL,
  Barcode       VARCHAR(200)  NOT NULL,
  Product_id    INT           NOT NULL,
  Product_name  VARCHAR(200)  NOT NULL,
  Qty           INT           NOT NULL,
  Purchase_Price DECIMAL(10,2) NOT NULL,
  Rate          DECIMAL(10,2) NOT NULL,
  Order_date    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  INDEX idx_invoice (invoice_id),
  FOREIGN KEY (invoice_id)
    REFERENCES Tbl_invoice(Invoice_id)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB;

USE pos_barcode_db;

-- 1) Tbl_user: 10 sample users
INSERT INTO Tbl_user (User_name, User_email, Password, Role) VALUES
('Alice','alice@example.com', '$2y$10$examplehashedpassword12345','Admin'),
('Bob','bob@example.com', '$2y$10$examplehashedpassword12345','User'),
('Carol','carol@example.com', '$2y$10$examplehashedpassword12345','User'),
('Dave','dave@example.com', '$2y$10$examplehashedpassword12345','User'),
('Eve','eve@example.com', '$2y$10$examplehashedpassword12345','Admin'),
('Frank','frank@example.com', '$2y$10$examplehashedpassword12345','User'),
('Grace','grace@example.com', '$2y$10$examplehashedpassword12345','User'),
('Heidi','heidi@example.com', '$2y$10$examplehashedpassword12345','User'),
('Ivan','ivan@example.com', '$2y$10$examplehashedpassword12345','User'),
('Judy','judy@example.com', '$2y$10$examplehashedpassword12345','User');

-- 2) Tbl_Category: 10 categories
INSERT INTO Tbl_Category (Category_name) VALUES
('Electronics'),
('Clothing'),
('Groceries'),
('Books'),
('Furniture'),
('Toys'),
('Stationery'),
('Beauty'),
('Sports'),
('Automotive');

-- 3) Tbl_Product: 10 products, one per category
INSERT INTO Tbl_Product (Barcode, Product_name, Category_id, Description, Stock, Purchase_price, Sale_price, Image) VALUES
('ELEC001','Smartphone',        1,'Latest model smartphone',    50,200.00,300.00,''),
('CLTH001','Jeans',             2,'Denim jeans, various sizes',  100,20.00,40.00,''),
('GROC001','Rice (5kg)',        3,'Premium white rice',          200,5.00,10.00,''),
('BOOK001','Novel',             4,'Bestselling fiction novel',   80,8.00,15.00,''),
('FURN001','Sofa',              5,'3-seat fabric sofa',          10,150.00,300.00,''),
('TOY001','Action Figure',      6,'Collector\'s edition toy',    75,10.00,25.00,''),
('STAT001','Notebook Pack',     7,'Set of 5 ruled notebooks',    150,2.00,5.00,''),
('BEAU001','Lipstick',          8,'Matte finish lipstick',       120,3.00,8.00,''),
('SPORT001','Soccer Ball',      9,'Official size soccer ball',   60,12.00,25.00,''),
('AUTO001','Engine Oil 1L',     10,'Synthetic engine oil',        40,7.00,15.00,'');

-- 4) Tbl_Taxdis: 10 tax/discount configurations
INSERT INTO Tbl_Taxdis (sgst, cgst, discount) VALUES
(5.00, 5.00, 0.00),
(5.00, 5.00, 1.00),
(5.00, 5.00, 2.00),
(5.00, 5.00, 3.00),
(5.00, 5.00, 4.00),
(5.00, 5.00, 5.00),
(5.00, 5.00, 6.00),
(5.00, 5.00, 7.00),
(5.00, 5.00, 8.00),
(5.00, 5.00, 9.00);

-- 5) Tbl_invoice: 10 invoices
INSERT INTO Tbl_invoice (Order_date, Sub_total, Discount, sgst, cgst, Total, Payment_type, Due, Paid) VALUES
('2025-06-01', 100.00, 10.00, 5.00, 5.00, 100.00, 'Cash', 0.00, 100.00),
('2025-06-02', 200.00, 20.00, 5.00, 5.00, 190.00, 'Card', 0.00, 190.00),
('2025-06-03', 300.00, 30.00, 5.00, 5.00, 280.00, 'Cash', 0.00, 280.00),
('2025-06-04', 400.00, 40.00, 5.00, 5.00, 370.00, 'Card', 0.00, 370.00),
('2025-06-05', 500.00, 50.00, 5.00, 5.00, 460.00, 'Cash', 0.00, 460.00),
('2025-06-06', 600.00, 60.00, 5.00, 5.00, 550.00, 'Card', 0.00, 550.00),
('2025-06-07', 700.00, 70.00, 5.00, 5.00, 640.00, 'Cash', 0.00, 640.00),
('2025-06-08', 800.00, 80.00, 5.00, 5.00, 730.00, 'Card', 0.00, 730.00),
('2025-06-09', 900.00, 90.00, 5.00, 5.00, 820.00, 'Cash', 0.00, 820.00),
('2025-06-10', 1000.00,100.00,5.00, 5.00, 910.00, 'Card', 0.00, 910.00);

-- 6) Tbl_Invoice_Detail: 10 details (one per invoice)
INSERT INTO Tbl_Invoice_Detail (invoice_id, Category, Barcode, Product_id, Product_name, Qty, Purchase_Price, Rate, Order_date) VALUES
(1, 'Electronics', 'ELEC001',  1, 'Smartphone',     1, 200.00,300.00,'2025-06-01'),
(2, 'Clothing',    'CLTH001',   2, 'Jeans',          2, 20.00, 40.00,'2025-06-02'),
(3, 'Groceries',   'GROC001',   3, 'Rice (5kg)',     3, 5.00,  10.00,'2025-06-03'),
(4, 'Books',       'BOOK001',   4, 'Novel',          1, 8.00,  15.00,'2025-06-04'),
(5, 'Furniture',   'FURN001',   5, 'Sofa',           1,150.00,300.00,'2025-06-05'),
(6, 'Toys',        'TOY001',    6, 'Action Figure',  4,10.00,  25.00,'2025-06-06'),
(7, 'Stationery',  'STAT001',   7, 'Notebook Pack',  5,2.00,   5.00,'2025-06-07'),
(8, 'Beauty',      'BEAU001',   8, 'Lipstick',       3,3.00,   8.00,'2025-06-08'),
(9, 'Sports',      'SPORT001',  9, 'Soccer Ball',    2,12.00,  25.00,'2025-06-09'),
(10,'Automotive',  'AUTO001',  10, 'Engine Oil 1L',  1,7.00,   15.00,'2025-06-10');