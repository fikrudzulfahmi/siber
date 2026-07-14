<?php
require_once 'BaseController.php';
require_once '../app/models/Rekat.php';

class RekatController extends BaseController
{
    private $model;

    public function __construct($db)
    {
        parent::__construct($db);
        $this->model = new Rekat($db);
    }

    public function index()
    {
        $tahun_pelajaran = $this->model->getTahunPelajaran();

        $id_tahun = $_GET['tahun_pelajaran'] ?? null;

        $rekap = [];
        if ($id_tahun) {
            $data = $this->model->getRekapPerangkat($id_tahun);

            // Grouping mapel badges per guru per mapel+tingkat supaya tidak duplikat
            $rekapFinal = [];
            foreach ($data as $guru) {
                if (!isset($rekapFinal[$guru['id_employe']])) {
                    $rekapFinal[$guru['id_employe']] = [
                        'id_employe' => $guru['id_employe'],
                        'nama' => $guru['nama'],
                        'mapel_badges' => []
                    ];
                }

                foreach ($guru['mapel_badges'] ?? [ // fallback supaya tidak error jika mapel_badges belum ada
                    [
                        'id_mapel' => $guru['id_mapel'],
                        'nama_mapel' => $guru['nama_mapel'],
                        'tingkat' => $guru['tingkat'],
                        'total_perangkat' => $guru['total_perangkat'],
                        'terupload' => $guru['terupload'],
                    ]
                ] as $badge) {
                    $key = $badge['nama_mapel'] . '|' . $badge['tingkat'];

                    if (!isset($rekapFinal[$guru['id_employe']]['mapel_badges'][$key])) {
                        $rekapFinal[$guru['id_employe']]['mapel_badges'][$key] = $badge;
                    } else {
                        // Ambil total_perangkat tetap (asumsi sama)
                        // Terupload ambil maksimum supaya status lebih akurat
                        $rekapFinal[$guru['id_employe']]['mapel_badges'][$key]['terupload'] = max(
                            $rekapFinal[$guru['id_employe']]['mapel_badges'][$key]['terupload'],
                            $badge['terupload']
                        );
                    }
                }
            }

            // Convert mapel_badges associative ke indexed array
            foreach ($rekapFinal as &$g) {
                $g['mapel_badges'] = array_values($g['mapel_badges']);
            }
            unset($g);

            $rekap = array_values($rekapFinal);
        }

        $this->view('admin/rekat/index', compact('rekap', 'tahun_pelajaran', 'id_tahun'));
    }

    public function getDetail()
    {
        $id_tahun = $_GET['id_tahun'] ?? null;
        $id_guru = $_GET['id_guru'] ?? null;
        $id_mapel = $_GET['id_mapel'] ?? null;
        $tingkat = $_GET['tingkat'] ?? null;

        if (!$id_tahun || !$id_guru || !$id_mapel || !$tingkat) {
            echo json_encode([]);
            return;
        }

        $data = $this->model->getDetailPerangkat($id_tahun, $id_guru, $id_mapel, $tingkat);
        echo json_encode($data);
    }
}
