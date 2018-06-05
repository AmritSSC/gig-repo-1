<?php

/**
 * controllers/Admin.php
 *
 * Admin controller for Gig Central
 * 
 * @package ITC260
 * @subpackage Pages
 * @author Rattana Neak
 * @version 2.0 2016/06/14
 * @license http://www.apache.org/licenses/LICENSE-2.0
 * @see models/admin_model.php
 * @see view/admins/add.php
 * @see view/admins/admin.php
 * @see view/admins/index.php
 * @see view/admins/login.php
 * @see view/admins/reset.php
 * @see view/admins/success.php
 * @see view/admins/view.php
 * @todo
 */
 
class Admin extends CI_Controller {
         public function __construct()
        {//begin constructor
            parent::__construct();
            $this->load->model('admin_model');
            $this->load->library('form_validation');
            $this->load->database();
            $this->load->helper('form');
            $this->load->library('email');
		    //$this->load->helper('email');
            //$this->load->library('session'); this will regenerate a new session everytime the constructor is called
            // which will destroy the userData that is set when logging in!
            //$this->load->library('recaptcha');
            
            $this->config->set_item('nav-active', 'Login');//sets active class on current nav item
        }#end constructor
        
        public function index(){
            $data['title'] ="Admin dashboard";
            if ($this->session->logged_in == TRUE){
                $logged = 'Welcome to GigCentral website!';
            }else{
                $logged = 'You have Logout!';
            }
            if (isset($this->session->first_name)){
                $data['first_name'] = $this->session->first_name;   
            }else{
                $data['first_name'] = "";
            }
            
            $data['logged'] = $logged;
            $this->load->view('admins/index',$data);
        }
        public function login()
        {
            $data['title'] = "Login page";
            if(!isset($_POST['Submit'])){
                $data['error']='';
                $this->load->view('admins/login',$data);    
            }else{
                $this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');
                $this->form_validation->set_rules('pass', 'Pass', 'trim|required',array(
                    'required' => 'The password field is required'
                ));
                if ($this->form_validation->run() == FALSE) // validation hasn't been passed
                { 
                    $this->load->view('admins/login',$data);
                    
                }else{
                    $form_data = array(
                    'email' => set_value('email'),
                    'pass' => set_value('pass')
                    );
                 if ($this->admin_model->getInfor($form_data)){
                  $data['error'] = $this->admin_model->getInfor($form_data);   
                 }else{
                   $data['error']='';
                 }
                 $this->load->view('admins/login',$data);    
                }
                
            }
            
        }

        // todo: form validation, documentation
        public function requestReset()
        {
            $data['title'] = 'Forgot Password';

            if(isset($_POST['email'])) {
                // get form data
                $email = $_POST['email'];
                // verify user exists in database
               if($this->admin_model->verifyUserExists($email, 'email')){
                    //get unique data from matching user
                    $unique_data = $this->admin_model->getUniqueData($email);

                    //build the unique url to send via email
                    $unique_url = base_url('admin/resetpassword');
                    $unique_url .= '?key=';
                    $unique_url .= $unique_data['key'];
                    $unique_url .= '&reset=';
                    $unique_url .= $unique_data['reset'];

                    // build and send email
                    $this->email->from('reset@gigcentral.com');
                    $this->email->to($email);
                    $this->email->subject('Gig Central Password Reset');
                    $this->email->message('Click to reset password: ' . "\n" . $unique_url);
                    // if email successfully sends show success message
                    if($this->email->send()){
                        $data['title'] = 'Success!';
                        $data['success_message'] = 'Request Successful. An Email was sent to the provided address!';
                        $this->load->view('admins/success', $data);
                    } else{
                        $data['error'] = 'Something Went Wrong!';
                        $this->load->view('admins/requestReset', $data);
                    }
                
                // if user doesn't exist in DB return to form with error message
                } else {
                    $data['error'] = 'No account for that email';
                    $this->load->view('admins/requestReset', $data);

                }
            } else {
                $this->load->view('admins/requestReset', $data);
            }
        }

        // this method will get the data from the form that is sent to users via
        // the requestReset method
        // it will parse the query string, verify the hashed info in the query string against what
        // is stored in the database, and then change the password with the value in the new password field

        //todo: form validation, commenting and documentation
        public function resetPassword() 
        {
            $data['title'] = 'Reset Password';
            
            // if form submitted
            if(isset($_POST['new_password']) && isset($_POST['key'])){
                // get the data from the form
                $key = $_POST['key'];
                $reset = $_POST['reset'];
                // salt and hash new password
                $new_password = password_hash($_POST['new_password'], PASSWORD_BCRYPT);
                
                // if password was updated successfully, show success page
                if($this->admin_model->resetPassword($key, $reset, $new_password)){
                    $data['title'] = 'Success!';
                    $data['success_message'] = 'Password succesfully updated. You can now login with your new password';
                    $this->load->view('admins/success', $data);
                } else {
                    // else show error message
                    $data['error'] = 'Something went Wrong!';
                    $this->load->view('admins/resetPassword', $data);
                }
            } elseif(!isset($_POST['new_password']) && !empty($_GET)) {
                //if form hasn't been submitted, parse the query string provided in password reset email and pass to view
                // put the key and reset values in hidden form fields 
                $data['key'] = $_GET['key'];
                $data['reset'] = $_GET['reset'];
                $this->load->view('admins/resetPassword', $data);
            } else {
                redirect('admin/login');
            }
        }
        

        public function view($slug = NULL)
        {
            
            $this->load->view('admins/view', $data);
        }
        
        public function logout(){
          
           unset(
            $_SESSION['status'],
            $_SESSION['first_name'],
            $_SESSION['last_name'],
            $_SESSION['picture'],
            $_SESSION['email'],
            $_SESSION['logged_in'],
            $_SESSION['lang']
            );
          
            redirect('admin/');
        }
      
}