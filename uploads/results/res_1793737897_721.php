<?php
// ============================================================
// view_pdfs.php — PDF Storage Viewer with Auto Save & State Tracker
// ============================================================

session_start();

$storage_dir = 'uploads_storage/';
if (!is_dir($storage_dir)) {
    @mkdir($storage_dir, 0777, true);
}

$saved_cards_dir = 'saved_cards/';
if (!is_dir($saved_cards_dir)) {
    @mkdir($saved_cards_dir, 0777, true);
}

// Single file delete action
if (isset($_GET['delete'])) {
    $fileToDelete = basename($_GET['delete']);
    $targetPath = $storage_dir . $fileToDelete;
    if (file_exists($targetPath) && strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)) === 'pdf') {
        @unlink($targetPath);
    }
    header('Location: view_pdfs.php');
    exit;
}

// Direct Download Handler
if (isset($_GET['download'])) {
    $fileToDownload = basename($_GET['download']);
    $targetPath = $storage_dir . $fileToDownload;
    if (file_exists($targetPath) && strtolower(pathinfo($targetPath, PATHINFO_EXTENSION)) === 'pdf') {
        header('Content-Description: File Transfer');
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileToDownload . '"');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($targetPath));
        readfile($targetPath);
        exit;
    }
}

// Helper function to find related JSON data for a PDF
function getMatchedJsonData($pdfPath, $storage_dir) {
    $pdfBase = pathinfo($pdfPath, PATHINFO_FILENAME);
    
    // Check if filename has prefix pattern (e.g., 1792682571_5692_...)
    $parts = explode('_', $pdfBase);
    if (count($parts) >= 2) {
        $prefix = $parts[0] . '_' . $parts[1];
        $jsonCandidate = $storage_dir . $prefix . '_data.json';
        if (file_exists($jsonCandidate)) {
            $data = json_decode(@file_get_contents($jsonCandidate), true);
            if ($data) return $data;
        }
        $jsonCandidate2 = $storage_dir . $prefix . '.json';
        if (file_exists($jsonCandidate2)) {
            $data = json_decode(@file_get_contents($jsonCandidate2), true);
            if ($data) return $data;
        }
    }
    
    // Check same base name JSON
    if (file_exists($storage_dir . $pdfBase . '.json')) {
        $data = json_decode(@file_get_contents($storage_dir . $pdfBase . '.json'), true);
        if ($data) return $data;
    }

    return null;
}

// 🌟 নতুন কঠোর ইমেজ ভ্যালিডেশন (ডিকোড করে ডাইমেনশন চেক করবে)
function isValidImageData($imgString, $minSize = 500) {
    if (empty(trim($imgString))) return false;
    
    // ডিফল্ট ফাঁকা ছবি বাদ দেওয়া
    if (strpos($imgString, 'avatar_user.png') !== false || strpos($imgString, 'blank.png') !== false) {
        return false;
    }

    // Base64 ইমেজ চেক
    if (strpos($imgString, 'data:image') !== false) {
        $base64_str = substr($imgString, strpos($imgString, ',') + 1);
        $decoded = base64_decode($base64_str, true);
        
        if ($decoded === false || strlen($decoded) < $minSize) {
            return false;
        }
        
        $sizeInfo = @getimagesizefromstring($decoded);
        if ($sizeInfo === false || $sizeInfo[0] < 20 || $sizeInfo[1] < 20) {
            return false; // ২০ পিক্সেলের নিচে হলে সেটা ফাঁকা
        }
        return true;
    } 
    
    // লোকাল ফাইল পাথ চেক
    if (file_exists($imgString)) {
        if (filesize($imgString) < $minSize) return false;
        $sizeInfo = @getimagesize($imgString);
        if ($sizeInfo === false || $sizeInfo[0] < 20 || $sizeInfo[1] < 20) return false;
        return true;
    }

    return false;
}

// Get all PDF files
$all_pdf_paths = glob($storage_dir . '*.pdf');
if ($all_pdf_paths) {
    usort($all_pdf_paths, function ($a, $b) {
        return filemtime($b) - filemtime($a);
    });
} else {
    $all_pdf_paths = [];
}

// Separate files into Other and Only NID
$nid_pdf_paths   = [];
$other_pdf_paths = [];

