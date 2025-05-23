    -- e_sinav veri tabanı yapısı: Tüm tablolar, ilişkiler ve alanlar dahil

    CREATE DATABASE IF NOT EXISTS e_sinav CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
    USE e_sinav;

    -- 1. Kullanıcılar
    CREATE TABLE kullanicilar (
        kullanici_id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_adi VARCHAR(50) UNIQUE NOT NULL,
        sifre CHAR(32) NOT NULL,
        ad_soyad VARCHAR(100) NOT NULL,
        e_posta VARCHAR(100),
        telefon VARCHAR(20),
        rol ENUM('admin', 'ogretmen', 'ogrenci') NOT NULL,
        aktif BOOLEAN DEFAULT TRUE,
        kayit_tarihi DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    -- 2. Roller (isteğe bağlı, kullanıcıdan bağımsız kullanılabilir)
    CREATE TABLE roller (
        rol_id INT AUTO_INCREMENT PRIMARY KEY,
        rol_adi VARCHAR(50) UNIQUE NOT NULL,
        seviye INT NOT NULL
    );

    -- 3. Sınavlar
    CREATE TABLE sinavlar (
        sinav_id INT AUTO_INCREMENT PRIMARY KEY,
        baslik VARCHAR(100) NOT NULL,
        aciklama TEXT,
        ogretmen_id INT NOT NULL,
        baslangic DATETIME,
        bitis DATETIME,
        sure INT NOT NULL,
        aktif BOOLEAN DEFAULT TRUE,
        FOREIGN KEY (ogretmen_id) REFERENCES kullanicilar(kullanici_id)
    );

    -- 4. Sorular
    CREATE TABLE sorular (
        soru_id INT AUTO_INCREMENT PRIMARY KEY,
        sinav_id INT NOT NULL,
        icerik TEXT NOT NULL,
        puan DECIMAL(5,2) DEFAULT 1.00,
        zorluk ENUM('kolay', 'orta', 'zor') DEFAULT 'orta',
        FOREIGN KEY (sinav_id) REFERENCES sinavlar(sinav_id)
    );

    -- 5. Seçenekler
    CREATE TABLE secenekler (
        secenek_id INT AUTO_INCREMENT PRIMARY KEY,
        soru_id INT NOT NULL,
        secenek_harf CHAR(1) NOT NULL,
        secenek_icerik TEXT NOT NULL,
        dogru_mu BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (soru_id) REFERENCES sorular(soru_id)
    );

    -- 6. Öğrenci - Sınav eşleşmesi
    CREATE TABLE ogrenci_sinav (
        ogrenci_sinav_id INT AUTO_INCREMENT PRIMARY KEY,
        ogrenci_id INT NOT NULL,
        sinav_id INT NOT NULL,
        baslangic_zamani DATETIME DEFAULT CURRENT_TIMESTAMP,
        bitis_zamani DATETIME,
        tamamlandi BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (ogrenci_id) REFERENCES kullanicilar(kullanici_id),
        FOREIGN KEY (sinav_id) REFERENCES sinavlar(sinav_id)
    );

    -- 7. Öğrenci cevapları
    CREATE TABLE ogrenci_cevaplar (
        cevap_id INT AUTO_INCREMENT PRIMARY KEY,
        ogrenci_sinav_id INT NOT NULL,
        soru_id INT NOT NULL,
        secilen_secenek_id INT,
        FOREIGN KEY (ogrenci_sinav_id) REFERENCES ogrenci_sinav(ogrenci_sinav_id),
        FOREIGN KEY (soru_id) REFERENCES sorular(soru_id),
        FOREIGN KEY (secilen_secenek_id) REFERENCES secenekler(secenek_id)
    );

    -- 8. Etiketler
    CREATE TABLE etiketler (
        etiket_id INT AUTO_INCREMENT PRIMARY KEY,
        isim VARCHAR(50) UNIQUE NOT NULL
    );

    -- 9. Soru - Etiket eşleşmesi
    CREATE TABLE soru_etiket (
        soru_id INT NOT NULL,
        etiket_id INT NOT NULL,
        PRIMARY KEY (soru_id, etiket_id),
        FOREIGN KEY (soru_id) REFERENCES sorular(soru_id),
        FOREIGN KEY (etiket_id) REFERENCES etiketler(etiket_id)
    );

    -- 10. Giriş kayıtları
    CREATE TABLE giris_kaydi (
        kayit_id INT AUTO_INCREMENT PRIMARY KEY,
        kullanici_id INT NOT NULL,
        ip_adresi VARCHAR(45),
        tarayici VARCHAR(255),
        zaman DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (kullanici_id) REFERENCES kullanicilar(kullanici_id)
    );
