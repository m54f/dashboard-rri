<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RRI Malang - YouTube Description Generator</title>
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --text-color: #212529;
            --border-color: #dee2e6;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            padding: 20px;
        }

        .container {
            max-width: 1100px;
            margin: 0 auto;
        }

        header {
            text-align: center;
            margin-bottom: 30px;
        }

        header h1 {
            color: var(--primary-color);
            font-size: 24px;
            margin-bottom: 5px;
        }

        header p {
            color: #6c757d;
            font-size: 14px;
        }

        .main-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        @media (max-width: 768px) {
            .main-layout {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .card h2 {
            font-size: 18px;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--bg-color);
            padding-bottom: 8px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        /* Grid fleksibel untuk baris nama program utama */
        .program-row {
            display: grid;
            grid-template-columns: 1fr;
            gap: 10px;
            transition: all 0.3s ease;
        }

        .program-row.show-side {
            grid-template-columns: 1fr 1fr;
        }

        /* Grid khusus untuk kolom penyiar agar dropdown ada di kiri */
        .presenter-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            gap: 10px;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 14px;
        }

        input[type="text"], input[type="time"], input[type="date"], select, textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 14px;
            outline: none;
            transition: border 0.2s;
        }

        input:focus, select:focus, textarea:focus {
            border-color: var(--primary-color);
        }

        textarea.preview-box {
            height: 380px;
            font-family: Courier, monospace;
            background-color: #f1f3f5;
            resize: none;
        }

        .btn {
            display: inline-block;
            width: 100%;
            padding: 12px;
            background-color: var(--primary-color);
            color: white;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            font-size: 15px;
            cursor: pointer;
            transition: background 0.2s;
            text-align: center;
        }

        .btn:hover {
            background-color: #0b5ed7;
        }

        .btn-success {
            background-color: var(--success-color);
            margin-top: 10px;
        }

        .btn-success:hover {
            background-color: #157347;
        }

        .toast {
            display: none;
            background-color: #333;
            color: white;
            padding: 10px;
            position: fixed;
            bottom: 20px;
            right: 20px;
            border-radius: 4px;
            font-size: 14px;
            z-index: 1000;
        }

        .hidden-element {
            display: none !important;
        }
    </style>
</head>
<body>

