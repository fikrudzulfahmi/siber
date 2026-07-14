<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/Kalender.php';

class KalenderController extends BaseController {
    private $model;

    public function __construct($pdo) {
        parent::__construct($pdo);
        $this->model = new Kalender($pdo);
    }

public function index() {
    $libur = $this->model->getAllHolidays();

    // Ambil Hari Minggu dari 2025 sampai 2027
    $sundays = [];
    for ($tahun = 2025; $tahun <= 2027; $tahun++) {
        $sundays = array_merge($sundays, $this->getAllSundays($tahun));
    }

    view('admin/kalender/index', [
        'libur' => array_merge($libur, $sundays)
    ]);
}

public function store() {
    header('Content-Type: application/json');

    $data = json_decode(file_get_contents("php://input"), true);

    if (!$data || !isset($data['tanggal_mulai'], $data['tanggal_selesai'], $data['keterangan'])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Data tidak lengkap atau tidak valid.'
        ]);
        return;
    }

    $mulai = $data['tanggal_mulai'];
    $selesai = $data['tanggal_selesai'];
    $keterangan = $data['keterangan'];

    if ($this->containsSunday($mulai, $selesai)) {
        echo json_encode(['status' => 'error', 'message' => 'Rentang tanggal mengandung Hari Minggu.']);
        return;
    }

    $id = $this->model->simpan($mulai, $selesai, $keterangan);
    echo json_encode(['status' => 'success', 'id' => $id]);
}


    public function deleteById() {
        $data = json_decode(file_get_contents("php://input"), true);
        $id = $data['id'];
        $this->model->hapus($id);
        echo json_encode(['status' => 'success']);
    }

    private function getAllSundays($year) {
        $start = strtotime("$year-01-01");
        $end = strtotime("$year-12-31");
        $sundays = [];
        while ($start <= $end) {
            if (date('w', $start) == 0) {
                $sundays[] = [
                    'title' => 'Hari Minggu',
                    'start' => date('Y-m-d', $start),
                    'display' => 'background',
                    'color' => '#ffcccc'
                ];
            }
            $start = strtotime("+1 day", $start);
        }
        return $sundays;
    }

    private function containsSunday($start, $end) {
        $current = strtotime($start);
        $end = strtotime($end);
        while ($current <= $end) {
            if (date('w', $current) == 0) return true;
            $current = strtotime('+1 day', $current);
        }
        return false;
    }
}
