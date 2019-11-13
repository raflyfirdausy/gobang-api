<?php

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use Restserver\Libraries\REST_Controller;

class Request extends REST_Controller
{

    public function __construct($config = 'rest')
    {
        parent::__construct($config);
    }

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

        $cekData    = $this->m_data->getWhere("no_reg_tilang", $no_reg_tilang);
        $cekData    = $this->m_data->getData("daftar_terpidana")->row();

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
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_CREATED,
                    "respon_mess"   => "Berhasil melakukan permintaan, silahkan lakukan pembayaran",
                    "data"          => $ambilDataTerakhir
                ), REST_Controller::HTTP_CREATED);
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_BAD_REQUEST,
                    "respon_mess"   => $this->m_data->getError(),
                    "data"          => NULL
                ), REST_Controller::HTTP_BAD_REQUEST);
            }
        } else {
            //DATA NOT FOUND
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Nomer Registrasi Tilang Tidak ditemukan!",
                "data"          => NULL
            ), REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function cek_data_post()
    {
        $no_reg_tilang  = $this->input->post('no_reg_tilang');
        $cekData        = $this->m_data->getWhere("no_reg_tilang", $no_reg_tilang);
        $cekData        = $this->m_data->getData("daftar_terpidana")->row();
        if ($cekData) {
            if ($cekData->posisi !== "selesai") {
                $cekRequest     = $this->m_data->getWhere("no_reg_tilang", $cekData->no_reg_tilang);
                $cekRequest     = $this->m_data->order_by("waktu_expired", "DESC");
                $cekRequest     = $this->m_data->limitOffset(1, NULL);
                $cekRequest     = $this->m_data->getData("permintaan_user")->row();
                if ($cekRequest) {
                    if ($cekRequest->waktu_expired > date("Y-m-d H:i:s")) {
                        //CEK UDAH BAYAR BELUM - CEK DI TABLE BB_STATUS - KALO ADA BERARTI DAH BAYAR
                        //BUTUH JOIN TABLE PERMINTAAN USER KAYANE
                        $cekBbStatus    = $this->m_data->getWhere("id_permintaan", $cekRequest->id_permintaan);
                        $cekBbStatus    = $this->m_data->getData("bb_status")->row();
                        if ($cekBbStatus) {
                            //UDAH BAYAR
                            $this->response(array(
                                "status"        => true,
                                "respon_code"   => REST_Controller::HTTP_OK,
                                "respon_mess"   => "Bukti tilang sedang kami kirim, silahkan lacak pengiriman dengan memasukan nomer resi yang sudah kami masukan",
                                "data"          => $cekBbStatus
                            ), REST_Controller::HTTP_OK);
                        } else {
                            //BELUM BAYAR
                            $this->response(array(
                                "status"        => true,
                                "respon_code"   => REST_Controller::HTTP_PAYMENT_REQUIRED,
                                "respon_mess"   => "Silahkan bayar sesuai dengan nominal sebelum batas pembayaran berakhir",
                                "data"          => $cekRequest
                            ), REST_Controller::HTTP_PAYMENT_REQUIRED);
                        }
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                            "respon_mess"   => "Request expired, silahkan isi ulang data request pengiriman bukti tilang",
                            "data"          => $cekData
                        ), REST_Controller::HTTP_EXPECTATION_FAILED);
                    }
                } else {
                    // BELUM REQUEST - ISI DATA - PROSES CEK SELESAI
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_FOUND,
                        "respon_mess"   => "Data ditemukan, silahkan isi data request pengiriman bukti tilang",
                        "data"          => $cekData
                    ), REST_Controller::HTTP_FOUND);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_MOVED_PERMANENTLY,
                    "respon_mess"   => "Barang bukti sudah di ambil atau sudah di antarkan ke alamat tujuan",
                    "data"          => NULL
                ), REST_Controller::HTTP_MOVED_PERMANENTLY);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Data Tidak Ditemukan, silahkan periksa kembali no registrasi tilang anda atau coba lagi beberapa saat",
                "data"          => NULL
            ), REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function vasbupos_payment_post()
    {
        $nomor_va       = $this->input->post('nomor_va');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $nominal        = $this->input->post('nominal');
        $admin          = $this->input->post('admin');
        $refnumber      = $this->input->post('refnumber');
        $waktu_proses   = $this->input->post('waktu_proses');
        $nopen          = $this->input->post('nopen');
        $hashing        = $this->input->post('hashing');
        $screet_key     = $this->input->post('screet_key');

        // Cek kode institusi Gobang
        if ($kode_inst == gobang()->kode_inst) {
            // Cek no VA 
            $cekVA     = $this->m_data->getWhere("no_va", $nomor_va);
            $cekVA     = $this->m_data->getData("permintaan_user")->row();
            if ($cekVA != NULL) {
                // Cek waktu Expired
                if ($cekVA->waktu_expired > date("Y-m-d H:i:s")) {
                    // Cek Hashing
                    $rumus = base64_encode(gobang()->kode_inst . "#" . $screet_key . "#" . $nomor_va);
                    if ($hashing == $rumus) {
                        // Cek key di table keys
                        $rumusExplode = explode("#", base64_decode($hashing));
                        $cekKey     = $this->m_data->getWhere("key", $rumusExplode[1]);
                        $cekKey     = $this->m_data->getWhere("level", 2);
                        $cekKey     = $this->m_data->getData("keys")->row();
                        if ($cekKey != NULL) {
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
                                ), REST_Controller::HTTP_PRECONDITION_FAILED);
                            }
                        } else {
                            $this->response(array(
                                "status"        => true,
                                "respon_code"   => REST_Controller::HTTP_FORBIDDEN,
                                "respon_mess"   => "Akses ditolak, user tidak di kenali",
                                "nomor_va"      => $nomor_va,
                                "kode_inst"     => $kode_inst,
                                "channel_id"    => $channel_id,
                                "nominal"       => $nominal,
                                "admin"         => $admin,
                                "refnumber"     => $refnumber,
                                "waktu_proses"  => $waktu_proses,
                                "nopen"         => $nopen
                            ), REST_Controller::HTTP_FORBIDDEN);
                        }
                    } else {
                        $this->response(array(
                            "status"        => true,
                            "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                            "respon_mess"   => "Transaksi ditolak. hash tidak dikenali",
                            "nomor_va"      => $nomor_va,
                            "kode_inst"     => $kode_inst,
                            "channel_id"    => $channel_id,
                            "nominal"       => $nominal,
                            "admin"         => $admin,
                            "refnumber"     => $refnumber,
                            "waktu_proses"  => $waktu_proses,
                            "nopen"         => $nopen
                        ), REST_Controller::HTTP_EXPECTATION_FAILED);
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
                    ), REST_Controller::HTTP_EXPECTATION_FAILED);
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
                ), REST_Controller::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Kode institusi tidak dikenal",
                "nomor_va"      => $nomor_va,
                "kode_inst"     => $kode_inst,
                "channel_id"    => $channel_id,
                "nominal"       => $nominal,
                "admin"         => $admin,
                "refnumber"     => $refnumber,
                "waktu_proses"  => $waktu_proses,
                "nopen"         => $nopen
            ), REST_Controller::HTTP_NOT_FOUND);
        }
    }

    public function vasbupos_inquiry_post()
    {
        $nomor_va       = $this->input->post('nomor_va');
        $kode_inst      = $this->input->post('kode_inst');
        $channel_id     = $this->input->post('channel_id');
        $waktu_proses   = $this->input->post('waktu_proses');

        $cekVA          = $this->m_data->getWhere("no_va", $nomor_va);
        $cekVA          = $this->m_data->getJoin("daftar_terpidana", "permintaan_user.no_reg_tilang = daftar_terpidana.no_reg_tilang", "INNER");
        $cekVA          = $this->m_data->getData("permintaan_user")->row();

        // Cek Kode Institusi
        if ($kode_inst == gobang()->kode_inst) {
            // Cek No VA
            if ($cekVA != NULL) {
                // Cek waktu Expired
                if ($cekVA->waktu_expired > date("Y-m-d H:i:s")) { 
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => "00",
                        "respon_mess"   => "Data ditemukan",
                        "nomor_va"      => $nomor_va,
                        "nominal"       => (int) $cekVA->nominal_denda + (int) $cekVA->nominal_perkara + (int) $cekVA->nominal_pos,
                        "admin"         => (int) $cekVA->nominal_gobang,
                        "nama"          => $cekVA->nama_penerima,
                        "info"          => "Pembayaran Gobang|" . $cekVA->no_reg_tilang . "|" . $cekVA->nama_terpidana,
                        "rekgiro"       => NULL,
                        "channel_id"    => $channel_id,
                        "waktu_proses"  => $waktu_proses,
                    ), REST_Controller::HTTP_OK);
                } else {
                    $this->response(array(
                        "status"        => true,
                        "respon_code"   => REST_Controller::HTTP_EXPECTATION_FAILED,
                        "respon_mess"   => "Nomor VA expired",
                        "nomor_va"      => $nomor_va,
                        "nominal"       => 0,
                        "admin"         => 0,
                        "nama"          => NULL,
                        "info"          => "Nomor VA expired",
                        "rekgiro"       => NULL,
                        "channel_id"    => $channel_id,
                        "waktu_proses"  => $waktu_proses,
                    ), REST_Controller::HTTP_EXPECTATION_FAILED);
                }
            } else {
                $this->response(array(
                    "status"        => true,
                    "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                    "respon_mess"   => "Nomer Virtual Account tidak ditemukan",
                    "nomor_va"      => $nomor_va,
                    "nominal"       => 0,
                    "admin"         => 0,
                    "nama"          => NULL,
                    "info"          => "Nomer Virtual Account tidak ditemukan",
                    "rekgiro"       => NULL,
                    "channel_id"    => $channel_id,
                    "waktu_proses"  => $waktu_proses,
                ), REST_Controller::HTTP_NOT_FOUND);
            }
        } else {
            $this->response(array(
                "status"        => true,
                "respon_code"   => REST_Controller::HTTP_NOT_FOUND,
                "respon_mess"   => "Kode institusi tidak dikenali",
                "nomor_va"      => $nomor_va,
                "nominal"       => 0,
                "admin"         => 0,
                "nama"          => NULL,
                "info"          => "Kode institusi tidak dikenali",
                "rekgiro"       => NULL,
                "channel_id"    => $channel_id,
                "waktu_proses"  => $waktu_proses,
            ), REST_Controller::HTTP_NOT_FOUND);
        }
    }
}
