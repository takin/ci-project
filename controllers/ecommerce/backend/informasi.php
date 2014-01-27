<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Informasi extends BController {

	public function __construct()
	{
		parent::__construct();
		$this->load->model('minformasi','info');
		$this->load->library('pagination');
	}

	public function daftar()
	{
		// initialize the pagination setup
		$pagination_config = array('base_url'=>base_url('backend/informasi/daftar/?'),'per_page'=>$this->_per_page,'total_rows'=>$this->info->count());
		$this->pagination->initialize($pagination_config);
		$data['pagination'] = $this->pagination->create_links();
		$informasi = $this->info->get_all_informasi($this->_per_page, $this->_offset);
		/**
		 * cek apakah $offset > 0 ?
		 * jika iya, maka lakukan pengambilan data pertama, jika pada pengambilan data pertama 
		 * mengembalikan null/false, maka lakukan pengambilan kedua dengan menggunakan $offset = 0
		 * ini dilakukan untuk mengantisipasi request dalam mode AJAX setelah proses penghapusan informasi
		 * jika informasi yang dihapus adalah informasi terakhir dari halaman terakhir, agar tidak menampikan nodata
		 * dan pagination tetap ada
		 */
		if( ! $informasi && $this->_offset > 0 && $this->input->is_ajax_request() ) {
			$informasi = $this->info->get_all_informasi($this->_per_page, 0);
		}
		if( $informasi ) {
			$tabel_heading = array('#','Judul informasi','Isi Informasi','Opsi');
			foreach ($informasi as $key => $i) {
				$isi = substr($i->isi_informasi, 0, 300);
				$isi = $isi . '...';
				$tabel_rows[] = array(
					array('data'=>'<div class="btn-group">'.form_button(array('class'=>'btn btn-danger','content'=>++$key,'disabled'=>'disabled')).anchor('backend/informasi/action?state=edit&id='.$i->id_informasi.'&page='.$this->_page,'Edit',array('class'=>'btn btn-primary navButton','id'=>'editButton')).'</div>','width'=>120),
					array('data'=>$i->judul_informasi,'width'=>150),
					array('data'=>$isi),
					array('data'=>form_checkbox('id_informasi[]', $i->id_informasi),'id'=>$i->id_informasi,'width'=>50)
					);
			}
			$data['tabel_informasi'] = $this->gen_table($tabel_rows,$tabel_heading);
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/informasi/list_informasi',$data,TRUE);

			// setup for ajax response 
			$this->_ajax_response->code = 200;
			$this->_ajax_response->page = $this->_vdata->body;

			if ($this->input->get('partial')) {
				$this->_ajax_response->page = $data['tabel_informasi'];
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

	public function action()
	{
		$action = $this->input->post('action');
		if( $action ){
			switch ($action) {
				case 'tambah':
				if($this->validation('informasi')) {
					$data = $this->input->post('info');
					$insert = $this->info->add_informasi($data);
					if( $insert ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Penambahan Informasi berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Penambahan Informasi gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'edit' :
				if($this->validation('informasi')) {
					$data = $this->input->post('info');
					$id_informasi = $this->input->post('id_informasi');
					$update = $this->info->edit_informasi($data,$id_informasi);
					if( $update ) {
						$this->_ajax_response->code = 200;
						$this->_ajax_response->message = 'Update data informasi berhasil!';
					}
					else {
						$this->_ajax_response->message = 'Update data informasi gagal!';
					}
				}
				else {
					$this->_ajax_response->message = $this->_vdata->body;
				}
				break;
				case 'del' :
				$id_informasi = $this->input->post('id_informasi');
				$del = $this->info->del_informasi($id_informasi);
				if( $del ) {
					$this->_ajax_response->code = 200;
					$this->_ajax_response->message = 'Informasi telah dihapus!';
				}
				else {
					$this->_ajax_response->message = 'Informasi tidak dapat dihapus!';
				}
				break;
			}
			$this->_generate_ajax_response();
		}
		else {
			$state = strtolower($this->input->get('state'));
			$data['informasi'] = new stdClass;
			$data['informasi']->id_informasi = null;
			$data['informasi']->judul_informasi = null;
			$data['informasi']->isi_informasi = null;
			$data['informasi']->desc = null;
			$data['informasi']->keywords = null;
			if( $state === 'edit' ) {
				$id_informasi = $this->input->get('id');
				$data['informasi'] = $this->info->get_informasi($id_informasi);
			}
			$this->_vdata->body = $this->load->view('backend/sb-admin/parts/informasi/form_informasi',$data,TRUE);
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