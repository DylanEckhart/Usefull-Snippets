<?php
/* BELANGRIJK: reCAPTCHA integratie voor CMSTool. Om dit toe te kunnen voegen heb je toegang nodig tot FTP. */

// Bestand 1 - mail.php
if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class Mail extends CI_Controller
{
    private $recaptcha_secret_v3 = '6Ldk7x4sAAAAAGuKzMNPJ-rXWcsHARAnAOjnIYI8';

    public function __construct() {
        parent::__construct();
        $this->load->model('M_LeadAdmin');
        $this->load->helper('leadconnect');
    }

    public function index($url = false)
    {
        // Honeypot check!
        $this->_validate_honeypot();

        if ($this->input->post('form_id')) {

            // reCAPTCHA v3 check
            if ( ! $this->_validate_recaptcha_v3() ) {
                $this->nativesession->set_flashdata('form_error', true);
                redirect($_SERVER['HTTP_REFERER']);
            }

            $form = $this->M_Form->get_form($this->input->post('form_id'));

            $this->_load_language($form);

            $user_info = array();

            $html = $html_nice = '
            <html>
                <body>
                    <p>' . $this->lang->line('form_mail_preamble') . ',</p>
                    <p>' . sprintf($this->lang->line('form_mail_text_1'), date('d-m-Y'), $form[0]->form_name) . '</p>
                    <p>' . $this->lang->line('form_mail_text_2') . '</p>
                    <table border="0">';

            $attachments = array();
            if (!empty($_FILES)) {
                $this->load->library('upload');
                foreach ($_FILES as $ind => $val) {
                    if ($val['error'] == 4)
                        break;
                    $field = $ind;
                    $name = $val['name'];
                    $config['upload_path'] = './tmp/';
                    $config['allowed_types'] = 'jpg|png|pdf|doc|docx';
                    $config['max_size'] = '6020';
                    $config['file_name'] = $name;
                    $this->upload->initialize($config);
                    @$this->upload->do_upload($field);
                    $data = $this->upload->data();
                    $attachments[] = $data['full_path'];
                }
            }

            foreach ($this->input->post() as $key => $val) {
                if (!is_array($val)) {
                    if ($val != strip_tags($val)) {
                        redirect($_SERVER['HTTP_REFERER']);
                    }
                    $val = trim($val);
                }

                if (
                    $key != 'form_id'
                    && $key != 'captcha'
                    && $key != 'captchaHash'
                    && $key != 'refer_to_picked'
                    && $key != 'important-email'
                    && $key != 'g-recaptcha-response' // recaptcha veld niet in mail/lead opnemen
                    && strpos($key, '_rules') == false
                ) {
                    $field = $this->M_Form->get_field_by_name($key, $form[0]->id);
                    $fName = $key;
                    if (!empty($field)) {
                        $user_info[$field[0]->field_tag] = $val;

                        // Check if field is required, but is empty
                        if($field[0]->field_required == 1 && $val == '') {
                            $this->nativesession->set_flashdata('form_error', true);
                            redirect($_SERVER['HTTP_REFERER']);
                        }

                        if($field[0]->field_type == 'select' || $field[0]->field_type == 'radio') {
                            if($field[0]->field_options != '') {
                                $options = json_decode(str_replace('\'', '"', $field[0]->field_options));
                                foreach($options->value as $ind => $v) {
                                    if($v === $val) {
                                        $val = $options->text[$ind];
                                        break;
                                    }
                                }
                            }
                        } else if ($field[0]->field_type == 'checkbox') {
                            if(is_array($val)){
                                $options = json_decode(str_replace('\'', '"', $field[0]->field_options));
                                $val_str = array();
                                foreach($val as $ugly_v) {
                                    foreach($options->value as $ind => $v) {
                                        if($ugly_v === $v) {
                                            $val_str[] = $options->text[$ind];
                                            break;
                                        }
                                    }
                                }
                                $val = implode(" | ", $val_str);
                            }
                        }
                        if ($field[0]->field_type == 'product') {
                            $value = '';
                            foreach ($val as $val_key => $val_item) {
                                if ($val_key > 0) {
                                    $value .= ', ';
                                }
                                if (strpos($fName, 'mollie_') !== false) {
                                    $val_item = $this->M_Producten->get_product_name($val_item);
                                }
                                $value .= $val_item;
                            }
                            $html .= '<tr><td><b>'.$fName.'</b></td><td>'.$value.'</td></tr>';
                            if (!empty($field)) {
                                $html_nice .= '<tr><td><b>'.$field[0]->field_label.'</b></td><td>'.$value.'</td></tr>';
                            }
                        } else {
                            $html .= '<tr><td><b>'.$fName.'</b></td><td>'.$val.'</td></tr>';
                            if (!empty($field)) {
                                $html_nice .= '<tr><td><b>'.$field[0]->field_label.'</b></td><td>'.$val.'</td></tr>';
                            }
                        }
                    }
                }
            }

            $html .= '</table></body></html>';
            $html_nice .= '</table></body></html>';

            $master_email = $this->M_Settings->get_master_email();
            $mailto = ($master_email != '' ? $master_email.', ' : '').$form[0]->form_email;
            $club = NULL;
            if ($this->input->post('vestigingen')) {
                $club = $this->M_Page->get_by_name($this->input->post('vestigingen', TRUE));
                if (!empty($club) && trim($club[0]->vestiging_email) != '') {
                    $mailto .= ', '.trim($club[0]->vestiging_email);
                }
            }

            $config['mailtype'] = 'html';
            $config['charset'] = 'utf-8';
            $config['wordwrap'] = TRUE;

            $this->email->initialize($config);

            $host = get_host();

            $this->email->to($mailto);
            $this->email->from('info@'.$host);
            $this->email->subject($form[0]->form_subject.(!empty($attachments) ? ' (met bijlage)' : ''));
            $this->email->message($html_nice);
            if (!empty($attachments)) {
                foreach ($attachments as $a) {
                    $this->email->attach($a);
                }
            }
            $this->email->send();

            if (!empty($attachments)) {
                foreach ($attachments as $a) {
                    unlink($a);
                }
            }

            $mail = array(
                'mail_to' => $mailto,
                'form_id' => $form[0]->id,
                'club_id' => (!is_null($club) && !empty($club) ? $club[0]->id : 0),
                'mail_subject' => $form[0]->form_subject,
                'mail_date' => date("Y-m-d H:i"),
                'mail_body' => $html,
                'user_info' => json_encode($user_info),
                'autoresponders' => $form[0]->autoresponders,
                'extern' => ($this->input->post('cmstool_mail_extern') ? 1 : 0),
                'send_from' => (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '-'),
                'ip' => $_SERVER['REMOTE_ADDR'],
                'delete_at' => date('Y-m-d H:i:s',strtotime(date("Y-m-d H:i:s", time()) . " + " . $form[0]->save_period . " month"))
            );

            $mail_id = $this->M_Mail->insert_mail($mail);

            // Get redirect page and url
            $redirect_data = $this->_get_redirect($this->input->post('refer_to_picked'), $form);
            $refer_page = $redirect_data['page'];
            $refer = $redirect_data['link'];

            // Set users name, email phonenumber, country club and mail id
            $lead_array = $user_array = array(
                'name' => $this->_retrieve_user_name(),
                'email' => $this->_retrieve_user_email(),
                'phone' => $this->_retrieve_user_phone(),
                'country' => $this->input->post($this->lang->line('fieldname_country'), TRUE)
            );
            $lead_array['cmstoolMailId'] = $user_array['resource'] = $mail_id;
            $user_array['club'] = (isset($club[0]->id) ? $club[0]->id : NULL);

            // Add lead to administration
            $this->M_LeadAdmin->add($lead_array, $form[0]->save_period);

            if (!empty($refer_page) && $refer_page[0]->page_type == M_Page::$PAGETYPE_APPOINTMENT) {
                // Set session for appointment making
                $this->_set_appointment_session($user_array);
                // Tell database that user is in appointment making process
                $this->db->update('mail', array('making_appointment' => 1), array('id' => $mail_id));
            } elseif($form[0]->use_leadconnect == 1) {
                // If thank you page isnt an appointment making page insert user to leadconnect if needed
                $user_array['subject'] = $form[0]->form_subject;
                $user_array['appointment'] = FALSE;
                $lc_call_status = insert_to_leadconnect($user_array);
                $this->db->update('mail', array('lc_call_status' => $lc_call_status), array('id' => $mail_id));
            }

            //Mollie
            if ($this->M_Modules->check_module_status('mollie') == '1') {
                $this->_process_mollie($form, $refer);
            } else {
                redirect($refer);
            }
        }
    }

    private function _validate_honeypot()
    {
        if($this->M_Modules->check_module_status('formulier_honeypot'))
        {
            $honeypot_value = $this->input->post('important-email');
            if($honeypot_value && $honeypot_value != '')
            {
                log_message('error', 'MAIL: ' . $_SERVER['REMOTE_ADDR'] . ' failed honeypot check!');
                redirect($_SERVER['HTTP_REFERER']);
            }
        }
    }

    /**
     * reCAPTCHA v3 server side validatie
     */
    private function _validate_recaptcha_v3()
    {
        $response = $this->input->post('g-recaptcha-response');

        if (empty($response)) {
            log_message('error', 'MAIL: ' . $_SERVER['REMOTE_ADDR'] . ' heeft geen reCAPTCHA v3 token meegestuurd.');
            return false;
        }

        $remote_ip  = $this->input->ip_address();
        $verify_url = 'https://www.google.com/recaptcha/api/siteverify';

        $data = http_build_query([
            'secret'   => $this->recaptcha_secret_v3,
            'response' => $response,
            'remoteip' => $remote_ip,
        ]);

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => $data,
                'timeout' => 5,
            ],
        ];

        $context = stream_context_create($options);
        $result  = @file_get_contents($verify_url, false, $context);

        if ($result === false) {
            log_message('error', 'MAIL: kan geen verbinding maken met reCAPTCHA v3 API.');
            return false;
        }

        $captcha = json_decode($result, true);

        if (empty($captcha['success']) || $captcha['success'] !== true) {
            log_message('error', 'MAIL: reCAPTCHA v3 validatie mislukt voor ' . $_SERVER['REMOTE_ADDR']);
            return false;
        }

        $score  = isset($captcha['score']) ? (float) $captcha['score'] : 0.0;
        $action = isset($captcha['action']) ? $captcha['action'] : '';

        if ($score < 0.5) {
            return false;
        }

        // Moet overeenkomen met de action in de JS (M_Form)
        if ($action !== '' && $action !== 'small_contact_form') {
            log_message('error', 'MAIL: reCAPTCHA v3 action mismatch: ' . $action);
            return false;
        }

        return true;
    }

    private function _get_redirect($refer_to_picked, $form)
    {
        $redirect_data = array(
            'link' => '',
            'page' => '',
        );
        if ($refer_to_picked) {
            foreach ($refer_to_picked as $val) {
                $info = explode('|', $val);
                if (is_array($this->input->post(str_replace(' ', '_', $info[0])))) {
                    $selectedValue = $this->input->post(str_replace(' ', '_', $info[0]));
                    $selectedValue = $selectedValue[0];
                } else {
                    $selectedValue = $this->input->post(str_replace(' ', '_', $info[0]));
                }
                if ($selectedValue == $info[1]) {
                    $redirect_data['page'] = $this->M_Page->get_by_id(end($info), false);
                    if (!empty($redirect_data['page'])) {
                        $link = GenerateUrl(1, end($info));
                        $redirect_data['link'] = $link['link'];
                        return $redirect_data;
                    }
                }
            }
            $redirect_data['page'] = $this->M_Page->get_by_id($form[0]->form_page, false);
            if (!empty($redirect_data['page'])) {
                $link = GenerateUrl(1, $form[0]->form_page);
                $redirect_data['link'] = $link['link'];
                return $redirect_data;
            }
        } else {
            $redirect_data['page'] = $this->M_Page->get_by_id($form[0]->form_page, false);
            if (!empty($redirect_data['page'])) {
                $link = GenerateUrl(1, $form[0]->form_page);
                $redirect_data['link'] = $link['link'];
                return $redirect_data;
            }
        }
        return $redirect_data;
    }

    private function _load_language($form)
    {
        if(!empty($form))
        {
            switch ($form[0]->language) {
                case 'nl':
                    $this->lang->load('general', 'dutch');
                    break;
                case 'en':
                    $this->lang->load('general', 'english');
                    break;
                case 'de':
                    $this->lang->load('general', 'german');
                    break;
                default:
                    $this->lang->load('general', 'dutch');
                    break;
            }
        }
    }

    private function _set_appointment_session($user)
    {
        unset_appointment_session();
        $this->nativesession->set('name',  $user['name']);
        $this->nativesession->set('email', $user['email']);
        $this->nativesession->set('phone',  $user['phone']);
        $this->nativesession->set('country', $user['country']);
        $this->nativesession->set('resource', $user['resource']);
        $this->nativesession->set('club', $user['club']);
    }

    private function _retrieve_user_name()
    {
        $user_name = $this->input->post('lead-name', TRUE);
        // backward compatible checks
        if(!$user_name) {
            $user_name = $this->input->post($this->lang->line('fieldname_name'), TRUE);
        }
        return $user_name;
    }

    private function _retrieve_user_email()
    {
        $user_email = $this->input->post('lead-email', TRUE);
        // backward compatible email check
        if(!$user_email) {
            $user_email = $this->input->post($this->lang->line('fieldname_email'), TRUE);
        }
        return $user_email;
    }

    private function _retrieve_user_phone()
    {
        $user_phone = $this->input->post('lead-phone', TRUE);
        // backward compatible phone check
        if(!$user_phone) {
            $user_phone = $this->input->post($this->lang->line('fieldname_phone'), TRUE);
        }
        return $user_phone;
    }

    private function _process_mollie($form, $redirectUrl)
    {
        $amount = 0;
        $customer_mail = '';
        $posible_email_format = array('e-mail', 'email', 'e-mailadres', 'e-mail-adres', 'emailadres', 'email-adres');
        foreach ($posible_email_format as $possible_email) {
            if ($this->input->post($possible_email)) {
                $customer_mail = $this->input->post($possible_email);
                break;
            }
        }
        if ($this->input->post('mollie_checkbox')) {
            foreach ($this->input->post('mollie_checkbox') as $key) {
                $price = $this->M_Producten->get_product_price($key);
                $amount += $price[0]->price;
            }
        }
        if ($this->input->post('mollie_radio')) {
            foreach ($this->input->post('mollie_radio') as $key) {
                $price = $this->M_Producten->get_product_price($key);
                $amount += $price[0]->price;
            }
        }
        if ($this->input->post('mollie_dropdown')) {
            foreach ($this->input->post('mollie_dropdown') as $key) {
                $price = $this->M_Producten->get_product_price($key);
                $amount += $price[0]->price;
            }
        }
        if ($this->input->post('mollie_vast')) {
            foreach ($this->input->post('mollie_vast') as $key) {
                $price = $this->M_Producten->get_product_price($key);
                $amount += $price[0]->price;
            }
        }

        $incasso = $this->input->post('mollie_incasso');
        if ($incasso == "") {
            $incasso = 'iDEAL';
        }
        if ($amount != 0 && $incasso == 'iDEAL') {
            require_once 'application/third_party/Mollie/API/Autoloader.php';
            $mollieSettings = $this->M_Settings->get_mollie_api();
            if (!$mollieSettings) {
                Header("Location: ".base_url());
            }
            $mollie = new Mollie_API_Client;
            $mollie->setApiKey($mollieSettings[0]->api_key);
            $orderid = mt_rand(100000, 999999);
            try {
                $payment = $mollie->payments->create(
                    array(
                        'amount' => $amount,
                        'description' => 'Producten - '.$form[0]->form_name.' - Order nr: '.$orderid,
                        'redirectUrl' => $redirectUrl,
                        'webhookUrl' => site_url().'checkpayment/?identifier='.$mollieSettings[0]->identifier.'&customer='.$customer_mail,
                        'metadata' => array(
                            'order_id' => mt_rand(100000, 999999)
                        )
                    )
                );

                /*
                 * Send the customer off to complete the payment.
                 */
                header("Location: ".$payment->getPaymentUrl());
                exit;
            } catch (Mollie_API_Exception $e) {
                echo "API call failed: ".htmlspecialchars($e->getMessage())." on field " + htmlspecialchars($e->getField());
            }
        } else {
            redirect($redirectUrl);
        }
    }
}

