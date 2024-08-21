<?php
defined('BASEPATH') or exit('No direct script access allowed');

class Admin extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        is_logged_in();
        is_admin();
        $this->load->helper('tglindo');
        $this->load->model('Admin_model', 'admin');
    }

    public function index()
    {
        $data['title'] = 'Dashboard';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();

        $data['user_perbulan'] = $this->admin->countUserPerbulan();
        $data['count_user'] = $this->admin->countJmlUser();
        $data['user_aktif'] = $this->admin->countUserAktif();
        $data['user_tak_aktif'] = $this->admin->countUserTakAktif();
        $data['list_user'] = $this->admin->getAllUserLimit();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/index', $data);
        $this->load->view('templates/footer');
    }

    public function profile()
    {
        $data['title'] = 'My Profile';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/profile', $data);
        $this->load->view('templates/footer');
    }

    public function edit_profile()
    {
        $this->form_validation->set_rules('nama', 'Nama Lengkap', 'required|trim');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'My Profile';
            $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();

            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar_admin', $data);
            $this->load->view('admin/profile', $data);
            $this->load->view('templates/footer');
        } else {
            $upload_image = $_FILES['image']['name'];
            if ($upload_image) {
                $config['allowed_types'] = 'gif|jpg|png|jpeg';
                $config['max_size']     = '2048';
                $config['upload_path'] = './assets/img/profile';
                $this->load->library('upload', $config);
                if ($this->upload->do_upload('image')) {
                    $old_image = $data['id']['image'];
                    if ($old_image != 'default.jpg') {
                        unlink(FCPATH . 'assets/img/profile/' . $old_image);
                    }
                    $new_image = $this->upload->data('file_name');
                    $this->db->set('image', $new_image);
                } else {
                    $this->session->set_flashdata('msg', '<div class="alert alert-danger" role="alert">Update Gagal</div>');
                    redirect('user/edit_profile');
                }
            }
            $id = $this->input->post('id');
            $nama = $this->input->post('nama');

            $this->db->set('nama', $nama);
            $this->db->where('id', $id);
            $this->db->update('mst_user');

            $this->session->set_flashdata('message', 'Update data');
            redirect('admin/profile');
        }
    }

    public function changePassword()
    {

        $this->form_validation->set_rules('current_password', 'Current Password', 'required|trim');
        $this->form_validation->set_rules('new_password1', 'New Password1', 'required|trim|min_length[3]|matches[new_password2]');
        $this->form_validation->set_rules('new_password2', 'Confirm New Password', 'required|trim|min_length[3]|matches[new_password1]');

        if ($this->form_validation->run() == false) {
            $data['title'] = 'My Profile';
            $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();

            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar_admin', $data);
            $this->load->view('admin/profile', $data);
            $this->load->view('templates/footer');
        } else {
            $current_password = $this->input->post('current_password');
            $new_password = $this->input->post('new_password1');
            if ($current_password == $new_password) {
                $this->session->set_flashdata('msg', '<div class="alert alert-danger" role="alert">GAGAL..... Password baru tidak boleh sama dengan password lama</div>');
                redirect('admin/profile');
            } else {
                $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $this->db->set('password', $password_hash);
                $this->db->where('username', $this->session->userdata('username'));
                $this->db->update('mst_user');
                $this->session->set_flashdata('message', 'Ubah password');
                redirect('admin/profile');
            }
        }
    }

    public function man_user()
    {
        $this->form_validation->set_rules('username', 'Username', 'required|trim|is_unique[mst_user.username]', array(
            'is_unique' => 'Username sudah ada'
        ));
        $this->form_validation->set_rules('password1', 'Password', 'required|trim|min_length[3]|matches[password2]', array(
            'matches' => 'Password tidak sama',
            'min_length' => 'password min 3 karakter'
        ));
        $this->form_validation->set_rules('password2', 'Password', 'required|trim|matches[password1]');

        if ($this->form_validation->run() == FALSE) {
            $data['title'] = 'Management User';
            $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
            $data['list_user'] = $this->db->get('mst_user')->result_array();

            $this->load->view('templates/topbar', $data);
            $this->load->view('templates/sidebar_admin', $data);
            $this->load->view('admin/man_user', $data);
            $this->load->view('templates/footer');
        } else {
            $data = array(
                'nama' => $this->input->post('nama', true),
                'username' => $this->input->post('username', true),
                'role_id' => $this->input->post('role_id', true),
                'password' => password_hash($this->input->post('password1'), PASSWORD_DEFAULT),
                'date_created' => date('Y/m/d'),
                'image' => 'default.jpg',
                'is_active' => 1
            );
            $this->db->insert('mst_user', $data);
            $this->session->set_flashdata('message', 'Simpan data');
            redirect('admin/man_user');
        }
    }

    public function get_edit()
    {
        echo json_encode($this->admin->getUserEdit($_POST['id']));
    }

    public function edit_user()
    {
        $id = $this->input->post('id');
        $is_active = $this->input->post('is_active');
        $role_id = $this->input->post('role_id');

        $this->db->set('is_active', $is_active);
        $this->db->set('role_id', $role_id);
        $this->db->where('id', $id);
        $this->db->update('mst_user');
        $this->session->set_flashdata('message', 'Update user');
        redirect('admin/man_user');
    }

    public function del_user($id)
    {
        $this->db->where('id', $id);
        $this->db->delete('mst_user');
        $this->session->set_flashdata('message', 'Hapus user');
        redirect('admin/man_user');
    }

    public function user_aktif()
    {
        $data['title'] = 'User Aktif';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['list_user_aktif'] = $this->admin->getUserAktif();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/user_aktif', $data);
        $this->load->view('templates/footer');
    }

    public function user_tidak_aktif()
    {
        $data['title'] = 'User Non Aktif';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['list_user_nonaktif'] = $this->admin->getUserNonAktif();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/user_tidak_aktif', $data);
        $this->load->view('templates/footer');
    }

    public function lap_harian()
    {
        $data['title'] = 'Laporan Harian';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['lap_harian_saya'] = $this->admin->getLapHarian();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/lap_harian', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_harian($id)
    {
        $data = $this->db->get_where('lap_harian', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/lap_harian/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/lap_harian/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function lap_bulanan()
    {
        $data['title'] = 'Laporan Bulanan';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['lap_bulanan_saya'] = $this->admin->getLapBulanan();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/lap_bulanan', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_bulanan($id)
    {
        $data = $this->db->get_where('lap_bulanan', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/lap_bulanan/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/lap_bulanan/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function lap_tahunan()
    {
        $data['title'] = 'Laporan Tahunan';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['lap_tahunan_saya'] = $this->admin->getLapTahunan();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/lap_tahunan', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_tahunan($id)
    {
        $data = $this->db->get_where('lap_tahunan', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/lap_tahunan/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/lap_tahunan/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function lap_lain()
    {
        $data['title'] = 'Laporan Lain-Lain';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['lap_lain_saya'] = $this->admin->getLapLain();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/lap_lain', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_lain($id)
    {
        $data = $this->db->get_where('lap_lain', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/lap_lain/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/lap_lain/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function dok_kerja()
    {
        $data['title'] = 'Dokumen Kerja';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['dok_kerja_saya'] = $this->admin->getDokKerja();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/dok_kerja', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_dok_kerja($id)
    {
        $data = $this->db->get_where('dok_kerja', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/dok_kerja/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/dok_kerja/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function dok_pribadi()
    {
        $data['title'] = 'Dokumen Pribadi';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['dok_pribadi_saya'] = $this->admin->getDokPribadi();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/dok_pribadi', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_dok_pribadi($id)
    {
        $data = $this->db->get_where('dok_pribadi', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/dok_pribadi/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/dok_pribadi/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function scan_berkas_utama()
    {
        $data['title'] = 'Scan Berkas Utama';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['scan_utama_saya'] = $this->admin->getScanUtama();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/scan_berkas_utama', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_scan_utama($id)
    {
        $data = $this->db->get_where('scan_utama', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/scan_utama/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/scan_utama/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }

    public function scan_berkas_pendukung()
    {
        $data['title'] = 'Scan Berkas Pendukung';
        $data['user'] = $this->db->get_where('mst_user', ['username' => $this->session->userdata('username')])->row_array();
        $data['scan_pendukung_saya'] = $this->admin->getScanPendukung();

        $this->load->view('templates/topbar', $data);
        $this->load->view('templates/sidebar_admin', $data);
        $this->load->view('admin/scan_berkas_pendukung', $data);
        $this->load->view('templates/footer');
    }

    public function file_download_scan_pendukung($id)
    {
        $data = $this->db->get_where('scan_pendukung', ['id' => $id])->row_array();
        header("Content-Disposition: attachment; filename=" . $data['file_upload']);
        $fp = fopen("assets/files/scan_pendukung/" . $data['file_upload'], 'r');
        $content = fread($fp, filesize('assets/files/scan_pendukung/' . $data['file_upload']));
        fclose($fp);
        echo $content;
        exit;
    }
}
