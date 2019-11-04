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

    function cek_data_post()
    {
        $no_reg_tilang  = $this->input->post('no_reg_tilang');        
        
        $cekData    = $this->m_data->getWhere("no_reg_tilang", $no_reg_tilang);
        $cekData    = $this->m_data->getData("daftar_terpidana")->row();        

        if ($cekData) {
            if ($cekData->posisi !== "selesai") {
                $cekRequest     = $this->m_data->getWhere("no_reg_tilang", $cekData->no_reg_tilang);
                $cekRequest     = $this->m_data->order_by("waktu_expired", "DESC");
                $cekRequest     = $this->m_data->limitOffset(1, NULL);
                $cekRequest     = $this->m_data->getData("permintaan_user")->row();

                if ($cekRequest) {
                    if ($cekRequest->waktu_expired > date("Y-m-d H:i:s")) {
                        //CEK UDAH BAYAR BELUM - CEK DI TABLE BB_STATUS - KALO ADA BERARTI DAH BAYAR
                        $cekBbStatus    = $this->m_data->getWhere("id_permintaan", $cekRequest->id_permintaan);
                        $cekBbStatus    = $this->m_data->getData("bb_status")->row();
                        if($cekBbStatus){
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
}