// Bestand 2 - M_form.php
<?php

if (!defined('BASEPATH'))
    exit('No direct script access allowed');

class M_Form extends CI_Model
{
    public function __construct()
    {
        parent::__construct();
        $CI = & get_instance();
        $defaultLanguage = $CI->M_Talen->getDefault();
        $this->defaultLanguage = $defaultLanguage[0]->taalKort;
    }

    public function get_form($id = FALSE, $language = FALSE, $ignore_language = FALSE)
    {
        if ($language) {
            $this->db->where('language', $language);
        }
        if (is_numeric($id)) {
            $query = $this->db->get_where('form', array('id' => $id));
        } else {
            $query = $this->db->get('form');
        }
        return $query->result();
    }

    public function lookFor($like, $lang)
    {
        $this->db->select('form_name, id');
        $this->db->where('language', $lang);
        $this->db->like('form_name', $like);
        $query = $this->db->get('form');
        return $query->result_array();
    }

    public function get_last_added_form()
    {
        $this->db->limit('1');
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('form');

        return $query->result();
    }

    public function get_form_fields($id, $from_archive = FALSE)
    {
        if ($from_archive) {
            $this->load->model('M_Archive');
            $fields = $this->M_Archive->get_components('form '.$id);
            $fields_array = array();
            foreach ($fields as $f) {
                if ($f->table === 'form_fields') {
                    $fields_array[] = json_decode($f->data);
                }
            }
            return $fields_array;
        } else {
            $this->db->order_by('field_position', 'ASC');
            $query = $this->db->get_where('form_fields', array('form' => $id));
            return $query->result();
        }
    }

