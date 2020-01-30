<?php

defined('BASEPATH') or exit('No direct script access allowed');

function gobang()
{
    return (object) array(
        "request_by"        => "app",
        "nominal_perkara"   => 1000,
        "nominal_gobang"    => 3000,
        "code_sukses"       => "00",
        "admin_rp"          => 3000,
        "kode_inst"         => "333"
    );
}
