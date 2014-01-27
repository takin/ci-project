<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Controller utama untuk halaman admin
 * Semua method di halaman ini membutuhkan autentifikasi (user harus login terlebih dahulu)
* oleh karena itu, maka @method authenticate() akan dipanggil setiap akan mengakses semua method
* method ini berada di ec_app/core/EC_Controller.php
* jika variabel session is_logged_in bernilai false (user belum login) maka user akan di redirect ke halaman auth/login
 * @method {} index() index([args]) [description]
 */

class Dashboard extends BController {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mcustomer');
		$this->load->model('morder');
	}

	public function index()
	{
		$bulan = date('m');
		$tahun = date('Y');
		$data['new'] = $this->morder->count_new_order();
		$data['success'] = $this->morder->count_confirmed_order();
		$data['monthly'] = $this->morder->count_monthly_delivered_order($bulan);
		$data['annual'] = $this->morder->get_annual_order($tahun);
		$data['new_order_table'] = '<div class="alert alert-warning">Tidak ada data</div>';

		if( $data['new'] > 0 ){
			// tabel list pesanan terbaru 
			$this->load->library('table');
			$new_order_data = $this->morder->get_all_new_order(5,0);
			$this->table->set_template(array('table_open'=>'<table class="table table-bordered table-hover table-striped tablesorter">'));
			$this->table->set_heading('Kode Order','Nama Pelanggan','Nominal Order');
			foreach ($new_order_data as $order) {
				$table_data[] = array(anchor('backend/pesanan/detail/'.$order->kode_order,$order->kode_order,array('class'=>'navButton')),$order->nama_depan_penerima . ' ' . $order->nama_belakang_penerima,array('data'=>$order->total_harga,'class'=>'rupiah'));
			}
			$data['new_order_table'] = $this->table->generate($table_data);
		}

		$this->_vdata->body = $this->load->view('backend/sb-admin/parts/dashboard/cards',$data,TRUE);
		if( $this->input->is_ajax_request() ) {
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}

	public function login()
	{
		// controller untuk login
	}

	public function logout()
	{
		// controller untuk proses logout
	}

	public function countorder($type = 'total')
	{
		if( $this->input->is_ajax_request() ) {
			$new = $this->morder->count_new_order();
			$confirmed = $this->morder->count_confirmed_order();
			$data['baru'] = ($new > 0) ? $new : null;
			$data['sukses'] = ($confirmed > 0) ? $confirmed: null;
			$data['total'] = ( $new > 0 || $confirmed  > 0 ) ? $new + $confirmed : null;
			echo json_encode($data);
		}
	}
}

/* End of file main.php */
/* Location: ./application/controllers/main.php */