foreach ($all_pdf_paths as $filepath) {
    $filename = strtolower(basename($filepath));
    if (strpos($filename, 'nid-') !== false || strpos($filename, 'nid_') !== false) {
        $nid_pdf_paths[] = $filepath;
    } else {
        $other_pdf_paths[] = $filepath;
    }
}
?>
<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PDF Storage Viewer - TotthoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link href="https://fonts.maateen.me/solaiman-lipi/font.css" rel="stylesheet">
    <style>
        body {
            font-family: 'SolaimanLipi', sans-serif;
            background: #f8fafc;
            color: #334155;
            padding: 25px 0;
        }
        .main-card {
            background: #ffffff;
            border-radius: 14px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            border: 1px solid #e2e8f0;
            padding: 25px;
        }
        .search-input {
            border-radius: 10px;
            padding: 12px 18px 12px 42px;
            border: 1px solid #cbd5e1;
            font-size: 0.95rem;
        }
        .search-input:focus {
            border-color: #4e54c8;
            box-shadow: 0 0 0 0.2rem rgba(78, 84, 200, 0.15);
        }
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .badge-count {
            background: #e0e7ff;
            color: #4338ca;
            font-size: 0.9rem;
            padding: 6px 14px;
            border-radius: 20px;
            font-weight: 600;
        }
        .table thead th {
            background: #f1f5f9;
            color: #475569;
            font-weight: 600;
            white-space: nowrap;
        }
        .file-name-text {
            word-break: break-all;
            display: inline-block;
            font-size: 0.93rem;
            color: #1e293b;
        }
        .btn-view {
            background-color: #e0f2fe;
            color: #0284c7;
            border: 1px solid #bae6fd;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .btn-view:hover {
            background-color: #0284c7;
            color: #ffffff;
        }
        .btn-download {
            background-color: #dcfce7;
            color: #16a34a;
            border: 1px solid #bbf7d0;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
        }
        .btn-download:hover {
            background-color: #16a34a;
            color: #ffffff;
        }
        .btn-makenid {
            background-color: #198754;
            color: #ffffff;
            border: 1px solid #157347;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
        }
        .btn-makenid:hover {
            background-color: #157347;
            color: #ffffff;
        }
        .btn-already-made {
            background-color: #64748b !important;
            border-color: #475569 !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            transition: all 0.15s ease-in-out;
            cursor: pointer !important;
        }
        .btn-already-made:hover {
            background-color: #475569 !important;
            border-color: #334155 !important;
            color: #ffffff !important;
        }
        .btn-del {
            background-color: #fee2e2;
            color: #dc2626;
            border: 1px solid #fecaca;
            padding: 5px 9px;
            border-radius: 6px;
        }
        .btn-del:hover {
            background-color: #dc2626;
            color: #ffffff;
        }
        .nav-tabs .nav-link {
            font-weight: 600;
            color: #64748b;
            border: none;
            border-bottom: 3px solid transparent;
            padding: 10px 20px;
        }
        .nav-tabs .nav-link:hover {
            color: #1e293b;
            border-color: transparent;
        }
        .nav-tabs .nav-link.active {
            color: #2563eb;
            background: transparent;
            border-bottom: 3px solid #2563eb;
        }
    </style>
</head>
<body>