    public function get_form_by_title($form_subject)
    {
        $query = $this->db->get_where('form', array('form_subject' => $form_subject));
        return $query->result();
    }

    public function get_field($id)
    {
        $query = $this->db->get_where('form_fields', array('id' => $id));
        return $query->result();
    }

    public function get_field_by_name($name, $form)
    {
        $this->db->where('form', $form);
        $this->db->where('field_name', $name);
        $q = $this->db->get('form_fields');
        return $q->result();
    }

    public function get_last_field_id()
    {
        $this->db->limit(1);
        $this->db->order_by('id', 'desc');
        $query = $this->db->get('form_fields');
        return $query->result();
    }

    public function hasAttachmentField($form)
    {
        $this->db->limit(1);
        $this->db->where('form', $form);
        $this->db->where('field_type', 'attachment');
        $q = $this->db->get('form_fields');
        $r = $q->result();
        if (empty($r))
            return false;
        else
            return true;
    }

    public function handel_form($content, $html_export = FALSE)
    {
        if ($this->M_Talen->isLang($this->uri->segment(1))) {
            $lang = $this->uri->segment(1);
        } else {
            $lang = $this->M_Talen->getDefault();
        }
        switch ($lang) {
            case 'nl':
                $this->lang->load('general', 'dutch');
                break;
            case 'en':
                $this->lang->load('general', 'english');
                break;
            case 'fr':
                $this->lang->load('general', 'france');
                break;
            case 'de':
                $this->lang->load('general', 'german');
                break;
            case 'frl':
                $this->lang->load('general', 'fries');
                break;
        }

        preg_match('/{formulier-[0-9]+}/ixsm', $content, $matches);

        if (count($matches) > 0) {

            $fields = '';

            $match = str_replace('{formulier-', '', $matches[0]);
            $match = str_replace('}', '', $match);

            $form = $this->get_form($match);
            if (!empty($form)) {
                if ($form[0]->status == '1' || $this->nativesession->get('username') != '') {

                    $honeypot = $this->M_Modules->check_module_status('formulier_honeypot');

                    if($this->nativesession->flashdata('form_error') === true) {
                        $fields .= '<p style="color: red;">' . $this->lang->line('form_could_not_be_sent') . '</p>';
                    }

                    // Form start
                    $fields .= '
                    <div class="clear"></div>
                    <form action="'.base_url().'mail.html" id="form-'.$match.'" method="post" '.($this->hasAttachmentField($match) ? 'enctype="multipart/form-data"' : '').'>
                        <div class="formError"></div>
                        <input type="hidden" value="'.$match.'" name="form_id" />';

                    if ($html_export) {
                        $fields .= '<input value="1" type="hidden" name="cmstool_mail_extern">';
                    }

                    // Hidden reCAPTCHA veld (alleen op frontend)
                    if (!$html_export) {
                        $fields .= '<input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response-'.$match.'" />';
                    }

                    $fields .= '
                        <ul class="generatedForm cmstool">';

                    if($honeypot && !$html_export)
                    {
                        $fields .= '<li id="do-not-forget"><input type="text" name="important-email"></li>';
                    }

                    $fields .= ($this->nativesession->get('username') != '' && $form[0]->status == '0'
                        ? '<li><div class="caution"><strong>Let op!</strong> Alleen u kunt dit formulier bekijken en/of testen, omdat dit formulier opgeslagen is als "concept"</div></li>'
                        : ''
                    );

                    $show_mollie_stuff = ($this->has_product_field($match) && $this->M_Modules->check_module_status('mollie') == 1 ? TRUE : FALSE);
                    $submit_button = null;
                    $privacy_policy = null;
                    foreach ($this->M_Form->get_form_fields($match) as $field) {
                        if ($field->field_type == 'submit') {
                            $submit_button = $field;

                            if ($show_mollie_stuff) {
                                $mollieSettings = $this->M_Settings->get_mollie_api();
                                if ($mollieSettings[0]->incasso == 1) {
                                    $incassofield = new StdClass();
                                    $incassofield->field_type = 'incasso';
                                    $fields .= print_field($incassofield, $form, 'on_page');
                                }
                            }
                        } else if($field->field_name == 'privacy-policy') {
                            $privacy_policy = $field;
                        } else {
                            $fields .= print_field($field, $form, 'on_page');
                        }
                    }

                    // Render privacy policy field
                    if($privacy_policy == null) {
                        $fields = '';
                    } else {
                        $fields .= print_field($privacy_policy, $form, 'on_page', false, $html_export);
                    }
                    // Render submit button
                    if($submit_button == null) {
                        $fields = '';
                    } else {
                        $fields .= print_field($submit_button, $form, 'on_page');
                    }

                    if(!$html_export)
                    {
                        $fields .= '
                                <li>
                                    <span class="formInfo">
                                        <span style="color: #D61717;">*</span>
                                        '.$this->lang->line('general_fields_are_required').'.
                                    </span>
                                </li>';
                    }

                    $fields .= '
                        </ul>
                    </form>
                    <div style="clear:both;"></div>';

                    if ($show_mollie_stuff) {
                        $fields .= '
                        <div class="molliebanks">
                            <span class="ideal">
                                <img src="'.base_url().'images/iDeal2.png" alt="">
                                <span class="purple">Online betalen via uw eigen bank</span>
                            </span>
                            <span class="border">&nbsp;</span>
                            <img src="'.base_url().'images/ideal-banks.png" alt="" class="banks">
                        </div>';
                    }

                    if($html_export)
                    {
                        $form_css = file_get_contents('./stylesheets/add-on/formulieren.css');
                        $fields .= "<style>$form_css</style>";
                    }

                    if($honeypot && !$html_export)
                    {
                        $fields .= '<style>#do-not-forget{display:none;}</style>';
                    }

                    // reCAPTCHA v3 script alleen op frontend
                    if (!$html_export) {
                        $fields .= '
                        <script src="https://www.google.com/recaptcha/api.js?render=6Ldk7x4sAAAAAFUOGF6Gn0BFNHd-qJLwDVBen6Jj"></script>
                        <script>
                        document.addEventListener("DOMContentLoaded", function() {
                            if (typeof grecaptcha === "undefined") {
                                return;
                            }
                            grecaptcha.ready(function() {
                                grecaptcha.execute("6Ldk7x4sAAAAAFUOGF6Gn0BFNHd-qJLwDVBen6Jj", {action: "small_contact_form"})
                                    .then(function(token) {
                                        var el = document.getElementById("g-recaptcha-response-' . $match . '");
                                        if (el) {
                                            el.value = token;
                                        }
                                    });
                            });
                        });
                        </script>';
                    }

                    $content = str_replace($matches[0], $fields, $content);

                    return $this->handel_form($content);
                }
            }
        }
        return $content;
    }

