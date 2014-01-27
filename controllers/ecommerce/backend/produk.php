<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Produk extends BController {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mproduk','produk');
		$this->load->model('mkategori','kategori');
		$this->load->library('pagination');
	}

	public function daftar()
	{
		$id_kategori = ($this->input->get('kategori')) ? $this->input->get('kategori') : 'all';
		// initialize the pagination setup
		$pagination_config = array('base_url'=>base_url('backend/produk/daftar?kategori='.$id_kategori),'per_page'=>$this->_per_page);
		$kategori = $this->kategori->get_all_kategori(100,0);
		$data_produk['list_kategori']['all'] = '-- Kategori Produk --';
		
		foreach ($kategori as $kat) {
			$data_produk['list_kategori'][$kat->id_kategori_produk] = $kat->nama_kategori;
		}

		$produk = $this->produk->get_all_product($this->_per_page,$this->_offset);
		if( is_numeric($id_kategori) ) {
			$produk = $this->produk->get_product_by_kategori($id_kategori,$this->_per_page,$this->_offset);
		}

		if( $produk ) {
			$table_heading = array('#','Kode Produk','Nama Produk','Harga Satuan','Stok','Opsi');
			// label stok produk
			foreach ($produk as $n => $p) {
				if ($p->stok > 0) {
					$stok = '<span class="label label-success">'.$p->stok.'</span>';
				}
				if($p->stok == 0) {
					$stok = '<span class="label label-danger">kosong</span>';
				}
				if( $p->stok < 0) {
					$stok = '<span class="label label-info">pre-order</span>';
				}
				$table_rows[] = array(
					array('data'=>'<div class="btn-group">'.form_button(array('class'=>'btn btn-danger','content'=>++$n,'disabled'=>'disabled')).anchor('backend/produk/action?state=edit&produk='.$p->id_produk.'&kategori='.$id_kategori.'&page='.$this->_page,'<i class="fa fa-qrcode"></i> Detail',array('class'=>'btn btn-primary navButton')).'</div>','width'=>150),
					array('data'=>$p->SKU,'width'=>150),
					$p->nama_produk,
					array('data'=>$p->harga,'class'=>'rupiah','width'=>130,'cellspacing'=>20),
					array('data'=>$stok,'width'=>120),
					array('data'=>form_checkbox('id_produk', $p->id_produk),'id'=>$p->id_produk,'width'=>50)
					);
			}
			// generate tabel daftar produk
			$data_produk['tabel_produk'] = $this->gen_table($table_rows,$table_heading);
			
			$pagination_config['total_rows'] = ( $id_kategori === 'all') ? $this->produk->count_all_product() : $this->produk->count_product_per_category($id_kategori);
			// initialize the pagination object
			$this->pagination->initialize($pagination_config);
			// create the pagination link
			$data_produk['pagination'] = $this->pagination->create_links();

			$this->_ajax_response->code = 200;
			$this->_ajax_response->partial = FALSE;
			$this->_ajax_response->pagination = $data_produk['pagination'];
		}
		else {
			$this->nodata();
		}
		$this->_vdata->body = $this->load->view('backend/sb-admin/parts/produk/list_produk',$data_produk,TRUE);
		
		if( $this->input->is_ajax_request() ) {
			$this->_ajax_response->page = $this->_vdata->body;
			$partial = $this->input->get('partial');
			if( $partial ){
				$this->_ajax_response->partial = TRUE;
				$this->_ajax_response->page = $data_produk['tabel_produk'];
			}
			$this->_generate_ajax_response();
		}
		else {
			// var_dump($produk);
			$this->vRender();
		}
	}

	public function uploadfoto()
	{
		
	}

/*	public function info($id_produk)
	{
		$data['produk'] = $this->produk->get_product($id_produk);
		$data['spesifikasi'] = json_decode($data['produk']->spesifikasi);
		$this->_vdata->body = $this->load->view('backend/sb-admin/parts/produk/info_produk',$data,TRUE);
		if( $this->input->is_ajax_request() ){
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}

	public function tambah()
	{
		$this->_vdata->body = $this->load->view('backend/sb-admin/parts/produk/tambah_produk','',TRUE);
		if( $this->input->is_ajax_request() ){
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}

	public function edit($id_produk)
	{
		$data['produk'] = $this->produk->get_product($id_produk);
		$data['spesifikasi'] = json_decode($data['produk']->spesifikasi);
		$this->_vdata->body = $this->load->view('backend/sb-admin/parts/produk/edit_produk',$data,TRUE);
		if( $this->input->is_ajax_request() ){
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;
			$this->_generate_ajax_response();
		}
		else {
			$this->vRender();
		}
	}*/

	public function action()
	{
		$form = $this->input->get('state');
		$id_produk = $this->input->get('produk');
		$action = $this->input->post('action');
		if( $action ){
			switch ($action) {
				case 'tambah':
				$valid = $this->validation('add_product');
				if( $valid ) {
					$data = $this->input->post('data');
					$insert = $this->produk->add_product($data);
					if( $insert ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Penambahan Produk berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Penambahan Produk gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'edit' :
				if($this->validation('add_product')) {
					$data = $this->input->post('produk');
					$id_produk = $this->input->post('id_produk');
					$update = $this->produk->update_product($id_produk,$data);
					if( $update ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Update data produk berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Update data produk gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'del' :
				$id_produk = $this->input->post('id_produk');
				$del = $this->produk->del_product($id_produk);
				if( $del ) {
					$this->_ajax_response->code = 200;
					$this->_ajax_response->message = 'Produk telah dihapus!';
				}
				else {
					$this->_ajax_response->message = 'Produk tidak dapat dihapus!';
				}
				break;
			}
			$this->_generate_ajax_response();
		}
		else {
			$data = array('produk'=>new stdClass,'spesifikasi'=>null);
			$data['produk']->nama_produk = null;
			$data['produk']->SKU = null;
			$data['produk']->harga = null;
			$data['produk']->stok = null;
			$data['produk']->desc = null;
			$data['produk']->foto = 'images/products/default_image.jpg';
			$data['produk']->spesifikasi = null;
			if (strtolower($form) === 'edit' ) {
				$data['produk'] = $this->produk->get_product($id_produk);
				$data['spesifikasi'] = json_decode($data['produk']->spesifikasi);
			}

			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/produk/form_produk',$data,TRUE);
			
			if( $this->input->is_ajax_request() ) {
				$this->_ajax_response->code = 200;
				$this->_ajax_response->page = $this->_vdata->body;
				$this->_generate_ajax_response();
			}
			else {
				$this->vRender();
			}
		}
	}
}

/* End of file produk.php */
/* Location: ./application/controllers/produk.php */