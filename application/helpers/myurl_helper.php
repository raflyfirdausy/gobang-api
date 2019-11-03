<?php
defined('BASEPATH') or exit('No direct script access allowed');

function asset($uri = null)
{
    return base_url("assets/$uri");
}