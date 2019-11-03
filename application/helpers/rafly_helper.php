<?php
defined('BASEPATH') or exit('No direct script access allowed');

function faker()
{
    return Faker\Factory::create('id_ID');
}

function aaa()
{
    $CI = &get_instance();
    $userData = $this->m_data->getWhere("nip", $CI->session->userdata("login")->nip);
    $userData = $this->m_data->getData("admin")->row();
    return $userData;
}

function coba()
{
    return "hehehee";
}

function d($x)
{
    return die(json_encode($x));
}
