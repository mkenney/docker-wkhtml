<?php

class WkHtml {
    const TO_GIF = 0;
    const TO_JPG = 1;
    const TO_PNG = 2;
    const TO_PDF = 3;

    const TMP_DIR = '/tmp';
    const ISSUE_2231 = '/var/www/lib/css';

    protected $_command = null;
    protected $_defaults = [
        'disable-local-file-access' => true
        , 'disable-smart-width' => true
        , 'quiet' => true
        , 'user-style-sheet' => '/var/www/lib/css/issue-2231.css'
    ];
    protected $_image_defaults = [
    ];
    protected $_pdf_defaults = [
    ];
    protected $_input = null;
    protected $_filename = null;
    protected $_fileext = 'jpg';
    protected $_output_format = self::TO_JPG;
    protected $_options = [];

    public function __construct(
        string $input
        , array $options
        , int $format = null
    ) {
        $this->setInput($input);
        $this->_input = $input;
        $this->_output_format = $format;
        $this->_filename = tempnam('/tmp', random_int(0, time()));
        switch ($this->_output_format) {
            case self::TO_GIF:
                $this->_fileext = 'gif';
            break;
            case self::TO_JPG:
                $this->_fileext = 'jpg';
            break;
            case self::TO_PNG:
                $this->_fileext = 'png';
            break;
            case self::TO_PDF:
                $this->_fileext = 'pdf';
            break;
        }

        if (!isset($options['allow'])) {
            $options['allow'] = [];
        }
        $options['allow'] = (array) $options['allow'];
        $options['allow'][] = self::TMP_DIR;
        $options['allow'][] = self::ISSUE_2231;
        $options = array_merge($this->_defaults, $options);
        if (self::TO_PDF === $this->_output_format) {
            $options = array_merge($this->_pdf_defaults, $options);
        } else {
            $options = array_merge($this->_image_defaults, $options);
        }
        array_walk_recursive($options, function(&$value, $key) {
            $value = escapeshellarg($value);
        });
        $this->_options = $options;
    }

    /**
     * Generate and output an image using the specified options
     * @return void
     */
    public function generate() {
        $fp = fopen("{$this->_filename}.html", 'w');
        fwrite($fp, $this->_input);
        fclose($fp);

        $this->_generateCmd();
        `{$this->_command}`;
        $this->sendHeaders();
        echo file_get_contents("{$this->_filename}.{$this->_fileext}");

        `rm -f {$this->_filename}.*`;
        `rm -f {$this->_filename}`;
    }

    /**
     * Send the appropriate content-type header
     * @return void
     */
    public function sendHeaders() {
        switch ($this->_output_format) {
            case self::TO_GIF:
                header('Content-Type: image/gif');
            break;
            case self::TO_JPG:
                header('Content-Type: image/jpeg');
            break;
            case self::TO_PNG:
                header('Content-Type: image/png');
            break;
            case self::TO_PDF:
                header('Content-Type: application/pdf');
            break;
        }
    }

    protected function _generateCmd() {
        if (self::TO_PDF === $this->_output_format) {
            $this->_command = '/usr/bin/wkhtmltopdf';
        } else {
            $this->_command = "/usr/bin/wkhtmltoimage --format {$this->_fileext}";
        }
        $this->_addCmdOptions();
        $this->_command .= " {$this->_filename}.html {$this->_filename}.{$this->_fileext}";
    }

    protected function _addCmdOptions() {
        foreach($this->_options as $option => $val) {
            // Common optiosn
            switch ($option) {
                // no args
                case "custom-header-propagation":
                case "debug-javascript":
                case "disable-javascript":
                case "disable-local-file-access":
                case "disable-plugins":
                case "enable-javascript":
                case "enable-plugins":
                case "images":
                case "no-custom-header-propagation":
                case "no-debug-javascript":
                case "no-images":
                case "no-stop-slow-scripts":
                case "quiet":
                case "stop-slow-scripts":
                    $this->_command .= " --{$option}";
                break;

                // 1 arg
                case "checkbox-checked-svg":
                case "checkbox-svg":
                case "cookie-jar":
                case "encoding":
                case "javascript-delay":
                case "load-error-handling":
                case "minimum-font-size":
                case "password":
                case "proxy":
                case "radiobutton-checked-svg":
                case "radiobutton-svg":
                case "user-style-sheet":
                case "username":
                case "window-status":
                case "zoom":
                    $this->_command .= " --{$option} {$val}";
                break;

                // 1 arg, repeatable
                case "allow":
                    if (!is_array($val)) {
                        $this->_command .= " --{$option} {$val}";
                    } else {
                        foreach ($val as $aval) {
                            $this->_command .= " --{$option} {$aval}";
                        }
                    }
                break;

                // 2 args, repeatable
                case "cookie":
                case "custom-header":
                case "post":
                case "post-file":
                    if (!is_array($val)) {
                        header("HTTP/1.1 400 Bad Request");
                        echo "Invalid value '{$val}' for option '{$option}'";
                        exit;
                    } else {
                        foreach ($val as $aval) {
                            $this->_command .= " --{$option} {$aval[0]} {$aval[1]}";
                        }
                    }
                break;

                default:

                    // PDF options
                    if (self::TO_PDF === $this->_output_format) {
                        switch ($option) {
                            default:
                                header("HTTP/1.1 400 Bad Request");
                                echo "Invalid option '{$option}'";
                                exit;
                            break;

                            // no args
                            case "background":
                            case "collate":
                            case "grayscale":
                            case "lowquality":
                            case "no-background":
                            case "no-collate":
                                $this->_command .= " --{$option}";
                            break;

                            // 1 arg
                            case "copies":
                            case "dpi":
                            case "margin-bottom":
                            case "margin-left":
                            case "margin-right":
                            case "margin-top":
                            case "orientation":
                            case "output-format":
                            case "page-height":
                            case "page-offset":
                            case "page-size":
                            case "page-width":
                            case "title":
                                $this->_command .= " --{$option} {$val}";
                            break;
                        }

                    // Image options
                    } else {
                        switch ($option) {
                            default:
                                header("HTTP/1.1 400 Bad Request");
                                echo "Invalid option '{$option}'";
                                exit;
                            break;

                            case "disable-smart-width":
                                $this->_command .= " --{$option}";
                            break;

                            // 1 arg
                            case "crop-h":
                            case "crop-w":
                            case "crop-x":
                            case "crop-y":
                            case "format":
                            case "height":
                            case "quality":
                            case "width":
                                $this->_command .= " --{$option} {$val}";
                            break;
                        }
                    }
                break;
            }
        }
    }

    public function setInput(string $input) {
        // Remove tracking pixels, atches img tags that are size 1x1
        $search = '/<img[^>]*(width=["\']1["\'][^>]*|height=["\']1["\'][^>]*){2}>/im';
        $replace = '<!-- TRACKING PIXEL REMOVED -->';
        $input = preg_replace($search, $replace, $input, -1, $count);

        $this->_input = $input;
        return $this;
    }
}
