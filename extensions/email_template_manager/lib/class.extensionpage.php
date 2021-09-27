<?php

require_once(TOOLKIT . '/class.administrationpage.php');

class ExtensionPage extends AdministrationPage
{
    protected $_useTemplate = null;
    public $viewDir = '';
    protected $_XSLTProc;

    public function __construct($params)
    {
        $this->_XSLTProc = new XsltProcess();
        parent::__construct($params);
    }

    public function __switchboard($type = 'view')
    {
        $this->_type = $type;
        if (!isset($this->_context[0]) || trim($this->_context[0]) === '') $this->_function = 'index';
        else $this->_function = $this->_context[0];
        parent::__switchboard($type);
    }

    public function view()
    {
        $this->Contents = new XMLElement('div', null, array('id' => 'contents'));
        $this->Form->setAttribute('style','display:none;');

        return parent::view();
    }

    public function generate($page = null)
    {
        if ($this->_useTemplate !== false) {
            $template = $this->viewDir . '/' . (empty($this->_useTemplate)?$this->_getTemplate($this->_type, $this->_function):$this->_useTemplate . '.xsl');

            if (file_exists($template)) {
                $current_path = explode(dirname($_SERVER['SCRIPT_NAME']), $_SERVER['REQUEST_URI'], 2);
                $current_path = '/' . ltrim(end($current_path), '/');
                $upload_size_php = ini_size_to_bytes(ini_get('upload_max_filesize'));
                $upload_size_sym = Symphony::Configuration()->get('max_upload_size', 'admin');
                $params = array(
                    'today' => DateTimeObj::get('Y-m-d'),
                    'current-time' => DateTimeObj::get('H:i'),
                    'this-year' => DateTimeObj::get('Y'),
                    'this-month' => DateTimeObj::get('m'),
                    'this-day' => DateTimeObj::get('d'),
                    'timezone' => DateTimeObj::get('P'),
                    'website-name' => Symphony::Configuration()->get('sitename', 'general'),
                    'root' => URL,
                    'symphony-url' => SYMPHONY_URL,
                    'workspace' => URL . '/workspace',
                    'current-page' => strtolower($this->_type) . ucfirst($this->_function),
                    'current-path' => $current_path,
                    'current-url' => URL . $current_path,
                    'upload-limit' => min($upload_size_php, $upload_size_sym),
                    'symphony-version' => Symphony::Configuration()->get('version', 'symphony'),
                );
                $html = $this->_XSLTProc->process($this->_XML->generate(), file_get_contents($template), $params);
                if ($this->_XSLTProc->isErrors()) {
                    $errstr = null;

                    while (list($key, $val) = $this->_XSLTProc->getError()) {
                        $errstr .= 'Line: ' . $val['line'] . ' - ' . $val['message'] . self::CRLF;
                    }

                    throw new SymphonyErrorPage(trim($errstr), null, 'xslt-error', array('proc' => clone $this->_XSLTProc));
                }
            } else {
                Administration::instance()->errorPageNotFound();
            }
            $this->Form = null;
            $this->Contents->setValue($html);
        }

        return parent::generate();
    }

    protected function _getTemplate($type, $context)
    {
        return sprintf('%s%s.xsl', strtolower($type), ucfirst(strtolower($context)));
    }
}
