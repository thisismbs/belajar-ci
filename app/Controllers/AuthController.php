<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AuthController extends BaseController
{
    public function __construct()
        {
            $this->model = new \App\Models\User();
        }
        public function registrasi()
        {
            return view('registrasi');
        }
        public function login() {
            $data = [
                'validation' => \Config\Services::validation()
            ];
            return view('auth/login', $data);
        }

        public function simpanRegistrasi()
        {
         $data = [
            'nama'                         => $this->request->getPost('nama'), 
            'email'                        => $this->request->getPost('email'), 
            'password'                     => $this->request->getPost('password'), 
            'konfirmasi_password'          => $this->request->getPost('kofirm_pass')
         ];

         $validation = \Config\Services::validation();

         $validation->setRules([
            'nama'                         => 'required',
            'email'                        => 'required|valid_email|is_unique[users.email]',
            'password'                     => 'required|min_length[8]',
            'konfirmasi_password'          => 'required|matches[password]'
         ]);

         if  ($validation->run($data)) {

            $this->model->save([
                'name'             => $data['nama'],
                'email'            => $data['email'],
                'password'         => password_hash($data['password'], PASSWORD_BCRYPT),
                'role'             => 'siswa'
            ]);

            return redirect()->to(base_url('registrasi'))->with('sukses', 'Registrasi Berhasil !');
         } else {

            $errorMessages = $validation->getErrors();
            return redirect()->to(base_url('registrasi'))->with('gagal', $errorMessages);
         }
        }

        public function prosesLogin()
        {
            if ($this->validate($this->rulesLogin())) {

                $query = $this->model->where('email', $this->request->getPost('email'));
                $count = $query->countAllResults(false);
                $data = $query->get()->getRow();

                if ($count > 0) {
                    
                    $hashPassword = $data->password;

                    if (password_verify($this->request->getPost('password'), $hashPassword)) {

                        $session = [
                            'role' => $data->role,
                            'logged_in' => TRUE
                        ];
                        session()->set($session);

                        return redirect()->to(base_url('data-siswa'));
                    } else {
                        return redirect()->to(base_url('login'))->with('login_failed', 'Username / password anda salah');
                    }
                } else {
                    return redirect()->to(base_url('login'))->with('login_failed', 'Username tidak ditemukan');
                }
            } else {
                return redirect()->to(base_url('login'))->withInput();
            }
        }

        public function rulesLogin()
        {
            $setRules = [
                'email' => [
                    'rules' => 'required|valid_email',
                    'errors' => [
                        'required' => 'Email arus diisi',
                        'valid_email' => 'Email Anda Tidak Valid',
                    ]
                ],
                'password' => [
                    'rules' => 'required', 
                    'errors' => [
                        'required' => 'Password harus diisi'
                    ]
                ]
            ];
            return $setRules;
        }

        public function logout()
        {
            session()->destroy();
            return redirect()->to('/login');
        }
}
