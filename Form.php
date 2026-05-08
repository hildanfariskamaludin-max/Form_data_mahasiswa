<?php
require_once 'koneksi.php';
session_start();

$mode      = 'tambah';   
$mahasiswa = ['id' => '', 'nim' => '', 'nama' => '', 'jurusan' => '', 'foto' => ''];
$errors    = [];
$success   = false;


if (isset($_GET['id'])) {
    $id   = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT * FROM mahasiswa WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $row  = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        header('Location: index.php');
        exit;
    }

    $mode      = 'edit';
    $mahasiswa = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_post  = (int)   ($_POST['id']      ?? 0);
    $nim      = trim(   $_POST['nim']      ?? '');
    $nama     = trim(   $_POST['nama']     ?? '');
    $jurusan  = trim(   $_POST['jurusan']  ?? '');
    $modePost = trim(   $_POST['mode']     ?? 'tambah');

    if ($nim === '')    $errors[] = 'NIM tidak boleh kosong.';
    if ($nama === '')   $errors[] = 'Nama tidak boleh kosong.';
    if ($jurusan === '') $errors[] = 'Jurusan tidak boleh kosong.';

    $namaFile = $mahasiswa['foto'] ?: 'default.png';

    if (isset($_FILES['foto']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
        $file = $_FILES['foto'];

      
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Terjadi kesalahan saat mengunggah file (kode: ' . $file['error'] . ').';
        } else {
            // Validasi ukuran
            if ($file['size'] > UPLOAD_MAX_SIZE) {
                $errors[] = 'Ukuran file foto melebihi 2 MB.';
            }

          
            $finfo    = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);

            if (!in_array($mimeType, ALLOWED_TYPES)) {
                $errors[] = 'Tipe file tidak diizinkan. Gunakan JPG atau PNG.';
            }

           
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTS)) {
                $errors[] = 'Ekstensi file tidak diizinkan. Gunakan .jpg, .jpeg, atau .png.';
            }

            if (empty($errors)) {
             
                $namaFileBaru = uniqid('mhs_', true) . '.' . $ext;
                $tujuan       = UPLOAD_DIR . $namaFileBaru;

                if (!is_dir(UPLOAD_DIR)) {
                    mkdir(UPLOAD_DIR, 0755, true);
                }

                if (move_uploaded_file($file['tmp_name'], $tujuan)) {
                    
                    if ($modePost === 'edit' && $namaFile !== 'default.png') {
                        $fotoLama = UPLOAD_DIR . $namaFile;
                        if (file_exists($fotoLama)) unlink($fotoLama);
                    }
                    $namaFile = $namaFileBaru;
                } else {
                    $errors[] = 'Gagal memindahkan file ke direktori uploads/.';
                }
            }
        }
    } elseif ($modePost === 'tambah') {
        
        $errors[] = 'Foto profil wajib diunggah.';
    }

  
    if (empty($errors)) {
        if ($modePost === 'tambah') {
            $stmt = $conn->prepare(
                "INSERT INTO mahasiswa (nim, nama, jurusan, foto) VALUES (?, ?, ?, ?)"
            );
            $stmt->bind_param('ssss', $nim, $nama, $jurusan, $namaFile);
        } else {
            $stmt = $conn->prepare(
                "UPDATE mahasiswa SET nim=?, nama=?, jurusan=?, foto=? WHERE id=?"
            );
            $stmt->bind_param('ssssi', $nim, $nama, $jurusan, $namaFile, $id_post);
        }

        if ($stmt->execute()) {
            $stmt->close();
            $_SESSION['flash'] = ($modePost === 'tambah')
                ? "Data mahasiswa \"$nama\" berhasil ditambahkan."
                : "Data mahasiswa \"$nama\" berhasil diperbarui.";

            header('Location: index.php');
            exit;
        } else {
            $errors[] = 'Gagal menyimpan data: ' . $stmt->error;
            $stmt->close();
        }
    }

   
    $mahasiswa['nim']     = $nim;
    $mahasiswa['nama']    = $nama;
    $mahasiswa['jurusan'] = $jurusan;
    $mode                 = $modePost;
    if ($id_post) $mahasiswa['id'] = $id_post;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $mode === 'edit' ? 'Edit' : 'Tambah' ?> Mahasiswa — CRUD System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="logo">Maha<span>Siswa</span></div>
  <a href="index.php" class="back-link">← Kembali ke Daftar</a>
