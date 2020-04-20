<?php

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use PhpParser\Node\Expr\Cast\String_;
use Restserver\Libraries\REST_Controller;

class Request extends REST_Controller
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
    }

    public function tes_koneksi_get()
    {
        $this->response(array(
            "status"        => true,
            "respon_code"   => REST_Controller::HTTP_OK,
            "respon_mess"   => "Connected",
            "data"          => ""
        ), REST_Controller::HTTP_OK);
    }

    //generate nomer virtual akun PGM
    public function generateVA()
    {
        $no_va     = "";
        for ($i = 0; $i < 8; $i++) {
            $no_va .= mt_rand(0, 9);
        }
        $cekNoVa    = $this->m_data->getWhere("no_va", $no_va);
        $cekNoVa    = $this->m_data->getData("permintaan_user")->row();
        if ($cekNoVa) {
            return $this->generateVA();
        } else {
            return $no_va;
        }
    }

    //add permintaan by app
    public function add_permintaan_post()
    {
        $no_reg_tilang      = $this->input->post('no_reg_tilang');
        $nama_penerima      = $this->input->post('nama_penerima');
        $alamat_antar       = $this->input->post('alamat_antar');
        $detail_alamat      = $this->input->post('detail_alamat');
        $kode_pos           = $this->input->post('kode_pos');
        $nomer_hp           = $this->input->post('nomer_hp');
        $request_by         = $this->input->post('request_by') == NULL ? gobang()->request_by : $this->input->post('request_by');
        $no_va              = $this->generateVA();
        $nominal_denda      = $this->input->post('nominal_denda');
        $nominal_perkara    = $this->input->post('nominal_perkara') == NULL ? gobang()->nominal_perkara : $this->input->post('nominal_perkara');
        $nominal_pos        = $this->input->post('nominal_pos');
        $nominal_gobang     = $this->input->post('nominal_gobang') == NULL ? gobang()->nominal_gobang : $this->input->post('nominal_gobang');
        $waktu_expired      = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " +1 days"));

        $cekData            = $this->m_data->getWhere("LOWER(no_reg_tilang)", strtolower($no_reg_tilang));
        $cekData            = $this->m_data->getData("daftar_terpidana")->row();

        if ($cekData) {
            //INSERT
            $dataInsert = array(
                "no_reg_tilang"     => $no_reg_tilang,
                "nama_penerima"     => $nama_penerima,
                "alamat_antar"      => $alamat_antar,
                "detail_alamat"     => $detail_alamat,
                "kode_pos"          => $kode_pos,
                "nomer_hp"          => $nomer_hp,
                "request_by"        => $request_by,
                "no_va"             => $no_va,
                "nominal_denda"     => $nominal_denda,
                "nominal_perkara"   => $nominal_perkara,
                "nominal_pos"       => $nominal_pos,
                "nominal_gobang"    => $nominal_gobang,
                "waktu_expired"     => $waktu_expired
            );
            $insert = $this->m_data->insert("permintaan_user", $dataInsert);
            if ($insert) {
                $ambilDataTerakhir     = $this->m_data->getWhere("no_reg_tilang", $cekData->no_reg_tilang);
                $ambilDataTerakhir     = $this->m_data->order_by("waktu_expired", "DESC");
                $ambilDataTerakhir     = $this->m_data->limitOffset(1, NULL);
                $ambilDataTerakhir     = $this->m_data->getData("permintaan_user")->row();

                $ambilDataTerakhir->kode_inst = (string) gobang()->kode_inst;

                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_CREATED,
                    "respon_mess"   => "Berhasil melakukan permintaan, silahkan lakukan pembayaran",
                    "data"          => $ambilDataTerakhir
                ), REST_Controller::HTTP_OK);
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_BAD_REQUEST,
                    "respon_mess"   => $this->m_data->getError(),
                    "data"          => ""
                ), REST_Controller::HTTP_OK);
            }
        } else {
            //DATA NOT FOUND
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Nomer Registrasi Tilang Tidak ditemukan!",
                "data"          => ""
            ), REST_Controller::HTTP_OK);
        }
    }

    //cek data by app
    public function cek_data_post()
    {
        $no_reg_tilang  = $this->input->post('no_reg_tilang');
        $cekData        = $this->m_data->getWhere("LOWER(no_reg_tilang)", strtolower($no_reg_tilang));
        $cekData        = $this->m_data->getData("daftar_terpidana")->row();

        if ($cekData) {
            $cekRequest     = $this->m_data->getWhere("no_reg_tilang", $cekData->no_reg_tilang);
            $cekRequest     = $this->m_data->order_by("waktu_expired", "DESC");
            $cekRequest     = $this->m_data->limitOffset(1, NULL);
            $cekRequest     = $this->m_data->getData("permintaan_user")->row();
            if ($cekRequest) {
                $cekRequest->kode_inst = (string) gobang()->kode_inst;
                $cekBbStatus    = $this->m_data->select(array(
                    "bb_status.*",
                    "permintaan_user.nama_penerima",
                    "permintaan_user.no_reg_tilang",
                ));
                $cekBbStatus    = $this->m_data->getJoin("permintaan_user", "bb_status.id_permintaan = permintaan_user.id_permintaan", "INNER");
                $cekBbStatus    = $this->m_data->getWhere("bb_status.id_permintaan", $cekRequest->id_permintaan);
                $cekBbStatus    = $this->m_data->getData("bb_status")->row();
                if ($cekBbStatus) {
                    //# DONE - UDAH BAYAR
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_OK,
                        "respon_mess"   => "Bukti tilang sudah di bayar, silahkan lihat status pengirimanya",
                        "data"          => $cekBbStatus
                    ), REST_Controller::HTTP_OK);
                } else {
                    if ($cekRequest->waktu_expired > date("Y-m-d H:i:s")) {
                        # DONE - BELUM BAYAR
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_PAYMENT_REQUIRED,
                            "respon_mess"   => "Silahkan bayar sesuai dengan nominal sebelum batas pembayaran berakhir",
                            "data"          => $cekRequest
                        ), REST_Controller::HTTP_OK);
                    } else {
                        # DONE - EXPIRED
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                            "respon_mess"   => "Request expired, silahkan isi ulang data request pengiriman bukti tilang",
                            "data"          => $cekData
                        ), REST_Controller::HTTP_OK);
                    }
                }
            } else {
                # DONE - BELUM REQUEST - ISI DATA - PROSES CEK SELESAI
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_FOUND,
                    "respon_mess"   => "Data ditemukan, silahkan isi data request pengiriman bukti tilang",
                    "data"          => $cekData
                ), REST_Controller::HTTP_OK);
            }
        } else {
            # DONE - DATA TIDAK DITEMUKAN
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Data Tidak Ditemukan, silahkan periksa kembali no registrasi tilang anda atau coba beberapa saat lagi",
                "data"          => ""
            ), REST_Controller::HTTP_OK);
        }
    }

    //payment
    public function vasbupos_payment_post()
    {
        if (empty($this->input->post())) {
            $inputFromJson = file_get_contents('php://input');
            $_POST = json_decode($inputFromJson, TRUE);
        }

        $nomor_va       = $this->input->post('nomor_va');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $nominal        = $this->input->post('nominal');
        $admin          = $this->input->post('admin');
        $refnumber      = $this->input->post('refnumber');
        $waktu_proses   = $this->input->post('waktu_proses');
        $nopen          = $this->input->post('nopen');
        $hashing        = $this->input->post('hashing');

        // Cek Hashing
        $rumus = base64_encode(gobang()->kode_inst . "#GOBANG#" . $nomor_va);
        if ($hashing == $rumus) {
            // Cek kode institusi Gobang
            if ($kode_inst == gobang()->kode_inst) {
                // Cek no VA 
                $cekVA     = $this->m_data->getWhere("no_va", $nomor_va);
                $cekVA     = $this->m_data->getData("permintaan_user")->row();
                if ($cekVA != NULL) {
                    // CEK SUDAH DI BAYAR BELUM
                    $sudahBayar  = $this->m_data->getWhere("id_permintaan", $cekVA->id_permintaan);
                    $sudahBayar  = $this->m_data->getData("bb_status")->row();
                    if (!$sudahBayar) {
                        // Cek waktu Expired
                        if ($cekVA->waktu_expired > date("Y-m-d H:i:s")) {
                            // Semua udah benar , masukin req ke table bb_status
                            $dataInsert = array(
                                "id_permintaan"     => $cekVA->id_permintaan,
                                "req"               => 0,
                                "channel_id"        => $channel_id,
                                "nominal"           => $nominal,
                                "admin"             => $admin,
                                "refnumber"         => $refnumber,
                                "waktu_proses"      => $waktu_proses,
                                "nopen"             => $nopen
                            );

                            $insertBBStatus = $this->m_data->insert("bb_status", $dataInsert);
                            if ($insertBBStatus) {
                                $this->response(array(
                                    "status"        => true,
                                    "respon_code"   => "00",
                                    "respon_mess"   => "Pembayaran Gobang|" . $cekVA->no_reg_tilang . "|" . $cekVA->nama_penerima,
                                    "nomor_va"      => $nomor_va,
                                    "kode_inst"     => $kode_inst,
                                    "channel_id"    => $channel_id,
                                    "nominal"       => $nominal,
                                    "admin"         => $admin,
                                    "refnumber"     => $refnumber,
                                    "waktu_proses"  => $waktu_proses,
                                    "nopen"         => $nopen
                                ), REST_Controller::HTTP_OK);
                            } else {
                                $this->response(array(
                                    "status"        => true,
                                    "respon_code"   => REST_Controller::HTTP_PRECONDITION_FAILED,
                                    "respon_mess"   => "Terjadi kesalahan pada server",
                                    "nomor_va"      => $nomor_va,
                                    "kode_inst"     => $kode_inst,
                                    "channel_id"    => $channel_id,
                                    "nominal"       => $nominal,
                                    "admin"         => $admin,
                                    "refnumber"     => $refnumber,
                                    "waktu_proses"  => $waktu_proses,
                                    "nopen"         => $nopen
                                ), REST_Controller::HTTP_OK);
                            }
                        } else {
                            $this->response(array(
                                "status"        => true,
                                "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                                "respon_mess"   => "Nomor Virtual Account expired",
                                "nomor_va"      => $nomor_va,
                                "kode_inst"     => $kode_inst,
                                "channel_id"    => $channel_id,
                                "nominal"       => $nominal,
                                "admin"         => $admin,
                                "refnumber"     => $refnumber,
                                "waktu_proses"  => $waktu_proses,
                                "nopen"         => $nopen
                            ), REST_Controller::HTTP_OK);
                        }
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_NO_CONTENT,
                            "respon_mess"   => "Tagihan Sudah Di bayar",
                            "nomor_va"      => $nomor_va,
                            "kode_inst"     => $kode_inst,
                            "channel_id"    => $channel_id,
                            "nominal"       => $nominal,
                            "admin"         => $admin,
                            "refnumber"     => $refnumber,
                            "waktu_proses"  => $waktu_proses,
                            "nopen"         => $nopen
                        ), REST_Controller::HTTP_OK);
                    }
                } else {
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                        "respon_mess"   => "Nomer Virtual Account tidak ditemukan",
                        "nomor_va"      => $nomor_va,
                        "kode_inst"     => $kode_inst,
                        "channel_id"    => $channel_id,
                        "nominal"       => $nominal,
                        "admin"         => $admin,
                        "refnumber"     => $refnumber,
                        "waktu_proses"  => $waktu_proses,
                        "nopen"         => $nopen
                    ), REST_Controller::HTTP_OK);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_BAD_REQUEST,
                    "respon_mess"   => "Kode institusi tidak dikenal",
                    "nomor_va"      => $nomor_va,
                    "kode_inst"     => $kode_inst,
                    "channel_id"    => $channel_id,
                    "nominal"       => $nominal,
                    "admin"         => $admin,
                    "refnumber"     => $refnumber,
                    "waktu_proses"  => $waktu_proses,
                    "nopen"         => $nopen
                ), REST_Controller::HTTP_OK);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_CONFLICT,
                "respon_mess"   => "Transaksi ditolak. hash tidak dikenali",
                "nomor_va"      => $nomor_va,
                "kode_inst"     => $kode_inst,
                "channel_id"    => $channel_id,
                "nominal"       => $nominal,
                "admin"         => $admin,
                "refnumber"     => $refnumber,
                "waktu_proses"  => $waktu_proses,
                "nopen"         => $nopen
            ), REST_Controller::HTTP_OK);
        }
    }

    //get inquiry
    public function vasbupos_inquiry_post()
    {
        if (empty($this->input->post())) {
            $inputFromJson = file_get_contents('php://input');
            $_POST = json_decode($inputFromJson, TRUE);
        }

        $nomor_va       = $this->input->post('nomor_va');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $waktu_proses   = $this->input->post('waktu_proses');

        $cekVA          = $this->m_data->getWhere("permintaan_user.no_va", $nomor_va);
        $cekVA          = $this->m_data->getJoin(
            "daftar_terpidana",
            "permintaan_user.no_reg_tilang = daftar_terpidana.no_reg_tilang",
            "INNER"
        );
        $cekVA          = $this->m_data->getData("permintaan_user")->row();

        // Cek Kode Institusi
        // if ($kode_inst == gobang()->kode_inst || $kode_inst == "\"". gobang()->kode_inst ."\"") {
        if ($kode_inst == gobang()->kode_inst) {
            // Cek No VA
            if ($cekVA != NULL) {
                //Cek Udah Di bayar atau belum
                $cekUdahBayar   = $this->m_data->getWhere("permintaan_user.no_reg_tilang", $cekVA->no_reg_tilang);
                $cekUdahBayar   = $this->m_data->getJoin(
                    "bb_status",
                    "permintaan_user.id_permintaan = bb_status.id_permintaan",
                    "INNER"
                );
                $cekUdahBayar   = $this->m_data->getData("permintaan_user")->num_rows();
                if ($cekUdahBayar < 1) { // BELUM BAYAR
                    // Cek waktu Expired
                    if ($cekVA->waktu_expired > date("Y-m-d H:i:s")) {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => "00",
                            "respon_mess"   => "Data ditemukan",
                            "nomor_va"      => $nomor_va,
                            "denda_tilang"  => (int) $cekVA->nominal_denda + (int) $cekVA->nominal_perkara,
                            "biaya_kirim"   => 0,
                            "info_tambahan" => "biaya kirim di pindah ke key admin",
                            "nominal"       => (int) $cekVA->nominal_denda + (int) $cekVA->nominal_perkara + (int) $cekVA->nominal_pos + (int) $cekVA->nominal_gobang,                            
                            "admin"         => (int) $cekVA->nominal_pos,
                            "nama"          => $cekVA->nama_penerima,
                            "info"          => "Pembayaran Gobang|" . $cekVA->no_reg_tilang . "|" . $cekVA->nama_terpidana,
                            "alamat"        => $cekVA->detail_alamat . " " . $cekVA->alamat_antar . " " . $cekVA->kode_pos,
                            "nomer_hp"      => $cekVA->nomer_hp,
                            // "rekgiro"       => "",
                            "channel_id"    => $channel_id,
                            "waktu_proses"  => $waktu_proses,

                        ), REST_Controller::HTTP_OK);
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                            "respon_mess"   => "Nomor VA expired",
                            "nomor_va"      => $nomor_va,
                            "denda_tilang"  => 0,
                            "biaya_kirim"   => 0,
                            "nominal"       => 0,
                            "admin"         => 0,
                            "nama"          => "",
                            "info"          => "Nomor VA expired",
                            "alamat"        => "",
                            "nomer_hp"      => "",
                            // "rekgiro"       => "",
                            "channel_id"    => $channel_id,
                            "waktu_proses"  => $waktu_proses,
                        ), REST_Controller::HTTP_OK);
                    }
                } else {
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_ALREADY_REPORTED,
                        "respon_mess"   => "Nomor VA sudah di bayar",
                        "nomor_va"      => $nomor_va,
                        "denda_tilang"  => 0,
                        "biaya_kirim"   => 0,
                        "nominal"       => 0,
                        "admin"         => 0,
                        "nama"          => "",
                        "info"          => "Nomor VA sudah di bayar",
                        "alamat"        => "",
                        "nomer_hp"      => "",
                        // "rekgiro"       => "",
                        "channel_id"    => $channel_id,
                        "waktu_proses"  => $waktu_proses,
                    ), REST_Controller::HTTP_OK);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                    "respon_mess"   => "Nomer Virtual Account tidak ditemukan",
                    "nomor_va"      => $nomor_va,
                    "denda_tilang"  => 0,
                    "biaya_kirim"   => 0,
                    "nominal"       => 0,
                    "admin"         => 0,
                    "nama"          => "",
                    "info"          => "Nomer Virtual Account tidak ditemukan",
                    "alamat"        => "",
                    "nomer_hp"      => "",
                    // "rekgiro"       => "",
                    "channel_id"    => $channel_id,
                    "waktu_proses"  => $waktu_proses,
                ), REST_Controller::HTTP_OK);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_BAD_REQUEST,
                "respon_mess"   => "Transaksi Di Tolak ! Kode Institusi Tidak Dikenal",
                "nomor_va"      => $nomor_va,
                "denda_tilang"  => 0,
                "biaya_kirim"   => 0,
                "nominal"       => 0,
                "admin"         => 0,
                "nama"          => "",
                "info"          => "Kode institusi tidak dikenali",
                "alamat"        => "",
                // "rekgiro"       => "",
                "nomer_hp"      => "",
                "channel_id"    => $channel_id,
                "waktu_proses"  => $waktu_proses,
            ), REST_Controller::HTTP_OK);
        }
    }

    //get inquiry
    public function vasbupos_inquiry2_post()
    {
        $nomor_va       = $this->input->post('nomor_va');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $waktu_proses   = $this->input->post('waktu_proses');

        $inputFromJson = file_get_contents('php://input');

        if ($inputFromJson) {
            $_POST = json_decode($inputFromJson, TRUE);
            echo json_encode($this->input->post());
            die();
        }


        // $nomor_va       = (String) $nomor_va;

        $cekVA          = $this->m_data->getWhere("permintaan_user.no_va", $nomor_va);
        $cekVA          = $this->m_data->getJoin(
            "daftar_terpidana",
            "permintaan_user.no_reg_tilang = daftar_terpidana.no_reg_tilang",
            "INNER"
        );
        $cekVA          = $this->m_data->getData("permintaan_user")->row();

        // Cek Kode Institusi
        if ($kode_inst == gobang()->kode_inst || $kode_inst == "\"" . gobang()->kode_inst . "\"") {
            // Cek No VA
            if ($cekVA != NULL) {
                //Cek Udah Di bayar atau belum
                $cekUdahBayar   = $this->m_data->getWhere("permintaan_user.no_reg_tilang", $cekVA->no_reg_tilang);
                $cekUdahBayar   = $this->m_data->getJoin(
                    "bb_status",
                    "permintaan_user.id_permintaan = bb_status.id_permintaan",
                    "INNER"
                );
                $cekUdahBayar   = $this->m_data->getData("permintaan_user")->num_rows();
                if ($cekUdahBayar < 1) { // BELUM BAYAR
                    // Cek waktu Expired
                    if ($cekVA->waktu_expired > date("Y-m-d H:i:s")) {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => "00",
                            "respon_mess"   => "Data ditemukan",
                            "nomor_va"      => $nomor_va,
                            "denda_tilang"  => (int) $cekVA->nominal_denda + (int) $cekVA->nominal_perkara,
                            "biaya_kirim"   => (int) $cekVA->nominal_pos,
                            "nominal"       => (int) $cekVA->nominal_denda + (int) $cekVA->nominal_perkara + (int) $cekVA->nominal_pos,
                            "admin"         => (int) $cekVA->nominal_gobang,
                            "nama"          => $cekVA->nama_penerima,
                            "info"          => "Pembayaran Gobang|" . $cekVA->no_reg_tilang . "|" . $cekVA->nama_terpidana,
                            "alamat"        => $cekVA->detail_alamat . " " . $cekVA->alamat_antar . " " . $cekVA->kode_pos,
                            "nomer_hp"      => $cekVA->nomer_hp,
                            // "rekgiro"       => "",
                            "channel_id"    => $channel_id,
                            "waktu_proses"  => $waktu_proses,
                        ), REST_Controller::HTTP_OK);
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                            "respon_mess"   => "Nomor VA expired",
                            "nomor_va"      => $nomor_va,
                            "denda_tilang"  => 0,
                            "biaya_kirim"   => 0,
                            "nominal"       => 0,
                            "admin"         => 0,
                            "nama"          => "",
                            "info"          => "Nomor VA expired",
                            "alamat"        => "",
                            "nomer_hp"      => "",
                            // "rekgiro"       => "",
                            "channel_id"    => $channel_id,
                            "waktu_proses"  => $waktu_proses,
                        ), REST_Controller::HTTP_OK);
                    }
                } else {
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_ALREADY_REPORTED,
                        "respon_mess"   => "Nomor VA sudah di bayar",
                        "nomor_va"      => $nomor_va,
                        "denda_tilang"  => 0,
                        "biaya_kirim"   => 0,
                        "nominal"       => 0,
                        "admin"         => 0,
                        "nama"          => "",
                        "info"          => "Nomor VA sudah di bayar",
                        "alamat"        => "",
                        "nomer_hp"      => "",
                        // "rekgiro"       => "",
                        "channel_id"    => $channel_id,
                        "waktu_proses"  => $waktu_proses,
                    ), REST_Controller::HTTP_OK);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                    "respon_mess"   => "Nomer Virtual Account tidak ditemukan",
                    "nomor_va"      => $nomor_va,
                    "denda_tilang"  => 0,
                    "biaya_kirim"   => 0,
                    "nominal"       => 0,
                    "admin"         => 0,
                    "nama"          => "",
                    "info"          => "Nomer Virtual Account tidak ditemukan",
                    "alamat"        => "",
                    "nomer_hp"      => "",
                    // "rekgiro"       => "",
                    "channel_id"    => $channel_id,
                    "waktu_proses"  => $waktu_proses,
                ), REST_Controller::HTTP_OK);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_BAD_REQUEST,
                "respon_mess"   => "Transaksi Di Tolak ! Kode Institusi Tidak Dikenal",
                "nomor_va"      => $nomor_va,
                "denda_tilang"  => 0,
                "biaya_kirim"   => 0,
                "nominal"       => 0,
                "admin"         => 0,
                "nama"          => "",
                "info"          => "Kode institusi tidak dikenali",
                "alamat"        => "",
                "rekgiro"       => "",
                "nomer_hp"      => "",
                "channel_id"    => $channel_id,
                "waktu_proses"  => $waktu_proses,
            ), REST_Controller::HTTP_OK);
        }
    }

    //get data from teller | digunakan buat inquiry (info) dan payment direct dari teller
    public function get_data_tilang($dataRequest = NULL)
    {
        $no_reg_tilang  = $dataRequest['no_reg_tilang'];

        $cekData        = $this->m_data->select(array(
            "no_reg_tilang",
            "nama_terpidana",
            "alamat_terpidana",
            "nomor_briva",
            "tgl_putusan",
            "(denda + biaya_perkara) as denda_tilang"
        ));
        $cekData        = $this->m_data->getWhere("LOWER(no_reg_tilang)", strtolower($no_reg_tilang));
        $cekData        = $this->m_data->getData("daftar_terpidana")->row();

        if ($dataRequest['kode_inst'] == gobang()->kode_inst) {
            if ($cekData) { //DATA KETEMU
                $cekUdahBayar   = $this->m_data->getWhere(
                    "permintaan_user.no_reg_tilang",
                    $cekData->no_reg_tilang
                );
                $cekUdahBayar   = $this->m_data->getJoin(
                    "bb_status",
                    "permintaan_user.id_permintaan = bb_status.id_permintaan",
                    "INNER"
                );
                $cekUdahBayar   = $this->m_data->getData("permintaan_user")->num_rows();
                if ($cekUdahBayar < 1) {  // BELUM DI BAYAR
                    return array(
                        "status"            => true,
                        "respon_code"       => gobang()->code_sukses,
                        "respon_mess"       => "No Reg Tilang ($no_reg_tilang) Ditemukan",
                        "no_reg_tilang"     => $no_reg_tilang,
                        "nomor_va"          => "",
                        "denda_tilang"      => (int) $cekData->denda_tilang,
                        "biaya_kirim"       => "",
                        "nominal"           => (int) $cekData->denda_tilang,
                        "admin"             => (int) gobang()->nominal_gobang,
                        "nama"              => "",
                        "info"              => "Pembayaran Gobang|" . $cekData->no_reg_tilang . "|" . $cekData->nama_terpidana,
                        "alamat"            => "",
                        "nomer_hp"          => "",
                        // "rekgiro"           => "",
                        "channel_id"        => $dataRequest['channel_id'],
                        "waktu_proses"      => $dataRequest['waktu_proses'],
                        "nama_terpidana"    => $cekData->nama_terpidana,
                        "alamat_terpidana"  => $cekData->alamat_terpidana,
                        "nomor_briva"       => $cekData->nomor_briva,
                        "tgl_putusan"       => $cekData->tgl_putusan,
                    );
                } else { // SUDAH DI BAYAR
                    return array(
                        "status"            => true,
                        "respon_code"       => REST_Controller::HTTP_ALREADY_REPORTED,
                        "respon_mess"       => "No Reg Tilang ($no_reg_tilang) Sudah Di Bayar",
                        "no_reg_tilang"     => $no_reg_tilang,
                        "nomor_va"          => "",
                        "denda_tilang"      => (int) $cekData->denda_tilang,
                        "biaya_kirim"       => "",
                        "nominal"           => (int) $cekData->denda_tilang,
                        "admin"             => (int) gobang()->nominal_gobang,
                        "nama"              => "",
                        "info"              => "Pembayaran Gobang|" . $cekData->no_reg_tilang . "|" . $cekData->nama_terpidana,
                        "alamat"            => "",
                        "nomer_hp"          => "",
                        // "rekgiro"           => "",
                        "channel_id"        => $dataRequest['channel_id'],
                        "waktu_proses"      => $dataRequest['waktu_proses'],
                        "nama_terpidana"    => $cekData->nama_terpidana,
                        "alamat_terpidana"  => $cekData->alamat_terpidana,
                        "nomor_briva"       => $cekData->nomor_briva,
                        "tgl_putusan"       => $cekData->tgl_putusan,
                    );
                }
            } else { //DATA TIDAK DITEMUKAN
                return array(
                    "status"            => true,
                    "respon_code"       => REST_Controller::HTTP_NOT_FOUND,
                    "respon_mess"       => "No Reg Tilang ($no_reg_tilang) Tidak Ditemukan",
                    "no_reg_tilang"     => $no_reg_tilang,
                    "nomor_va"          => "",
                    "denda_tilang"      => "",
                    "biaya_kirim"       => "",
                    "nominal"           => "",
                    "admin"             => "",
                    "nama"              => "",
                    "info"              => "No Reg Tilang ($no_reg_tilang) Tidak Ditemukan",
                    "alamat"            => "",
                    "nomer_hp"          => "",
                    // "rekgiro"           => "",
                    "channel_id"        => $dataRequest['channel_id'],
                    "waktu_proses"      => $dataRequest['waktu_proses'],
                    "nama_terpidana"    => "",
                    "alamat_terpidana"  => "",
                    "nomor_briva"       => "",
                    "tgl_putusan"       => "",
                );
            }
        } else {
            return array(
                "status"            => true,
                "respon_code"       => REST_Controller::HTTP_BAD_REQUEST,
                "respon_mess"       => "Kode institusi tidak dikenali",
                "no_reg_tilang"     => $no_reg_tilang,
                "nomor_va"          => "",
                "denda_tilang"      => "",
                "biaya_kirim"       => "",
                "nominal"           => "",
                "admin"             => "",
                "nama"              => "",
                "info"              => "Kode institusi tidak dikenali",
                "alamat"            => "",
                "nomer_hp"          => "",
                // "rekgiro"           => "",
                "channel_id"        => $dataRequest['channel_id'],
                "waktu_proses"      => $dataRequest['waktu_proses'],
                "nama_terpidana"    => "",
                "alamat_terpidana"  => "",
                "nomor_briva"       => "",
                "tgl_putusan"       => "",
            );
        }
    }

    //get data untuk teller
    public function vasbupos_info_post()
    {

        if (empty($this->input->post())) {
            $inputFromJson = file_get_contents('php://input');
            $_POST = json_decode($inputFromJson, TRUE);
        }

        $no_reg_tilang  = $this->input->post('no_reg_tilang');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $waktu_proses   = $this->input->post('waktu_proses');

        $dataRequest    = array(
            "no_reg_tilang" => $no_reg_tilang,
            "kode_inst"     => $kode_inst,
            "channel_id"    => $channel_id,
            "waktu_proses"  => $waktu_proses
        );

        $this->response($this->get_data_tilang($dataRequest), REST_Controller::HTTP_OK);
    }

    //get data untuk teller backup
    public function vasbupos_info_backup_post()
    {
        $no_reg_tilang  = $this->input->post('no_reg_tilang');

        $cekData        = $this->m_data->select(array(
            "no_reg_tilang",
            "nama_terpidana",
            "alamat_terpidana",
            "nomor_briva",
            "tgl_putusan",
            // "denda",
            // "biaya_perkara",
            "(denda + biaya_perkara) as denda_tilang"
        ));
        $cekData        = $this->m_data->getWhere("LOWER(no_reg_tilang)", strtolower($no_reg_tilang));
        $cekData        = $this->m_data->getData("daftar_terpidana")->row_array();

        if ($cekData) { //DATA KETEMU
            $cekUdahBayar   = $this->m_data->getWhere(
                "permintaan_user.no_reg_tilang",
                $cekData['no_reg_tilang']
            );
            $cekUdahBayar   = $this->m_data->getJoin(
                "bb_status",
                "permintaan_user.id_permintaan = bb_status.id_permintaan",
                "INNER"
            );
            $cekUdahBayar   = $this->m_data->getData("permintaan_user")->num_rows();
            if ($cekUdahBayar < 1) {  // BELUM DI BAYAR
                $cekData['biaya_admin']   = (string) gobang()->nominal_gobang;
                $result = array_merge(
                    array(
                        "status"        => true,
                        "respon_code"   => "00",
                        "respon_mess"   => "Data Ditemukan"
                    ),
                    $cekData
                );
                $this->response($result, REST_Controller::HTTP_OK);
            } else { // SUDAH DI BAYAR
                $this->response(array(
                    "status"            => true,
                    "respon_code"       => REST_Controller::HTTP_ALREADY_REPORTED,
                    "respon_mess"       => "No Reg Tilang ($no_reg_tilang) Sudah Di Bayar",
                    "no_reg_tilang"     => $no_reg_tilang,
                    "nama_terpidana"    => "",
                    "alamat_terpidana"  => "",
                    "nomor_briva"       => "",
                    "tgl_putusan"       => "",
                    "denda"             => "",
                    "biaya_admin"       => "",
                ), REST_Controller::HTTP_OK);
            }
        } else { //DATA TIDAK DITEMUKAN
            $this->response(array(
                "status"            => true,
                "respon_code"       => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"       => "No Reg Tilang ($no_reg_tilang) Tidak Ditemukan",
                "no_reg_tilang"     => $no_reg_tilang,
                "nama_terpidana"    => "",
                "alamat_terpidana"  => "",
                "nomor_briva"       => "",
                "tgl_putusan"       => "",
                "denda"             => "",
                "biaya_admin"       => "",
            ), REST_Controller::HTTP_OK);
        }
    }

    //payment via teller
    public function vasbupos_direct_payment_post()
    {

        if (empty($this->input->post())) {
            $inputFromJson = file_get_contents('php://input');
            $_POST = json_decode($inputFromJson, TRUE);
        }

        $no_reg_tilang  = $this->input->post('no_reg_tilang');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $nominal        = $this->input->post('nominal');
        $admin          = $this->input->post('admin');
        $refnumber      = $this->input->post('refnumber');
        $waktu_proses   = $this->input->post('waktu_proses');
        $nopen          = $this->input->post('nopen');
        $hashing        = $this->input->post('hashing'); // base64(333#GOBANG#$no_reg_tilang)    

        $dataRequest    = array(
            "no_reg_tilang" => $no_reg_tilang,
            "kode_inst"     => $kode_inst,
            "channel_id"    => $channel_id,
            "waktu_proses"  => $waktu_proses
        );

        // Cek Hashing
        $rumus = base64_encode(gobang()->kode_inst . "#GOBANG#" . $no_reg_tilang);
        if ($hashing == $rumus) {
            $dataTilang = $this->get_data_tilang($dataRequest);
            if ($dataTilang['respon_code'] == gobang()->code_sukses) {
                //INSERT DULU KE TABLE PERMINTAAN | RIBET SLUUR

                //VARIABLE BUAT INSERT KE PERMINTAAN
                $nama_penerima      = "-";
                $alamat_antar       = "-";
                $detail_alamat      = "";
                $kode_pos           = "";
                $nomer_hp           = "-";
                $request_by         = "pos";
                $no_va              = $this->generateVA();
                $nominal_denda      = $dataTilang['denda_tilang'];
                $nominal_perkara    = gobang()->nominal_perkara;
                $nominal_pos        = 0;
                $nominal_gobang     = $dataTilang['admin'];
                $waktu_expired      = date("Y-m-d H:i:s", strtotime(date("Y-m-d H:i:s") . " +1 days"));

                $dataInsert = array(
                    "no_reg_tilang"     => $no_reg_tilang,
                    "nama_penerima"     => $nama_penerima,
                    "alamat_antar"      => $alamat_antar,
                    "detail_alamat"     => $detail_alamat,
                    "kode_pos"          => $kode_pos,
                    "nomer_hp"          => $nomer_hp,
                    "request_by"        => $request_by,
                    "no_va"             => $no_va,
                    "nominal_denda"     => $nominal_denda,
                    "nominal_perkara"   => $nominal_perkara,
                    "nominal_pos"       => $nominal_pos,
                    "nominal_gobang"    => $nominal_gobang,
                    "waktu_expired"     => $waktu_expired
                );
                $insert = $this->m_data->insert("permintaan_user", $dataInsert);

                if ($insert > 0) {
                    // BERHASIL INSERT KE TABLE PERMINTAAN USER
                    $dataInsert = array(
                        "id_permintaan"     => $insert,
                        "req"               => 0,
                        "channel_id"        => $channel_id,
                        "nominal"           => $nominal,
                        "admin"             => $admin,
                        "refnumber"         => $refnumber,
                        "waktu_proses"      => $waktu_proses,
                        "nopen"             => $nopen
                    );
                    $insertBBStatus = $this->m_data->insert("bb_status", $dataInsert);
                    if ($insertBBStatus > 0) {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => gobang()->code_sukses,
                            "respon_mess"   => "Pembayaran Gobang|" . $dataTilang['no_reg_tilang'] . "|" . $dataTilang['nama_terpidana'],
                            "nomor_va"      => $no_va,
                            "kode_inst"     => $kode_inst,
                            "channel_id"    => $channel_id,
                            "nominal"       => $nominal,
                            "admin"         => $admin,
                            "refnumber"     => $refnumber,
                            "waktu_proses"  => $waktu_proses,
                            "nopen"         => $nopen
                        ), REST_Controller::HTTP_OK);
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_PRECONDITION_FAILED,
                            "respon_mess"   => "Terjadi kesalahan pada server (GB-412)",
                            "nomor_va"      => $no_va,
                            "kode_inst"     => $kode_inst,
                            "channel_id"    => $channel_id,
                            "nominal"       => $nominal,
                            "admin"         => $admin,
                            "refnumber"     => $refnumber,
                            "waktu_proses"  => $waktu_proses,
                            "nopen"         => $nopen
                        ), REST_Controller::HTTP_OK);
                    }
                } else {
                    // GAGAL INSERT KE TABLE PERMINTAAN USER
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_BAD_GATEWAY,
                        "respon_mess"   => "Terjadi Kesalahan Pada Server (GB-502)",
                        "no_reg_tilang" => $no_reg_tilang,
                        "nomor_va"      => "",
                        "kode_inst"     => $kode_inst,
                        "channel_id"    => $channel_id,
                        "nominal"       => $nominal,
                        "admin"         => $admin,
                        "refnumber"     => $refnumber,
                        "waktu_proses"  => $waktu_proses,
                        "nopen"         => $nopen
                    ), REST_Controller::HTTP_OK);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => $dataTilang['respon_code'],
                    "respon_mess"   => $dataTilang['respon_mess'],
                    "no_reg_tilang" => $no_reg_tilang,
                    "nomor_va"      => "",
                    "kode_inst"     => $kode_inst,
                    "channel_id"    => $channel_id,
                    "nominal"       => $nominal,
                    "admin"         => $admin,
                    "refnumber"     => $refnumber,
                    "waktu_proses"  => $waktu_proses,
                    "nopen"         => $nopen
                ), REST_Controller::HTTP_OK);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_CONFLICT,
                "respon_mess"   => "Transaksi ditolak. hash tidak dikenali",
                "no_reg_tilang" => $no_reg_tilang,
                "nomor_va"      => "",
                "kode_inst"     => $kode_inst,
                "channel_id"    => $channel_id,
                "nominal"       => "",
                "admin"         => gobang()->nominal_gobang,
                "refnumber"     => $refnumber,
                "waktu_proses"  => $waktu_proses,
                "nopen"         => $nopen
            ), REST_Controller::HTTP_OK);
        }
    }
}