    public function has_product_field($id)
    {
        $field = $this->db->where('form', $id)
            ->where('field_type', 'product')
            ->limit(1)
            ->get('form_fields')
            ->result();
        if (empty($field)) {
            return FALSE;
        }
        return TRUE;
    }

    public function has_email_field($id)
    {
        $field = $this->db->where('form', $id)
            // Will be deprecated soon
            ->where('field_type', 'text')
            ->where('field_name', 'e-mail')
            ->or_where('field_name', 'email')
            ->or_where('field_name', 'e-mailadres')
            ->or_where('field_name', 'emailadres')
            ->or_where('field_name', 'e-mail-adres')
            ->or_where('field_name', 'email-adres')
            ->or_where('field_name', 'lead-email')
            ->limit(1)
            ->get('form_fields')
            ->result();
        if (empty($field)) {
            return FALSE;
        }
        return TRUE;
    }

    public function delete($id, $archive = FALSE)
    {
        $form = $this->M_Form->get_form($id);

        if ($archive) {
            $this->load->model('M_Archive');
            $fields = $this->get_form_fields($id);
            foreach ($fields as $f) {
                $this->M_Archive->add($f->id, 'form_fields', ($f->field_label != '' ? $f->field_label : $f->field_value), FALSE, 'form '.$id);
            }
            $mails = $this->M_Mail->get_mail_by_subject($form[0]->form_subject);
            foreach ($mails as $m) {
                $this->M_Archive->add($m->id, 'mail', $form[0]->form_subject, FALSE, 'form '.$id);
            }
        } else {
            $this->db->delete('form_fields', array('form' => $id));
            $this->db->delete('mail', array('mail_subject' => $form[0]->form_subject));
            $this->db->delete('form', array('id' => $id));
        }
    }