</header>

<div class="container">
  <div class="page-header">
    <div class="chip"><?= $mode === 'edit' ? '✏ MODE EDIT' : '＋ TAMBAH BARU' ?></div>
    <h1><?= $mode === 'edit' ? 'Edit Data Mahasiswa' : 'Tambah Data Mahasiswa' ?></h1>
    <p><?= $mode === 'edit'
      ? 'Perbarui informasi mahasiswa di bawah ini.'
      : 'Isi formulir berikut untuk menambahkan data mahasiswa baru.' ?></p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="error-box">
      <ul>
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="card">

    <?php if ($mode === 'edit' && $mahasiswa['foto'] && $mahasiswa['foto'] !== 'default.png'): ?>
      <div class="preview-wrap">
        <?php
          $fotoSrc = file_exists('uploads/' . $mahasiswa['foto'])
            ? 'uploads/' . htmlspecialchars($mahasiswa['foto'])
            : 'https://ui-avatars.com/api/?name=' . urlencode($mahasiswa['nama']) . '&background=1e2330&color=f0c040&size=96';
        ?>
        <img src="<?= $fotoSrc ?>" alt="Foto saat ini" class="preview-img" id="currentPreview">
        <div class="preview-info">
          <strong>Foto saat ini</strong>
          <span>Unggah foto baru untuk menggantinya, atau biarkan kosong untuk mempertahankan foto ini.</span>
        </div>
      </div>
    <?php endif; ?>

    <form id="formMahasiswa" method="POST" enctype="multipart/form-data" novalidate>
      <input type="hidden" name="mode" value="<?= htmlspecialchars($mode) ?>">
      <?php if ($mode === 'edit'): ?>
        <input type="hidden" name="id" value="<?= (int) $mahasiswa['id'] ?>">
      <?php endif; ?>

      <!-- NIM -->
      <div class="form-group">
        <label for="nim">NIM <span class="req">*</span></label>
        <input type="text" id="nim" name="nim"
               value="<?= htmlspecialchars($mahasiswa['nim']) ?>"
               placeholder="Contoh: 2021001001"
               maxlength="20">
      </div>

      <!-- Nama -->
      <div class="form-group">
        <label for="nama">Nama Lengkap <span class="req">*</span></label>
        <input type="text" id="nama" name="nama"
               value="<?= htmlspecialchars($mahasiswa['nama']) ?>"
               placeholder="Masukkan nama lengkap"
               maxlength="100">
      </div>

      <!-- Jurusan -->
      <div class="form-group">
        <label for="jurusan">Jurusan <span class="req">*</span></label>
        <div class="select-wrap">
          <select id="jurusan" name="jurusan">
            <option value=""></option>
            <?php
              $jurusanList = [
                'Teknik Informatika',
                'Sistem Informasi',
                'Teknik Elektro',
                'Teknik Sipil',
                'Manajemen',
                'Akuntansi',
                'Ilmu Komunikasi',
                'Psikologi',
                'Hukum',
                'Kedokteran',
              ];
              foreach ($jurusanList as $j):
                $sel = ($mahasiswa['jurusan'] === $j) ? 'selected' : '';
            ?>
              <option value="<?= htmlspecialchars($j) ?>" <?= $sel ?>><?= htmlspecialchars($j) ?></option>
            <?php endforeach; ?>
            <?php
              // Tampilkan jurusan custom jika tidak ada di list
              if ($mahasiswa['jurusan'] && !in_array($mahasiswa['jurusan'], $jurusanList)):
            ?>
              <option value="<?= htmlspecialchars($mahasiswa['jurusan']) ?>" selected>
                <?= htmlspecialchars($mahasiswa['jurusan']) ?>
              </option>
            <?php endif; ?>
          </select>
        </div>
      </div>

      <!-- Foto -->
      <div class="form-group">
        <label>
          Foto Profil
          <?= $mode === 'tambah' ? '<span class="req">*</span>' : '(Opsional)' ?>
        </label>
        <div class="file-area" id="fileArea">
          <input type="file" name="foto" id="foto" accept=".jpg,.jpeg,.png">
          <div class="file-icon">🖼</div>
          <div class="file-text">
            <strong>Klik untuk memilih</strong> atau seret file ke sini<br>
            <small>JPG, JPEG, PNG — Maks. 2 MB</small>
          </div>
          <div class="file-name" id="fileName"></div>
        </div>
      </div>

      <div class="btn-row">
        <a href="index.php" class="btn btn-cancel">Batal</a>
        <button type="submit" class="btn btn-submit" id="btnSubmit">
          <?= $mode === 'edit' ? '💾 Simpan Perubahan' : '＋ Simpan Data' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- ─── VALIDASI JAVASCRIPT ─── -->
