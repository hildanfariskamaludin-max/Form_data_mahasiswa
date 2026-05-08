<?php
require_once 'koneksi.php';

// Ambil pesan flash dari session
session_start();
$flash = '';
if (isset($_SESSION['flash'])) {
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
}

// Hapus data
if (isset($_GET['hapus'])) {
    $id = (int) $_GET['hapus'];

    // Ambil nama foto sebelum dihapus
    $stmt = $conn->prepare("SELECT foto FROM mahasiswa WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();

    if ($row) {
        // Hapus file foto jika bukan default
        if ($row['foto'] !== 'default.png') {
            $fotoPath = UPLOAD_DIR . $row['foto'];
            if (file_exists($fotoPath)) {
                unlink($fotoPath);
            }
        }

        $stmt = $conn->prepare("DELETE FROM mahasiswa WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();

        $_SESSION['flash'] = 'Data mahasiswa berhasil dihapus.';
    }

    header('Location: index.php');
    exit;
}

// Ambil semua data
$query  = "SELECT * FROM mahasiswa ORDER BY created_at DESC";
$result = $conn->query($query);
$data   = $result->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Data Mahasiswa — CRUD System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:wght@300;400;500&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>
<body>

<header>
  <div class="logo">Maha<span>Siswa</span></div>
  <span class="badge">CRUD System v1.0</span>
</header>

<div class="container">

  <div class="page-header">
    <div class="page-title">
      <small>Manajemen Data</small>
      Daftar Mahasiswa
    </div>
    <a href="form.php" class="btn btn-primary">
      ＋ Tambah Mahasiswa
    </a>
  </div>

  <?php if ($flash): ?>
    <div class="flash">✓ <?= htmlspecialchars($flash) ?></div>
  <?php endif; ?>

  <!-- Stats -->
  <div class="stats">
    <div class="stat-card">
      <div class="val"><?= count($data) ?></div>
      <div class="lbl">Total Mahasiswa</div>
    </div>
    <div class="stat-card">
      <div class="val"><?= count(array_unique(array_column($data, 'jurusan'))) ?></div>
      <div class="lbl">Jurusan</div>
    </div>
    <div class="stat-card">
      <div class="val"><?= date('Y') ?></div>
      <div class="lbl">Tahun Akademik</div>
    </div>
  </div>

  <!-- Table -->
  <div class="table-wrap">
    <?php if (empty($data)): ?>
      <div class="empty">
        <div class="empty-icon">📋</div>
        <h3>Belum ada data</h3>
        <p>Mulai dengan menambahkan data mahasiswa pertama Anda.</p>
        <a href="form.php" class="btn btn-primary">＋ Tambah Mahasiswa</a>
      </div>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>#</th>
            <th>Foto</th>
            <th>NIM</th>
            <th>Nama Lengkap</th>
            <th>Jurusan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($data as $i => $m): ?>
            <tr>
              <td style="color:var(--muted)"><?= $i + 1 ?></td>
              <td>
                <?php
                  $fotoSrc = (file_exists('uploads/' . $m['foto']) && $m['foto'] !== 'default.png')
                    ? 'uploads/' . htmlspecialchars($m['foto'])
                    : 'https://ui-avatars.com/api/?name=' . urlencode($m['nama']) . '&background=1e2330&color=f0c040&size=96&bold=true&rounded=true';
                ?>
                <img src="<?= $fotoSrc ?>" alt="Foto <?= htmlspecialchars($m['nama']) ?>" class="thumb">
              </td>
              <td><span class="nim-badge"><?= htmlspecialchars($m['nim']) ?></span></td>
              <td style="font-weight:500"><?= htmlspecialchars($m['nama']) ?></td>
              <td><span class="jurusan-tag"><?= htmlspecialchars($m['jurusan']) ?></span></td>
              <td>
                <div class="actions">
                  <a href="form.php?id=<?= $m['id'] ?>" class="btn btn-edit">✏ Edit</a>
                  <a href="index.php?hapus=<?= $m['id'] ?>"
                     class="btn btn-hapus"
                     onclick="return konfirmasiHapus('<?= htmlspecialchars($m['nama'], ENT_QUOTES) ?>')">
                     🗑 Hapus
                  </a>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

</div>

<footer>
  <p>MahaSiswa CRUD System &mdash; Dibuat dengan PHP Native &amp; MySQL</p>
</footer>

<script>
function konfirmasiHapus(nama) {
  return confirm('⚠️ Hapus Data Mahasiswa\n\nAnda yakin ingin menghapus data milik:\n"' + nama + '"\n\nTindakan ini tidak dapat dibatalkan!');
}
</script>

</body>
</html>