    public function form_has_privacy_policy_field($id) {
        $field = $this->get_field_by_name('privacy-policy', $id);
        return !empty($field);
    }

    public function fix_missing_privacy_policy_field() {
        $translations = array(
            'nl' => $this->lang->load('general', 'dutch', true),
            'en' => $this->lang->load('general', 'english', true),
            'de' => $this->lang->load('general', 'german', true),
        );

        $forms = $this->get_form();
        $privacy_policy_field = new StdClass();
        $privacy_policy_field->field_name = 'privacy-policy';
        $privacy_policy_field->field_tag = 'privacy-policy';
        $privacy_policy_field->field_label_position = '1';
        $privacy_policy_field->field_type = 'checkbox';
        $privacy_policy_field->field_value = '';
        $privacy_policy_field->field_position = 99;
        $privacy_policy_field->field_required = 1;

        foreach($forms as $form) {
            if(!$this->form_has_privacy_policy_field($form->id)) {
                // Set form specific values
                $privacy_policy_field->form = $form->id;
                $privacy_policy_field->field_label = $translations[$form->language]['privacy_policy_label'];
                $privacy_policy_field->field_options = '{"value":["ja_ik_ga_akkoord_met_de_algemene_voorwaarden"],"text":["' . sprintf($translations[$form->language]['privacy_policy_text'], base_url() . $form->language . '/privacy-policy/' ) . '"],"refer_to":["Niet van toepassing"],"selected":["0"]}';
                $this->db->insert('form_fields', $privacy_policy_field);
            }
        }
    }

