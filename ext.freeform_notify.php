<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Freeform Notify Extension Class for ExpressionEngine 6
 *
 * @package     ExpressionEngine
 * @subpackage  Freeform Notify
 * @category    Extensions
 * @author      Ron Hickson
 * @link        http://ee-forge.com/
 */

class Freeform_notify_ext {

    var $name             = 'Freeform Notify';
    var $version          = '1.5.0';
    var $description      = 'Adds an alternate email message to selected Freeform forms.';
    var $settings_exist   = 'y';
    var $docs_url         = '';
    var $settings         = array();

    function __construct($settings = '')
    {
        $this->settings = $settings;
    }

    /**
     * Settings
     *
     * This function returns the settings for the extensions
     *
     * @param  $current
     * @return  void
     */
    function settings_form($current)
    {
        ee()->load->helper('form');
        ee()->load->library('table');

        $vars = array(
            'form_url' => ee('CP/URL')->make('addons/settings/freeform_notify/save'),
            'cp_heading' => 'Freeform Notify',
            'rows' => array()
        );

        // Get all existing forms
        $forms = ee()->db->select('name, handle, id')->get('freeform_next_forms');
        if ($forms->num_rows() > 0) {
            // We have an array of settings to work with
            foreach ($forms->result() as $form) {
                if (is_array($current)) {
                    foreach ($current as $k => $v) {
                        if ($form->id == $k) {
                            $vars['rows'][$form->id]['handle'] = $form->handle;
                            $vars['rows'][$form->id]['name'] = $form->name;
                            $vars['rows'][$form->id]['enabled'] = isset($v['enabled']) ? 'y' : 'n';
                            $vars['rows'][$form->id]['notify_email'] = str_replace('|', "\n", $v['notify_email']);
                            $vars['rows'][$form->id]['notify_message'] = $v['notify_message'];
                            // Found settings so unset and break out of the current foreach loop
                            unset($current[$k]);
                            break;
                        } else {
                            $vars['rows'][$form->id]['handle'] = $form->handle;
                            $vars['rows'][$form->id]['name'] = $form->name;
                            $vars['rows'][$form->id]['enabled'] = 'n';
                            $vars['rows'][$form->id]['notify_email'] = '';
                            $vars['rows'][$form->id]['notify_message'] = '';
                        }
                    }
                } else {
                    $vars['rows'][$form->id]['handle'] = $form->handle;
                    $vars['rows'][$form->id]['name'] = $form->name;
                    $vars['rows'][$form->id]['enabled'] = 'n';
                    $vars['rows'][$form->id]['notify_email'] = '';
                    $vars['rows'][$form->id]['notify_message'] = '';
                }
            }
        }

        return ee()->load->view('index', $vars, true);
    }

    /**
     * Save Settings
     *
     * This function saves the settings for the extension
     *
     * @return  void
     */
    function save_settings() {

        $rows = $_POST['rows'];

        foreach ($rows as &$row) {
            $row['notify_email'] = str_replace("\n", "|", $row['notify_email']);
        }

        ee()->db->where('class', __CLASS__);
        ee()->db->update('extensions', array('settings' => serialize($rows)));

        ee('CP/Alert')->makeInline('settings_saved')->asSuccess()->withTitle(lang('settings_saved'))->defer();

    }

    /**
     * Activate Extension
     *
     * This function enters the extension into the exp_extensions table
     *
     * @see http://codeigniter.com/user_guide/database/index.html for
     * more information on the db class.
     *
     * @return void
     */
    function activate_extension()
    {
        $data = array(
            'class'       => __CLASS__,
            'hook'        => 'freeform_next_submission_after_save',
            'method'      => 'send_message',
            'settings'    => serialize($this->settings),
            'priority'    => 10,
            'version'     => $this->version,
            'enabled'     => 'y'
        );

        // insert in database
        ee()->db->insert('extensions', $data);
    }


    /**
     * Update Extension
     *
     * This function performs any necessary db updates when the extension
     * page is visited
     *
     * @return 	mixed	void on update / false if none
     */
    function update_extension($current = '')
    {
        if ($current == '' || $current == $this->version)
        {
            return FALSE;
        }

        if ($current < '1.5.0')
        {
            // Update to version 1.5.0
            ee()->db->where('class', __CLASS__);
            ee()->db->update('extensions', array('hook' => 'freeform_next_submission_after_save', 'version' => $this->version));
        }

    }


    /**
     * Disable Extension
     *
     * This method removes information from the exp_extensions table
     *
     * @return void
     */
    function disable_extension()
    {
        ee()->db->where('class', __CLASS__);
        ee()->db->delete('extensions');
    }

    /**
     * Send SMS message
     * @param Solspace\Addons\FreeformNext\Model\SubmissionModel $submission
     * @param boolean $isNew
     */
    function send_message($submission, $isNew) {

        // Load the helpers we need
        ee()->load->library('email');
        ee()->load->helper('text');
        ee()->load->library('template');

        $form = $this->settings[$submission->formId];

        // Form SMS is enabled so send SMS
        if (isset($form['enabled'])) {

            /* Create the data array */
            $data = array();
            $fields = $submission->getForm()->getForm()->getLayout()->getFields();
            foreach ($fields as $field) {
                if ($field->getID()) {
                    $data[$field->getHandle()] = $submission->getFieldValue($field->getHandle());
                }
            }

            $sms = ee()->TMPL->parse_variables($form['notify_message'], array($data));

            ee()->email->mailtype = "text";
            ee()->email->from(ee()->config->item('webmaster_email'));
            ee()->email->to(str_replace("|", ",", $form['notify_email']));
            ee()->email->subject($submission->getForm()->name);
            ee()->email->message(entities_to_ascii($sms));
            ee()->email->Send();
        }

    }

}