<div class="container-fluid px-4">
    <div class="main-card">
        <!-- Header -->
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center mb-4 gap-3 border-bottom pb-3">
            <div>
                <h3 class="fw-bold mb-1 text-primary">
                    <i class="fa-solid fa-file-pdf me-2 text-danger"></i> PDF Storage Explorer
                </h3>
                <p class="text-muted small mb-0">uploads_storage folder-e shongrokkhito shob sign copy PDF file</p>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="badge-count">
                    Visible File: <span id="totalVisibleCount"><?php echo count($other_pdf_paths); ?></span> ti
                </span>
                <a href="admin_mr.php" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left me-1"></i> Dashboard
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-tabs mb-3" id="pdfTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="other-tab" data-bs-toggle="tab" data-bs-target="#other-files" type="button" role="tab">
                    <i class="fa-solid fa-folder-open me-1"></i> Other Files (<?php echo count($other_pdf_paths); ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="nid-tab" data-bs-toggle="tab" data-bs-target="#nid-files" type="button" role="tab">
                    <i class="fa-solid fa-id-card me-1 text-primary"></i> Only NID Files (<?php echo count($nid_pdf_paths); ?>)
                </button>
            </li>
        </ul>

        <!-- Live Search Box -->
        <div class="position-relative mb-4">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="pdfSearchInput" class="form-control search-input" placeholder="PDF file name, NID number ba date likhe search korun...">
        </div>

        <!-- Tabs Content -->
        <div class="tab-content" id="pdfTabsContent">
            
            <!-- TAB 1: OTHER FILES -->
            <div class="tab-pane fade show active" id="other-files" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>File Name</th>
                                <th style="width: 100px;">Size</th>
                                <th style="width: 170px;">Upload Date</th>
                                <th class="text-end" style="width: 350px;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="pdf-table-body">
                            <?php if (empty($other_pdf_paths)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-regular fa-folder-open fa-2x mb-2 d-block"></i>
                                        Kono onnyanno PDF file pawa jayni!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $sl = 1;
                                foreach ($other_pdf_paths as $filepath): 
                                    $filename = basename($filepath);
                                    $filesize = round(filesize($filepath) / 1024, 2) . ' KB';
                                    $filedate = date('d M Y, h:i A', filemtime($filepath));
                                    $data = getMatchedJsonData($filepath, $storage_dir);

                                    $nid = $data['nationalId'] ?? $data['nid'] ?? '';
                                    $cleanNid = preg_replace('/[^0-9A-Za-z_-]/', '', $nid);
                                    $userIMG = $data['userIMG'] ?? '';
                                    $signIMG = $data['signIMG'] ?? '';

                                    // কঠোর ভ্যালিডেশন
                                    $hasPhoto = isValidImageData($userIMG, 1000);
                                    $hasSign  = isValidImageData($signIMG, 500);

                                    $isValidToMake = ($hasPhoto && $hasSign && !empty($cleanNid));

                                    // LIFETIME "ALREADY MADE" CHECK
                                    $savedPdfPath = $saved_cards_dir . "nid-{$cleanNid}.pdf";
                                    $isAlreadyMade = (!empty($cleanNid) && file_exists($savedPdfPath) && filesize($savedPdfPath) > 1000);

                                    $formAction = $isValidToMake ? 'nid-bn.php' : 'index.php';
                                ?>
                                    <tr class="pdf-row">
                                        <td class="text-muted"><?php echo $sl++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                <i class="fa-solid fa-file-pdf text-danger fs-5 me-2 mt-1 flex-shrink-0"></i>
                                                <span class="fw-semibold file-name-text"><?php echo htmlspecialchars($filename); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><?php echo $filesize; ?></td>
                                        <td class="text-muted text-nowrap file-date-text"><?php echo $filedate; ?></td>
                                        <td class="text-end text-nowrap">
                                            <?php if ($data): ?>
                                                <form action="<?php echo $formAction; ?>" method="POST" target="_blank" class="d-inline make-nid-form">
                                                    <input type="hidden" name="nid" value="<?php echo htmlspecialchars($nid); ?>">
                                                    <input type="hidden" name="pin" value="<?php echo htmlspecialchars($data['pin'] ?? ''); ?>">
                                                    <input type="hidden" name="nameBangla" value="<?php echo htmlspecialchars($data['nameBangla'] ?? ''); ?>">
                                                    <input type="hidden" name="nameEnglish" value="<?php echo htmlspecialchars($data['nameEnglish'] ?? ''); ?>">
                                                    <input type="hidden" name="dob" value="<?php echo htmlspecialchars($data['dateOfBirth'] ?? ''); ?>">
                                                    <input type="hidden" name="birthPlace" value="<?php echo htmlspecialchars($data['birthPlace'] ?? ''); ?>">
                                                    <input type="hidden" name="nameFather" value="<?php echo htmlspecialchars($data['fatherName'] ?? ''); ?>">
                                                    <input type="hidden" name="nameMother" value="<?php echo htmlspecialchars($data['motherName'] ?? ''); ?>">
                                                    <input type="hidden" name="gender" value="<?php echo htmlspecialchars($data['gender'] ?? ''); ?>">
                                                    <input type="hidden" name="bloodGroup" value="<?php echo htmlspecialchars($data['bloodGroup'] ?? ''); ?>">
                                                    <input type="hidden" name="fulladdress" value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>">
                                                    <input type="hidden" name="imageUrl12" value="<?php echo htmlspecialchars($userIMG); ?>">
                                                    <input type="hidden" name="imageUrl22" value="<?php echo htmlspecialchars($signIMG); ?>">
                                                    
                                                    <?php if ($isValidToMake): ?>
                                                        <input type="hidden" name="download_pdf" value="1">
                                                    <?php endif; ?>

                                                    <?php if ($isAlreadyMade): ?>
                                                        <button type="submit" class="btn btn-sm btn-already-made me-1" title="Already Made! Click to re-make/download">
                                                            <i class="fa-solid fa-check me-1"></i> Already Made
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-sm btn-makenid me-1" title="<?php echo $isValidToMake ? 'Make NID (Direct Save & Download)' : 'Missing Photo/Signature! (Redirect to index.php)'; ?>">
                                                            <i class="fa fa-id-card me-1"></i> Make NID
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>

                                            <a href="<?php echo htmlspecialchars($storage_dir . $filename); ?>" target="_blank" class="btn btn-sm btn-view me-1" title="View in Browser">
                                                <i class="fa-regular fa-eye me-1"></i> View
                                            </a>
                                            <a href="view_pdfs.php?download=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-download me-1" title="Download">
                                                <i class="fa-solid fa-download me-1"></i> Download
                                            </a>
                                            <a href="view_pdfs.php?delete=<?php echo urlencode($filename); ?>" onclick="return confirm('Apni ki file-ti delete korte chan?')" class="btn btn-sm btn-del" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: ONLY NID FILES -->
            <div class="tab-pane fade" id="nid-files" role="tabpanel">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th style="width: 50px;">#</th>
                                <th>File Name</th>
                                <th style="width: 100px;">Size</th>
                                <th style="width: 170px;">Upload Date</th>
                                <th class="text-end" style="width: 350px;">Action</th>
                            </tr>
                        </thead>
                        <tbody class="pdf-table-body">
                            <?php if (empty($nid_pdf_paths)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="fa-regular fa-id-card fa-2x mb-2 d-block"></i>
                                        Kono NID PDF file pawa jayni!
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                $sl2 = 1;
                                foreach ($nid_pdf_paths as $filepath): 
                                    $filename = basename($filepath);
                                    $filesize = round(filesize($filepath) / 1024, 2) . ' KB';
                                    $filedate = date('d M Y, h:i A', filemtime($filepath));
                                    $data = getMatchedJsonData($filepath, $storage_dir);

                                    $nid = $data['nationalId'] ?? $data['nid'] ?? '';
                                    $cleanNid = preg_replace('/[^0-9A-Za-z_-]/', '', $nid);
                                    $userIMG = $data['userIMG'] ?? '';
                                    $signIMG = $data['signIMG'] ?? '';

                                    $hasPhoto = isValidImageData($userIMG, 1000);
                                    $hasSign  = isValidImageData($signIMG, 500);

                                    $isComplete = ($hasPhoto && $hasSign && !empty($cleanNid));

                                    $savedPdfPath = $saved_cards_dir . "nid-{$cleanNid}.pdf";
                                    $isAlreadyMade = (!empty($cleanNid) && file_exists($savedPdfPath) && filesize($savedPdfPath) > 1000);

                                    $formAction = $isComplete ? 'nid-bn.php' : 'index.php';
                                ?>
                                    <tr class="pdf-row">
                                        <td class="text-muted"><?php echo $sl2++; ?></td>
                                        <td>
                                            <div class="d-flex align-items-start">
                                                <i class="fa-solid fa-file-pdf text-danger fs-5 me-2 mt-1 flex-shrink-0"></i>
                                                <span class="fw-semibold file-name-text text-primary"><?php echo htmlspecialchars($filename); ?></span>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><?php echo $filesize; ?></td>
                                        <td class="text-muted text-nowrap file-date-text"><?php echo $filedate; ?></td>
                                        <td class="text-end text-nowrap">
                                            <?php if ($data): ?>
                                                <form action="<?php echo $formAction; ?>" method="POST" target="_blank" class="d-inline make-nid-form">
                                                    <input type="hidden" name="nid" value="<?php echo htmlspecialchars($nid); ?>">
                                                    <input type="hidden" name="pin" value="<?php echo htmlspecialchars($data['pin'] ?? ''); ?>">
                                                    <input type="hidden" name="nameBangla" value="<?php echo htmlspecialchars($data['nameBangla'] ?? ''); ?>">
                                                    <input type="hidden" name="nameEnglish" value="<?php echo htmlspecialchars($data['nameEnglish'] ?? ''); ?>">
                                                    <input type="hidden" name="dob" value="<?php echo htmlspecialchars($data['dateOfBirth'] ?? ''); ?>">
                                                    <input type="hidden" name="birthPlace" value="<?php echo htmlspecialchars($data['birthPlace'] ?? ''); ?>">
                                                    <input type="hidden" name="nameFather" value="<?php echo htmlspecialchars($data['fatherName'] ?? ''); ?>">
                                                    <input type="hidden" name="nameMother" value="<?php echo htmlspecialchars($data['motherName'] ?? ''); ?>">
                                                    <input type="hidden" name="gender" value="<?php echo htmlspecialchars($data['gender'] ?? ''); ?>">
                                                    <input type="hidden" name="bloodGroup" value="<?php echo htmlspecialchars($data['bloodGroup'] ?? ''); ?>">
                                                    <input type="hidden" name="fulladdress" value="<?php echo htmlspecialchars($data['address'] ?? ''); ?>">
                                                    <input type="hidden" name="imageUrl12" value="<?php echo htmlspecialchars($userIMG); ?>">
                                                    <input type="hidden" name="imageUrl22" value="<?php echo htmlspecialchars($signIMG); ?>">

                                                    <?php if ($isComplete): ?>
                                                        <input type="hidden" name="download_pdf" value="1">
                                                    <?php endif; ?>

                                                    <?php if ($isAlreadyMade): ?>
                                                        <button type="submit" class="btn btn-sm btn-already-made me-1" title="Already Made! Click to open">
                                                            <i class="fa fa-check me-1"></i> Already Made
                                                        </button>
                                                    <?php else: ?>
                                                        <button type="submit" class="btn btn-sm btn-makenid me-1" title="<?php echo $isComplete ? 'Direct Download' : 'Missing Signature/Photo! Edit in index.php'; ?>">
                                                            <i class="fa fa-id-card me-1"></i> Make NID
                                                        </button>
                                                    <?php endif; ?>
                                                </form>
                                            <?php endif; ?>

                                            <a href="<?php echo htmlspecialchars($storage_dir . $filename); ?>" target="_blank" class="btn btn-sm btn-view me-1" title="View in Browser">
                                                <i class="fa-regular fa-eye me-1"></i> View
                                            </a>
                                            <a href="view_pdfs.php?download=<?php echo urlencode($filename); ?>" class="btn btn-sm btn-download me-1" title="Download">
                                                <i class="fa-solid fa-download me-1"></i> Download
                                            </a>
                                            <a href="view_pdfs.php?delete=<?php echo urlencode($filename); ?>" onclick="return confirm('Apni ki file-ti delete korte chan?')" class="btn btn-sm btn-del" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function () {
    $(document).on('click', '.btn-makenid', function () {
        const $btn = $(this);
        $btn.removeClass('btn-makenid')
            .addClass('btn-already-made')
            .html('<i class="fa-solid fa-check me-1"></i> Already Made');
    });

    function filterTable() {
        const query = $('#pdfSearchInput').val().toLowerCase().trim();
        const activeTab = $('.tab-pane.active');
        let visibleCount = 0;

        activeTab.find('.pdf-row').each(function () {
            const fileName = $(this).find('.file-name-text').text().toLowerCase();
            const fileDate = $(this).find('.file-date-text').text().toLowerCase();

            if (fileName.includes(query) || fileDate.includes(query)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        $('#totalVisibleCount').text(visibleCount);

        activeTab.find('.search-not-found').remove();
        if (visibleCount === 0 && query !== '') {
            activeTab.find('.pdf-table-body').append('<tr class="search-not-found"><td colspan="5" class="text-center text-muted py-4"><i class="fa fa-search me-1"></i> Ei name kono PDF file pawa jayni!</td></tr>');
        }
    }

    $('#pdfSearchInput').on('keyup', filterTable);

    $('button[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        filterTable();
    });
});
</script>

</body>
</html>