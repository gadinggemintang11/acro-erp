<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Welcome extends CI_Controller {

    public function index()
    {
        redirect('commoncode'); // arahkan ke modul pertama nanti
    }
}
