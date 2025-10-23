<?php
namespace App\Controllers;

use DB;
use DateTime;

class DashboardController {
    public function index() {
        require_login();
        ob_start();
        view('dashboard', []);
        return ob_get_clean();
    }
}