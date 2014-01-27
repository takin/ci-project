<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Kategori extends BController {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('mkategori','kategori');
		$this->load->library('pagination');
	}

	public function daftar()
	{
		// initialize the pagination setup
		$pagination_config = array('base_url'=>base_url('backend/kategori/daftar/?'),'per_page'=>$this->_per_page,'total_rows'=>$this->kategori->count());
		$this->pagination->initialize($pagination_config);
		$data['pagination'] = $this->pagination->create_links();

		$kategori = $this->kategori->get_all_kategori($this->_per_page,$this->_offset);
		/**
		 * cek apakah $offset > 0 ?
		 * jika iya, maka lakukan pengambilan data pertama, jika pada pengambilan data pertama 
		 * mengembalikan null/false, maka lakukan pengambilan kedua dengan menggunakan $offset = 0
		 * ini dilakukan untuk mengantisipasi request dalam mode AJAX setelah proses penghapusan data
		 * jika hanya terdapat satu buah data pada halaman terakhir, agar tidak menampikan nodata
		 * dan pagination tetap ada
		 */
		if( ! $kategori && $this->_offset > 0 && $this->input->is_ajax_request() ) {
			$kategori = $this->kategori->get_all_kategori($this->_per_page,0);
		}
		// jika terdapat kategori
		if( $kategori ) {
			$tabel_heading = array('#','Nama Kategori','Alias Kategori','Deskripsi','Opsi');
			foreach ($kategori as $key => $kat) {
				$tabel_rows[] = array(
					array('data'=>'<div class="btn-group">'.form_button(array('class'=>'btn btn-danger','content'=>++$key,'disabled'=>'disabled')).anchor('backend/kategori/action?state=edit&id='.$kat->id_kategori_produk.'&page='.$this->_page,'Edit',array('class'=>'btn btn-primary navButton','id'=>'editButton')).'</div>','width'=>150),
					array('data'=>$kat->nama_kategori,'width'=>250),
					array('data'=>$kat->alias_kategori,'width'=>250),
					$kat->desc,
					array('data'=>form_checkbox('id_kategori_produk[]', $kat->id_kategori_produk),'id'=>$kat->id_kategori_produk,'width'=>50)
					);
			}
			$data['tabel_kategori'] = $this->gen_table($tabel_rows,$tabel_heading);
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/kategori/list_kategori',$data,TRUE);
			// setup for ajax response 
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;

			if ($this->input->get('partial')) {
				$this->_ajax_response->page = $data['tabel_kategori'];
			}
			$this->_ajax_response->pagination = $data['pagination'];
		}
		// jika kategori masih kosong
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

	public function action()
	{
		$action = $this->input->post('action');
		if( $action ){
			switch ($action) {
				case 'tambah':
				if($this->validation('add_kategori')) {
					$data = $this->input->post('kategori');
					$insert = $this->kategori->add_kategori($data);
					if( $insert ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Penambahan kategori berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Penambahan kategori gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'edit' :
				if($this->validation('add_kategori')) {
					$data = $this->input->post('kategori');
					$id_kategori = $this->input->post('id_kategori_produk');
					$update = $this->kategori->edit_kategori($data,$id_kategori);
					if( $update ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Update data kategori berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Update data kategori gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'del' :
				$id_kategori = $this->input->post('id_kategori_produk');
				$del = $this->kategori->del_kategori($id_kategori);
				if( $del ) {
					$this->_ajax_response->code = 200;
					$this->_ajax_response->message = 'Kategori telah dihapus!';
				}
				else {
					$this->_ajax_response->message = 'Kategori tidak dapat dihapus!';
				}
				break;
			}
			$this->_generate_ajax_response();
		}
		else {
			$id_kategori_produk = $this->input->get('id');
			$action = strtolower($this->input->get('state'));
			// template data kategori 
			$data['kategori'] = new stdClass;
			$data['kategori']->id_kategori_produk = null;
			$data['kategori']->nama_kategori = null;
			$data['kategori']->induk_kategori = null;
			$data['kategori']->desc = null;
			$data['kategori']->keywords = null;

			$list_kategori = $this->kategori->get_all_kategori(100,0);
			$data['list_kategori'][0] = '-- Tidak ada Induk --';
			foreach ($list_kategori as $k) {
				$data['list_kategori'][$k->id_kategori_produk] =$k->nama_kategori;
			}

			if( $action === 'edit' ) {
				$data['kategori'] = $this->kategori->get_kategori($id_kategori_produk);
			}
			
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/kategori/form_kategori',$data,TRUE);
			if ( $this->input->is_ajax_request() ) {
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