<div class="container">
    <header>
        <h1>RRI Malang YouTube Description Generator</h1>
        <p>Isi form di bawah untuk membuat teks deskripsi YouTube yang seragam dan siap baca oleh sistem.</p>
    </header>

    <div class="main-layout">
        <!-- SISI KIRI: FORM INPUT -->
        <div class="card">
            <h2>Input Data Siaran</h2>
            <form id="metaForm">
                <div class="form-group">
                    <label>Nama Program / Acara</label>
                    <div id="programContainer" class="program-row">
                        <!-- Mengganti value BERITA PAGI menjadi BERITA saja untuk fleksibilitas -->
                        <select id="program" onchange="handleProgramChange(); generateText();">
                            <option value="DIALOG MALANG MENYAPA">Dialog Malang Menyapa</option>
                            <option value="MUTIARA PAGI">Mutiara Pagi</option>
                            <option value="BERITA">Berita (Pagi/Siang/Malam)</option>
                            <option value="CATUR LARAS (JAGONGAN UMKM)">Catur Laras (Jagongan UMKM)</option>
                            <option value="JAGA MALAM">Jaga Malam</option>
                            <option value="LAINNYA">Lainnya...</option>
                        </select>
                        
                        <!-- Dropdown tambahan khusus Waktu Berita -->
                        <select id="waktuBerita" class="hidden-element" onchange="generateText()">
                            <option value="PAGI">Pagi</option>
                            <option value="SIANG">Siang</option>
                            <option value="MALAM">Malam</option>
                        </select>

                        <!-- Input kustom untuk opsi Lainnya -->
                        <input type="text" id="programKustom" class="hidden-element" placeholder="Ketik nama acara baru di sini" oninput="generateText()">
                    </div>
                </div>

                <div class="form-group">
                    <label>Tema Acara</label>
                    <input type="text" id="tema" placeholder="Contoh: MPLS Ramah 2026, Tanpa Perploncoan" oninput="generateText()">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Hari</label>
                        <select id="hari" oninput="generateText()">
                            <option value="Senin">Senin</option>
                            <option value="Selasa">Selasa</option>
                            <option value="Rabu">Rabu</option>
                            <option value="Kamis">Kamis</option>
                            <option value="Jumat">Jumat</option>
                            <option value="Sabtu">Sabtu</option>
                            <option value="Minggu">Minggu</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" id="tanggalInput" oninput="generateText()">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Jam Mulai</label>
                        <input type="time" id="jamMulai" oninput="generateText()">
                    </div>
                    <div class="form-group">
                        <label>Jam Selesai</label>
                        <input type="time" id="jamSelesai" oninput="generateText()">
                    </div>
                </div>

                <div class="form-group">
                    <label>Live Dimana (Programa)</label>
                    <select id="live" oninput="generateText()">
                        <option value="94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official">Pro 1 FM 94.6 Mhz</option>
                        <option value="87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official">Pro 2 FM 87.9 Mhz</option>
                        <option value="105.3 FM Pro 4 RRI Malang dan Youtube RRI Malang Official">Pro 4 FM 105.3 Mhz</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Pemandu Acara</label>
                    <div class="presenter-row">
                        <select id="jenisPenyiar" oninput="generateText()">
                            <option value="Presenter">Presenter</option>
                            <option value="Penyiar">Penyiar</option>
                            <option value="Host">Host</option>
                        </select>
                        <input type="text" id="penyiar" placeholder="Nama pengisi acara" oninput="generateText()">
                    </div>
                </div>

                <div class="form-group">
                    <label>Narasumber</label>
                    <textarea id="narasumber" rows="2" placeholder="1. Nama (Jabatan)&#10;2. Nama (Jabatan)" oninput="generateText()"></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Petugas Teknik (PT)</label>
                        <input type="text" id="teknik" placeholder="Contoh: Yhanuar, Alif" oninput="generateText()">
                    </div>
                    <div class="form-group">
                        <label>Petugas PA / Acara</label>
                        <input type="text" id="pa" placeholder="Contoh: Roni" oninput="generateText()">
                    </div>
                </div>
            </form>
        </div>

        <!-- SISI KANAN: PREVIEW & COPY -->
        <div class="card" style="display: flex; flex-direction: column; justify-content: space-between;">
            <div>
                <h2>Preview Deskripsi YouTube</h2>
                <textarea id="preview" class="preview-box" readonly></textarea>
            </div>
            <div>
                <button class="btn btn-success" onclick="copyToClipboard()">📋 Salin Deskripsi</button>
            </div>
        </div>
    </div>
</div>

<div id="toast" class="toast">Teks berhasil disalin ke clipboard!</div>

