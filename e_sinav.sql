CREATE TABLE okullar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(150),
    sehir VARCHAR(100),
    ilce VARCHAR(100)
);

CREATE TABLE siniflar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(50),
    seviye INT,
    okul_id INT,
    FOREIGN KEY (okul_id) REFERENCES okullar(id)
);

CREATE TABLE kullanicilar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100),
    eposta VARCHAR(100) UNIQUE,
    sifre VARCHAR(255),
    rol ENUM('ogrenci', 'ogretmen', 'admin') NOT NULL,
    okul_id INT,
    sinif_id INT,
    kayit_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (okul_id) REFERENCES okullar(id),
    FOREIGN KEY (sinif_id) REFERENCES siniflar(id)
);

CREATE TABLE dersler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100)
);

CREATE TABLE ogretmen_ders_sinif (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogretmen_id INT,
    ders_id INT,
    sinif_id INT,
    FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (ders_id) REFERENCES dersler(id),
    FOREIGN KEY (sinif_id) REFERENCES siniflar(id)
);

CREATE TABLE sinavlar (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ad VARCHAR(100),
    ders_id INT,
    ogretmen_id INT,
    odev_mi BOOLEAN DEFAULT 0,
    olusturma_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ders_id) REFERENCES dersler(id),
    FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id)
);

CREATE TABLE sorular (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sinav_id INT,
    soru_metni TEXT,
    dogru_secenek_id INT,
    zorluk ENUM('kolay', 'orta', 'zor'),
    FOREIGN KEY (sinav_id) REFERENCES sinavlar(id)
);

CREATE TABLE secenekler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    soru_id INT,
    harf CHAR(1),
    metin TEXT,
    FOREIGN KEY (soru_id) REFERENCES sorular(id)
);

CREATE TABLE ogrenci_sinavlari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT,
    sinav_id INT,
    puan DECIMAL(5,2),
    baslama_zamani DATETIME,
    bitis_zamani DATETIME,
    FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (sinav_id) REFERENCES sinavlar(id)
);

CREATE TABLE ogrenci_cevaplari (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_sinav_id INT,
    soru_id INT,
    secilen_secenek_id INT,
    dogru_mu BOOLEAN,
    FOREIGN KEY (ogrenci_sinav_id) REFERENCES ogrenci_sinavlari(id),
    FOREIGN KEY (soru_id) REFERENCES sorular(id),
    FOREIGN KEY (secilen_secenek_id) REFERENCES secenekler(id)
);

CREATE TABLE hata_bildirimleri (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT,
    soru_id INT,
    aciklama TEXT,
    bildirim_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (soru_id) REFERENCES sorular(id)
);

CREATE TABLE takip_edilen_ogretmenler (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT,
    ogretmen_id INT,
    FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(id)
);

CREATE TABLE ders_analizi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ogrenci_id INT,
    ders_id INT,
    dogru_sayisi INT,
    yanlis_sayisi INT,
    sinav_sayisi INT,
    guncelleme_tarihi TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar(id),
    FOREIGN KEY (ders_id) REFERENCES dersler(id)
);
