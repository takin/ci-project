<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Pesanan extends BController {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('morder','order');
		$this->load->library('pagination');
	}

	public function index()
	{
		$this->daftar();
	}

	public function daftar()
	{
		$pesanan = $this->input->get('kategori') ? $this->input->get('kategori') : 'baru';
		// pagination setup 
		$pagination_config = array('base_url'=>base_url('backend/pesanan/daftar?kategori='.$pesanan),'per_page'=>$this->_per_page);
		$section_header['title'] = 'Pesanan';
		switch ( $pesanan ) {
			case 'baru':
			$order = $this->order->get_all_new_order($this->_per_page,$this->_offset);
			if( ! $order && $this->_offset > 0 && $this->input->is_ajax_request() ) {
				$order = $this->order->get_all_new_order($this->_per_page,0);
			}
			$pagination_config['total_rows'] = MOrder::count_new_order();
			$section_header['sub_title'] = 'Terbaru';
			break;
			case 'sukses' : 
			$order = $this->order->get_all_confirmed_order($this->_per_page,$this->_offset);
			if( ! $order && $this->_offset > 0 && $this->input->is_ajax_request() ) {
				$order = $this->order->get_all_confirmed_order($this->_per_page,0);
			}
			$pagination_config['total_rows'] = MOrder::count_confirmed_order();
			$section_header['sub_title'] = 'Sudah Dibayar';
			break;
			case 'terkirim' : 
			$bulan = date('m');
			$tanggal = Tanggal::konversi(date('Y-m-d'),2);
			$tanggal = explode(' ', $tanggal);
			$this_month = $tanggal[2].' '.$tanggal[3];
			$order = $this->order->get_all_delivered_order_per_month($bulan,$this->_per_page,$this->_offset);
			if( ! $order && $this->_offset > 0 && $this->input->is_ajax_request() ) {
				$order = $this->order->get_all_delivered_order_per_month($bulan,$this->_per_page,0);
			}
			$pagination_config['total_rows'] = MOrder::count_monthly_delivered_order($bulan);
			$section_header['sub_title'] = 'Sudah Dikirim '.$this_month;
			break;
			case 'all' : 
			$order = $this->order->get_all_delivered_order($this->_per_page,$this->_offset);
			if( ! $order && $this->_offset > 0 && $this->input->is_ajax_request() ) {
				$order = $this->order->get_all_delivered_order($this->_per_page,0);
			}
			$pagination_config['total_rows'] = MOrder::count_delivered_order();
			$section_header['sub_title'] = 'Keseluruhan ';
			break;
		}
		
		if( $order ) {
			$this->pagination->initialize($pagination_config);
			$table_template = 'table table-bordered table-hover table-striped tablesorter';
			$table_heading = array('#','No. Order','Nama Pelanggan','Tanggal Pesan','Telp/HP','Nominal Order','Opsi');
			// $back_offset = ($this->_offset === 0) ? null : $this->_offset;
			foreach ($order as $i => $o) {
				$table_rows[] = array(++$i,anchor('backend/pesanan/detail?kode='.$o->kode_order.'&type='.$pesanan.'&page='.$this->_page,'<i class="fa fa-qrcode"></i> '.$o->kode_order,array('class'=>'navButton')),$o->nama_depan_penerima . ' ' . $o->nama_belakang_penerima,Tanggal::konversi($o->tgl_order,2),$o->telp,array('data'=>$o->total_harga,'class'=>'rupiah'),array('data'=>form_checkbox('kode[]', $o->kode_order),'align'=>'center','id'=>$o->kode_order));
			}
			$data['table'] = $this->gen_table($table_rows,$table_heading,$table_template);
			$data['list_title'] = 'Daftar Pesanan ' . $section_header['sub_title'];
			$data['pagination'] = $this->pagination->create_links();
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/pesanan/list',$data,TRUE);
			
			// setup for ajax response 
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;

			if ($this->input->get('partial')) {
				$this->_ajax_response->page = $data['table'];
			}
			$this->_ajax_response->pagination = $data['pagination'];
		}
		else {
			$this->_ajax_response->pagination = null;
			$this->_ajax_response->page = '<div class="alert alert-danger">Tidak ada data</div>';
			$this->nodata();
		}
		if ( $this->input->is_ajax_request() ) {
			$this->_ajax_response->partial = ( $this->input->get('partial') ) ? TRUE : FALSE;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}

	public function detail()
	{
		$kode_order = $this->input->get('kode');
		$origin = $this->input->get('type') ? $this->input->get('type') : 'backend';
		if (is_null($kode_order)) $this->nodata();
		$order = $this->order->get_order($kode_order);
		if( $order ) {
			$detail_pesanan = $this->order->get_detail_order($kode_order);
			$tgl_order = Tanggal::konversi($order->tgl_order,2);
			$tgl_pengiriman = (is_null($order->tgl_kirim)) ? '<span class="label label-danger">Belum Dikirim</span>' : Tanggal::konversi($order->tgl_kirim,2);
			$tgl_konfirmasi = (is_null($order->tgl_konfirmasi)) ? '<span class="label label-danger">Belum Konfirmasi</span>' : Tanggal::konversi($order->tgl_konfirmasi,2);
			$order_heading = array('#','Kode Produk','Nama Produk','Qty','Harga Satuan','Sub Total');
			$info_heading = array('data'=>'Data Pelanggan','colspan'=>2);
			$raw_info_rows = array('Tanggal Pemesanan'=>$tgl_order,
				'Nama Penerima'=>$order->nama_depan_penerima . ' ' . $order->nama_belakang_penerima,
				'Provinsi'=>$order->nm_prop,
				'Kabupaten/Kota'=>$order->nm_kab,
				'Kecamatan'=>$order->nm_kec,
				'Alamat'=>$order->alamat_pengiriman,
				'Tanggal Konfirmasi'=>$tgl_konfirmasi,
				'Tanggal Pengiriman'=>$tgl_pengiriman,
				'Alamat Pembayaran'=>$order->nama_bank,
				'Kode Transfer'=>$order->kode_transfer);

			foreach ($raw_info_rows as $key => $value) {
				$info_rows[] = array($key,$value);
			}
			foreach ($detail_pesanan as $i=>$detail) {
				$order_rows[] = array(++$i,array('data'=>$detail->SKU,'width'=>150),$detail->nama_produk,$detail->jumlah,array('data'=>$detail->harga,'class'=>'rupiah','width'=>150),array('data'=>$detail->sub_total,'class'=>'rupiah','width'=>150));
			}
			
			$data['order_ok'] = ($order->status_order == 1) ? TRUE : FALSE;
			$data['kode_order'] = $kode_order;
			$data['detail_order_table'] = $this->gen_table($order_rows,$order_heading);
			$data['info_order_table'] = $this->gen_table($info_rows,$info_heading);
			$data['back'] = ($origin == 'backend') ? $origin : base_url('backend/pesanan?kategori='.$origin.'&page='.$this->_page);
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/pesanan/detail',$data,TRUE);
		}
		else {
			$this->nodata();
		}
		if ( $this->input->is_ajax_request() ) {
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}

	/**
	 * method untuk melakukan proses pengiriman atau penghapusan data
	 * yang ada di dalam list produk.
	 * mode tergantung dari $_POST['mode'] yang dikirimkan, del/send
	 * @return integer kode untuk menotifkasi client apakah proses berhasil atau tidak
	 * @return string pesan untuk ditampilkan pada cliens side
	 */
	public function action()
	{
		// array kode order yang akan di proses
		$kode_order = $this->input->post('kode');	
		// mode apakah del/send
		$mode = $this->input->post('mode');
		// pesan tergantung mode
		$action = ($mode == 'del') ? 'hapus' : 'kirim';
		if( ! $kode_order ) {
			$this->_ajax_response->message = 'tidak ada data untuk di'.$action;
		}
		else {
			$action_done = ($mode == 'del') ? $this->order->hapus($kode_order) : $this->order->kirim($kode_order);
			if( $action_done ) {
				$this->_ajax_response->code = 200;
				$this->_ajax_response->message = 'Data berhasil di'.$action.'!';
			}
			else {
				$this->_ajax_response->message = 'Item tidak dapat di'.$action;
			}
		}
		$this->_generate_ajax_response();
	}

}

/* End of file pesanan.php */
/* Location: ./application/controllers/pesanan.php */