<script>
    // MANAJEMEN VISIBILITAS KOLOM SAMPING
    function handleProgramChange() {
        const programSelect = document.getElementById('program').value;
        const customInput = document.getElementById('programKustom');
        const beritaSelect = document.getElementById('waktuBerita');
        const container = document.getElementById('programContainer');

        // Sembunyikan semua elemen opsional terlebih dahulu
        customInput.classList.add('hidden-element');
        beritaSelect.classList.add('hidden-element');
        container.classList.remove('show-custom', 'show-side');

        if (programSelect === "LAINNYA") {
            customInput.classList.remove('hidden-element');
            container.classList.add('show-side');
            customInput.focus();
        } else if (programSelect === "BERITA") {
            beritaSelect.classList.remove('hidden-element');
            container.classList.add('show-side');
        }
    }

    function formatJamSistem(timeString) {
        if (!timeString) return "00.00";
        return timeString.replace(":", ".");
    }

    function formatTanggalIndonesia(dateString) {
        if (!dateString) return "-";
        const date = new Date(dateString);
        if (isNaN(date.getTime())) return "-";

        const bulanIndo = [
            "Januari", "Februari", "Maret", "April", "Mei", "Juni",
            "Juli", "Agustus", "September", "Oktober", "November", "Desember"
        ];

        return `${date.getDate()} ${bulanIndo[date.getMonth()]} ${date.getFullYear()}`;
    }

    function setTodayDateAndDefaultTime() {
        const today = new Date();
        const yyyy = today.getFullYear();
        let mm = today.getMonth() + 1;
        let dd = today.getDate();

        if (dd < 10) dd = '0' + dd;
        if (mm < 10) mm = '0' + mm;

        document.getElementById('tanggalInput').value = `${yyyy}-${mm}-${dd}`;
        
        const hariIndo = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
        document.getElementById('hari').value = hariIndo[today.getDay()];

        let currentHour = today.getHours();
        let nextHour = (currentHour + 1) % 24;
        
        let formatHH = currentHour < 10 ? '0' + currentHour : currentHour;
        let formatNextHH = nextHour < 10 ? '0' + nextHour : nextHour;

        document.getElementById('jamMulai').value = `${formatHH}:00`;
        document.getElementById('jamSelesai').value = `${formatNextHH}:00`;
    }

    function generateText() {
        const programSelect = document.getElementById('program').value;
        const customProgramText = document.getElementById('programKustom').value;
        const waktuBerita = document.getElementById('waktuBerita').value;
        
        // Logika penggabungan teks program final
        let programFinal = "";
        if (programSelect === "LAINNYA") {
            programFinal = customProgramText || "Nama Program Kustom";
        } else if (programSelect === "BERITA") {
            programFinal = `BERITA ${waktuBerita}`; // Menghasilkan "BERITA PAGI", "BERITA SIANG", atau "BERITA MALAM"
        } else {
            programFinal = programSelect;
        }

        const programTitleCase = programFinal.toUpperCase();
        const tema = document.getElementById('tema').value || '-';
        const hari = document.getElementById('hari').value;
        const tanggalMentah = document.getElementById('tanggalInput').value;
        const tanggalFormatted = formatTanggalIndonesia(tanggalMentah);
        const hariTanggalGabung = `${hari}, ${tanggalFormatted}`;

        const jamMulaiMentah = document.getElementById('jamMulai').value;
        const jamSelesaiMentah = document.getElementById('jamSelesai').value;
        const jamMulai = formatJamSistem(jamMulaiMentah);
        const jamSelesai = formatJamSistem(jamSelesaiMentah);

        const liveSelection = document.getElementById('live').value;
        let liveText = "";
        if (liveSelection.includes("94,6 FM Pro 1")) {
            liveText = "Live di 94,6 FM Pro 1 RRI Malang dan Youtube RRI Malang Official";
        } else if (liveSelection.includes("87.9 FM Pro 2")) {
            liveText = "Live di 87.9 FM Pro 2 RRI Malang dan Youtube RRI Malang Official";
        } else {
            liveText = "Live di 105,3 FM Pro 4 RRI Malang dan YouTube RRI Malang Official";
        }
        
        const jenisPenyiar = document.getElementById('jenisPenyiar').value;
        const penyiar = document.getElementById('penyiar').value || '-';
        const narasumber = document.getElementById('narasumber').value || '-';
        const teknik = document.getElementById('teknik').value || '-';
        const pa = document.getElementById('pa').value || '-';

        let template = "";
        if (programSelect === "DIALOG MALANG MENYAPA") {
            template += "Prioritas Nasional\n";
        }
        
        template += `${programTitleCase}\n`;
        template += `${hariTanggalGabung}\n`;
        template += `Pukul ${jamMulai} s/d ${jamSelesai} WIB\n`;
        template += `${liveText}\n\n`;
        
        template += `Tema : " ${tema} "\n\n`;
        template += `${jenisPenyiar} : ${penyiar}\n\n`;
        template += `Narasumber :\n${narasumber}\n\n`;
        
        template += `Pengarah Teknik : ${teknik}\n`;
        template += `Pengarah Acara : ${pa}\n\n`;
        
        template += `Dapatkan informasi terupdate dan hiburan terkini hanya di Radio Republik Indonesia\n`;
        template += `Programa 1 FM 94.6 Mhz & AM 891 KHz\n`;
        template += `Programa 2 FM 87.9 Mhz\n`;
        template += `Programa 4 FM 105.3 Mhz\n`;
        template += `Aplikasi RRI Digital dan Kanal rri.co.id\n`;
        template += `Youtube RRI MALANG Official`;

        document.getElementById('preview').value = template;
    }

    function copyToClipboard() {
        const previewTextArea = document.getElementById('preview');
        previewTextArea.select();
        previewTextArea.setSelectionRange(0, 99999);

        navigator.clipboard.writeText(previewTextArea.value).then(() => {
            const toast = document.getElementById('toast');
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 2500);
        });
    }

    window.onload = function() {
        setTodayDateAndDefaultTime();
        generateText();
    };
</script>

</body>
</html>