<script>
(function () {
  const form     = document.getElementById('formMahasiswa');
  const nim      = document.getElementById('nim');
  const nama     = document.getElementById('nama');
  const jurusan  = document.getElementById('jurusan');
  const foto     = document.getElementById('foto');
  const fileName = document.getElementById('fileName');
  const fileArea = document.getElementById('fileArea');
  const mode     = document.querySelector('input[name="mode"]').value;

  const MAX_SIZE   = 2 * 1024 * 1024; // 2 MB
  const ALLOWED    = ['image/jpeg', 'image/jpg', 'image/png'];

  // ── Preview nama file setelah dipilih ──────────────────────────────
  foto.addEventListener('change', function () {
    if (this.files.length > 0) {
      const f = this.files[0];
      fileName.style.display = 'block';
      fileName.textContent   = '✓ ' + f.name + ' (' + (f.size / 1024).toFixed(1) + ' KB)';

      // Live preview
      if (ALLOWED.includes(f.type)) {
        const reader = new FileReader();
        reader.onload = function (e) {
          const cur = document.getElementById('currentPreview');
          if (cur) cur.src = e.target.result;
        };
        reader.readAsDataURL(f);
      }
    } else {
      fileName.style.display = 'none';
      fileName.textContent   = '';
    }
  });

  // ── Validasi sebelum submit ────────────────────────────────────────
  form.addEventListener('submit', function (e) {
    const errs = [];

    // Field kosong
    if (nim.value.trim() === '') {
      errs.push('NIM tidak boleh kosong.');
      nim.focus();
    }
    if (nama.value.trim() === '') {
      errs.push('Nama Lengkap tidak boleh kosong.');
    }
    if (jurusan.value === '') {
      errs.push('Jurusan harus dipilih.');
    }

    // Foto wajib saat tambah
    if (mode === 'tambah' && foto.files.length === 0) {
      errs.push('Foto profil wajib diunggah untuk data baru.');
    }

    // Validasi file jika dipilih
    if (foto.files.length > 0) {
      const f = foto.files[0];

      if (!ALLOWED.includes(f.type)) {
        errs.push('File foto harus berformat JPG atau PNG.');
      }
      if (f.size > MAX_SIZE) {
        errs.push('Ukuran file foto tidak boleh melebihi 2 MB. Ukuran Anda: ' + (f.size / (1024*1024)).toFixed(2) + ' MB.');
      }
    }

    if (errs.length > 0) {
      e.preventDefault();
      alert('⚠️ Terdapat kesalahan pada form:\n\n• ' + errs.join('\n• '));
      return false;
    }

    // Disable tombol untuk mencegah double-submit
    document.getElementById('btnSubmit').disabled = true;
    document.getElementById('btnSubmit').textContent = 'Menyimpan...';
  });
})();
</script>
</body>
</html>