    public function getOrderList()
    {
        $sql = "SELECT id, field_position FROM form_fields";
        $query = $this->db->query($sql);
        if (!empty($query) && $query->num_rows() > 0) {
            $result = $query->result_array();
            $list = array();
            foreach ($query->result() as $row) {
                $list[$row->id] = $row->field_position;
            }
            return $list;
        } else {
            return FALSE;
        }
    }

    public function switchOrder($rank, $newrank)
    {
        $this->db->query("UPDATE form_fields SET field_position='".$newrank."' WHERE id='".$rank."' ");
    }

    // Autoresponders
    public function get_autoresponder($id = false)
    {
        if ($id) {
            $this->db->where('id', $id);
        }
        $this->db->order_by('name');
        $q = $this->db->get('autoresponders');
        return $q->result();
    }

    public function get_something_by_autoresponder($id, $where)
    {
        $this->db->like('autoresponders', $id, 'none');
        $q = $this->db->get($where);
        return $q->result();
    }

    public function get_predefined_field_names() {
        return $predefined_field_names = array(
            'vestigingen',
            'privacy-policy',
            'lead-name',
            'lead-email',
            'lead-phone'
        );
    }

    public function is_predefined_field_name($name) {
        $predefined_field_names = $this->get_predefined_field_names();
        return in_array($name, $predefined_field_names);
    }
}


