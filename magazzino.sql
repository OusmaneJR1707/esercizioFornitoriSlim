-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Creato il: Mar 08, 2026 alle 20:48
-- Versione del server: 10.4.32-MariaDB
-- Versione PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `magazzino`
--

-- --------------------------------------------------------

--
-- Struttura della tabella `catalogo`
--

CREATE TABLE `catalogo` (
  `fid` int(10) UNSIGNED NOT NULL,
  `pid` int(10) UNSIGNED NOT NULL,
  `costo` decimal(6,2) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `catalogo`
--

INSERT INTO `catalogo` (`fid`, `pid`, `costo`) VALUES
(2, 1, 23.00),
(2, 2, 1.20),
(3, 3, 0.15),
(4, 4, 15.00),
(5, 5, 22.30),
(6, 6, 5.40),
(7, 7, 8.90),
(8, 8, 12.10),
(9, 9, 3.50),
(10, 10, 0.99),
(11, 11, 45.00),
(12, 12, 7.50),
(14, 14, 32.10),
(15, 15, 4.80),
(16, 16, 2.30),
(17, 17, 12.00),
(18, 18, 65.00),
(19, 19, 9.15),
(20, 20, 0.45),
(21, 21, 28.90),
(21, 27, 5.00),
(21, 28, 3.00),
(22, 22, 14.20),
(23, 23, 0.85),
(24, 24, 5.60),
(26, 12, 5.00),
(26, 19, 25.00),
(26, 26, 15.00),
(26, 29, 12.00),
(28, 16, 5.00);

-- --------------------------------------------------------

--
-- Struttura della tabella `fornitori`
--

CREATE TABLE `fornitori` (
  `fid` int(10) UNSIGNED NOT NULL,
  `fnome` varchar(50) DEFAULT NULL,
  `indirizzo` varchar(100) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `fornitori`
--

INSERT INTO `fornitori` (`fid`, `fnome`, `indirizzo`) VALUES
(1, 'Logistica Nord S.p.A.', 'Via Milano 1, Milano'),
(2, 'Ferramenta Rossi', 'Corso Italia 22, Torino'),
(3, 'Distribuzione Beta', 'Via Roma 5, Roma'),
(4, 'Forniture Elettriche', 'Via Napoli 12, Napoli'),
(5, 'Meccanica Precisione', 'Via Emilia 8, Bologna'),
(6, 'Utensileria Veneta', 'Via Brenta 4, Padova'),
(7, 'Ricambi Sud', 'Via Etna 90, Catania'),
(8, 'Componenti S.r.l.', 'Via Toscana 11, Firenze'),
(9, 'Global Service', 'Via Liguria 3, Genova'),
(10, 'Soluzioni Magazzino', 'Via Sardegna 45, Cagliari'),
(11, 'TecnoRicambi', 'Via Bari 7, Bari'),
(12, 'Officina Meccanica', 'Via Palermo 33, Palermo'),
(14, 'Fast Delivery', 'Via Trento 2, Trento'),
(15, 'MetalSupply', 'Via Brescia 9, Brescia'),
(16, 'Quality Parts', 'Via Udine 21, Udine'),
(17, 'Industrial Tools', 'Via Lucca 6, Lucca'),
(18, 'Punto Meccanica', 'Via Pisa 18, Pisa'),
(19, 'Direct Hardware', 'Via Ancona 4, Ancona'),
(20, 'Master Service', 'Via Parma 10, Parma'),
(21, 'Elite Supplies', 'Via Modena 14, Modena'),
(22, 'Tech Solutions', 'Via Ferrara 55, Ferrara'),
(23, 'Prime Parts', 'Via Salerno 8, Salerno'),
(24, 'Global Logistics', 'Via Latina 2, Latina'),
(25, 'Top Tools', 'Via Como 1, Como'),
(26, 'Acme', 'Via G. Leopardi 9, Catanzaro'),
(28, 'Fratelli Gualandris', 'Via Roma 17, Rovato'),
(29, 'Azienda agricola Scotti', 'Via A. Manzoni, Napoli');

-- --------------------------------------------------------

--
-- Struttura della tabella `pezzi`
--

CREATE TABLE `pezzi` (
  `pid` int(10) UNSIGNED NOT NULL,
  `pnome` varchar(50) DEFAULT NULL,
  `colore` varchar(20) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `pezzi`
--

INSERT INTO `pezzi` (`pid`, `pnome`, `colore`) VALUES
(1, 'Bullone M8', 'Zinco'),
(2, 'Vite Autofortante', 'Nero'),
(3, 'Rondella Piana', 'Grigio'),
(4, 'Ingranaggio Plastica', 'Rosso'),
(5, 'Cuscinetto a Sfera', 'Metallo'),
(6, 'Molla a Compressione', 'Acciaio'),
(7, 'Guarnizione OR', 'Nero'),
(8, 'Dado Autobloccante', 'Blu'),
(9, 'Perno Centrale', 'Verde'),
(10, 'Flangia Acciaio', 'Giallo'),
(11, 'Valvola a Sfera', 'Ottone'),
(12, 'Raccordo Rapido', 'Grigio'),
(13, 'Cinghia Trapezoidale', 'Nero'),
(14, 'Puleggia Alluminio', 'Argento'),
(15, 'Staffa Fissaggio', 'Zincato'),
(16, 'Manicotto Gomma', 'Nero'),
(17, 'Filtro Aria', 'Bianco'),
(18, 'Sensore Pressione', 'Nero'),
(19, 'Cavo Acciaio', 'Grigio'),
(20, 'Morsetto Elettrico', 'Blu'),
(21, 'Tubo Rame', 'Rame'),
(22, 'Piastra Supporto', 'Verde'),
(23, 'Vite a Brugola', 'Nero'),
(24, 'Chiave Esagonale', 'Cromo'),
(25, 'Rivetto Alluminio', 'Argento'),
(26, 'Matita', 'Rosa'),
(27, 'Gomma', 'Rosso'),
(28, 'Righello', 'Verde'),
(29, 'Penna', 'Nero');

-- --------------------------------------------------------

--
-- Struttura della tabella `utenti`
--

CREATE TABLE `utenti` (
  `uid` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `ruolo` enum('admin','fornitore') NOT NULL,
  `fid` int(10) UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dump dei dati per la tabella `utenti`
--

INSERT INTO `utenti` (`uid`, `username`, `password`, `ruolo`, `fid`) VALUES
(2222222, 'Suru', 'password', 'fornitore', 26),
(1111111111, 'ciao', '1234', 'admin', NULL);

--
-- Indici per le tabelle scaricate
--

--
-- Indici per le tabelle `catalogo`
--
ALTER TABLE `catalogo`
  ADD PRIMARY KEY (`fid`,`pid`),
  ADD KEY `pid` (`pid`);

--
-- Indici per le tabelle `fornitori`
--
ALTER TABLE `fornitori`
  ADD PRIMARY KEY (`fid`);

--
-- Indici per le tabelle `pezzi`
--
ALTER TABLE `pezzi`
  ADD PRIMARY KEY (`pid`);

--
-- Indici per le tabelle `utenti`
--
ALTER TABLE `utenti`
  ADD PRIMARY KEY (`uid`),
  ADD UNIQUE KEY `username` (`username`),
  ADD KEY `fid` (`fid`);

--
-- AUTO_INCREMENT per le tabelle scaricate
--

--
-- AUTO_INCREMENT per la tabella `fornitori`
--
ALTER TABLE `fornitori`
  MODIFY `fid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=30;

--
-- AUTO_INCREMENT per la tabella `pezzi`
--
ALTER TABLE `pezzi`
  MODIFY `pid` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=31;

--
-- AUTO_INCREMENT per la tabella `utenti`
--
ALTER TABLE `utenti`
  MODIFY `uid` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=1111111112;

--
-- Limiti per le tabelle scaricate
--

--
-- Limiti per la tabella `catalogo`
--
ALTER TABLE `catalogo`
  ADD CONSTRAINT `catalogo_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `fornitori` (`fid`),
  ADD CONSTRAINT `catalogo_ibfk_2` FOREIGN KEY (`pid`) REFERENCES `pezzi` (`pid`);

--
-- Limiti per la tabella `utenti`
--
ALTER TABLE `utenti`
  ADD CONSTRAINT `utenti_ibfk_1` FOREIGN KEY (`fid`) REFERENCES `fornitori` (`fid`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
